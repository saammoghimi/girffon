<?php
declare(strict_types=1);

require_once __DIR__ . '/products-data.php';

function girffonAdminCatalogRootPath(string $relativePath): string
{
    return dirname(__DIR__, 2) . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
}

function girffonAdminCatalogReadFile(string $relativePath): string
{
    $absolutePath = girffonAdminCatalogRootPath($relativePath);
    if (!is_file($absolutePath)) {
        return '';
    }

    $content = @file_get_contents($absolutePath);
    return is_string($content) ? $content : '';
}

function girffonAdminCatalogCategoryLabel(string $value): string
{
    $map = [
        'men' => 'Men',
        'women' => 'Women',
        'kids' => 'Kids',
        'kids-babies' => 'Kids',
        'accessories' => 'Accessories',
        'home-living' => 'Home & Living',
        'home' => 'Home & Living',
        'custom-design' => 'Custom Design',
        'product-details' => 'Product Details',
        'neonati' => 'Custom Design',
        'accerico' => 'Custom Design',
    ];

    $normalized = strtolower(trim($value));
    return $map[$normalized] ?? trim($value);
}

function girffonAdminCatalogBuildImageFromTemplate(string $template, string $defaultColor = 'Bk'): string
{
    $folder = str_replace('{color}', $defaultColor, trim($template));
    return girffonAdminNormalizeCatalogPath($folder . '400.jpg');
}

function girffonAdminCatalogImageIdentity(string $path): string
{
    $normalized = girffonAdminNormalizeCatalogPath($path);
    if ($normalized === '') {
        return '';
    }

    $parts = array_values(array_filter(explode('/', strtolower($normalized)), static fn ($part) => $part !== ''));
    $partCount = count($parts);
    if ($partCount >= 4 && str_contains($parts[$partCount - 1], '.')) {
        array_pop($parts);
        if ($parts !== []) {
            array_pop($parts);
        }
    }

    return implode('/', $parts);
}

function girffonAdminCatalogPriceFromMoneyString(string $value): ?float
{
    if (!preg_match('/([0-9]+(?:\.[0-9]+)?)/', $value, $matches)) {
        return null;
    }

    return round((float) $matches[1], 2);
}

function girffonAdminCatalogLooseName(string $name, string $category): string
{
    $normalized = girffonAdminNormalizeCatalogText($name);
    $categoryKey = girffonAdminNormalizeCatalogText($category);

    $prefixes = [
        'men' => ['mens ', 'men s ', 'men '],
        'women' => ['womens ', 'women s ', 'women '],
        'kids' => ['kids ', 'kid '],
    ];

    if (isset($prefixes[$categoryKey])) {
        foreach ($prefixes[$categoryKey] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $normalized = substr($normalized, strlen($prefix));
                break;
            }
        }
    }

    return trim($normalized);
}

function girffonAdminCatalogShouldMergeByName(array $existing, array $incoming): bool
{
    $existingPrice = isset($existing['price']) ? round((float) $existing['price'], 2) : null;
    $incomingPrice = isset($incoming['price']) ? round((float) $incoming['price'], 2) : null;
    if ($existingPrice === null || $incomingPrice === null || abs($existingPrice - $incomingPrice) > 0.001) {
        return false;
    }

    $existingName = girffonAdminCatalogLooseName((string) ($existing['name'] ?? ''), (string) ($existing['category'] ?? ''));
    $incomingName = girffonAdminCatalogLooseName((string) ($incoming['name'] ?? ''), (string) ($incoming['category'] ?? ''));
    if ($existingName === '' || $incomingName === '') {
        return false;
    }

    $sameCategory = girffonAdminCatalogCategoryLabel((string) ($existing['category'] ?? '')) === girffonAdminCatalogCategoryLabel((string) ($incoming['category'] ?? ''));
    if (!$sameCategory) {
        $existingCode = (string) ($existing['code'] ?? '');
        $incomingCode = (string) ($incoming['code'] ?? '');
        $hasUncodedVariant = $existingCode === '' || $incomingCode === '';
        $existingImageIdentity = girffonAdminCatalogImageIdentity((string) ($existing['image'] ?? ''));
        $incomingImageIdentity = girffonAdminCatalogImageIdentity((string) ($incoming['image'] ?? ''));
        $sameImage = $existingImageIdentity !== '' && $existingImageIdentity === $incomingImageIdentity;

        if (!$hasUncodedVariant || !$sameImage) {
            return false;
        }
    }

    return str_contains($existingName, $incomingName) || str_contains($incomingName, $existingName);
}

function girffonAdminCatalogMergeProduct(array $existing, array $incoming): array
{
    foreach (['code', 'barcode', 'description', 'image', 'detail_url', 'source_id', 'source_key'] as $field) {
        if (($existing[$field] ?? '') === '' && ($incoming[$field] ?? '') !== '') {
            $existing[$field] = $incoming[$field];
        }
    }

    if (($existing['name'] ?? '') === '' && ($incoming['name'] ?? '') !== '') {
        $existing['name'] = $incoming['name'];
    }

    if (($existing['category'] ?? '') === '' && ($incoming['category'] ?? '') !== '') {
        $existing['category'] = girffonAdminCatalogCategoryLabel((string) $incoming['category']);
    }

    if ((empty($existing['price']) || (float) $existing['price'] <= 0) && !empty($incoming['price'])) {
        $existing['price'] = round((float) $incoming['price'], 2);
    }

    $existing['sources'] = array_values(array_unique(array_merge($existing['sources'] ?? [], $incoming['sources'] ?? [])));
    return $existing;
}

function girffonAdminCatalogUpsertRaw(array &$products, array &$duplicates, array $item): void
{
    $item['category'] = girffonAdminCatalogCategoryLabel((string) ($item['category'] ?? ''));
    $item['name'] = trim((string) ($item['name'] ?? ''));
    $item['code'] = girffonAdminNormalizeProductSkuValue((string) ($item['code'] ?? ''));
    $item['barcode'] = girffonAdminNormalizeProductSkuValue((string) ($item['barcode'] ?? ''));
    $item['image'] = girffonAdminNormalizeCatalogPath((string) ($item['image'] ?? ''));
    $item['detail_url'] = girffonAdminNormalizeCatalogPath((string) ($item['detail_url'] ?? ''));
    $item['source_id'] = trim((string) ($item['source_id'] ?? ''));
    $item['source_key'] = trim((string) ($item['source_key'] ?? ''));
    $item['description'] = trim((string) ($item['description'] ?? ''));
    $item['price'] = isset($item['price']) && $item['price'] !== '' ? round((float) $item['price'], 2) : null;
    $item['sources'] = array_values(array_filter(array_map('strval', $item['sources'] ?? [])));

    if ($item['name'] === '' || $item['category'] === '' || $item['price'] === null || $item['price'] <= 0) {
        return;
    }

    $directKeys = [];
    foreach (['code', 'source_key', 'source_id'] as $field) {
        if (($item[$field] ?? '') !== '') {
            $directKeys[] = $field . ':' . strtolower((string) $item[$field]);
        }
    }

    foreach ($products as $index => $existing) {
        foreach ($directKeys as $key) {
            foreach (['code', 'source_key', 'source_id'] as $field) {
                $existingValue = strtolower((string) ($existing[$field] ?? ''));
                if ($existingValue !== '' && $key === $field . ':' . $existingValue) {
                    $products[$index] = girffonAdminCatalogMergeProduct($existing, $item);
                    $duplicates[] = ['type' => 'direct', 'name' => $item['name'], 'source_key' => $item['source_key']];
                    return;
                }
            }
        }

        if (girffonAdminCatalogShouldMergeByName($existing, $item)) {
            $products[$index] = girffonAdminCatalogMergeProduct($existing, $item);
            $duplicates[] = ['type' => 'name', 'name' => $item['name'], 'source_key' => $item['source_key']];
            return;
        }
    }

    $products[] = $item;
}

function girffonAdminCatalogParseSearchDataset(string $relativePath, string $sourceName): array
{
    $content = girffonAdminCatalogReadFile($relativePath);
    if ($content === '') {
        return [];
    }

    $pattern = '/\{\s*code:\s*"([^"]+)",\s*name:\s*"([^"]+)",\s*category:\s*"([^"]+)",\s*description:\s*"([^"]*)",\s*priceEur:\s*([0-9.]+),\s*image:\s*"([^"]+)",\s*href:\s*"([^"]+)"/s';
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    $products = [];
    foreach ($matches as $match) {
        $code = girffonAdminNormalizeProductSkuValue($match[1]);
        $products[] = [
            'name' => $match[2],
            'category' => $match[3],
            'description' => $match[4],
            'price' => (float) $match[5],
            'image' => $match[6],
            'detail_url' => $match[7],
            'code' => $code,
            'barcode' => girffonAdminBuildProductBarcode($code),
            'source_id' => $code,
            'source_key' => $sourceName . ':' . strtolower($code),
            'sources' => [$sourceName],
        ];
    }

    return $products;
}

function girffonAdminCatalogParseShopDataset(): array
{
    $content = girffonAdminCatalogReadFile('JS/shop.js');
    if ($content === '') {
        return [];
    }

    $sectionPattern = '/\{\s*key:\s*\'([^\']+)\',\s*title:\s*\'([^\']+)\',\s*href:\s*\'([^\']+)\',.*?products:\s*\[(.*?)\]\s*\}/s';
    $productPattern = '/\{\s*id:\s*\'([^\']+)\',\s*title:\s*\'([^\']+)\',\s*priceEur:\s*([0-9.]+),\s*folderTemplate:\s*\'([^\']+)\',\s*href:\s*\'([^\']+)\'\s*\}/s';
    preg_match_all($sectionPattern, $content, $sections, PREG_SET_ORDER);

    $products = [];
    foreach ($sections as $section) {
        preg_match_all($productPattern, $section[4], $items, PREG_SET_ORDER);
        foreach ($items as $item) {
            $sourceId = trim($item[1]);
            $sourceKey = 'shop:' . $sourceId;
            $code = '';
            if (preg_match('/^[A-Z]{2}-[A-Z]{3}-[0-9]{3}$/', strtoupper($sourceId))) {
                $code = strtoupper($sourceId);
            }

            $products[] = [
                'name' => $item[2],
                'category' => girffonAdminCatalogCategoryLabel($section[1]),
                'price' => (float) $item[3],
                'image' => girffonAdminCatalogBuildImageFromTemplate($item[4]),
                'detail_url' => $item[5],
                'code' => $code,
                'barcode' => $code !== '' ? girffonAdminBuildProductBarcode($code) : '',
                'source_id' => $sourceId,
                'source_key' => $sourceKey,
                'sources' => ['shop.js'],
            ];
        }
    }

    return $products;
}

function girffonAdminCatalogParseCategoryPageDataset(string $relativePath, string $category, string $detailUrl): array
{
    $content = girffonAdminCatalogReadFile($relativePath);
    if ($content === '') {
        return [];
    }

    $pattern = '/\{\s*title:\s*"([^"]+)",\s*priceEur:\s*([0-9.]+),\s*folderTemplate:\s*"([^"]+)"\s*\}/s';
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    $products = [];
    foreach ($matches as $match) {
        $name = trim($match[1]);
        $sourceKey = 'page:' . strtolower(str_replace('.js', '', basename($relativePath))) . ':' . girffonAdminNormalizeCatalogText($name);
        $products[] = [
            'name' => $name,
            'category' => $category,
            'price' => (float) $match[2],
            'image' => girffonAdminCatalogBuildImageFromTemplate($match[3]),
            'detail_url' => $detailUrl,
            'source_id' => '',
            'source_key' => $sourceKey,
            'sources' => [basename($relativePath)],
        ];
    }

    return $products;
}

function girffonAdminCatalogParseCustomDesignDataset(): array
{
    $content = girffonAdminCatalogReadFile('Image/Custom Design Pro/Js/product.js');
    if ($content === '') {
        return [];
    }

    $sectionPattern = '/([a-z]+):\s*\[(.*?)\]/s';
    $itemPattern = '/\{\s*id:\s*"([^"]+)",\s*name:\s*"([^"]+)",\s*image:\s*"([^"]+)",\s*price:\s*"([^"]+)",\s*html:\s*"([^"]+)"\s*\}/s';
    preg_match_all($sectionPattern, $content, $sections, PREG_SET_ORDER);

    $products = [];
    foreach ($sections as $section) {
        preg_match_all($itemPattern, $section[2], $items, PREG_SET_ORDER);
        foreach ($items as $item) {
            $price = girffonAdminCatalogPriceFromMoneyString($item[4]);
            if ($price === null || $price <= 0) {
                continue;
            }

            $htmlFile = trim($item[5]);
            $detailPath = 'Image/Custom Design Pro/' . $htmlFile;
            $sourceId = trim($item[1]);
            $htmlSlug = girffonAdminNormalizeProductSkuValue(pathinfo($htmlFile, PATHINFO_FILENAME));
            $sourceKey = 'cdp:' . strtolower($htmlSlug !== '' ? $htmlSlug : $sourceId);

            $products[] = [
                'name' => trim($item[2]),
                'category' => 'Custom Design',
                'price' => $price,
                'image' => girffonAdminNormalizeCatalogPath($item[3]),
                'detail_url' => $detailPath,
                'source_id' => $sourceId,
                'source_key' => $sourceKey,
                'sources' => ['custom-design-product.js'],
            ];
        }
    }

    return $products;
}

function girffonAdminBuildStorefrontCatalogManifest(): array
{
    $products = [];
    $duplicates = [];

    $sources = [
        girffonAdminCatalogParseSearchDataset('JS/index-search.js', 'index-search.js'),
        girffonAdminCatalogParseSearchDataset('JS/site-search.js', 'site-search.js'),
        girffonAdminCatalogParseShopDataset(),
        girffonAdminCatalogParseCategoryPageDataset('JS/men-page.js', 'Men', 'men.html'),
        girffonAdminCatalogParseCategoryPageDataset('JS/women-page.js', 'Women', 'woman.html'),
        girffonAdminCatalogParseCategoryPageDataset('JS/kids-page.js', 'Kids', 'kids.html'),
        girffonAdminCatalogParseCategoryPageDataset('JS/accessories-page.js', 'Accessories', 'accessories.html'),
        girffonAdminCatalogParseCategoryPageDataset('JS/home-living-page.js', 'Home & Living', 'home-living.html'),
        girffonAdminCatalogParseCustomDesignDataset(),
    ];

    foreach ($sources as $sourceItems) {
        foreach ($sourceItems as $item) {
            girffonAdminCatalogUpsertRaw($products, $duplicates, $item);
        }
    }

    foreach ($products as $index => $product) {
        $sourceKey = trim((string) ($product['source_key'] ?? ''));
        $code = trim((string) ($product['code'] ?? ''));
        $resolvedSku = $code !== '' ? $code : girffonAdminBuildGeneratedCatalogSku($sourceKey !== '' ? $sourceKey : ($product['category'] . ':' . $product['name']));
        $products[$index]['sku'] = $resolvedSku;
        $products[$index]['barcode'] = ($product['barcode'] ?? '') !== '' ? girffonAdminNormalizeProductSkuValue((string) $product['barcode']) : girffonAdminBuildProductBarcode($resolvedSku);
        $products[$index]['detail_url'] = girffonAdminNormalizeCatalogPath((string) ($product['detail_url'] ?? ''));
        $products[$index]['image'] = girffonAdminNormalizeCatalogPath((string) ($product['image'] ?? ''));
        $products[$index]['description'] = (string) ($product['description'] ?? '');
    }

    return [
        'products' => array_values($products),
        'duplicates' => $duplicates,
        'sources' => [
            'search' => count($sources[0]) + count($sources[1]),
            'shop' => count($sources[2]),
            'category_pages' => count($sources[3]) + count($sources[4]) + count($sources[5]) + count($sources[6]) + count($sources[7]),
            'custom_design' => count($sources[8]),
        ],
    ];
}
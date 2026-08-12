<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/products-data.php';
require_once __DIR__ . '/storefront-catalog-manifest.php';

girffonAdminEnsureProductsTable($pdo);
$manifest = girffonAdminBuildStorefrontCatalogManifest();
$catalogProducts = $manifest['products'];
$duplicateCount = count($manifest['duplicates']);

$existingProducts = girffonAdminFetchProducts($pdo);
$bySku = [];
$byBarcode = [];
$bySourceKey = [];
$byFallback = [];

foreach ($existingProducts as $product) {
    $productId = (int) ($product['id'] ?? 0);
    if ($productId <= 0) {
        continue;
    }

    $sku = girffonAdminNormalizeProductSkuValue((string) ($product['sku'] ?? ''));
    $barcode = girffonAdminNormalizeProductSkuValue((string) ($product['barcode'] ?? ''));
    $sourceKey = trim((string) ($product['source_key'] ?? ''));
    $fallbackKey = girffonAdminCatalogCategoryLabel((string) ($product['category'] ?? '')) . '|' . girffonAdminNormalizeCatalogText((string) ($product['name'] ?? ''));

    if ($sku !== '') {
        $bySku[$sku] = $product;
    }
    if ($barcode !== '') {
        $byBarcode[$barcode] = $product;
    }
    if ($sourceKey !== '') {
        $bySourceKey[$sourceKey] = $product;
    }
    if ($fallbackKey !== '|') {
        if (!isset($byFallback[$fallbackKey])) {
            $byFallback[$fallbackKey] = [];
        }
        $byFallback[$fallbackKey][] = $product;
    }
}

$summary = [
    'discovered' => count($catalogProducts),
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'duplicates_detected' => $duplicateCount,
    'matched_by' => [
        'sku' => 0,
        'barcode' => 0,
        'source_key' => 0,
        'category_name' => 0,
        'new' => 0,
    ],
    'discount_preserved_rows' => 0,
    'sources' => $manifest['sources'],
    'products' => [],
];

$selectColumns = girffonAdminGetProductsColumns($pdo);
$insertFields = ['sku', 'barcode', 'source_key', 'name', 'description', 'price', 'stock', 'category', 'size', 'color', 'image', 'detail_url', 'status'];
$insertParams = [':sku', ':barcode', ':source_key', ':name', ':description', ':price', ':stock', ':category', ':size', ':color', ':image', ':detail_url', ':status'];
$updateAssignments = [
    'sku = :sku',
    'barcode = :barcode',
    'source_key = :source_key',
    'name = :name',
    'description = :description',
    'price = :price',
    'category = :category',
    'image = :image',
    'detail_url = :detail_url',
];

if (isset($selectColumns['image_path'])) {
    $insertFields[] = 'image_path';
    $insertParams[] = ':image_path';
    $updateAssignments[] = 'image_path = :image_path';
}

$insertStatement = $pdo->prepare('INSERT INTO products (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $insertParams) . ')');
$updateStatement = $pdo->prepare('UPDATE products SET ' . implode(', ', $updateAssignments) . ' WHERE id = :id LIMIT 1');

foreach ($catalogProducts as $product) {
    $sku = girffonAdminNormalizeProductSkuValue((string) ($product['sku'] ?? ''));
    $barcode = girffonAdminNormalizeProductSkuValue((string) ($product['barcode'] ?? ''));
    $sourceKey = trim((string) ($product['source_key'] ?? ''));
    $fallbackKey = girffonAdminCatalogCategoryLabel((string) ($product['category'] ?? '')) . '|' . girffonAdminNormalizeCatalogText((string) ($product['name'] ?? ''));
    $matchedProduct = null;
    $matchedBy = 'new';

    if ($sku !== '' && isset($bySku[$sku])) {
        $matchedProduct = $bySku[$sku];
        $matchedBy = 'sku';
    } elseif ($barcode !== '' && isset($byBarcode[$barcode])) {
        $matchedProduct = $byBarcode[$barcode];
        $matchedBy = 'barcode';
    } elseif ($sourceKey !== '' && isset($bySourceKey[$sourceKey])) {
        $matchedProduct = $bySourceKey[$sourceKey];
        $matchedBy = 'source_key';
    } elseif (isset($byFallback[$fallbackKey]) && count($byFallback[$fallbackKey]) === 1) {
        $matchedProduct = $byFallback[$fallbackKey][0];
        $matchedBy = 'category_name';
    }

    $defaultStock = is_array($matchedProduct) ? (int) (($matchedProduct['stock'] ?? 0) ?: 0) : 0;
    $defaultSize = is_array($matchedProduct) ? (string) ($matchedProduct['size'] ?? '') : '';
    $defaultColor = is_array($matchedProduct) ? (string) ($matchedProduct['color'] ?? '') : '';
    $defaultStatus = is_array($matchedProduct) && (string) ($matchedProduct['status'] ?? '') !== ''
        ? (string) $matchedProduct['status']
        : 'active';

    $baseParams = [
        ':sku' => $sku,
        ':barcode' => $barcode !== '' ? $barcode : null,
        ':source_key' => $sourceKey !== '' ? $sourceKey : null,
        ':name' => (string) ($product['name'] ?? ''),
        ':description' => ($product['description'] ?? '') !== '' ? (string) $product['description'] : null,
        ':price' => round((float) ($product['price'] ?? 0), 2),
        ':category' => girffonAdminCatalogCategoryLabel((string) ($product['category'] ?? '')),
        ':image' => ($product['image'] ?? '') !== '' ? (string) $product['image'] : null,
        ':detail_url' => ($product['detail_url'] ?? '') !== '' ? (string) $product['detail_url'] : null,
    ];

    if (isset($selectColumns['image_path'])) {
        $baseParams[':image_path'] = $baseParams[':image'];
    }

    if (is_array($matchedProduct)) {
        $updateParams = $baseParams;
        $updateParams[':id'] = (int) ($matchedProduct['id'] ?? 0);
        $updateStatement->execute($updateParams);
        $matchedProduct = girffonAdminFetchProductById($pdo, (int) $matchedProduct['id']);
        $summary['updated'] += 1;
        $summary['matched_by'][$matchedBy] += 1;
        if (!empty($matchedProduct['discount_enabled']) || !empty($matchedProduct['discount_percent']) || !empty($matchedProduct['sale_price'])) {
            $summary['discount_preserved_rows'] += 1;
        }
    } else {
        $insertParams = $baseParams + [
            ':stock' => $defaultStock,
            ':size' => $defaultSize,
            ':color' => $defaultColor,
            ':status' => $defaultStatus,
        ];
        $insertStatement->execute($insertParams);
        $matchedProduct = girffonAdminFetchProductById($pdo, (int) $pdo->lastInsertId());
        $summary['inserted'] += 1;
        $summary['matched_by']['new'] += 1;
    }

    if (is_array($matchedProduct)) {
        $bySku[$sku] = $matchedProduct;
        if ($barcode !== '') {
            $byBarcode[$barcode] = $matchedProduct;
        }
        if ($sourceKey !== '') {
            $bySourceKey[$sourceKey] = $matchedProduct;
        }
        if (!isset($byFallback[$fallbackKey])) {
            $byFallback[$fallbackKey] = [];
        }
        $alreadyIndexed = false;
        foreach ($byFallback[$fallbackKey] as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === (int) ($matchedProduct['id'] ?? 0)) {
                $alreadyIndexed = true;
                break;
            }
        }
        if (!$alreadyIndexed) {
            $byFallback[$fallbackKey][] = $matchedProduct;
        }
    } else {
        $summary['skipped'] += 1;
    }

    $summary['products'][] = [
        'sku' => $sku,
        'name' => $product['name'],
        'category' => $product['category'],
        'matched_by' => $matchedBy,
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
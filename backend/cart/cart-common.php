<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const GIRFFON_CART_SESSION_KEY = 'cart';

function girffonCartSendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonCartRequestData(): array
{
    $data = $_POST;
    $rawBody = file_get_contents('php://input');
    if (!is_string($rawBody) || trim($rawBody) === '') {
        return is_array($data) ? $data : [];
    }

    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_merge(is_array($data) ? $data : [], $decoded);
    }

    return is_array($data) ? $data : [];
}

function girffonCartSessionItems(): array
{
    $items = $_SESSION[GIRFFON_CART_SESSION_KEY] ?? [];
    return is_array($items) ? $items : [];
}

function girffonCartSaveSessionItems(array $items): void
{
    $_SESSION[GIRFFON_CART_SESSION_KEY] = array_values($items);
}

function girffonCartParsePrice($value): float
{
    if (is_int($value) || is_float($value)) {
        return max(0, (float) $value);
    }

    $text = trim((string) $value);
    if ($text === '') {
        return 0.0;
    }

    $normalized = preg_replace('/[^0-9,.-]/', '', $text);
    if ($normalized === null || $normalized === '') {
        return 0.0;
    }

    if (substr_count($normalized, ',') === 1 && substr_count($normalized, '.') === 0) {
        $normalized = str_replace(',', '.', $normalized);
    } elseif (substr_count($normalized, ',') > 0 && substr_count($normalized, '.') > 0) {
        $normalized = str_replace(',', '', $normalized);
    } else {
        $normalized = str_replace(',', '', $normalized);
    }

    return max(0, (float) $normalized);
}

function girffonCartLineKey(string $sku, string $size, string $color): string
{
    return sha1(strtolower(trim($sku)) . '|' . strtolower(trim($size)) . '|' . strtolower(trim($color)));
}

function girffonCartNormalizeItem(array $input): ?array
{
    $sku = trim((string) ($input['sku'] ?? $input['id'] ?? ''));
    $name = trim((string) ($input['name'] ?? $input['title'] ?? ''));
    $image = trim((string) ($input['image'] ?? $input['img'] ?? ''));
    $size = trim((string) ($input['size'] ?? ''));
    $color = trim((string) ($input['color'] ?? ''));
    $quantity = max(1, (int) ($input['quantity'] ?? $input['qty'] ?? 1));
    $price = girffonCartParsePrice($input['price'] ?? $input['priceNumber'] ?? 0);

    if ($sku === '' || $name === '' || $price <= 0) {
        return null;
    }

    return [
        'id' => $sku,
        'sku' => $sku,
        'name' => $name,
        'price' => $price,
        'image' => $image,
        'size' => $size,
        'color' => $color,
        'quantity' => $quantity,
        'line_key' => girffonCartLineKey($sku, $size, $color),
    ];
}

function girffonCartFormatItems(array $items): array
{
    return array_values(array_map(static function (array $item): array {
        $price = max(0, (float) ($item['price'] ?? 0));
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $sku = trim((string) ($item['sku'] ?? $item['id'] ?? ''));
        $size = trim((string) ($item['size'] ?? ''));
        $color = trim((string) ($item['color'] ?? ''));

        return [
            'id' => $sku,
            'sku' => $sku,
            'name' => trim((string) ($item['name'] ?? $item['title'] ?? '')),
            'title' => trim((string) ($item['name'] ?? $item['title'] ?? '')),
            'price' => $price,
            'priceNumber' => $price,
            'image' => trim((string) ($item['image'] ?? $item['img'] ?? '')),
            'img' => trim((string) ($item['image'] ?? $item['img'] ?? '')),
            'size' => $size,
            'color' => $color,
            'quantity' => $quantity,
            'qty' => $quantity,
            'line_key' => trim((string) ($item['line_key'] ?? girffonCartLineKey($sku, $size, $color))),
            'total_price' => round($price * $quantity, 2),
            'code' => $sku,
        ];
    }, $items));
}

function girffonCartPayload(): array
{
    $items = girffonCartFormatItems(girffonCartSessionItems());
    $subtotal = 0.0;
    $itemCount = 0;

    foreach ($items as $item) {
        $subtotal += (float) ($item['total_price'] ?? 0);
        $itemCount += (int) ($item['quantity'] ?? 0);
    }

    return [
        'items' => $items,
        'line_count' => count($items),
        'item_count' => $itemCount,
        'subtotal' => round($subtotal, 2),
        'total' => round($subtotal, 2),
    ];
}

function girffonCartFindItemIndex(array $items, string $lineKey): int
{
    foreach ($items as $index => $item) {
        $currentKey = trim((string) ($item['line_key'] ?? ''));
        if ($currentKey === $lineKey) {
            return $index;
        }
    }

    return -1;
}
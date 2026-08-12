<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/products-data.php';

function girffonAdminRedirectBulkDiscount(string $type, string $message): void
{
    header('Location: ../../admin-products.php?' . http_build_query([$type => $message]));
    exit;
}

function girffonAdminDiscountSelectedIds(array $values): array
{
    $ids = array_values(array_filter(array_map(static function ($value): int {
        return max(0, (int) $value);
    }, $values)));

    return array_values(array_unique($ids));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminRedirectBulkDiscount('error', 'Invalid discount request method.');
}

girffonAdminEnsureProductsTable($pdo);

$action = strtolower(trim((string) ($_POST['discount_action'] ?? '')));
$scope = strtolower(trim((string) ($_POST['discount_scope'] ?? 'selected')));
$selectedIds = girffonAdminDiscountSelectedIds((array) ($_POST['product_ids'] ?? []));

if (!in_array($action, ['apply', 'remove'], true)) {
    girffonAdminRedirectBulkDiscount('error', 'Unknown discount action.');
}

if (!in_array($scope, ['selected', 'all'], true)) {
    $scope = 'selected';
}

if ($scope !== 'all' && !$selectedIds) {
    girffonAdminRedirectBulkDiscount('error', 'Select at least one product or choose All Products.');
}

$whereClause = '';
$whereParams = [];
if ($scope !== 'all') {
    $placeholders = implode(', ', array_fill(0, count($selectedIds), '?'));
    $whereClause = ' WHERE id IN (' . $placeholders . ')';
    $whereParams = $selectedIds;
}

try {
    if ($action === 'remove') {
        $statement = $pdo->prepare(
            'UPDATE products
             SET discount_enabled = 0,
                 discount_percent = NULL,
                 discount_label = NULL,
                 discount_start_at = NULL,
                 discount_end_at = NULL' . $whereClause
        );
        $statement->execute($whereParams);

        $targetLabel = $scope === 'all' ? 'all products' : (count($selectedIds) . ' selected product(s)');
        girffonAdminRedirectBulkDiscount('status', 'Discount removed for ' . $targetLabel . '.');
    }

    $discountPercentRaw = $_POST['discount_percent'] ?? null;
    $discountPercent = girffonAdminNormalizeDiscountPercent($discountPercentRaw);
    if ($discountPercent === null) {
        girffonAdminRedirectBulkDiscount('error', 'Discount must be between 5% and 50%.');
    }

    $discountLabel = trim((string) ($_POST['discount_label'] ?? ''));
    $discountStartRaw = trim((string) ($_POST['discount_start_at'] ?? ''));
    $discountEndRaw = trim((string) ($_POST['discount_end_at'] ?? ''));
    $discountStartAt = girffonAdminNormalizeDiscountDateTimeValue($discountStartRaw);
    $discountEndAt = girffonAdminNormalizeDiscountDateTimeValue($discountEndRaw);
    $discountEnabled = isset($_POST['discount_enabled']) ? 1 : 0;

    if ($discountStartRaw !== '' && $discountStartAt === null) {
        girffonAdminRedirectBulkDiscount('error', 'Invalid discount start date.');
    }

    if ($discountEndRaw !== '' && $discountEndAt === null) {
        girffonAdminRedirectBulkDiscount('error', 'Invalid discount end date.');
    }

    if ($discountStartAt !== null && $discountEndAt !== null && strtotime($discountEndAt) < strtotime($discountStartAt)) {
        girffonAdminRedirectBulkDiscount('error', 'Discount end date must be after the start date.');
    }

    $statement = $pdo->prepare(
        'UPDATE products
         SET discount_enabled = ?,
             discount_percent = ?,
             discount_label = ?,
             discount_start_at = ?,
             discount_end_at = ?' . $whereClause
    );
    $statement->execute(array_merge([
        $discountEnabled,
        $discountPercent,
        $discountLabel !== '' ? $discountLabel : null,
        $discountStartAt,
        $discountEndAt,
    ], $whereParams));

    $targetLabel = $scope === 'all' ? 'all products' : (count($selectedIds) . ' selected product(s)');
    girffonAdminRedirectBulkDiscount('status', 'Discount campaign saved for ' . $targetLabel . '.');
} catch (PDOException $exception) {
    girffonAdminRedirectBulkDiscount('error', 'Unable to update product discounts right now.');
}
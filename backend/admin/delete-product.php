<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/products-data.php";

function girffonAdminRedirectDeletedProduct(string $type, string $message): void
{
    header("Location: ../../admin-products.php?" . http_build_query([$type => $message]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    girffonAdminRedirectDeletedProduct('error', 'Invalid request method.');
}

girffonAdminEnsureProductsTable($pdo);

$productId = max(0, (int) ($_POST['id'] ?? 0));
if ($productId <= 0) {
    girffonAdminRedirectDeletedProduct('error', 'Invalid product selected.');
}

if (!girffonAdminFetchProductById($pdo, $productId)) {
    girffonAdminRedirectDeletedProduct('error', 'Product not found.');
}

try {
    $statement = $pdo->prepare('DELETE FROM products WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $productId]);
    girffonAdminRedirectDeletedProduct('status', 'Product deleted successfully.');
} catch (PDOException $exception) {
    girffonAdminRedirectDeletedProduct('error', 'Unable to delete the product right now.');
}
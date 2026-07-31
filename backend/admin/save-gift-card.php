<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/gift-cards-data.php';
require_once __DIR__ . '/../utils/csrf.php';

function girffonAdminGiftCardRedirect(string $type, string $message, array $query = []): void
{
    $query[$type] = $message;
    header('Location: ../../admin-gift-cards.php?' . http_build_query($query));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminGiftCardRedirect('error', 'Invalid request method.');
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonAdminGiftCardRedirect('error', 'Security token mismatch.');
}

$amount = girffonAdminGiftCardResolveAmount($_POST);

try {
    $pdo->beginTransaction();
    $giftCard = girffonGiftCardCreate($pdo, [
        'initial_amount' => $amount,
        'delivery_type' => $_POST['delivery_type'] ?? 'digital',
        'buyer_name' => $_POST['buyer_name'] ?? '',
        'buyer_email' => $_POST['buyer_email'] ?? '',
        'recipient_name' => $_POST['recipient_name'] ?? '',
        'recipient_email' => $_POST['recipient_email'] ?? '',
        'gift_message' => $_POST['gift_message'] ?? '',
        'expires_at' => $_POST['expires_at'] ?? '',
        'status' => $_POST['status'] ?? 'active',
    ]);
    $pdo->commit();

    if (($giftCard['delivery_type'] ?? '') === 'digital' && !empty($giftCard['recipient_email'])) {
        try {
            girffonSendGiftCardEmail($giftCard);
        } catch (Throwable $throwable) {
            girffonAdminGiftCardRedirect('status', 'Gift card created, but email sending failed: ' . $throwable->getMessage(), ['view' => $giftCard['gift_code']]);
        }
    }

    girffonAdminGiftCardRedirect('status', 'Gift card created successfully.', ['view' => $giftCard['gift_code']]);
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    girffonAdminGiftCardRedirect('error', $throwable->getMessage());
}

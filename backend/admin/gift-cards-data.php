<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/gift-card-service.php';

function girffonAdminGiftCardStatusOptions(): array
{
    return girffonGiftCardStatuses();
}

function girffonAdminGiftCardDeliveryOptions(): array
{
    return girffonGiftCardDeliveryTypes();
}

function girffonAdminFetchGiftCards(PDO $pdo): array
{
    return girffonGiftCardFetchAll($pdo);
}

function girffonAdminFetchGiftCardSummary(PDO $pdo): array
{
    return girffonGiftCardSummary($pdo);
}

function girffonAdminFetchGiftCardByCode(PDO $pdo, string $giftCode): ?array
{
    $code = strtoupper(trim($giftCode));
    return $code !== '' ? girffonGiftCardFetchByCode($pdo, $code) : null;
}

function girffonAdminFetchGiftCardById(PDO $pdo, int $giftCardId): ?array
{
    return girffonGiftCardFetchById($pdo, $giftCardId);
}

function girffonAdminFetchGiftCardTransactions(PDO $pdo, int $giftCardId): array
{
    return girffonGiftCardFetchTransactions($pdo, $giftCardId);
}

function girffonAdminGiftCardDeleteAllowed(PDO $pdo, int $giftCardId): bool
{
    return girffonGiftCardDeleteAllowed($pdo, $giftCardId);
}

function girffonAdminGiftCardResolveAmount(array $input): float
{
    $presetAmount = girffonGiftCardNormalizeAmount($input['preset_amount'] ?? 0);
    $customAmount = trim((string) ($input['custom_amount'] ?? ''));
    if ($customAmount !== '') {
        return girffonGiftCardNormalizeAmount($customAmount);
    }

    return $presetAmount;
}

<?php
declare(strict_types=1);

function girffonAiDefaultSettings(): array
{
    return [
        'feature_enabled' => true,
        'generation_enabled' => true,
        'package_price' => 2.00,
        'package_generations' => 5,
    ];
}

function girffonAiEnsureTables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS girffon_ai_settings (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            feature_enabled TINYINT(1) NOT NULL DEFAULT 1,
            generation_enabled TINYINT(1) NOT NULL DEFAULT 1,
            package_price DECIMAL(10,2) NOT NULL DEFAULT 2.00,
            package_generations INT UNSIGNED NOT NULL DEFAULT 5,
            updated_by INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS girffon_ai_wallets (
            user_id INT NOT NULL PRIMARY KEY,
            credits INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_girffon_ai_wallet_credits (credits)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS girffon_ai_credit_ledger (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            event_type VARCHAR(32) NOT NULL,
            credits_delta INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            is_mock TINYINT(1) NOT NULL DEFAULT 1,
            ai_mode VARCHAR(32) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_girffon_ai_ledger_user (user_id),
            INDEX idx_girffon_ai_ledger_event (event_type),
            INDEX idx_girffon_ai_ledger_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $defaults = girffonAiDefaultSettings();
    $statement = $pdo->prepare(
        "INSERT IGNORE INTO girffon_ai_settings (
            id, feature_enabled, generation_enabled, package_price, package_generations
        ) VALUES (1, :feature_enabled, :generation_enabled, :package_price, :package_generations)"
    );
    $statement->execute([
        'feature_enabled' => $defaults['feature_enabled'] ? 1 : 0,
        'generation_enabled' => $defaults['generation_enabled'] ? 1 : 0,
        'package_price' => $defaults['package_price'],
        'package_generations' => $defaults['package_generations'],
    ]);
}

function girffonAiGetSettings(PDO $pdo): array
{
    girffonAiEnsureTables($pdo);
    $statement = $pdo->query(
        'SELECT feature_enabled, generation_enabled, package_price, package_generations
         FROM girffon_ai_settings WHERE id = 1 LIMIT 1'
    );
    $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;
    $defaults = girffonAiDefaultSettings();

    if (!is_array($row)) {
        return $defaults;
    }

    return [
        'feature_enabled' => (bool) ($row['feature_enabled'] ?? $defaults['feature_enabled']),
        'generation_enabled' => (bool) ($row['generation_enabled'] ?? $defaults['generation_enabled']),
        'package_price' => round((float) ($row['package_price'] ?? $defaults['package_price']), 2),
        'package_generations' => max(1, (int) ($row['package_generations'] ?? $defaults['package_generations'])),
    ];
}

function girffonAiSaveSettings(PDO $pdo, array $input, int $adminId): array
{
    girffonAiEnsureTables($pdo);
    $price = filter_var($input['package_price'] ?? null, FILTER_VALIDATE_FLOAT);
    $generations = filter_var($input['package_generations'] ?? null, FILTER_VALIDATE_INT);

    if ($price === false || $price < 0 || $price > 999999.99) {
        throw new InvalidArgumentException('Package price must be between 0 and 999999.99.');
    }
    if ($generations === false || $generations < 1 || $generations > 100000) {
        throw new InvalidArgumentException('Generations per package must be between 1 and 100000.');
    }

    $settings = [
        'feature_enabled' => filter_var($input['feature_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'generation_enabled' => filter_var($input['generation_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'package_price' => round((float) $price, 2),
        'package_generations' => (int) $generations,
    ];

    $statement = $pdo->prepare(
        'UPDATE girffon_ai_settings SET
            feature_enabled = :feature_enabled,
            generation_enabled = :generation_enabled,
            package_price = :package_price,
            package_generations = :package_generations,
            updated_by = :updated_by
         WHERE id = 1'
    );
    $statement->execute([
        'feature_enabled' => $settings['feature_enabled'] ? 1 : 0,
        'generation_enabled' => $settings['generation_enabled'] ? 1 : 0,
        'package_price' => $settings['package_price'],
        'package_generations' => $settings['package_generations'],
        'updated_by' => $adminId,
    ]);

    return $settings;
}

function girffonAiGetBalance(PDO $pdo, int $userId): int
{
    girffonAiEnsureTables($pdo);
    $statement = $pdo->prepare('SELECT credits FROM girffon_ai_wallets WHERE user_id = :user_id LIMIT 1');
    $statement->execute(['user_id' => $userId]);
    $balance = $statement->fetchColumn();
    return $balance === false ? 0 : max(0, (int) $balance);
}

function girffonAiMockPurchase(PDO $pdo, int $userId): int
{
    $settings = girffonAiGetSettings($pdo);
    if (!$settings['feature_enabled']) {
        throw new RuntimeException('GIRFFON AI is currently unavailable.');
    }

    throw new RuntimeException('AI package purchases are currently unavailable. Please contact support.');
}

function girffonAiConsumeCredit(PDO $pdo, int $userId, string $mode): int
{
    $settings = girffonAiGetSettings($pdo);
    if (!$settings['feature_enabled']) {
        throw new RuntimeException('GIRFFON AI is currently unavailable.');
    }
    if (!$settings['generation_enabled']) {
        throw new RuntimeException('AI generation is temporarily paused.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT IGNORE INTO girffon_ai_wallets (user_id, credits) VALUES (:user_id, 0)')
            ->execute(['user_id' => $userId]);
        $lock = $pdo->prepare('SELECT credits FROM girffon_ai_wallets WHERE user_id = :user_id FOR UPDATE');
        $lock->execute(['user_id' => $userId]);
        $balance = (int) $lock->fetchColumn();
        if ($balance <= 0) {
            throw new RuntimeException('No AI credits remaining.');
        }

        $newBalance = $balance - 1;
        $pdo->prepare('UPDATE girffon_ai_wallets SET credits = :credits WHERE user_id = :user_id')
            ->execute(['credits' => $newBalance, 'user_id' => $userId]);
        $pdo->prepare(
            "INSERT INTO girffon_ai_credit_ledger (user_id, event_type, credits_delta, amount, is_mock, ai_mode)
             VALUES (:user_id, 'generation', -1, 0.00, 1, :ai_mode)"
        )->execute(['user_id' => $userId, 'ai_mode' => substr($mode, 0, 32)]);
        $pdo->commit();
        return $newBalance;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function girffonAiGetCounters(PDO $pdo): array
{
    girffonAiEnsureTables($pdo);
    $statement = $pdo->query(
        "SELECT
            SUM(event_type = 'package_purchase') AS packages_sold,
            COALESCE(SUM(CASE WHEN event_type = 'package_purchase' THEN amount ELSE 0 END), 0) AS ai_revenue,
            COALESCE(SUM(CASE WHEN event_type = 'package_purchase' THEN credits_delta ELSE 0 END), 0) AS credits_purchased,
            ABS(COALESCE(SUM(CASE WHEN event_type = 'generation' THEN credits_delta ELSE 0 END), 0)) AS credits_used
         FROM girffon_ai_credit_ledger"
    );
    $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : [];

    return [
        'packages_sold' => (int) ($row['packages_sold'] ?? 0),
        'ai_revenue' => round((float) ($row['ai_revenue'] ?? 0), 2),
        'credits_purchased' => (int) ($row['credits_purchased'] ?? 0),
        'credits_used' => (int) ($row['credits_used'] ?? 0),
    ];
}

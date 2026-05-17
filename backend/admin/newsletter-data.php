<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../profile/communication-common.php';
require_once __DIR__ . '/messages-data.php';

function girffonAdminTableColumns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cache[$table] = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $column) {
            $name = (string) ($column['Field'] ?? '');
            if ($name !== '') {
                $cache[$table][$name] = true;
            }
        }
    } catch (PDOException $exception) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function girffonAdminTableExists(PDO $pdo, string $table): bool
{
    return girffonAdminTableColumns($pdo, $table) !== [];
}

function girffonAdminEnsureNewsletterCampaignLogsTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS newsletter_campaign_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id VARCHAR(64) NOT NULL,
                user_id INT UNSIGNED NULL,
                recipient_name VARCHAR(150) NOT NULL DEFAULT '',
                email VARCHAR(190) NOT NULL,
                subject VARCHAR(190) NOT NULL,
                message MEDIUMTEXT NOT NULL,
                attachment_url VARCHAR(255) NOT NULL DEFAULT '',
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                transport VARCHAR(40) NOT NULL DEFAULT '',
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_newsletter_campaign (campaign_id),
                KEY idx_newsletter_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminNotificationPreferenceTable(PDO $pdo): string
{
    if (girffonAdminTableExists($pdo, 'user_preferences')) {
        return 'user_preferences';
    }

    if (girffonAdminTableExists($pdo, 'customer_notification_preferences')) {
        return 'customer_notification_preferences';
    }

    return '';
}

function girffonAdminEnsureBirthdayEmailLogsTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS birthday_email_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                email VARCHAR(190) NOT NULL,
                coupon_code VARCHAR(80) NOT NULL DEFAULT '',
                sent_date DATE NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                transport VARCHAR(40) NOT NULL DEFAULT '',
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_birthday_user_date (user_id, sent_date),
                KEY idx_birthday_email (email),
                KEY idx_birthday_sent_date (sent_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminLogBirthdayEmailResult(PDO $pdo, array $payload): void
{
    if (!girffonAdminEnsureBirthdayEmailLogsTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO birthday_email_logs (
                user_id,
                email,
                coupon_code,
                sent_date,
                status,
                transport,
                error_message
             ) VALUES (
                :user_id,
                :email,
                :coupon_code,
                :sent_date,
                :status,
                :transport,
                :error_message
             )
             ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                coupon_code = VALUES(coupon_code),
                status = VALUES(status),
                transport = VALUES(transport),
                error_message = VALUES(error_message)'
        );
        $statement->execute([
            ':user_id' => (int) ($payload['user_id'] ?? 0),
            ':email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            ':coupon_code' => (string) ($payload['coupon_code'] ?? ''),
            ':sent_date' => (string) ($payload['sent_date'] ?? date('Y-m-d')),
            ':status' => (string) ($payload['status'] ?? 'pending'),
            ':transport' => (string) ($payload['transport'] ?? ''),
            ':error_message' => ($payload['error_message'] ?? '') !== '' ? (string) $payload['error_message'] : null,
        ]);
    } catch (PDOException $exception) {
    }
}

function girffonAdminFetchRecentBirthdayEmailLogs(PDO $pdo, int $limit = 30): array
{
    if (!girffonAdminEnsureBirthdayEmailLogsTable($pdo)) {
        return [];
    }

    try {
        $sql = 'SELECT user_id, email, coupon_code, sent_date, status, transport, error_message, created_at
                FROM birthday_email_logs
                ORDER BY sent_date DESC, created_at DESC, id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminPromotionalFlagEnabled($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string) $value));
    return !in_array($normalized, ['', '0', 'false', 'off', 'no', 'disabled', 'inactive', 'unsubscribed', 'blocked', 'suspended'], true);
}

function girffonAdminPromotionalSkippedReason(array $row): string
{
    if (empty($row['valid_email'])) {
        return 'Invalid email address.';
    }

    if (empty($row['promotional_emails'])) {
        return 'Promotional Emails disabled.';
    }

    if (empty($row['is_active'])) {
        return 'Inactive status.';
    }

    return '';
}

function girffonAdminBuildPromotionalAudienceRow(array $record, string $source, bool $hasExplicitStatus): array
{
    $email = strtolower(trim((string) ($record['email'] ?? '')));
    $name = trim((string) ($record['name'] ?? ''));
    if ($name === '') {
        $name = $email !== '' ? $email : 'GirffoN Member';
    }

    $promotionalEnabled = girffonAdminPromotionalFlagEnabled($record['promotional_emails'] ?? 1);
    $validEmail = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
    $isActive = true;
    if ($hasExplicitStatus) {
        $isActive = girffonAdminPromotionalFlagEnabled($record['status'] ?? '');
    }

    $row = [
        'user_id' => (int) ($record['user_id'] ?? 0),
        'name' => $name,
        'email' => $email,
        'status' => trim((string) ($record['status'] ?? '')),
        'promotional_emails' => $promotionalEnabled ? 1 : 0,
        'is_active' => $isActive ? 1 : 0,
        'valid_email' => $validEmail ? 1 : 0,
        'source' => $source,
        'status_explicit' => $hasExplicitStatus ? 1 : 0,
    ];

    $row['ready_to_send'] = ($row['promotional_emails'] && $row['is_active'] && $row['valid_email']) ? 1 : 0;
    $row['skipped_reason'] = girffonAdminPromotionalSkippedReason($row);

    return $row;
}

function girffonAdminMergePromotionalAudienceRows(array $current, array $incoming): array
{
    $merged = $current;

    if (($merged['user_id'] ?? 0) === 0 && !empty($incoming['user_id'])) {
        $merged['user_id'] = (int) $incoming['user_id'];
    }

    if ((trim((string) ($merged['name'] ?? '')) === '' || ($merged['name'] ?? '') === ($merged['email'] ?? '')) && trim((string) ($incoming['name'] ?? '')) !== '') {
        $merged['name'] = (string) $incoming['name'];
    }

    $currentSources = array_filter(array_map('trim', explode(', ', (string) ($merged['source'] ?? ''))));
    $incomingSource = trim((string) ($incoming['source'] ?? ''));
    if ($incomingSource !== '' && !in_array($incomingSource, $currentSources, true)) {
        $currentSources[] = $incomingSource;
    }
    $merged['source'] = implode(', ', $currentSources);

    $hasExplicitStatus = !empty($merged['status_explicit']) || !empty($incoming['status_explicit']);
    $merged['status_explicit'] = $hasExplicitStatus ? 1 : 0;

    $currentStatusActive = !empty($merged['status_explicit']) ? !empty($merged['is_active']) : true;
    $incomingStatusActive = !empty($incoming['status_explicit']) ? !empty($incoming['is_active']) : true;
    $merged['is_active'] = ($currentStatusActive && $incomingStatusActive) ? 1 : 0;

    $merged['promotional_emails'] = (!empty($merged['promotional_emails']) && !empty($incoming['promotional_emails'])) ? 1 : 0;
    $merged['valid_email'] = (!empty($merged['valid_email']) || !empty($incoming['valid_email'])) ? 1 : 0;

    $status = trim((string) ($merged['status'] ?? ''));
    if ($status === '' && trim((string) ($incoming['status'] ?? '')) !== '') {
        $merged['status'] = trim((string) ($incoming['status'] ?? ''));
    }

    $merged['ready_to_send'] = ($merged['promotional_emails'] && $merged['is_active'] && $merged['valid_email']) ? 1 : 0;
    $merged['skipped_reason'] = girffonAdminPromotionalSkippedReason($merged);

    return $merged;
}

function girffonAdminFetchPromotionalAudience(PDO $pdo): array
{
    $userColumns = girffonAdminTableColumns($pdo, 'users');
    $subscriberColumns = girffonAdminTableColumns($pdo, 'newsletter_subscribers');
    $preferenceTable = girffonAdminNotificationPreferenceTable($pdo);
    $preferenceColumns = $preferenceTable !== '' ? girffonAdminTableColumns($pdo, $preferenceTable) : [];
    $audience = [];

    if ($userColumns !== [] && isset($userColumns['id'], $userColumns['email'])) {
        $firstNameExpression = isset($userColumns['first_name']) ? 'COALESCE(u.first_name, \'\')' : "''";
        $lastNameExpression = isset($userColumns['last_name']) ? 'COALESCE(u.last_name, \'\')' : "''";
        $usernameExpression = isset($userColumns['username']) ? 'NULLIF(u.username, \'\')' : 'NULL';
        $statusExpression = isset($userColumns['status']) ? 'COALESCE(u.status, \'\')' : "''";
        $promotionalExpression = ($preferenceTable !== '' && isset($preferenceColumns['user_id'], $preferenceColumns['promotional_emails']))
            ? 'COALESCE(up.promotional_emails, 1)'
            : '1';
        $joinPreferences = ($preferenceTable !== '' && isset($preferenceColumns['user_id']))
            ? ' LEFT JOIN ' . $preferenceTable . ' up ON up.user_id = u.id'
            : '';

        try {
            $statement = $pdo->query(
                "SELECT
                    u.id AS user_id,
                    COALESCE(
                        NULLIF(TRIM(CONCAT({$firstNameExpression}, ' ', {$lastNameExpression})), ''),
                        {$usernameExpression},
                        LOWER(TRIM(COALESCE(u.email, '')))
                    ) AS name,
                    LOWER(TRIM(COALESCE(u.email, ''))) AS email,
                    {$statusExpression} AS status,
                    {$promotionalExpression} AS promotional_emails
                 FROM users u{$joinPreferences}
                 ORDER BY u.id ASC"
            );

            foreach ($statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [] as $record) {
                $row = girffonAdminBuildPromotionalAudienceRow($record, 'user', isset($userColumns['status']));
                $email = (string) ($row['email'] ?? '');
                $key = $email !== '' ? $email : 'user-id:' . (int) ($row['user_id'] ?? 0);
                $audience[$key] = isset($audience[$key])
                    ? girffonAdminMergePromotionalAudienceRows($audience[$key], $row)
                    : $row;
            }
        } catch (PDOException $exception) {
        }
    }

    if ($subscriberColumns !== [] && isset($subscriberColumns['email'])) {
        $subscriberUserIdExpression = isset($subscriberColumns['user_id']) ? 'ns.user_id' : '0';
        $subscriberStatusExpression = isset($subscriberColumns['status']) ? 'COALESCE(ns.status, \'\')' : "''";
        $joinUser = isset($userColumns['id']) && isset($subscriberColumns['user_id'])
            ? ' LEFT JOIN users u ON u.id = ns.user_id'
            : '';
        $resolvedUserIdExpression = isset($userColumns['id']) && isset($subscriberColumns['user_id'])
            ? 'COALESCE(ns.user_id, u.id, 0)'
            : 'COALESCE(' . $subscriberUserIdExpression . ', 0)';
        $joinPreferences = ($preferenceTable !== '' && isset($preferenceColumns['user_id']))
            ? ' LEFT JOIN ' . $preferenceTable . ' up ON up.user_id = ' . $resolvedUserIdExpression
            : '';
        $promotionalExpression = ($preferenceTable !== '' && isset($preferenceColumns['user_id'], $preferenceColumns['promotional_emails']))
            ? 'COALESCE(up.promotional_emails, 1)'
            : '1';
        $subscriberNameExpression = 'LOWER(TRIM(COALESCE(ns.email, \'\')))';

        if ($joinUser !== '') {
            $nameCandidates = [];
            if (isset($userColumns['first_name']) || isset($userColumns['last_name'])) {
                $firstNameExpression = isset($userColumns['first_name']) ? 'COALESCE(u.first_name, \'\')' : "''";
                $lastNameExpression = isset($userColumns['last_name']) ? 'COALESCE(u.last_name, \'\')' : "''";
                $nameCandidates[] = "NULLIF(TRIM(CONCAT({$firstNameExpression}, ' ', {$lastNameExpression})), '')";
            }
            if (isset($userColumns['username'])) {
                $nameCandidates[] = "NULLIF(u.username, '')";
            }
            if (isset($userColumns['email'])) {
                $nameCandidates[] = "NULLIF(u.email, '')";
            }
            $nameCandidates[] = $subscriberNameExpression;
            $subscriberNameExpression = 'COALESCE(' . implode(', ', $nameCandidates) . ')';
        }

        try {
            $statement = $pdo->query(
                "SELECT
                    {$resolvedUserIdExpression} AS user_id,
                    {$subscriberNameExpression} AS name,
                    LOWER(TRIM(COALESCE(ns.email, ''))) AS email,
                    {$subscriberStatusExpression} AS status,
                    {$promotionalExpression} AS promotional_emails
                 FROM newsletter_subscribers ns{$joinUser}{$joinPreferences}
                 ORDER BY " . (isset($subscriberColumns['id']) ? 'ns.id ASC' : 'LOWER(TRIM(COALESCE(ns.email, \"\"))) ASC')
            );

            foreach ($statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [] as $record) {
                $row = girffonAdminBuildPromotionalAudienceRow($record, 'subscriber', isset($subscriberColumns['status']));
                $email = (string) ($row['email'] ?? '');
                $key = $email !== '' ? $email : 'subscriber-id:' . (int) ($row['user_id'] ?? 0);
                $audience[$key] = isset($audience[$key])
                    ? girffonAdminMergePromotionalAudienceRows($audience[$key], $row)
                    : $row;
            }
        } catch (PDOException $exception) {
        }
    }

    uasort($audience, static function (array $left, array $right): int {
        return strcasecmp((string) ($left['email'] ?? ''), (string) ($right['email'] ?? ''));
    });

    return array_values($audience);
}

function girffonAdminEnsurePromotionalEmailLogsTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS promotional_email_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id VARCHAR(64) NOT NULL,
                user_id INT UNSIGNED NULL,
                email VARCHAR(190) NOT NULL,
                subject VARCHAR(190) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                transport VARCHAR(40) NOT NULL DEFAULT '',
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_promotional_campaign (campaign_id),
                KEY idx_promotional_email (email),
                KEY idx_promotional_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminLogPromotionalEmailResult(PDO $pdo, array $payload): void
{
    if (!girffonAdminEnsurePromotionalEmailLogsTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO promotional_email_logs (
                campaign_id,
                user_id,
                email,
                subject,
                status,
                transport,
                error_message
             ) VALUES (
                :campaign_id,
                :user_id,
                :email,
                :subject,
                :status,
                :transport,
                :error_message
             )'
        );
        $statement->execute([
            ':campaign_id' => (string) ($payload['campaign_id'] ?? ''),
            ':user_id' => !empty($payload['user_id']) ? (int) $payload['user_id'] : null,
            ':email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            ':subject' => (string) ($payload['subject'] ?? ''),
            ':status' => (string) ($payload['status'] ?? 'pending'),
            ':transport' => (string) ($payload['transport'] ?? ''),
            ':error_message' => ($payload['error_message'] ?? '') !== '' ? (string) $payload['error_message'] : null,
        ]);
    } catch (PDOException $exception) {
    }
}

function girffonAdminFetchRecentPromotionalEmailLogs(PDO $pdo, int $limit = 30): array
{
    if (!girffonAdminEnsurePromotionalEmailLogsTable($pdo)) {
        return [];
    }

    try {
        $sql = 'SELECT campaign_id, user_id, email, subject, status, transport, error_message, created_at
                FROM promotional_email_logs
                ORDER BY created_at DESC, id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminFetchNewsletterSubscribers(PDO $pdo): array
{
    girffonEnsureNewsletterSubscribersTable($pdo);
    girffonEnsureUserPreferencesTable($pdo);

    try {
        $subscriberColumns = girffonAdminTableColumns($pdo, 'newsletter_subscribers');
        if ($subscriberColumns === []) {
            return [];
        }

        $userColumns = girffonAdminTableColumns($pdo, 'users');

        $preferenceTable = '';
        if (girffonAdminTableExists($pdo, 'user_preferences')) {
            $preferenceTable = 'user_preferences';
        } elseif (girffonAdminTableExists($pdo, 'customer_notification_preferences')) {
            $preferenceTable = 'customer_notification_preferences';
        }
        $preferenceColumns = $preferenceTable !== '' ? girffonAdminTableColumns($pdo, $preferenceTable) : [];

        $subscriberIdExpression = isset($subscriberColumns['id']) ? 'ns.id' : '0';
        $subscriberEmailExpression = isset($subscriberColumns['email']) ? 'ns.email' : "''";
        $subscriberUserIdExpression = isset($subscriberColumns['user_id']) ? 'ns.user_id' : 'NULL';

        if (isset($subscriberColumns['subscribed_at'])) {
            $subscribedAtExpression = 'ns.subscribed_at';
        } elseif (isset($subscriberColumns['created_at'])) {
            $subscribedAtExpression = 'ns.created_at';
        } elseif (isset($subscriberColumns['updated_at'])) {
            $subscribedAtExpression = 'ns.updated_at';
        } else {
            $subscribedAtExpression = 'NULL';
        }

        $joinUser = '';
        if (isset($userColumns['id'])) {
            $joinConditions = [];
            if (isset($subscriberColumns['user_id']) && isset($userColumns['id'])) {
                $joinConditions[] = 'u.id = ns.user_id';
            }
            if ($joinConditions) {
                $joinUser = ' LEFT JOIN users u ON (' . implode(' OR ', $joinConditions) . ')';
            }
        }

        $resolvedUserIdExpression = isset($userColumns['id'])
            ? 'COALESCE(' . $subscriberUserIdExpression . ', u.id, 0)'
            : 'COALESCE(' . $subscriberUserIdExpression . ', 0)';

        $joinPreferences = '';
        if ($preferenceTable !== '' && isset($preferenceColumns['user_id'])) {
            $joinPreferences = ' LEFT JOIN ' . $preferenceTable . ' up ON up.user_id = ' . $resolvedUserIdExpression;
        }

        $nameExpression = 'LOWER(TRIM(COALESCE(' . $subscriberEmailExpression . ", '')))";
        if (isset($userColumns['email'])) {
            $nameCandidates = [];
            if (isset($userColumns['first_name']) || isset($userColumns['last_name'])) {
                $firstNameExpression = isset($userColumns['first_name']) ? 'COALESCE(u.first_name, \'\')' : "''";
                $lastNameExpression = isset($userColumns['last_name']) ? 'COALESCE(u.last_name, \'\')' : "''";
                $nameCandidates[] = "NULLIF(TRIM(CONCAT(" . $firstNameExpression . ", ' ', " . $lastNameExpression . ")), '')";
            }
            if (isset($userColumns['username'])) {
                $nameCandidates[] = "NULLIF(u.username, '')";
            }
            if (isset($userColumns['email'])) {
                $nameCandidates[] = "NULLIF(u.email, '')";
            }
            $nameCandidates[] = 'LOWER(TRIM(COALESCE(' . $subscriberEmailExpression . ", '')))";
            $nameExpression = 'COALESCE(' . implode(', ', $nameCandidates) . ')';
        }

        $phoneExpression = isset($userColumns['phone'])
            ? "COALESCE(NULLIF(u.phone, ''), '')"
            : "''";

        $catalogExpression = ($preferenceTable !== '' && isset($preferenceColumns['catalog_emails']))
            ? 'COALESCE(up.catalog_emails, 1)'
            : '1';
        $promotionalExpression = ($preferenceTable !== '' && isset($preferenceColumns['promotional_emails']))
            ? 'COALESCE(up.promotional_emails, 1)'
            : '1';
        $birthdayExpression = ($preferenceTable !== '' && isset($preferenceColumns['birthday_discount_emails']))
            ? 'COALESCE(up.birthday_discount_emails, 1)'
            : '1';

        $statusExpression = isset($subscriberColumns['status'])
            ? "LOWER(TRIM(COALESCE(ns.status, 'subscribed')))"
            : "'subscribed'";
        $isActiveExpression = "CASE WHEN " . $statusExpression . " = 'subscribed' THEN 1 ELSE 0 END";

        $sql = "SELECT
                    " . $subscriberIdExpression . " AS subscriber_id,
                    " . $nameExpression . " AS name,
                    LOWER(TRIM(COALESCE(" . $subscriberEmailExpression . ", ''))) AS email,
                    " . $phoneExpression . " AS phone,
                    " . $catalogExpression . " AS catalog_emails,
                    " . $promotionalExpression . " AS promotional_emails,
                    " . $birthdayExpression . " AS birthday_discount_emails,
                    " . $subscribedAtExpression . " AS subscribed_at,
                    " . $statusExpression . " AS status,
                    " . $isActiveExpression . " AS is_active,
                    CASE
                        WHEN (" . $isActiveExpression . ") = 1 AND " . $catalogExpression . " <> 0 THEN 1
                        ELSE 0
                    END AS is_eligible,
                    " . $resolvedUserIdExpression . " AS user_id
                FROM newsletter_subscribers ns"
            . $joinUser
            . $joinPreferences
            . " WHERE " . $statusExpression . " = 'subscribed'
                AND LOWER(TRIM(COALESCE(" . $subscriberEmailExpression . ", ''))) <> ''
                ORDER BY " . $subscribedAtExpression . " DESC, " . $subscriberIdExpression . " DESC";

        $statement = $pdo->query($sql);
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminFetchNewsletterSubscriberByEmails(PDO $pdo, array $emails): array
{
    $normalized = array_values(array_unique(array_filter(array_map(static function ($value) {
        $email = strtolower(trim((string) $value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }, $emails))));

    if (!$normalized) {
        return [];
    }

    $subscribers = girffonAdminFetchNewsletterSubscribers($pdo);
    $lookup = array_flip($normalized);

    return array_values(array_filter($subscribers, static function (array $subscriber) use ($lookup): bool {
        return isset($lookup[strtolower(trim((string) ($subscriber['email'] ?? '')))]);
    }));
}

function girffonAdminLogNewsletterCampaignResult(PDO $pdo, array $payload): void
{
    if (!girffonAdminEnsureNewsletterCampaignLogsTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO newsletter_campaign_logs (
                campaign_id,
                user_id,
                recipient_name,
                email,
                subject,
                message,
                attachment_url,
                status,
                transport,
                error_message
             ) VALUES (
                :campaign_id,
                :user_id,
                :recipient_name,
                :email,
                :subject,
                :message,
                :attachment_url,
                :status,
                :transport,
                :error_message
             )'
        );
        $statement->execute([
            ':campaign_id' => (string) ($payload['campaign_id'] ?? ''),
            ':user_id' => !empty($payload['user_id']) ? (int) $payload['user_id'] : null,
            ':recipient_name' => (string) ($payload['recipient_name'] ?? ''),
            ':email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            ':subject' => (string) ($payload['subject'] ?? ''),
            ':message' => (string) ($payload['message'] ?? ''),
            ':attachment_url' => (string) ($payload['attachment_url'] ?? ''),
            ':status' => (string) ($payload['status'] ?? 'pending'),
            ':transport' => (string) ($payload['transport'] ?? ''),
            ':error_message' => ($payload['error_message'] ?? '') !== '' ? (string) $payload['error_message'] : null,
        ]);
    } catch (PDOException $exception) {
    }
}

function girffonAdminFetchRecentNewsletterCampaignLogs(PDO $pdo, int $limit = 50): array
{
    if (!girffonAdminEnsureNewsletterCampaignLogsTable($pdo)) {
        return [];
    }

    try {
        $sql = 'SELECT campaign_id, recipient_name, email, subject, attachment_url, status, transport, error_message, created_at
                FROM newsletter_campaign_logs
                ORDER BY created_at DESC, id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminLogNewsletterCampaignSummary(PDO $pdo, string $campaignId, int $sentCount, int $failedCount): void
{
    girffonCommunicationLogAdminMessage(
        $pdo,
        'GirffoN Admin',
        'admin@girffon.local',
        'Catalog Campaign Summary',
        'Catalog campaign sent to ' . $sentCount . ' users, failed ' . $failedCount . '. Campaign: ' . $campaignId,
        'unread'
    );
}

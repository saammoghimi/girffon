<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../profile/communication-common.php";

function girffonAdminBuildUsersWhereClause(array $filters, array &$params): string
{
    $conditions = [];

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $params[':search'] = '%' . $search . '%';
        $conditions[] = "(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE :search
            OR username LIKE :search
            OR email LIKE :search
            OR phone LIKE :search)";
    }

    $role = trim((string) ($filters['role'] ?? ''));
    if ($role !== '') {
        $params[':role'] = $role;
        $conditions[] = "role = :role";
    }

    $status = trim((string) ($filters['status'] ?? ''));
    if ($status !== '') {
        $params[':status'] = $status;
        $conditions[] = "status = :status";
    }

    $country = trim((string) ($filters['country'] ?? ''));
    if ($country !== '') {
        $params[':country'] = $country;
        $conditions[] = "country = :country";
    }

    if (!$conditions) {
        return '';
    }

    return ' WHERE ' . implode(' AND ', $conditions);
}

function girffonAdminFetchUsers(PDO $pdo, array $filters = []): array
{
    try {
        $params = [];
        $sql = "SELECT id, first_name, last_name, username, email, phone, country, city, address, role, status, created_at
                FROM users";
        $sql .= girffonAdminBuildUsersWhereClause($filters, $params);
        $sql .= " ORDER BY created_at DESC, id DESC";

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminFetchUserById(PDO $pdo, int $userId): ?array
{
    try {
        $statement = $pdo->prepare(
            "SELECT id, first_name, last_name, username, email, phone, country, city, address, role, status, created_at
             FROM users
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute([':id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($user) ? $user : null;
    } catch (PDOException $exception) {
        return null;
    }
}

function girffonAdminFetchUserCommunicationSnapshot(PDO $pdo, int $userId): array
{
    $user = girffonAdminFetchUserById($pdo, $userId);
    if (!$user) {
        return [
            'preferences' => [],
            'newsletter' => [],
            'latest_test_email' => [],
        ];
    }

    $email = strtolower(trim((string) ($user['email'] ?? '')));
    $preferencesRow = girffonCommunicationFetchUserPreferences($pdo, $userId);

    return [
        'preferences' => [
            'promotional_emails' => !empty($preferencesRow['promotional_emails']),
            'catalog_emails' => !empty($preferencesRow['catalog_emails']),
            'birthday_discount_emails' => !empty($preferencesRow['birthday_discount_emails']),
            'order_updates' => !empty($preferencesRow['order_updates']),
            'sms_notifications' => !empty($preferencesRow['sms_notifications']),
            'two_factor_enabled' => !empty($preferencesRow['two_factor_enabled']),
            'updated_at' => (string) ($preferencesRow['updated_at'] ?? ''),
            'sms_note' => 'Available soon',
        ],
        'newsletter' => $email !== '' ? girffonCommunicationFetchNewsletterSubscriber($pdo, $email) : [],
        'latest_test_email' => $email !== '' ? girffonCommunicationFetchLatestTestEmailLog($pdo, $email) : [],
    ];
}

function girffonAdminFetchRecentOrdersForUser(PDO $pdo, int $userId, int $limit = 5): array
{
    $user = girffonAdminFetchUserById($pdo, $userId);
    $email = trim((string) ($user['email'] ?? ''));
    if ($email === '') {
        return [];
    }

    try {
        $sql = "SELECT orders.id, orders.order_number, orders.customer_name, orders.customer_email, orders.total, orders.payment_status, orders.order_status, orders.tracking_code, orders.created_at
                FROM orders
                WHERE orders.customer_email = :email
                ORDER BY orders.created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->prepare($sql);
        $statement->execute([':email' => $email]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminFetchRecentInvoicesForUser(PDO $pdo, int $userId, int $limit = 5): array
{
    $user = girffonAdminFetchUserById($pdo, $userId);
    $email = trim((string) ($user['email'] ?? ''));
    if ($email === '') {
        return [];
    }

    try {
        $sql = "SELECT invoices.id, invoices.invoice_number, invoices.invoice_status, invoices.invoice_total, invoices.created_at,
                       orders.order_number, orders.customer_name, orders.customer_email
                FROM invoices
                INNER JOIN orders ON orders.id = invoices.order_id
                WHERE orders.customer_email = :email
                ORDER BY invoices.created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->prepare($sql);
        $statement->execute([':email' => $email]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminUpdateUser(PDO $pdo, int $userId, array $payload): bool
{
    $statement = $pdo->prepare(
        "UPDATE users
         SET first_name = :first_name,
             last_name = :last_name,
             email = :email,
             phone = :phone,
             country = :country,
             city = :city,
             address = :address,
             role = :role,
             status = :status
         WHERE id = :id
         LIMIT 1"
    );

    return $statement->execute([
        ':id' => $userId,
        ':first_name' => $payload['first_name'],
        ':last_name' => $payload['last_name'],
        ':email' => $payload['email'],
        ':phone' => $payload['phone'],
        ':country' => $payload['country'],
        ':city' => $payload['city'],
        ':address' => $payload['address'],
        ':role' => $payload['role'],
        ':status' => $payload['status'],
    ]);
}

function girffonAdminDeleteUser(PDO $pdo, int $userId): bool
{
    $statement = $pdo->prepare(
        "DELETE FROM users
         WHERE id = :id
         LIMIT 1"
    );

    $statement->execute([':id' => $userId]);
    return $statement->rowCount() > 0;
}

function girffonAdminUserEmailExists(PDO $pdo, string $email, int $excludeUserId = 0): bool
{
    try {
        $statement = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = :email AND id <> :id
             LIMIT 1"
        );
        $statement->execute([
            ':email' => $email,
            ':id' => $excludeUserId,
        ]);

        return (bool) $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminCountMembers(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminCountActiveMembers(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminCountAdminUsers(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminCountNewMembersThisMonth(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchUserFilterOptions(PDO $pdo): array
{
    $options = [
        'roles' => [],
        'statuses' => [],
        'countries' => [],
    ];

    $queries = [
        'roles' => "SELECT DISTINCT role AS option_value FROM users WHERE role IS NOT NULL AND role <> '' ORDER BY role ASC",
        'statuses' => "SELECT DISTINCT status AS option_value FROM users WHERE status IS NOT NULL AND status <> '' ORDER BY status ASC",
        'countries' => "SELECT DISTINCT country AS option_value FROM users WHERE country IS NOT NULL AND country <> '' ORDER BY country ASC",
    ];

    foreach ($queries as $key => $sql) {
        try {
            $statement = $pdo->query($sql);
            $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
            $options[$key] = array_values(array_filter(array_map(static function ($row) {
                return trim((string) ($row['option_value'] ?? ''));
            }, $rows)));
        } catch (PDOException $exception) {
            $options[$key] = [];
        }
    }

    return $options;
}
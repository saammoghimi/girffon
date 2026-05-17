<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminDashboardLogPath(string $fileName): string
{
    return dirname(__DIR__) . "/logs/" . ltrim($fileName, "/");
}

function girffonAdminDashboardEnsureLogDirectory(): void
{
    $directory = dirname(girffonAdminDashboardLogPath("placeholder.log"));
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }
}

function girffonAdminDashboardReadJsonLog(string $fileName): array
{
    $path = girffonAdminDashboardLogPath($fileName);
    if (!is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === "") {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? array_values($decoded) : [];
}

function girffonAdminDashboardWriteJsonLog(string $fileName, array $entries): void
{
    girffonAdminDashboardEnsureLogDirectory();
    @file_put_contents(
        girffonAdminDashboardLogPath($fileName),
        json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function girffonAdminDashboardNormalizeTimestamp($value): int
{
    $timestamp = strtotime((string) $value);
    return $timestamp !== false ? $timestamp : 0;
}

function girffonAdminDashboardTrimLogEntries(array $entries, int $maxEntries = 600): array
{
    usort($entries, static function (array $left, array $right): int {
        return girffonAdminDashboardNormalizeTimestamp($right['created_at'] ?? '') <=> girffonAdminDashboardNormalizeTimestamp($left['created_at'] ?? '');
    });

    if ($maxEntries > 0 && count($entries) > $maxEntries) {
        $entries = array_slice($entries, 0, $maxEntries);
    }

    return array_values($entries);
}

function girffonAdminDashboardUserColumns(PDO $pdo): array
{
    static $columns = null;

    if (is_array($columns)) {
        return $columns;
    }

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM users");
        $columns = [];
        foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $column) {
            $name = strtolower(trim((string) ($column['Field'] ?? '')));
            if ($name !== '') {
                $columns[$name] = true;
            }
        }
        return $columns;
    } catch (PDOException $exception) {
        $columns = [];
        return $columns;
    }
}

function girffonAdminDashboardHasUserColumn(PDO $pdo, string $column): bool
{
    $columns = girffonAdminDashboardUserColumns($pdo);
    return isset($columns[strtolower($column)]);
}

function girffonAdminRecordLoginActivity(int $adminId, string $username): void
{
    if ($adminId <= 0 && trim($username) === '') {
        return;
    }

    $entries = girffonAdminDashboardReadJsonLog('admin-login-activity.json');
    $entries[] = [
        'admin_id' => $adminId,
        'username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'created_at' => date('c'),
    ];

    girffonAdminDashboardWriteJsonLog('admin-login-activity.json', girffonAdminDashboardTrimLogEntries($entries));
}

function girffonAdminTrackDashboardVisit(int $adminId, string $username): void
{
    $entries = girffonAdminDashboardReadJsonLog('admin-dashboard-visits.json');
    $entries[] = [
        'admin_id' => $adminId,
        'username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'created_at' => date('c'),
    ];

    girffonAdminDashboardWriteJsonLog('admin-dashboard-visits.json', girffonAdminDashboardTrimLogEntries($entries, 1200));
}

function girffonAdminFetchRecentLoginActivity(int $limit = 6): array
{
    $entries = girffonAdminDashboardReadJsonLog('admin-login-activity.json');
    $entries = girffonAdminDashboardTrimLogEntries($entries, 200);
    return $limit > 0 ? array_slice($entries, 0, $limit) : $entries;
}

function girffonAdminFetchActiveAdmins(int $minutes = 30, int $limit = 2): array
{
    $entries = girffonAdminDashboardReadJsonLog('admin-login-activity.json');
    $cutoff = time() - max(1, $minutes) * 60;
    $active = [];

    foreach (girffonAdminDashboardTrimLogEntries($entries, 200) as $entry) {
        $timestamp = girffonAdminDashboardNormalizeTimestamp($entry['created_at'] ?? '');
        if ($timestamp < $cutoff) {
            continue;
        }

        $adminKey = (string) (($entry['admin_id'] ?? '') !== '' ? $entry['admin_id'] : ($entry['username'] ?? ''));
        if ($adminKey === '' || isset($active[$adminKey])) {
            continue;
        }

        $active[$adminKey] = [
            'admin_id' => (int) ($entry['admin_id'] ?? 0),
            'username' => (string) ($entry['username'] ?? 'GirffoN Admin'),
            'created_at' => (string) ($entry['created_at'] ?? ''),
        ];

        if ($limit > 0 && count($active) >= $limit) {
            break;
        }
    }

    return array_values($active);
}

function girffonAdminFetchLastLoginTime(int $adminId = 0, string $username = ''): string
{
    foreach (girffonAdminFetchRecentLoginActivity(50) as $entry) {
        if ($adminId > 0 && (int) ($entry['admin_id'] ?? 0) === $adminId) {
            return (string) ($entry['created_at'] ?? '');
        }

        if ($adminId <= 0 && $username !== '' && strcasecmp((string) ($entry['username'] ?? ''), $username) === 0) {
            return (string) ($entry['created_at'] ?? '');
        }
    }

    return '';
}

function girffonAdminCountOrdersForDateRange(PDO $pdo, string $startExpression, string $endExpression): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= {$startExpression} AND created_at < {$endExpression}");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminSumRevenueForDateRange(PDO $pdo, string $startExpression, string $endExpression): float
{
    try {
        $statement = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= {$startExpression} AND created_at < {$endExpression}");
        return $statement ? (float) $statement->fetchColumn() : 0.0;
    } catch (PDOException $exception) {
        return 0.0;
    }
}

function girffonAdminCountInvoicesForDateRange(PDO $pdo, string $startExpression, string $endExpression): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM invoices WHERE created_at >= {$startExpression} AND created_at < {$endExpression}");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminCountMembersForDateRange(PDO $pdo, string $startExpression, string $endExpression): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= {$startExpression} AND created_at < {$endExpression}");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchPeriodStats(PDO $pdo): array
{
    $ranges = [
        'daily' => [
            'label' => 'Today',
            'start' => 'CURDATE()',
            'end' => 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)',
        ],
        'monthly' => [
            'label' => 'This Month',
            'start' => "DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            'end' => "DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)",
        ],
        'yearly' => [
            'label' => 'This Year',
            'start' => "MAKEDATE(YEAR(CURDATE()), 1)",
            'end' => "DATE_ADD(MAKEDATE(YEAR(CURDATE()), 1), INTERVAL 1 YEAR)",
        ],
    ];

    $stats = [];
    foreach ($ranges as $key => $range) {
        $stats[$key] = [
            'label' => $range['label'],
            'orders' => girffonAdminCountOrdersForDateRange($pdo, $range['start'], $range['end']),
            'revenue' => girffonAdminSumRevenueForDateRange($pdo, $range['start'], $range['end']),
            'invoices' => girffonAdminCountInvoicesForDateRange($pdo, $range['start'], $range['end']),
            'members' => girffonAdminCountMembersForDateRange($pdo, $range['start'], $range['end']),
        ];
    }

    return $stats;
}

function girffonAdminCountOrdersToday(PDO $pdo): int
{
    return girffonAdminCountOrdersForDateRange($pdo, 'CURDATE()', 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)');
}

function girffonAdminRevenueThisMonth(PDO $pdo): float
{
    return girffonAdminSumRevenueForDateRange($pdo, "DATE_FORMAT(CURDATE(), '%Y-%m-01')", "DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)");
}

function girffonAdminFetchVisitorAnalytics(): array
{
    $entries = girffonAdminDashboardReadJsonLog('admin-dashboard-visits.json');
    $today = date('Y-m-d');
    $month = date('Y-m');
    $year = date('Y');

    $analytics = [
        'today' => 0,
        'month' => 0,
        'year' => 0,
        'recent' => [],
    ];

    foreach (girffonAdminDashboardTrimLogEntries($entries, 300) as $entry) {
        $createdAt = (string) ($entry['created_at'] ?? '');
        if ($createdAt === '') {
            continue;
        }

        if (strpos($createdAt, $today) === 0) {
            $analytics['today']++;
        }
        if (strpos($createdAt, $month) === 0) {
            $analytics['month']++;
        }
        if (strpos($createdAt, $year) === 0) {
            $analytics['year']++;
        }
    }

    $analytics['recent'] = array_slice(girffonAdminDashboardTrimLogEntries($entries, 12), 0, 5);
    return $analytics;
}

function girffonAdminFetchAdminProfile(PDO $pdo, int $adminId): array
{
    if ($adminId <= 0) {
        return [];
    }

    $cityExpression = girffonAdminDashboardHasUserColumn($pdo, 'city') ? 'city' : "'' AS city";
    $countryExpression = girffonAdminDashboardHasUserColumn($pdo, 'country') ? 'country' : "'' AS country";

    try {
        $statement = $pdo->prepare("SELECT id, username, first_name, last_name, email, {$cityExpression}, {$countryExpression} FROM users WHERE id = :id AND role = 'admin' LIMIT 1");
        $statement->execute([':id' => $adminId]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
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

function girffonAdminFetchRecentMembers(PDO $pdo, int $limit = 5): array
{
    try {
        $sql = "SELECT first_name, last_name, email, created_at
                FROM users
                WHERE role = 'customer'
                ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $exception) {
        return [];
    }
}
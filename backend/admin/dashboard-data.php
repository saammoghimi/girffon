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

function girffonAdminFetchAvailableStatYears(PDO $pdo): array
{
    return [2026, 2027, 2028, 2029, 2030];
}

function girffonAdminAnalyticsBuildBaseSeries(array $labels): array
{
    $series = [];
    foreach ($labels as $key => $label) {
        $series[(string) $key] = [
            'key' => (string) $key,
            'label' => (string) $label,
            'orders' => 0,
            'revenue' => 0.0,
            'invoices' => 0,
            'members' => 0,
        ];
    }

    return $series;
}

function girffonAdminAnalyticsSeriesValues(array $series): array
{
    return array_values($series);
}

function girffonAdminAnalyticsSeriesSummary(array $series): array
{
    $summary = [
        'orders' => 0,
        'revenue' => 0.0,
        'invoices' => 0,
        'members' => 0,
    ];

    foreach ($series as $bucket) {
        $summary['orders'] += (int) ($bucket['orders'] ?? 0);
        $summary['revenue'] += (float) ($bucket['revenue'] ?? 0);
        $summary['invoices'] += (int) ($bucket['invoices'] ?? 0);
        $summary['members'] += (int) ($bucket['members'] ?? 0);
    }

    return $summary;
}

function girffonAdminAnalyticsDailySeries(PDO $pdo, int $year, int $month): array
{
    $year = max(2024, min(2035, $year));
    $month = max(1, min(12, $month));
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $labels = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $labels[(string) $day] = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
    }
    $series = girffonAdminAnalyticsBuildBaseSeries($labels);

    try {
        $statement = $pdo->prepare("SELECT DAY(created_at) AS bucket_day, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month GROUP BY DAY(created_at)");
        $statement->execute([':year' => $year, ':month' => $month]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ((int) ($row['bucket_day'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['orders'] = (int) ($row['orders'] ?? 0);
                $series[$key]['revenue'] = (float) ($row['revenue'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->prepare("SELECT DAY(created_at) AS bucket_day, COUNT(*) AS invoices FROM invoices WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month GROUP BY DAY(created_at)");
        $statement->execute([':year' => $year, ':month' => $month]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ((int) ($row['bucket_day'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['invoices'] = (int) ($row['invoices'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->prepare("SELECT DAY(created_at) AS bucket_day, COUNT(*) AS members FROM users WHERE role = 'customer' AND YEAR(created_at) = :year AND MONTH(created_at) = :month GROUP BY DAY(created_at)");
        $statement->execute([':year' => $year, ':month' => $month]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ((int) ($row['bucket_day'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['members'] = (int) ($row['members'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    return [
        'label' => date('F Y', strtotime(sprintf('%04d-%02d-01', $year, $month))),
        'summary' => girffonAdminAnalyticsSeriesSummary($series),
        'series' => girffonAdminAnalyticsSeriesValues($series),
    ];
}

function girffonAdminAnalyticsMonthlySeries(PDO $pdo, int $year): array
{
    $year = max(2024, min(2035, $year));
    $labels = [
        '1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'May', '6' => 'Jun',
        '7' => 'Jul', '8' => 'Aug', '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec',
    ];
    $series = girffonAdminAnalyticsBuildBaseSeries($labels);

    try {
        $statement = $pdo->prepare("SELECT MONTH(created_at) AS bucket_month, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE YEAR(created_at) = :year GROUP BY MONTH(created_at)");
        $statement->execute([':year' => $year]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ((int) ($row['bucket_month'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['orders'] = (int) ($row['orders'] ?? 0);
                $series[$key]['revenue'] = (float) ($row['revenue'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->prepare("SELECT MONTH(created_at) AS bucket_month, COUNT(*) AS invoices FROM invoices WHERE YEAR(created_at) = :year GROUP BY MONTH(created_at)");
        $statement->execute([':year' => $year]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ((int) ($row['bucket_month'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['invoices'] = (int) ($row['invoices'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->prepare("SELECT MONTH(created_at) AS bucket_month, COUNT(*) AS members FROM users WHERE role = 'customer' AND YEAR(created_at) = :year GROUP BY MONTH(created_at)");
        $statement->execute([':year' => $year]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ((int) ($row['bucket_month'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['members'] = (int) ($row['members'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    return [
        'label' => (string) $year,
        'summary' => girffonAdminAnalyticsSeriesSummary($series),
        'series' => girffonAdminAnalyticsSeriesValues($series),
    ];
}

function girffonAdminAnalyticsYearlySeries(PDO $pdo, array $years): array
{
    $keys = [];
    foreach ($years as $year) {
        $keys[(string) $year] = (string) $year;
    }
    $series = girffonAdminAnalyticsBuildBaseSeries($keys);

    try {
        $statement = $pdo->query("SELECT YEAR(created_at) AS bucket_year, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE created_at IS NOT NULL GROUP BY YEAR(created_at)");
        foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            $key = (string) ((int) ($row['bucket_year'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['orders'] = (int) ($row['orders'] ?? 0);
                $series[$key]['revenue'] = (float) ($row['revenue'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->query("SELECT YEAR(created_at) AS bucket_year, COUNT(*) AS invoices FROM invoices WHERE created_at IS NOT NULL GROUP BY YEAR(created_at)");
        foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            $key = (string) ((int) ($row['bucket_year'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['invoices'] = (int) ($row['invoices'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->query("SELECT YEAR(created_at) AS bucket_year, COUNT(*) AS members FROM users WHERE role = 'customer' AND created_at IS NOT NULL GROUP BY YEAR(created_at)");
        foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            $key = (string) ((int) ($row['bucket_year'] ?? 0));
            if (isset($series[$key])) {
                $series[$key]['members'] = (int) ($row['members'] ?? 0);
            }
        }
    } catch (PDOException $exception) {
    }

    return [
        'label' => count($years) > 1 ? ((string) min($years) . ' - ' . (string) max($years)) : ((string) ($years[0] ?? date('Y'))),
        'summary' => girffonAdminAnalyticsSeriesSummary($series),
        'series' => girffonAdminAnalyticsSeriesValues($series),
    ];
}

function girffonAdminFetchAnalyticsExplorer(PDO $pdo): array
{
    $years = girffonAdminFetchAvailableStatYears($pdo);
    $selectedYear = 2026;
    $selectedMonth = (int) date('n');

    $daily = [];
    $monthly = [];
    foreach ($years as $year) {
        $monthly[(string) $year] = girffonAdminAnalyticsMonthlySeries($pdo, (int) $year);
        $daily[(string) $year] = [];
        for ($month = 1; $month <= 12; $month++) {
            $daily[(string) $year][(string) $month] = girffonAdminAnalyticsDailySeries($pdo, (int) $year, $month);
        }
    }

    return [
        'years' => $years,
        'selectedYear' => $selectedYear,
        'selectedMonth' => $selectedMonth,
        'daily' => $daily,
        'monthly' => $monthly,
        'yearly' => girffonAdminAnalyticsYearlySeries($pdo, $years),
    ];
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
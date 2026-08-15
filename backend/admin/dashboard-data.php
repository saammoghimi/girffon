<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/custom-design-orders-data.php";
require_once __DIR__ . "/../utils/gift-card-service.php";

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

function girffonAdminAnalyticsDebugLog(array $entry): void
{
    girffonAdminDashboardEnsureLogDirectory();
    $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents(girffonAdminDashboardLogPath('visitor-analytics-debug.log'), $line, FILE_APPEND);
}

function girffonAdminAnalyticsSetLastTrackDebug(array $state): void
{
    $GLOBALS['girffon_admin_analytics_last_track_debug'] = $state;
}

function girffonAdminAnalyticsGetLastTrackDebug(): array
{
    $state = $GLOBALS['girffon_admin_analytics_last_track_debug'] ?? null;
    return is_array($state) ? $state : [];
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

function girffonAdminDashboardRomeTimezone(): DateTimeZone
{
    static $timezone = null;
    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    $timezone = new DateTimeZone('Europe/Rome');
    return $timezone;
}

function girffonAdminDashboardUtcTimezone(): DateTimeZone
{
    static $timezone = null;
    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    $timezone = new DateTimeZone('UTC');
    return $timezone;
}

function girffonAdminDashboardRomeNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', girffonAdminDashboardRomeTimezone());
}

function girffonAdminDashboardRomeCurrent(string $format = 'd M Y · H:i'): string
{
    return girffonAdminDashboardRomeNow()->format($format);
}

function girffonAdminDashboardFormatRome(string $value, string $format = 'd M Y · H:i'): string
{
    $value = trim($value);
    if ($value === '') {
        return '-';
    }

    try {
        $date = new DateTimeImmutable($value, girffonAdminDashboardUtcTimezone());
        return $date->setTimezone(girffonAdminDashboardRomeTimezone())->format($format);
    } catch (Throwable $exception) {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone(girffonAdminDashboardRomeTimezone())
            ->format($format);
    }
}

function girffonAdminDashboardRomeUtcRange(DateTimeImmutable $startRome, DateTimeImmutable $endRome): array
{
    return [
        'start' => $startRome->setTimezone(girffonAdminDashboardUtcTimezone())->format('Y-m-d H:i:s'),
        'end' => $endRome->setTimezone(girffonAdminDashboardUtcTimezone())->format('Y-m-d H:i:s'),
    ];
}

function girffonAdminDashboardRomePeriodRange(string $period): array
{
    $now = girffonAdminDashboardRomeNow();

    if ($period === 'yearly') {
        $start = $now->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0, 0);
        return girffonAdminDashboardRomeUtcRange($start, $start->modify('+1 year'));
    }

    if ($period === 'monthly') {
        $start = $now->modify('first day of this month')->setTime(0, 0, 0);
        return girffonAdminDashboardRomeUtcRange($start, $start->modify('+1 month'));
    }

    $start = $now->setTime(0, 0, 0);
    return girffonAdminDashboardRomeUtcRange($start, $start->modify('+1 day'));
}

function girffonAdminDashboardRomeMonthRange(int $year, int $month): array
{
    $month = max(1, min(12, $month));
    $start = (new DateTimeImmutable('now', girffonAdminDashboardRomeTimezone()))->setDate($year, $month, 1)->setTime(0, 0, 0);
    return girffonAdminDashboardRomeUtcRange($start, $start->modify('+1 month'));
}

function girffonAdminDashboardRomeYearRange(int $year): array
{
    $start = (new DateTimeImmutable('now', girffonAdminDashboardRomeTimezone()))->setDate($year, 1, 1)->setTime(0, 0, 0);
    return girffonAdminDashboardRomeUtcRange($start, $start->modify('+1 year'));
}

function girffonAdminDashboardBucketKey(string $value, string $period): string
{
    try {
        $date = new DateTimeImmutable($value, girffonAdminDashboardUtcTimezone());
        $romeDate = $date->setTimezone(girffonAdminDashboardRomeTimezone());
    } catch (Throwable $exception) {
        return '';
    }

    if ($period === 'yearly') {
        return $romeDate->format('Y');
    }

    if ($period === 'monthly') {
        return (string) ((int) $romeDate->format('n'));
    }

    return (string) ((int) $romeDate->format('j'));
}

function girffonAdminDashboardFetchRowsForRange(PDO $pdo, string $table, string $startUtc, string $endUtc, array $columns, string $extraWhere = ''): array
{
    try {
        $select = implode(', ', $columns);
        $where = "created_at >= :start_at AND created_at < :end_at";
        if ($extraWhere !== '') {
            $where .= ' AND ' . $extraWhere;
        }

        $statement = $pdo->prepare("SELECT {$select} FROM {$table} WHERE {$where}");
        $statement->execute([
            ':start_at' => $startUtc,
            ':end_at' => $endUtc,
        ]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
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
    $shopOrders = 0;
    try {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= :start_at AND created_at < :end_at");
        $statement->execute([':start_at' => $startExpression, ':end_at' => $endExpression]);
        $shopOrders = (int) $statement->fetchColumn();
    } catch (PDOException $exception) {
    }

    $customOrders = 0;
    foreach (girffonAdminDashboardFetchCustomDesignRowsForRange($pdo, $startExpression, $endExpression) as $row) {
        $createdAt = trim((string) ($row['created_at'] ?? ''));
        if ($createdAt !== '' && $createdAt >= $startExpression && $createdAt < $endExpression) {
            $customOrders++;
        }
    }

    return $shopOrders + $customOrders;
}

function girffonAdminSumRevenueForDateRange(PDO $pdo, string $startExpression, string $endExpression): float
{
    $shopRevenue = 0.0;
    try {
        $statement = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= :start_at AND created_at < :end_at");
        $statement->execute([':start_at' => $startExpression, ':end_at' => $endExpression]);
        $shopRevenue = (float) $statement->fetchColumn();
    } catch (PDOException $exception) {
    }

    $customRevenue = 0.0;
    foreach (girffonAdminDashboardFetchCustomDesignRowsForRange($pdo, $startExpression, $endExpression) as $row) {
        $paidAt = trim((string) ($row['paid_at'] ?? ''));
        $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? '')));
        if ($paymentStatus === 'paid' && $paidAt !== '' && $paidAt >= $startExpression && $paidAt < $endExpression) {
            $customRevenue += girffonAdminDashboardCustomDesignOrderTotal($row);
        }
    }

    return $shopRevenue + $customRevenue;
}

function girffonAdminCountInvoicesForDateRange(PDO $pdo, string $startExpression, string $endExpression): int
{
    try {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE created_at >= :start_at AND created_at < :end_at");
        $statement->execute([':start_at' => $startExpression, ':end_at' => $endExpression]);
        return (int) $statement->fetchColumn();
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminCountMembersForDateRange(PDO $pdo, string $startExpression, string $endExpression): int
{
    try {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= :start_at AND created_at < :end_at");
        $statement->execute([':start_at' => $startExpression, ':end_at' => $endExpression]);
        return (int) $statement->fetchColumn();
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchPeriodStats(PDO $pdo): array
{
    $ranges = [
        'daily' => [
            'label' => 'Today',
            'range' => girffonAdminDashboardRomePeriodRange('daily'),
        ],
        'monthly' => [
            'label' => 'This Month',
            'range' => girffonAdminDashboardRomePeriodRange('monthly'),
        ],
        'yearly' => [
            'label' => 'This Year',
            'range' => girffonAdminDashboardRomePeriodRange('yearly'),
        ],
    ];

    $stats = [];
    foreach ($ranges as $key => $range) {
        $stats[$key] = [
            'label' => $range['label'],
            'orders' => girffonAdminCountOrdersForDateRange($pdo, $range['range']['start'], $range['range']['end']),
            'revenue' => girffonAdminSumRevenueForDateRange($pdo, $range['range']['start'], $range['range']['end']),
            'invoices' => girffonAdminCountInvoicesForDateRange($pdo, $range['range']['start'], $range['range']['end']),
            'members' => girffonAdminCountMembersForDateRange($pdo, $range['range']['start'], $range['range']['end']),
            'shop_orders' => 0,
            'custom_design_orders' => 0,
            'shop_revenue' => 0.0,
            'custom_design_revenue' => 0.0,
        ];

        try {
            $statement = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total), 0) FROM orders WHERE created_at >= :start_at AND created_at < :end_at");
            $statement->execute([':start_at' => $range['range']['start'], ':end_at' => $range['range']['end']]);
            $shopRow = $statement->fetch(PDO::FETCH_NUM) ?: [0, 0];
            $stats[$key]['shop_orders'] = (int) ($shopRow[0] ?? 0);
            $stats[$key]['shop_revenue'] = (float) ($shopRow[1] ?? 0);
        } catch (PDOException $exception) {
        }

        foreach (girffonAdminDashboardFetchCustomDesignRowsForRange($pdo, $range['range']['start'], $range['range']['end']) as $row) {
            $createdAt = trim((string) ($row['created_at'] ?? ''));
            $paidAt = trim((string) ($row['paid_at'] ?? ''));
            $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? '')));
            if ($createdAt !== '' && $createdAt >= $range['range']['start'] && $createdAt < $range['range']['end']) {
                $stats[$key]['custom_design_orders']++;
            }
            if ($paymentStatus === 'paid' && $paidAt !== '' && $paidAt >= $range['range']['start'] && $paidAt < $range['range']['end']) {
                $stats[$key]['custom_design_revenue'] += girffonAdminDashboardCustomDesignOrderTotal($row);
            }
        }
    }

    return $stats;
}

function girffonAdminFetchAvailableStatYears(PDO $pdo): array
{
    return [2025, 2026, 2027, 2028, 2029, 2030];
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
    [$minimumYear, $maximumYear] = girffonAdminDashboardSaneYearRange();
    $year = max($minimumYear, min($maximumYear, $year));
    $month = max(1, min(12, $month));
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $labels = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $labels[(string) $day] = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
    }
    $series = girffonAdminAnalyticsBuildBaseSeries($labels);
    $range = girffonAdminDashboardRomeMonthRange($year, $month);

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'orders', $range['start'], $range['end'], ['created_at', 'total']) as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'daily');
        if (isset($series[$key])) {
            $series[$key]['orders']++;
            $series[$key]['revenue'] += (float) ($row['total'] ?? 0);
        }
    }

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'invoices', $range['start'], $range['end'], ['created_at']) as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'daily');
        if (isset($series[$key])) {
            $series[$key]['invoices']++;
        }
    }

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'users', $range['start'], $range['end'], ['created_at'], "role = 'customer'") as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'daily');
        if (isset($series[$key])) {
            $series[$key]['members']++;
        }
    }

    foreach (girffonAdminDashboardFetchCustomDesignRowsForRange($pdo, $range['start'], $range['end']) as $row) {
        $createdAtKey = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'daily');
        if (isset($series[$createdAtKey])) {
            $series[$createdAtKey]['orders']++;
        }

        $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? '')));
        if ($paymentStatus === 'paid') {
            $paidAtKey = girffonAdminDashboardBucketKey((string) ($row['paid_at'] ?? ''), 'daily');
            if (isset($series[$paidAtKey])) {
                $series[$paidAtKey]['revenue'] += girffonAdminDashboardCustomDesignOrderTotal($row);
            }
        }
    }

    return [
        'label' => (new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), girffonAdminDashboardRomeTimezone()))->format('F Y'),
        'summary' => girffonAdminAnalyticsSeriesSummary($series),
        'series' => girffonAdminAnalyticsSeriesValues($series),
    ];
}

function girffonAdminAnalyticsMonthlySeries(PDO $pdo, int $year): array
{
    [$minimumYear, $maximumYear] = girffonAdminDashboardSaneYearRange();
    $year = max($minimumYear, min($maximumYear, $year));
    $labels = [
        '1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'May', '6' => 'Jun',
        '7' => 'Jul', '8' => 'Aug', '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec',
    ];
    $series = girffonAdminAnalyticsBuildBaseSeries($labels);
    $range = girffonAdminDashboardRomeYearRange($year);

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'orders', $range['start'], $range['end'], ['created_at', 'total']) as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'monthly');
        if (isset($series[$key])) {
            $series[$key]['orders']++;
            $series[$key]['revenue'] += (float) ($row['total'] ?? 0);
        }
    }

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'invoices', $range['start'], $range['end'], ['created_at']) as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'monthly');
        if (isset($series[$key])) {
            $series[$key]['invoices']++;
        }
    }

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'users', $range['start'], $range['end'], ['created_at'], "role = 'customer'") as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'monthly');
        if (isset($series[$key])) {
            $series[$key]['members']++;
        }
    }

    foreach (girffonAdminDashboardFetchCustomDesignRowsForRange($pdo, $range['start'], $range['end']) as $row) {
        $createdAtKey = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'monthly');
        if (isset($series[$createdAtKey])) {
            $series[$createdAtKey]['orders']++;
        }

        $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? '')));
        if ($paymentStatus === 'paid') {
            $paidAtKey = girffonAdminDashboardBucketKey((string) ($row['paid_at'] ?? ''), 'monthly');
            if (isset($series[$paidAtKey])) {
                $series[$paidAtKey]['revenue'] += girffonAdminDashboardCustomDesignOrderTotal($row);
            }
        }
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
    if (!$years) {
        return [
            'label' => (string) girffonAdminDashboardRomeNow()->format('Y'),
            'summary' => girffonAdminAnalyticsSeriesSummary($series),
            'series' => girffonAdminAnalyticsSeriesValues($series),
        ];
    }

    $startRange = girffonAdminDashboardRomeYearRange((int) min($years));
    $endRange = girffonAdminDashboardRomeYearRange((int) max($years) + 1);

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'orders', $startRange['start'], $endRange['start'], ['created_at', 'total']) as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'yearly');
        if (isset($series[$key])) {
            $series[$key]['orders']++;
            $series[$key]['revenue'] += (float) ($row['total'] ?? 0);
        }
    }

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'invoices', $startRange['start'], $endRange['start'], ['created_at']) as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'yearly');
        if (isset($series[$key])) {
            $series[$key]['invoices']++;
        }
    }

    foreach (girffonAdminDashboardFetchRowsForRange($pdo, 'users', $startRange['start'], $endRange['start'], ['created_at'], "role = 'customer'") as $row) {
        $key = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'yearly');
        if (isset($series[$key])) {
            $series[$key]['members']++;
        }
    }

    foreach (girffonAdminDashboardFetchCustomDesignRowsForRange($pdo, $startRange['start'], $endRange['start']) as $row) {
        $createdAtKey = girffonAdminDashboardBucketKey((string) ($row['created_at'] ?? ''), 'yearly');
        if (isset($series[$createdAtKey])) {
            $series[$createdAtKey]['orders']++;
        }

        $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? '')));
        if ($paymentStatus === 'paid') {
            $paidAtKey = girffonAdminDashboardBucketKey((string) ($row['paid_at'] ?? ''), 'yearly');
            if (isset($series[$paidAtKey])) {
                $series[$paidAtKey]['revenue'] += girffonAdminDashboardCustomDesignOrderTotal($row);
            }
        }
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
    $romeNow = girffonAdminDashboardRomeNow();
    $selectedYear = (int) $romeNow->format('Y');
    $selectedMonth = (int) $romeNow->format('n');

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
    $range = girffonAdminDashboardRomePeriodRange('daily');
    return girffonAdminCountOrdersForDateRange($pdo, $range['start'], $range['end']);
}

function girffonAdminRevenueThisMonth(PDO $pdo): float
{
    $range = girffonAdminDashboardRomePeriodRange('monthly');
    return girffonAdminSumRevenueForDateRange($pdo, $range['start'], $range['end']);
}

function girffonAdminEnsureWebsiteAnalyticsTables(PDO $pdo): bool
{
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS website_visitor_sessions (
                session_id VARCHAR(128) NOT NULL PRIMARY KEY,
                visitor_id VARCHAR(128) NOT NULL,
                ip_address VARCHAR(45) NOT NULL DEFAULT '',
                country_code VARCHAR(8) NOT NULL DEFAULT '',
                country_name VARCHAR(120) NOT NULL DEFAULT 'Unknown',
                browser_name VARCHAR(32) NOT NULL DEFAULT 'Other',
                device_type VARCHAR(16) NOT NULL DEFAULT 'Desktop',
                traffic_source VARCHAR(32) NOT NULL DEFAULT 'Direct',
                landing_page VARCHAR(255) NOT NULL DEFAULT '/',
                first_page VARCHAR(255) NOT NULL DEFAULT '/',
                last_page VARCHAR(255) NOT NULL DEFAULT '/',
                first_seen_at DATETIME NOT NULL,
                last_seen_at DATETIME NOT NULL,
                page_views INT NOT NULL DEFAULT 0,
                add_to_cart_count INT NOT NULL DEFAULT 0,
                wishlist_add_count INT NOT NULL DEFAULT 0,
                custom_design_open_count INT NOT NULL DEFAULT 0,
                gift_card_view_count INT NOT NULL DEFAULT 0,
                checkout_started_count INT NOT NULL DEFAULT 0,
                completed_orders_count INT NOT NULL DEFAULT 0,
                is_bounce TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_visitor_id (visitor_id),
                KEY idx_last_seen_at (last_seen_at),
                KEY idx_country_name (country_name),
                KEY idx_traffic_source (traffic_source),
                KEY idx_browser_name (browser_name),
                KEY idx_device_type (device_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS website_visitor_events (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(128) NOT NULL,
                visitor_id VARCHAR(128) NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                page_path VARCHAR(255) NOT NULL DEFAULT '/',
                page_title VARCHAR(255) NOT NULL DEFAULT '',
                referrer_host VARCHAR(191) NOT NULL DEFAULT '',
                traffic_source VARCHAR(32) NOT NULL DEFAULT 'Direct',
                country_code VARCHAR(8) NOT NULL DEFAULT '',
                country_name VARCHAR(120) NOT NULL DEFAULT 'Unknown',
                browser_name VARCHAR(32) NOT NULL DEFAULT 'Other',
                device_type VARCHAR(16) NOT NULL DEFAULT 'Desktop',
                duration_seconds INT NOT NULL DEFAULT 0,
                metadata_json LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                KEY idx_session_id (session_id),
                KEY idx_visitor_id (visitor_id),
                KEY idx_event_type (event_type),
                KEY idx_page_path (page_path),
                KEY idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $ready = true;
    } catch (PDOException $exception) {
        $ready = false;
    }

    return $ready;
}

function girffonAdminAnalyticsSanitizeText($value, int $maxLength = 191): string
{
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/\s+/', ' ', $text);
    if (!is_string($text)) {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }

    return substr($text, 0, $maxLength);
}

function girffonAdminAnalyticsNormalizePagePath($value): string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        $raw = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    $path = parse_url($raw, PHP_URL_PATH);
    if (!is_string($path) || trim($path) === '') {
        $path = '/';
    }

    $path = '/' . ltrim($path, '/');
    return girffonAdminAnalyticsSanitizeText($path, 255);
}

function girffonAdminAnalyticsNormalizeHost($value): string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $host = parse_url($raw, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        $host = $raw;
    }

    $host = strtolower(trim($host));
    $host = preg_replace('/^www\./', '', $host);
    return girffonAdminAnalyticsSanitizeText($host, 191);
}

function girffonAdminAnalyticsCountryName(string $countryCode): string
{
    static $countries = [
        'IT' => 'Italy',
        'IR' => 'Iran',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'DE' => 'Germany',
        'FR' => 'France',
        'ES' => 'Spain',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'SE' => 'Sweden',
        'TR' => 'Turkey',
        'AE' => 'United Arab Emirates',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'CH' => 'Switzerland',
        'AT' => 'Austria',
    ];

    $countryCode = strtoupper(trim($countryCode));
    if ($countryCode === '') {
        return 'Unknown';
    }

    return $countries[$countryCode] ?? $countryCode;
}

function girffonAdminAnalyticsDetectCountry(array $payload = []): array
{
    $candidateCode = strtoupper(trim((string) ($payload['country_code'] ?? $_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['GEOIP_COUNTRY_CODE'] ?? '')));

    if ($candidateCode === '' || $candidateCode === 'XX') {
        $acceptLanguage = trim((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        if (preg_match('/[-_]([A-Za-z]{2})(?:,|;|$)/', $acceptLanguage, $matches)) {
            $candidateCode = strtoupper($matches[1]);
        }
    }

    $candidateCode = preg_match('/^[A-Z]{2}$/', $candidateCode) ? $candidateCode : '';
    $candidateName = girffonAdminAnalyticsSanitizeText($payload['country_name'] ?? '', 120);

    if ($candidateName === '') {
        $candidateName = girffonAdminAnalyticsCountryName($candidateCode);
    }

    return [
        'code' => $candidateCode,
        'name' => $candidateName !== '' ? $candidateName : 'Unknown',
    ];
}

function girffonAdminAnalyticsDetectBrowser(string $userAgent = ''): string
{
    $userAgent = strtolower(trim($userAgent !== '' ? $userAgent : (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    if ($userAgent === '') {
        return 'Other';
    }

    if (strpos($userAgent, 'edg/') !== false) {
        return 'Edge';
    }
    if (strpos($userAgent, 'firefox/') !== false) {
        return 'Firefox';
    }
    if (strpos($userAgent, 'chrome/') !== false || strpos($userAgent, 'crios/') !== false) {
        return 'Chrome';
    }
    if ((strpos($userAgent, 'safari/') !== false || strpos($userAgent, 'applewebkit/') !== false) && strpos($userAgent, 'chrome/') === false && strpos($userAgent, 'crios/') === false && strpos($userAgent, 'edg/') === false) {
        return 'Safari';
    }

    return 'Other';
}

function girffonAdminAnalyticsSignalString($value, int $maxLength = 255): string
{
    return girffonAdminAnalyticsSanitizeText((string) $value, $maxLength);
}

function girffonAdminAnalyticsSignalInt($value): int
{
    return max(0, (int) $value);
}

function girffonAdminAnalyticsSignalFloat($value): float
{
    return max(0, (float) $value);
}

function girffonAdminAnalyticsDeviceSignals(array $payload = []): array
{
    $metadata = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
    $clientHints = is_array($metadata['ua_client_hints'] ?? null) ? $metadata['ua_client_hints'] : [];
    $platform = girffonAdminAnalyticsSignalString(
        $payload['platform']
        ?? $metadata['platform']
        ?? $clientHints['platform']
        ?? $_SERVER['HTTP_SEC_CH_UA_PLATFORM']
        ?? $_SERVER['HTTP_USER_AGENT']
        ?? '',
        120
    );
    $pointerType = strtolower(girffonAdminAnalyticsSignalString($payload['pointer_type'] ?? $metadata['pointer_type'] ?? '', 32));
    $orientation = strtolower(girffonAdminAnalyticsSignalString($payload['orientation'] ?? $metadata['orientation'] ?? '', 32));
    $touchPoints = girffonAdminAnalyticsSignalInt($payload['touch_points'] ?? $metadata['touch_points'] ?? $_POST['touch_points'] ?? $_GET['touch_points'] ?? 0);
    $viewportWidth = girffonAdminAnalyticsSignalInt($payload['viewport_width'] ?? $metadata['viewport_width'] ?? $_POST['viewport_width'] ?? $_GET['viewport_width'] ?? 0);
    $screenWidth = girffonAdminAnalyticsSignalInt($payload['screen_width'] ?? $metadata['screen_width'] ?? 0);
    $devicePixelRatio = girffonAdminAnalyticsSignalFloat($payload['device_pixel_ratio'] ?? $metadata['device_pixel_ratio'] ?? 0);
    $clientHintsTextParts = [];
    foreach (['platform', 'model', 'architecture', 'bitness', 'platformVersion', 'uaFullVersion'] as $hintKey) {
        if (!empty($clientHints[$hintKey])) {
            $clientHintsTextParts[] = (string) $clientHints[$hintKey];
        }
    }
    foreach (['formFactors', 'brands'] as $hintListKey) {
        if (is_array($clientHints[$hintListKey] ?? null)) {
            foreach ($clientHints[$hintListKey] as $hintValue) {
                if ((string) $hintValue !== '') {
                    $clientHintsTextParts[] = (string) $hintValue;
                }
            }
        }
    }

    return [
        'platform' => $platform,
        'platform_lower' => strtolower($platform),
        'pointer_type' => $pointerType,
        'orientation' => $orientation,
        'touch_points' => $touchPoints,
        'viewport_width' => $viewportWidth,
        'screen_width' => $screenWidth,
        'short_size' => min(array_filter([$viewportWidth, $screenWidth]) ?: [0]),
        'long_size' => max($viewportWidth, $screenWidth),
        'device_pixel_ratio' => $devicePixelRatio,
        'client_hints_mobile' => filter_var($clientHints['mobile'] ?? ($_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? false), FILTER_VALIDATE_BOOLEAN),
        'client_hints_text' => strtolower(implode(' ', $clientHintsTextParts)),
    ];
}

function girffonAdminAnalyticsTabletModelMatch(string $value): bool
{
    return (bool) preg_match('/(lenovo[\s-]*tab|tb[-\s]?\w+|yt[-\s]?\w+|m10|m9|p11|p12|xiaomi[\s-]*pad|redmi[\s-]*pad|matepad|honor[\s-]*pad|oneplus[\s-]*pad|pixel[\s-]*tablet|sm-x\w+|galaxy[\s-]*tab|fire[\s-]*hd|kf[a-z]{2,4}\w*|nokia[\s-]*t\d+|tcl[\s-]*tab|alcatel[\s-]*(?:1t|3t|joy[\s-]*tab)|xperia[\s-]*tablet|zenpad|transformer|iconia|venue[\s-]*\d+|surface|mi[\s-]*pad)/i', $value);
}

function girffonAdminAnalyticsDetectDeviceDecision(string $userAgent = '', array $payload = []): array
{
    $userAgent = strtolower(trim($userAgent !== '' ? $userAgent : (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    $signals = girffonAdminAnalyticsDeviceSignals($payload);
    $touchPoints = $signals['touch_points'];
    $viewportWidth = $signals['viewport_width'];
    $screenWidth = $signals['screen_width'];
    $pointerType = $signals['pointer_type'];
    $shortSize = $signals['short_size'];
    $longSize = $signals['long_size'];
    $devicePixelRatio = $signals['device_pixel_ratio'];
    $platform = $signals['platform_lower'];
    $clientHintsText = $signals['client_hints_text'];
    $clientHintsMobile = !empty($signals['client_hints_mobile']);
    $isWindows = strpos($userAgent, 'windows nt') !== false || strpos($platform, 'win') !== false || strpos($clientHintsText, 'windows') !== false;
    $isAndroid = strpos($userAgent, 'android') !== false || strpos($platform, 'android') !== false || strpos($clientHintsText, 'android') !== false;
    $isIpadDesktopMode = (strpos($userAgent, 'macintosh') !== false || strpos($platform, 'mac') !== false) && $touchPoints > 1;
    $hasTabletKeyword = (bool) preg_match('/ipad|tablet|playbook|silk|kindle|nexus 7|nexus 9|nexus 10|sm-t|xoom/i', $userAgent)
        || girffonAdminAnalyticsTabletModelMatch($userAgent)
        || girffonAdminAnalyticsTabletModelMatch($clientHintsText);
    $androidWithoutMobile = $isAndroid && strpos($userAgent, 'mobile') === false;
    $explicitPhone = (bool) preg_match('/mobi|iphone|ipod|phone/i', $userAgent) || ($clientHintsMobile && !$isIpadDesktopMode);
    $surfaceTabletMode = $isWindows
        && $touchPoints > 1
        && $pointerType === 'coarse'
        && $shortSize >= 700
        && $shortSize <= 1100
        && $longSize <= 1500;
    $genericTouchTablet = $touchPoints > 1
        && $shortSize >= 600
        && $shortSize <= 1280
        && $longSize >= 800
        && !$explicitPhone
        && ($pointerType === 'coarse' || $devicePixelRatio >= 1.25)
        && (!$isWindows || $surfaceTabletMode);
    $screenSizedTablet = $touchPoints > 1
        && $shortSize >= 600
        && $shortSize <= 1600
        && !$explicitPhone
        && (!$isWindows || $surfaceTabletMode);

    if ($userAgent === '') {
        return [
            'device' => ($touchPoints > 1 && $viewportWidth >= 700 && $viewportWidth <= 1366) ? 'Tablet' : 'Desktop',
            'rule' => ($touchPoints > 1 && $viewportWidth >= 700 && $viewportWidth <= 1366) ? 'generic_touch_tablet' : 'desktop_fallback',
        ];
    }

    if ($isIpadDesktopMode) {
        return ['device' => 'Tablet', 'rule' => 'ipad_desktop_mode'];
    }
    if ($hasTabletKeyword) {
        return ['device' => 'Tablet', 'rule' => 'explicit_tablet_user_agent'];
    }
    if ($androidWithoutMobile) {
        return ['device' => 'Tablet', 'rule' => 'android_without_mobile'];
    }
    if ($surfaceTabletMode) {
        return ['device' => 'Tablet', 'rule' => 'generic_touch_tablet'];
    }
    if ($explicitPhone) {
        return ['device' => 'Mobile', 'rule' => 'mobile_user_agent'];
    }
    if ($genericTouchTablet) {
        return ['device' => 'Tablet', 'rule' => 'generic_touch_tablet'];
    }
    if ($screenSizedTablet) {
        return ['device' => 'Tablet', 'rule' => 'screen_sized_tablet'];
    }

    return ['device' => 'Desktop', 'rule' => 'desktop_fallback'];
}

function girffonAdminAnalyticsDetectDevice(string $userAgent = '', array $payload = []): string
{
    $decision = girffonAdminAnalyticsDetectDeviceDecision($userAgent, $payload);
    return (string) ($decision['device'] ?? 'Desktop');
}

function girffonAdminAnalyticsTrafficSource(string $referrerHost = '', string $explicitSource = ''): string
{
    $explicitSource = strtolower(trim($explicitSource));
    if ($explicitSource !== '') {
        return ucfirst($explicitSource);
    }

    $referrerHost = strtolower(trim($referrerHost));
    if ($referrerHost === '') {
        return 'Direct';
    }
    if (strpos($referrerHost, 'google.') !== false) {
        return 'Google';
    }
    if (strpos($referrerHost, 'instagram.') !== false) {
        return 'Instagram';
    }
    if (strpos($referrerHost, 'facebook.') !== false || strpos($referrerHost, 'fb.') !== false || strpos($referrerHost, 'm.facebook.') !== false) {
        return 'Facebook';
    }
    if (strpos($referrerHost, 'bing.') !== false) {
        return 'Bing';
    }

    return 'Other';
}

function girffonAdminAnalyticsTrafficSourceDecision(array $payload = [], string $serverUserAgent = '', string $serverReferer = ''): array
{
    $pageUrl = (string) ($payload['page_url'] ?? ($payload['meta']['page_url'] ?? ''));
    $documentReferrer = (string) ($payload['referrer'] ?? $serverReferer);
    $referrerHost = girffonAdminAnalyticsNormalizeHost($documentReferrer);
    $serverUserAgent = strtolower(trim((string) ($payload['user_agent'] ?? $serverUserAgent)));
    $utmSource = '';
    $fbclid = '';

    if ($pageUrl !== '') {
        $pageQuery = (string) parse_url($pageUrl, PHP_URL_QUERY);
        if ($pageQuery !== '') {
            parse_str($pageQuery, $pageParams);
            $utmSource = strtolower(trim((string) ($pageParams['utm_source'] ?? '')));
            $fbclid = trim((string) ($pageParams['fbclid'] ?? ''));
        }
    }

    if ($utmSource === '') {
        $utmSource = strtolower(trim((string) ($payload['utm_source'] ?? ($payload['meta']['utm_source'] ?? ''))));
    }
    if ($fbclid === '') {
        $fbclid = trim((string) ($payload['fbclid'] ?? ($payload['meta']['fbclid'] ?? '')));
    }

    $normalizedExplicitSource = strtolower(trim((string) ($payload['traffic_source'] ?? '')));
    if (in_array($normalizedExplicitSource, ['instagram', 'facebook', 'google', 'bing', 'direct', 'other'], true)) {
        $ruleMap = [
            'instagram' => 'utm_source_instagram',
            'facebook' => 'utm_source_facebook',
            'google' => 'google_referrer',
            'bing' => 'bing_referrer',
            'direct' => 'direct_no_referrer',
            'other' => 'other_referrer',
        ];

        return [
            'source' => ucfirst($normalizedExplicitSource),
            'rule' => (string) ($payload['matched_source_rule'] ?? ($payload['meta']['matched_source_rule'] ?? ($ruleMap[$normalizedExplicitSource] ?? 'other_referrer'))),
            'document_referrer' => $documentReferrer,
            'utm_source' => $utmSource,
            'fbclid' => $fbclid,
        ];
    }

    $isInternalReferrer = in_array($referrerHost, ['girffon.shop', 'www.girffon.shop', 'localhost'], true);
    if ($isInternalReferrer) {
        $referrerHost = '';
    }

    $instagramReferrer = (bool) preg_match('/(^|\.)((l|lm)\.)?instagram\.com$/i', $referrerHost);
    $facebookReferrer = (bool) preg_match('/(^|\.)((m|l|lm|web)\.)?facebook\.com$/i', $referrerHost)
        || (bool) preg_match('/(^|\.)fb\.com$/i', $referrerHost)
        || (bool) preg_match('/(^|\.)m\.me$/i', $referrerHost);
    $instagramUserAgent = strpos($serverUserAgent, 'instagram') !== false;
    $facebookUserAgent = (bool) preg_match('/fban|fbav|fb_iab|fb4a|fbios|facebook|messenger/i', $serverUserAgent);

    if ($utmSource === 'instagram') {
        return ['source' => 'Instagram', 'rule' => 'utm_source_instagram', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($utmSource === 'facebook' || $utmSource === 'messenger') {
        return ['source' => 'Facebook', 'rule' => 'utm_source_facebook', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($instagramReferrer) {
        return ['source' => 'Instagram', 'rule' => 'instagram_referrer', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($facebookReferrer) {
        return ['source' => 'Facebook', 'rule' => 'facebook_referrer', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($fbclid !== '') {
        return ['source' => 'Facebook', 'rule' => 'fbclid_parameter', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($referrerHost === '' && $instagramUserAgent) {
        return ['source' => 'Instagram', 'rule' => 'instagram_in_app_browser', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($referrerHost === '' && $facebookUserAgent) {
        return ['source' => 'Facebook', 'rule' => 'facebook_in_app_browser', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if (strpos($referrerHost, 'google.') !== false) {
        return ['source' => 'Google', 'rule' => 'google_referrer', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if (strpos($referrerHost, 'bing.') !== false) {
        return ['source' => 'Bing', 'rule' => 'bing_referrer', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }
    if ($referrerHost === '') {
        return ['source' => 'Direct', 'rule' => 'direct_no_referrer', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
    }

    return ['source' => 'Other', 'rule' => 'other_referrer', 'document_referrer' => $documentReferrer, 'utm_source' => $utmSource, 'fbclid' => $fbclid];
}

function girffonAdminFormatDurationLabel(int $seconds): string
{
    $seconds = max(0, $seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;

    if ($hours > 0) {
        return sprintf('%dh %02dm', $hours, $minutes);
    }
    if ($minutes > 0) {
        return sprintf('%dm %02ds', $minutes, $remainingSeconds);
    }

    return sprintf('%ds', $remainingSeconds);
}

function girffonAdminTrackWebsiteVisitor(PDO $pdo, array $payload): bool
{
    $eventType = strtolower(girffonAdminAnalyticsSanitizeText($payload['event_type'] ?? 'page_view', 64));
    if (!preg_match('/^[a-z0-9_]+$/', $eventType)) {
        $eventType = 'page_view';
    }

    $debugState = [
        'event_type' => $eventType,
        'page_url' => (string) ($payload['page_url'] ?? $payload['page_path'] ?? ''),
        'action_result' => 'tracking_started',
        'tracked' => false,
        'database_insert_result' => 'not_started',
    ];

    if (!girffonAdminEnsureWebsiteAnalyticsTables($pdo)) {
        $debugState['action_result'] = 'tracking_skipped_tables_unavailable';
        $debugState['database_insert_result'] = 'tables_unavailable';
        girffonAdminAnalyticsSetLastTrackDebug($debugState);
        return false;
    }

    $visitorId = girffonAdminAnalyticsSanitizeText($payload['visitor_id'] ?? '', 128);
    $sessionId = girffonAdminAnalyticsSanitizeText($payload['session_id'] ?? '', 128);
    if ($visitorId === '' || $sessionId === '') {
        $debugState['action_result'] = 'tracking_skipped_missing_identity';
        $debugState['database_insert_result'] = 'missing_visitor_or_session';
        girffonAdminAnalyticsSetLastTrackDebug($debugState);
        return false;
    }

    $pagePath = girffonAdminAnalyticsNormalizePagePath($payload['page_path'] ?? '');
    $pageTitle = girffonAdminAnalyticsSanitizeText($payload['page_title'] ?? '', 255);
    $sourceDecision = girffonAdminAnalyticsTrafficSourceDecision($payload, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), (string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $referrerHost = girffonAdminAnalyticsNormalizeHost((string) ($sourceDecision['document_referrer'] ?? ''));
    $country = girffonAdminAnalyticsDetectCountry($payload);
    $browserName = girffonAdminAnalyticsSanitizeText($payload['browser_name'] ?? girffonAdminAnalyticsDetectBrowser((string) ($payload['user_agent'] ?? '')), 32) ?: 'Other';
    $deviceType = girffonAdminAnalyticsSanitizeText($payload['device_type'] ?? '', 16);
    if ($deviceType === '') {
        $deviceType = girffonAdminAnalyticsDetectDevice((string) ($payload['user_agent'] ?? ''), $payload);
    }
    $deviceType = $deviceType ?: 'Desktop';
    $trafficSource = (string) ($sourceDecision['source'] ?? 'Direct');
    $ipAddress = girffonAdminAnalyticsSanitizeText($_SERVER['REMOTE_ADDR'] ?? '', 45);
    $metadata = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
    $durationSeconds = max(0, min(86400, (int) ($payload['duration_seconds'] ?? ($metadata['duration_seconds'] ?? 0))));
    $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $createdAt = gmdate('Y-m-d H:i:s');

    try {
        $insertEvent = $pdo->prepare(
            'INSERT INTO website_visitor_events (
                session_id, visitor_id, event_type, page_path, page_title, referrer_host, traffic_source, country_code, country_name, browser_name, device_type, duration_seconds, metadata_json, created_at
            ) VALUES (
                :session_id, :visitor_id, :event_type, :page_path, :page_title, :referrer_host, :traffic_source, :country_code, :country_name, :browser_name, :device_type, :duration_seconds, :metadata_json, :created_at
            )'
        );
        $insertEvent->execute([
            ':session_id' => $sessionId,
            ':visitor_id' => $visitorId,
            ':event_type' => $eventType,
            ':page_path' => $pagePath,
            ':page_title' => $pageTitle,
            ':referrer_host' => $referrerHost,
            ':traffic_source' => $trafficSource,
            ':country_code' => $country['code'],
            ':country_name' => $country['name'],
            ':browser_name' => $browserName,
            ':device_type' => $deviceType,
            ':duration_seconds' => $durationSeconds,
            ':metadata_json' => $metadataJson,
            ':created_at' => $createdAt,
        ]);
        $debugState['database_insert_result'] = 'event_inserted';

        $readSession = $pdo->prepare('SELECT * FROM website_visitor_sessions WHERE session_id = :session_id LIMIT 1');
        $readSession->execute([':session_id' => $sessionId]);
        $sessionRow = $readSession->fetch(PDO::FETCH_ASSOC) ?: null;

        $pageViews = (int) ($sessionRow['page_views'] ?? 0) + ($eventType === 'page_view' ? 1 : 0);
        $addToCartCount = (int) ($sessionRow['add_to_cart_count'] ?? 0) + ($eventType === 'add_to_cart' ? 1 : 0);
        $wishlistAddCount = (int) ($sessionRow['wishlist_add_count'] ?? 0) + ($eventType === 'wishlist_add' ? 1 : 0);
        $customDesignOpenCount = (int) ($sessionRow['custom_design_open_count'] ?? 0) + ($eventType === 'custom_design_open' ? 1 : 0);
        $giftCardViewCount = (int) ($sessionRow['gift_card_view_count'] ?? 0) + ($eventType === 'gift_card_view' ? 1 : 0);
        $checkoutStartedCount = (int) ($sessionRow['checkout_started_count'] ?? 0) + ($eventType === 'checkout_started' ? 1 : 0);
        $completedOrdersCount = (int) ($sessionRow['completed_orders_count'] ?? 0) + ($eventType === 'completed_order' ? 1 : 0);
        $isBounce = $pageViews <= 1 ? 1 : 0;

        if ($sessionRow) {
            $updateSession = $pdo->prepare(
                'UPDATE website_visitor_sessions
                 SET visitor_id = :visitor_id,
                     ip_address = :ip_address,
                     country_code = CASE WHEN (country_code = "" OR country_name = "Unknown") AND :country_code <> "" THEN :country_code ELSE country_code END,
                     country_name = CASE WHEN country_name = "Unknown" AND :country_name <> "Unknown" THEN :country_name ELSE country_name END,
                     browser_name = CASE WHEN browser_name = "Other" AND :browser_name <> "Other" THEN :browser_name ELSE browser_name END,
                     device_type = CASE WHEN device_type = "Desktop" AND :device_type <> "Desktop" THEN :device_type ELSE device_type END,
                     traffic_source = CASE WHEN traffic_source = "Direct" AND :traffic_source <> "Direct" THEN :traffic_source ELSE traffic_source END,
                     last_page = :last_page,
                     last_seen_at = :last_seen_at,
                     page_views = :page_views,
                     add_to_cart_count = :add_to_cart_count,
                     wishlist_add_count = :wishlist_add_count,
                     custom_design_open_count = :custom_design_open_count,
                     gift_card_view_count = :gift_card_view_count,
                     checkout_started_count = :checkout_started_count,
                     completed_orders_count = :completed_orders_count,
                     is_bounce = :is_bounce
                 WHERE session_id = :session_id'
            );
            $updateSession->execute([
                ':visitor_id' => $visitorId,
                ':ip_address' => $ipAddress,
                ':country_code' => $country['code'],
                ':country_name' => $country['name'],
                ':browser_name' => $browserName,
                ':device_type' => $deviceType,
                ':traffic_source' => $trafficSource,
                ':last_page' => $pagePath,
                ':last_seen_at' => $createdAt,
                ':page_views' => $pageViews,
                ':add_to_cart_count' => $addToCartCount,
                ':wishlist_add_count' => $wishlistAddCount,
                ':custom_design_open_count' => $customDesignOpenCount,
                ':gift_card_view_count' => $giftCardViewCount,
                ':checkout_started_count' => $checkoutStartedCount,
                ':completed_orders_count' => $completedOrdersCount,
                ':is_bounce' => $isBounce,
                ':session_id' => $sessionId,
            ]);
            $debugState['database_insert_result'] = 'event_inserted_session_updated';
        } else {
            $insertSession = $pdo->prepare(
                'INSERT INTO website_visitor_sessions (
                    session_id, visitor_id, ip_address, country_code, country_name, browser_name, device_type, traffic_source, landing_page, first_page, last_page, first_seen_at, last_seen_at, page_views, add_to_cart_count, wishlist_add_count, custom_design_open_count, gift_card_view_count, checkout_started_count, completed_orders_count, is_bounce
                ) VALUES (
                    :session_id, :visitor_id, :ip_address, :country_code, :country_name, :browser_name, :device_type, :traffic_source, :landing_page, :first_page, :last_page, :first_seen_at, :last_seen_at, :page_views, :add_to_cart_count, :wishlist_add_count, :custom_design_open_count, :gift_card_view_count, :checkout_started_count, :completed_orders_count, :is_bounce
                )'
            );
            $insertSession->execute([
                ':session_id' => $sessionId,
                ':visitor_id' => $visitorId,
                ':ip_address' => $ipAddress,
                ':country_code' => $country['code'],
                ':country_name' => $country['name'],
                ':browser_name' => $browserName,
                ':device_type' => $deviceType,
                ':traffic_source' => $trafficSource,
                ':landing_page' => $pagePath,
                ':first_page' => $pagePath,
                ':last_page' => $pagePath,
                ':first_seen_at' => $createdAt,
                ':last_seen_at' => $createdAt,
                ':page_views' => $pageViews,
                ':add_to_cart_count' => $addToCartCount,
                ':wishlist_add_count' => $wishlistAddCount,
                ':custom_design_open_count' => $customDesignOpenCount,
                ':gift_card_view_count' => $giftCardViewCount,
                ':checkout_started_count' => $checkoutStartedCount,
                ':completed_orders_count' => $completedOrdersCount,
                ':is_bounce' => $isBounce,
            ]);
            $debugState['database_insert_result'] = 'event_inserted_session_created';
        }
    } catch (PDOException $exception) {
        $debugState['action_result'] = 'tracking_failed_database_exception';
        $debugState['database_insert_result'] = 'database_exception';
        $debugState['error_message'] = $exception->getMessage();
        girffonAdminAnalyticsSetLastTrackDebug($debugState);
        return false;
    }

    $debugState['action_result'] = 'tracking_completed';
    $debugState['tracked'] = true;
    girffonAdminAnalyticsSetLastTrackDebug($debugState);
    return true;
}

function girffonAdminWebsiteAnalyticsBreakdown(PDO $pdo, string $sql, array $params = [], int $limit = 10): array
{
    try {
        $statement = $pdo->prepare($sql . ' LIMIT ' . max(1, $limit));
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminWebsiteAnalyticsCount(PDO $pdo, string $sql, array $params = []): int
{
    try {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminWebsiteAnalyticsAverage(PDO $pdo, string $sql, array $params = []): float
{
    try {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return (float) $statement->fetchColumn();
    } catch (PDOException $exception) {
        return 0.0;
    }
}

function girffonAdminWebsiteAnalyticsFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminVisitorAnalyticsDefault(): array
{
    return [
        'range_key' => '30days',
        'range_label' => 'Last 30 Days',
        'range_start' => '',
        'range_end' => '',
        'generated_at' => gmdate('c'),
        'online' => 0,
        'today' => 0,
        'week' => 0,
        'month' => 0,
        'total' => 0,
        'visitors' => 0,
        'new' => 0,
        'returning' => 0,
        'average_session_duration_seconds' => 0,
        'average_session_duration_label' => '0s',
        'average_time_per_page_seconds' => 0,
        'average_time_per_page_label' => '0s',
        'bounce_rate' => 0.0,
        'conversion_rate' => 0.0,
        'add_to_cart' => 0,
        'wishlist_adds' => 0,
        'custom_design_opens' => 0,
        'gift_card_views' => 0,
        'checkout_started' => 0,
        'completed_orders' => 0,
        'abandoned_carts' => 0,
        'countries' => [],
        'pages' => [],
        'landing_pages' => [],
        'exit_pages' => [],
        'page_durations' => [],
        'sources' => [],
        'referrers' => [],
        'referrer_hosts' => [],
        'browsers' => [],
        'devices' => [],
        'keywords' => [],
    ];
}

function girffonAdminResolveVisitorAnalyticsRange(array $options = []): array
{
    $romeNow = girffonAdminDashboardRomeNow();
    $rangeKey = strtolower(trim((string) ($options['range'] ?? '30days')));
    $start = null;
    $end = $romeNow->modify('+1 day')->setTime(0, 0, 0);
    $label = 'Last 30 Days';

    switch ($rangeKey) {
        case 'today':
            $start = $romeNow->setTime(0, 0, 0);
            $end = $start->modify('+1 day');
            $label = 'Today';
            break;
        case '7days':
            $start = $romeNow->modify('-6 days')->setTime(0, 0, 0);
            $label = 'Last 7 Days';
            break;
        case '30days':
            $start = $romeNow->modify('-29 days')->setTime(0, 0, 0);
            $label = 'Last 30 Days';
            break;
        case 'this_year':
            $start = $romeNow->setDate((int) $romeNow->format('Y'), 1, 1)->setTime(0, 0, 0);
            $end = $start->modify('+1 year');
            $label = 'This Year';
            break;
        case 'custom':
            $startDate = trim((string) ($options['start_date'] ?? ''));
            $endDate = trim((string) ($options['end_date'] ?? ''));
            try {
                $start = $startDate !== ''
                    ? (new DateTimeImmutable($startDate, girffonAdminDashboardRomeTimezone()))->setTime(0, 0, 0)
                    : $romeNow->modify('-29 days')->setTime(0, 0, 0);
                $end = $endDate !== ''
                    ? (new DateTimeImmutable($endDate, girffonAdminDashboardRomeTimezone()))->modify('+1 day')->setTime(0, 0, 0)
                    : $romeNow->modify('+1 day')->setTime(0, 0, 0);
            } catch (Throwable $exception) {
                $rangeKey = '30days';
                $start = $romeNow->modify('-29 days')->setTime(0, 0, 0);
                $end = $romeNow->modify('+1 day')->setTime(0, 0, 0);
            }
            if ($end <= $start) {
                $end = $start->modify('+1 day');
            }
            $label = $start->format('d M Y') . ' - ' . $end->modify('-1 day')->format('d M Y');
            break;
        default:
            $rangeKey = '30days';
            $start = $romeNow->modify('-29 days')->setTime(0, 0, 0);
            $label = 'Last 30 Days';
            break;
    }

    if (!$start instanceof DateTimeImmutable) {
        $start = $romeNow->modify('-29 days')->setTime(0, 0, 0);
    }

    return [
        'key' => $rangeKey,
        'label' => $label,
        'start_rome' => $start,
        'end_rome' => $end,
        'start_utc' => $start->setTimezone(girffonAdminDashboardUtcTimezone())->format('Y-m-d H:i:s'),
        'end_utc' => $end->setTimezone(girffonAdminDashboardUtcTimezone())->format('Y-m-d H:i:s'),
    ];
}

function girffonAdminNormalizeVisitorAnalyticsRows(array $rows, array $allLabels = []): array
{
    $counts = [];
    foreach ($rows as $row) {
        $counts[(string) ($row['label'] ?? '')] = (int) ($row['count'] ?? 0);
    }

    if (!$allLabels) {
        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'label' => (string) ($row['label'] ?? ''),
                'count' => (int) ($row['count'] ?? 0),
            ];
        }
        return $normalized;
    }

    $normalized = [];
    foreach ($allLabels as $label) {
        $normalized[] = [
            'label' => $label,
            'count' => (int) ($counts[$label] ?? 0),
        ];
    }
    return $normalized;
}

function girffonAdminDecodeAnalyticsMetadata($value): array
{
    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function girffonAdminKeywordLabel($value): string
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/\s+/', ' ', $value);
    return is_string($value) ? $value : '';
}

function girffonAdminTopKeywordRows(array $eventRows, int $limit = 10): array
{
    $counts = [];
    foreach ($eventRows as $row) {
        $metadata = girffonAdminDecodeAnalyticsMetadata($row['metadata_json'] ?? null);
        $keyword = girffonAdminKeywordLabel($metadata['search_keyword'] ?? '');
        if ($keyword === '') {
            continue;
        }

        $counts[$keyword] = (int) ($counts[$keyword] ?? 0) + 1;
    }

    arsort($counts);
    $rows = [];
    foreach (array_slice($counts, 0, max(1, $limit), true) as $keyword => $count) {
        $rows[] = [
            'label' => $keyword,
            'count' => (int) $count,
        ];
    }
    return $rows;
}

function girffonAdminFetchVisitorAnalytics(PDO $pdo, array $options = []): array
{
    $analytics = girffonAdminVisitorAnalyticsDefault();

    if (!girffonAdminEnsureWebsiteAnalyticsTables($pdo)) {
        return $analytics;
    }

    $range = girffonAdminResolveVisitorAnalyticsRange($options);
    $analytics['range_key'] = $range['key'];
    $analytics['range_label'] = $range['label'];
    $analytics['range_start'] = $range['start_rome']->format('Y-m-d');
    $analytics['range_end'] = $range['end_rome']->modify('-1 day')->format('Y-m-d');
    $analytics['generated_at'] = gmdate('c');

    $utcNow = new DateTimeImmutable('now', girffonAdminDashboardUtcTimezone());
    $onlineCutoff = $utcNow->modify('-5 minutes')->format('Y-m-d H:i:s');
    $abandonedCutoff = min($utcNow->modify('-1 hour')->format('Y-m-d H:i:s'), $range['end_utc']);
    $rangeParams = [
        ':range_start' => $range['start_utc'],
        ':range_end' => $range['end_utc'],
    ];

    $todayRange = girffonAdminDashboardRomePeriodRange('daily');
    $monthRange = girffonAdminDashboardRomePeriodRange('monthly');
    $romeNow = girffonAdminDashboardRomeNow();
    $weekStartRome = $romeNow->modify('monday this week')->setTime(0, 0, 0);
    $weekRange = girffonAdminDashboardRomeUtcRange($weekStartRome, $weekStartRome->modify('+1 week'));

    $analytics['online'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT visitor_id) FROM website_visitor_sessions WHERE last_seen_at >= :cutoff',
        [':cutoff' => $onlineCutoff]
    );
    $analytics['today'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT visitor_id) FROM website_visitor_sessions WHERE last_seen_at >= :start_at AND last_seen_at < :end_at',
        [':start_at' => $todayRange['start'], ':end_at' => $todayRange['end']]
    );
    $analytics['week'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT visitor_id) FROM website_visitor_sessions WHERE last_seen_at >= :start_at AND last_seen_at < :end_at',
        [':start_at' => $weekRange['start'], ':end_at' => $weekRange['end']]
    );
    $analytics['month'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT visitor_id) FROM website_visitor_sessions WHERE last_seen_at >= :start_at AND last_seen_at < :end_at',
        [':start_at' => $monthRange['start'], ':end_at' => $monthRange['end']]
    );
    $analytics['total'] = girffonAdminWebsiteAnalyticsCount($pdo, 'SELECT COUNT(DISTINCT visitor_id) FROM website_visitor_sessions');

    $analytics['visitors'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT visitor_id)
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start AND first_seen_at < :range_end',
        $rangeParams
    );
    $analytics['new'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT visitor_id)
         FROM website_visitor_sessions
         WHERE first_seen_at >= :range_start AND first_seen_at < :range_end',
        $rangeParams
    );
    $analytics['returning'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(DISTINCT current_sessions.visitor_id)
         FROM website_visitor_sessions current_sessions
         WHERE current_sessions.last_seen_at >= :range_start
           AND current_sessions.first_seen_at < :range_end
           AND EXISTS (
             SELECT 1
             FROM website_visitor_sessions previous_sessions
             WHERE previous_sessions.visitor_id = current_sessions.visitor_id
               AND previous_sessions.first_seen_at < :range_start
             LIMIT 1
           )',
        $rangeParams
    );

    $averageDurationSeconds = (int) round(girffonAdminWebsiteAnalyticsAverage(
        $pdo,
        'SELECT AVG(GREATEST(TIMESTAMPDIFF(SECOND, first_seen_at, last_seen_at), 0))
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start AND first_seen_at < :range_end',
        $rangeParams
    ));
    $analytics['average_session_duration_seconds'] = $averageDurationSeconds;
    $analytics['average_session_duration_label'] = girffonAdminFormatDurationLabel($averageDurationSeconds);

    $averageTimePerPageSeconds = (int) round(girffonAdminWebsiteAnalyticsAverage(
        $pdo,
        'SELECT AVG(duration_seconds)
         FROM website_visitor_events
         WHERE event_type = "page_exit"
           AND duration_seconds > 0
           AND created_at >= :range_start
           AND created_at < :range_end',
        $rangeParams
    ));
    $analytics['average_time_per_page_seconds'] = $averageTimePerPageSeconds;
    $analytics['average_time_per_page_label'] = girffonAdminFormatDurationLabel($averageTimePerPageSeconds);

    $bounceRate = girffonAdminWebsiteAnalyticsAverage(
        $pdo,
        'SELECT AVG(CASE WHEN page_views <= 1 THEN 100 ELSE 0 END)
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start AND first_seen_at < :range_end',
        $rangeParams
    );
    $analytics['bounce_rate'] = round($bounceRate, 1);

    foreach ([
        'add_to_cart' => 'add_to_cart',
        'wishlist_adds' => 'wishlist_add',
        'custom_design_opens' => 'custom_design_open',
        'gift_card_views' => 'gift_card_view',
        'checkout_started' => 'checkout_started',
        'completed_orders' => 'completed_order',
    ] as $resultKey => $eventType) {
        $analytics[$resultKey] = girffonAdminWebsiteAnalyticsCount(
            $pdo,
            'SELECT COUNT(*) FROM website_visitor_events WHERE event_type = :event_type AND created_at >= :range_start AND created_at < :range_end',
            array_merge($rangeParams, [':event_type' => $eventType])
        );
    }

    $analytics['conversion_rate'] = $analytics['visitors'] > 0
        ? round(((float) $analytics['completed_orders'] / (float) $analytics['visitors']) * 100, 1)
        : 0.0;

    $analytics['abandoned_carts'] = girffonAdminWebsiteAnalyticsCount(
        $pdo,
        'SELECT COUNT(*)
         FROM website_visitor_sessions
         WHERE completed_orders_count = 0
           AND (add_to_cart_count > 0 OR checkout_started_count > 0)
           AND last_seen_at >= :range_start
           AND last_seen_at < :abandoned_cutoff',
        [':range_start' => $range['start_utc'], ':abandoned_cutoff' => $abandonedCutoff]
    );

    $analytics['countries'] = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT country_name AS label, COUNT(*) AS count
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start
           AND first_seen_at < :range_end
           AND country_name <> "" AND country_name <> "Unknown"
         GROUP BY country_name
         ORDER BY count DESC',
        $rangeParams,
        6
    );

    $analytics['pages'] = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT page_path AS label, COUNT(*) AS count
         FROM website_visitor_events
         WHERE event_type = "page_view"
           AND created_at >= :range_start
           AND created_at < :range_end
         GROUP BY page_path
         ORDER BY count DESC',
        $rangeParams,
        10
    );

    $analytics['landing_pages'] = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT landing_page AS label, COUNT(*) AS count
         FROM website_visitor_sessions
         WHERE first_seen_at >= :range_start
           AND first_seen_at < :range_end
         GROUP BY landing_page
         ORDER BY count DESC',
        $rangeParams,
        10
    );

    $analytics['exit_pages'] = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT last_page AS label, COUNT(*) AS count
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start
           AND last_seen_at < :range_end
         GROUP BY last_page
         ORDER BY count DESC',
        $rangeParams,
        10
    );

    $pageDurationRows = girffonAdminWebsiteAnalyticsFetchAll(
        $pdo,
        'SELECT page_path AS label, AVG(duration_seconds) AS average_seconds, COUNT(*) AS count
         FROM website_visitor_events
         WHERE event_type = "page_exit"
           AND duration_seconds > 0
           AND created_at >= :range_start
           AND created_at < :range_end
         GROUP BY page_path
         ORDER BY average_seconds DESC, count DESC
         LIMIT 10',
        $rangeParams
    );
    foreach ($pageDurationRows as $pageDurationRow) {
        $seconds = (int) round((float) ($pageDurationRow['average_seconds'] ?? 0));
        $analytics['page_durations'][] = [
            'label' => (string) ($pageDurationRow['label'] ?? '/'),
            'count' => (int) ($pageDurationRow['count'] ?? 0),
            'average_seconds' => $seconds,
            'average_label' => girffonAdminFormatDurationLabel($seconds),
        ];
    }

    $sourceRows = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT traffic_source AS label, COUNT(*) AS count
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start
           AND first_seen_at < :range_end
         GROUP BY traffic_source
         ORDER BY count DESC',
        $rangeParams,
        12
    );
    $analytics['sources'] = girffonAdminNormalizeVisitorAnalyticsRows($sourceRows, ['Google', 'Direct', 'Instagram', 'Facebook', 'Bing', 'Other']);
    $analytics['referrers'] = $analytics['sources'];

    $analytics['referrer_hosts'] = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT referrer_host AS label, COUNT(*) AS count
         FROM website_visitor_events
         WHERE event_type = "page_view"
           AND created_at >= :range_start
           AND created_at < :range_end
           AND referrer_host <> ""
         GROUP BY referrer_host
         ORDER BY count DESC',
        $rangeParams,
        8
    );

    $browserRows = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT browser_name AS label, COUNT(*) AS count
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start
           AND first_seen_at < :range_end
         GROUP BY browser_name
         ORDER BY count DESC',
        $rangeParams,
        8
    );
    $analytics['browsers'] = girffonAdminNormalizeVisitorAnalyticsRows($browserRows, ['Chrome', 'Edge', 'Firefox', 'Safari']);

    $deviceRows = girffonAdminWebsiteAnalyticsBreakdown(
        $pdo,
        'SELECT device_type AS label, COUNT(*) AS count
         FROM website_visitor_sessions
         WHERE last_seen_at >= :range_start
           AND first_seen_at < :range_end
         GROUP BY device_type
         ORDER BY count DESC',
        $rangeParams,
        6
    );
    $analytics['devices'] = girffonAdminNormalizeVisitorAnalyticsRows($deviceRows, ['Desktop', 'Mobile', 'Tablet']);

    $keywordRows = girffonAdminWebsiteAnalyticsFetchAll(
        $pdo,
        'SELECT metadata_json
         FROM website_visitor_events
         WHERE event_type = "page_view"
           AND created_at >= :range_start
           AND created_at < :range_end
           AND metadata_json IS NOT NULL',
        $rangeParams
    );
    $analytics['keywords'] = girffonAdminTopKeywordRows($keywordRows, 10);

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

function girffonAdminCountSoldGiftCards(PDO $pdo): int
{
    try {
        girffonGiftCardEnsureSchema($pdo);
        girffonGiftCardUpdateExpiredStatus($pdo);

        $statement = $pdo->query(
            "SELECT COUNT(*)
             FROM gift_cards
             WHERE order_id IS NOT NULL
               AND status IN ('active', 'used', 'expired')"
        );

        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (Throwable $exception) {
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

function girffonAdminDashboardSaneYearRange(): array
{
    return [2025, 2030];
}

function girffonAdminDashboardIsSaneYear(int $year): bool
{
    [$minimumYear, $maximumYear] = girffonAdminDashboardSaneYearRange();
    return $year >= $minimumYear && $year <= $maximumYear;
}

function girffonAdminDashboardFetchCustomDesignRowsForRange(PDO $pdo, string $startUtc, string $endUtc): array
{
    if (!girffonAdminEnsureCustomDesignOrderTables($pdo)) {
        return [];
    }

    try {
        $statement = $pdo->prepare(
            "SELECT created_at, paid_at, payment_status, design_payload_json
             FROM custom_design_orders
             WHERE (
                 created_at >= :start_at AND created_at < :end_at
             ) OR (
                 payment_status = 'paid' AND paid_at IS NOT NULL AND paid_at >= :start_at AND paid_at < :end_at
             )"
        );
        $statement->execute([
            ':start_at' => $startUtc,
            ':end_at' => $endUtc,
        ]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminDashboardCustomDesignOrderTotal(array $row): float
{
    $payload = girffonAdminCustomDesignDecodeJson($row['design_payload_json'] ?? null);
    return (float) girffonAdminCustomDesignValueAt($payload, ['product.order_total', 'snapshot.orderTotal', 'order_total', 'snapshot.total'], 0);
}
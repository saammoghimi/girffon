<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/custom-design-orders-data.php";

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

function girffonAdminFetchVisitorAnalytics(): array
{
    $entries = girffonAdminDashboardReadJsonLog('admin-dashboard-visits.json');
    $romeNow = girffonAdminDashboardRomeNow();
    $today = $romeNow->format('Y-m-d');
    $month = $romeNow->format('Y-m');
    $year = $romeNow->format('Y');

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

        $romeCreatedAt = girffonAdminDashboardFormatRome($createdAt, 'Y-m-d H:i:s');
        if ($romeCreatedAt === '-') {
            continue;
        }

        if (strpos($romeCreatedAt, $today) === 0) {
            $analytics['today']++;
        }
        if (strpos($romeCreatedAt, $month) === 0) {
            $analytics['month']++;
        }
        if (strpos($romeCreatedAt, $year) === 0) {
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
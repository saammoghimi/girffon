<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionCookieName = session_name();
    if ($sessionCookieName !== '' && isset($_COOKIE[$sessionCookieName])) {
        session_start();
    }
}

require_once __DIR__ . '/../admin/homepage-data.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function girffonHomepageJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonHomepageIsAuthenticated(): bool
{
    $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0);
    return $userId > 0;
}

function girffonHomepageAudienceAllowed(array $item, bool $isAuthenticated): bool
{
    $audienceScope = strtolower(trim((string) ($item['audience_scope'] ?? 'all_visitors')));
    if ($audienceScope === 'logged_in') {
        return $isAuthenticated;
    }

    return true;
}

function girffonHomepageDecodeRelatedScope($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_array($value)) {
        return $value;
    }

    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $raw;
}

function girffonHomepageSerializePublicItem(array $item): array
{
    return [
        'id' => (int) ($item['id'] ?? 0),
        'item_type' => (string) ($item['item_type'] ?? ''),
        'title' => (string) ($item['title'] ?? ''),
        'message' => (string) ($item['message'] ?? ''),
        'cta_label' => (string) ($item['cta_label'] ?? ''),
        'cta_url' => (string) ($item['cta_url'] ?? ''),
        'severity' => (string) ($item['severity'] ?? 'info'),
        'event_key' => (string) ($item['event_key'] ?? 'none'),
        'display_mode' => (string) ($item['display_mode'] ?? 'promotion_only'),
        'display_percent' => ($item['display_percent'] ?? null) !== null && $item['display_percent'] !== ''
            ? round((float) $item['display_percent'], 2)
            : null,
        'coupon_code' => (string) ($item['coupon_code'] ?? ''),
        'related_product_scope' => girffonHomepageDecodeRelatedScope($item['related_product_scope'] ?? null),
        'target_surface' => (string) ($item['target_surface'] ?? 'above_hero'),
        'audience_scope' => (string) ($item['audience_scope'] ?? 'all_visitors'),
        'priority' => (int) ($item['priority'] ?? 0),
        'start_at' => ($item['start_at'] ?? null) !== '' ? ($item['start_at'] ?? null) : null,
        'end_at' => ($item['end_at'] ?? null) !== '' ? ($item['end_at'] ?? null) : null,
        'published_at' => ($item['published_at'] ?? null) !== '' ? ($item['published_at'] ?? null) : null,
    ];
}

function girffonHomepagePartitionItems(array $items): array
{
    $payload = [
        'announcementBars' => [],
        'campaigns' => [],
        'technicalAlerts' => [],
        'appAnnouncements' => [],
    ];

    foreach ($items as $item) {
        $serialized = girffonHomepageSerializePublicItem($item);
        $itemType = strtolower(trim((string) ($item['item_type'] ?? '')));

        switch ($itemType) {
            case 'announcement_bar':
                $payload['announcementBars'][] = $serialized;
                break;
            case 'homepage_campaign':
                $payload['campaigns'][] = $serialized;
                break;
            case 'technical_alert':
                $payload['technicalAlerts'][] = $serialized;
                break;
            case 'app_announcement':
                $payload['appAnnouncements'][] = $serialized;
                break;
            default:
                break;
        }
    }

    return $payload;
}

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        girffonHomepageJsonResponse(500, [
            'ok' => false,
            'error' => 'homepage_config_unavailable',
        ]);
    }

    $siteState = girffonAdminHomepageFetchSiteState($pdo);
    $maintenanceState = (string) ($siteState['maintenance_public_state'] ?? 'inactive');
    $maintenanceActive = $maintenanceState === 'active';
    $isAuthenticated = girffonHomepageIsAuthenticated();

    $response = [
        'ok' => true,
        'site' => [
            'status' => $maintenanceActive
                ? 'maintenance'
                : ((string) ($siteState['site_status'] ?? 'normal') === 'notice' ? 'notice' : 'normal'),
            'maintenance' => [
                'enabled' => $maintenanceActive,
                'state' => $maintenanceState,
                'title' => $maintenanceActive ? (string) ($siteState['maintenance_title'] ?? '') : '',
                'message' => $maintenanceActive ? (string) ($siteState['maintenance_message'] ?? '') : '',
                'eta' => $maintenanceActive
                    ? ((string) ($siteState['maintenance_eta'] ?? '') !== '' ? (string) $siteState['maintenance_eta'] : null)
                    : null,
            ],
        ],
        'announcementBars' => [],
        'campaigns' => [],
        'technicalAlerts' => [],
        'appAnnouncements' => [],
    ];

    if ($maintenanceActive) {
        girffonHomepageJsonResponse(200, $response);
    }

    $items = girffonAdminHomepageListContentItems($pdo, [
        'public_state' => 'active',
        'include_archived' => false,
    ]);

    $items = array_values(array_filter($items, static function (array $item) use ($isAuthenticated): bool {
        $workflowStatus = strtolower(trim((string) ($item['workflow_status'] ?? 'draft')));
        $isEnabled = !empty($item['is_enabled']);
        $publicState = strtolower(trim((string) ($item['public_state'] ?? 'inactive')));

        if ($workflowStatus !== 'published' || !$isEnabled || $publicState !== 'active') {
            return false;
        }

        return girffonHomepageAudienceAllowed($item, $isAuthenticated);
    }));

    $partitioned = girffonHomepagePartitionItems($items);
    $response['announcementBars'] = $partitioned['announcementBars'];
    $response['campaigns'] = $partitioned['campaigns'];
    $response['technicalAlerts'] = $partitioned['technicalAlerts'];
    $response['appAnnouncements'] = $partitioned['appAnnouncements'];

    girffonHomepageJsonResponse(200, $response);
} catch (Throwable $throwable) {
    girffonHomepageJsonResponse(500, [
        'ok' => false,
        'error' => 'homepage_config_unavailable',
    ]);
}
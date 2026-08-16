<?php
require_once __DIR__ . '/backend/admin/session.php';
require_once __DIR__ . '/backend/admin/homepage-data.php';
require_once __DIR__ . '/backend/utils/csrf.php';

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminHomepageCsrf = girffonCsrfToken();

function girffonAdminHomepagePageUrl(array $params = []): string
{
    $base = 'admin-homepage.php';
    if (!$params) {
        return $base;
    }

    return $base . '?' . http_build_query($params);
}

function girffonAdminHomepageRedirect(array $params = [], string $fragment = ''): void
{
    $location = girffonAdminHomepagePageUrl($params);
    if ($fragment !== '') {
        $location .= '#' . ltrim($fragment, '#');
    }

    header('Location: ' . $location);
    exit;
}

function girffonAdminHomepageEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function girffonAdminHomepageItemTypeLabels(): array
{
    return [
        'announcement_bar' => 'Announcement Bar',
        'homepage_campaign' => 'Homepage Campaign',
        'technical_alert' => 'Technical Alert',
        'app_announcement' => 'App Announcement',
    ];
}

function girffonAdminHomepagePublicStateLabels(): array
{
    return [
        'inactive' => 'Inactive',
        'scheduled' => 'Scheduled',
        'active' => 'Active',
        'expired' => 'Expired',
    ];
}

function girffonAdminHomepageContentSectionMap(): array
{
    return [
        'announcement_bar' => [
            'label' => 'Announcement Bar',
            'anchor' => 'section-announcement-bar',
            'form_id' => 'adminHomepageAnnouncementForm',
        ],
        'homepage_campaign' => [
            'label' => 'Homepage Campaign',
            'anchor' => 'section-homepage-campaign',
            'form_id' => 'adminHomepageCampaignForm',
        ],
        'technical_alert' => [
            'label' => 'Technical Alerts',
            'anchor' => 'section-technical-alerts',
            'form_id' => 'adminHomepageTechnicalAlertForm',
        ],
        'app_announcement' => [
            'label' => 'App Announcement',
            'anchor' => 'section-app-announcement',
            'form_id' => 'adminHomepageAppAnnouncementForm',
        ],
    ];
}

function girffonAdminHomepageSectionConfig(string $itemType): array
{
    $sections = girffonAdminHomepageContentSectionMap();
    return $sections[$itemType] ?? $sections['announcement_bar'];
}

function girffonAdminHomepageDefaultContentForm(string $itemType): array
{
    $defaults = [
        'item_id' => 0,
        'item_type' => $itemType,
        'title' => '',
        'message' => '',
        'cta_label' => '',
        'cta_url' => '',
        'severity' => 'info',
        'event_key' => 'none',
        'display_mode' => 'promotion_only',
        'display_percent_option' => '',
        'display_percent_custom' => '',
        'coupon_code' => '',
        'target_surface' => 'above_hero',
        'audience_scope' => 'all_visitors',
        'start_at_local' => '',
        'end_at_local' => '',
        'auto_expire' => '1',
        'priority' => '50',
        'internal_notes' => '',
        'alert_type' => 'custom',
        'platform' => 'coming_soon',
        'scope_reference' => '',
        'published_at' => '',
        'workflow_status' => 'draft',
        'is_enabled' => '0',
        'public_state' => 'inactive',
    ];

    if ($itemType === 'homepage_campaign') {
        $defaults['display_percent_option'] = '20';
    }

    return $defaults;
}

function girffonAdminHomepageDecodeMetadata($value): array
{
    if (is_array($value)) {
        return $value;
    }

    $raw = trim((string) $value);
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function girffonAdminHomepageContentFormFromItem(array $item): array
{
    $form = girffonAdminHomepageDefaultContentForm((string) ($item['item_type'] ?? 'announcement_bar'));
    $metadata = girffonAdminHomepageDecodeMetadata($item['related_product_scope'] ?? null);
    $displayPercent = $item['display_percent'] ?? null;
    $displayPercentText = $displayPercent !== null && $displayPercent !== '' ? rtrim(rtrim(number_format((float) $displayPercent, 2, '.', ''), '0'), '.') : '';
    $quickOptions = ['5', '10', '15', '20', '25', '30'];

    $form['item_id'] = (int) ($item['id'] ?? 0);
    $form['title'] = (string) ($item['title'] ?? '');
    $form['message'] = (string) ($item['message'] ?? '');
    $form['cta_label'] = (string) ($item['cta_label'] ?? '');
    $form['cta_url'] = (string) ($item['cta_url'] ?? '');
    $form['severity'] = (string) ($item['severity'] ?? 'info');
    $form['event_key'] = (string) ($item['event_key'] ?? 'none');
    $form['display_mode'] = (string) ($item['display_mode'] ?? 'promotion_only');
    $form['display_percent_option'] = in_array($displayPercentText, $quickOptions, true) ? $displayPercentText : ($displayPercentText !== '' ? 'custom' : '');
    $form['display_percent_custom'] = in_array($displayPercentText, $quickOptions, true) ? '' : $displayPercentText;
    $form['coupon_code'] = (string) ($item['coupon_code'] ?? '');
    $form['target_surface'] = (string) ($item['target_surface'] ?? 'above_hero');
    $form['audience_scope'] = (string) ($item['audience_scope'] ?? 'all_visitors');
    $form['start_at_local'] = girffonAdminHomepageUtcToRomeInputValue($item['start_at'] ?? null);
    $form['end_at_local'] = girffonAdminHomepageUtcToRomeInputValue($item['end_at'] ?? null);
    $form['auto_expire'] = !empty($item['auto_expire']) ? '1' : '0';
    $form['priority'] = (string) ($item['priority'] ?? 50);
    $form['internal_notes'] = (string) ($item['internal_notes'] ?? '');
    $form['alert_type'] = (string) ($metadata['alert_type'] ?? 'custom');
    $form['platform'] = (string) ($metadata['platform'] ?? 'coming_soon');
    $form['scope_reference'] = (string) ($metadata['scope_reference'] ?? '');
    $form['published_at'] = (string) ($item['published_at'] ?? '');
    $form['workflow_status'] = (string) ($item['workflow_status'] ?? 'draft');
    $form['is_enabled'] = !empty($item['is_enabled']) ? '1' : '0';
    $form['public_state'] = (string) ($item['public_state'] ?? 'inactive');

    return $form;
}

function girffonAdminHomepageContentFormFromPost(string $itemType, array $post): array
{
    $form = girffonAdminHomepageDefaultContentForm($itemType);
    $form['item_id'] = max(0, (int) ($post['item_id'] ?? 0));
    $form['title'] = trim((string) ($post['title'] ?? ''));
    $form['message'] = trim((string) ($post['message'] ?? ''));
    $form['cta_label'] = trim((string) ($post['cta_label'] ?? ''));
    $form['cta_url'] = trim((string) ($post['cta_url'] ?? ''));
    $form['severity'] = strtolower(trim((string) ($post['severity'] ?? 'info')));
    $form['event_key'] = strtolower(trim((string) ($post['event_key'] ?? 'none')));
    $form['display_mode'] = strtolower(trim((string) ($post['display_mode'] ?? 'promotion_only')));
    $form['display_percent_option'] = trim((string) ($post['display_percent_option'] ?? ''));
    $form['display_percent_custom'] = trim((string) ($post['display_percent_custom'] ?? ''));
    $form['coupon_code'] = trim((string) ($post['coupon_code'] ?? ''));
    $form['target_surface'] = strtolower(trim((string) ($post['target_surface'] ?? 'above_hero')));
    $form['audience_scope'] = strtolower(trim((string) ($post['audience_scope'] ?? 'all_visitors')));
    $form['start_at_local'] = trim((string) ($post['start_at_local'] ?? ''));
    $form['end_at_local'] = trim((string) ($post['end_at_local'] ?? ''));
    $form['auto_expire'] = array_key_exists('auto_expire', $post) ? '1' : '0';
    $form['priority'] = trim((string) ($post['priority'] ?? '50'));
    $form['internal_notes'] = trim((string) ($post['internal_notes'] ?? ''));
    $form['alert_type'] = strtolower(trim((string) ($post['alert_type'] ?? 'custom')));
    $form['platform'] = strtolower(trim((string) ($post['platform'] ?? 'coming_soon')));
    $form['scope_reference'] = trim((string) ($post['scope_reference'] ?? ''));
    $form['is_enabled'] = array_key_exists('is_enabled', $post) ? '1' : '0';
    return $form;
}

function girffonAdminHomepagePrepareContentInput(string $itemType, array $formState): array
{
    $displayPercentOption = trim((string) ($formState['display_percent_option'] ?? ''));
    $displayPercent = null;
    if ($displayPercentOption === 'custom') {
        $displayPercent = trim((string) ($formState['display_percent_custom'] ?? ''));
    } elseif ($displayPercentOption !== '') {
        $displayPercent = $displayPercentOption;
    }

    $metadata = [];
    $scopeReference = trim((string) ($formState['scope_reference'] ?? ''));
    if ($scopeReference !== '') {
        $metadata['scope_reference'] = $scopeReference;
    }
    if ($itemType === 'technical_alert') {
        $metadata['alert_type'] = strtolower(trim((string) ($formState['alert_type'] ?? 'custom')));
    }
    if ($itemType === 'app_announcement') {
        $metadata['platform'] = strtolower(trim((string) ($formState['platform'] ?? 'coming_soon')));
    }

    $title = trim((string) ($formState['title'] ?? ''));
    if ($itemType === 'technical_alert' && $title === '') {
        $title = ucwords(str_replace('_', ' ', (string) ($metadata['alert_type'] ?? 'Technical Alert')));
    }
    if ($itemType === 'app_announcement' && $title === '') {
        $title = ucwords(str_replace('_', ' ', (string) ($metadata['platform'] ?? 'App Announcement')));
    }
    if ($itemType === 'announcement_bar' && $title === '') {
        $title = 'Announcement Bar';
    }

    return [
        'item_type' => $itemType,
        'title' => $title,
        'message' => (string) ($formState['message'] ?? ''),
        'cta_label' => (string) ($formState['cta_label'] ?? ''),
        'cta_url' => (string) ($formState['cta_url'] ?? ''),
        'severity' => (string) ($formState['severity'] ?? 'info'),
        'event_key' => $itemType === 'homepage_campaign' ? (string) ($formState['event_key'] ?? 'none') : 'none',
        'display_mode' => $itemType === 'homepage_campaign' ? (string) ($formState['display_mode'] ?? 'promotion_only') : 'promotion_only',
        'display_percent' => $itemType === 'homepage_campaign' ? $displayPercent : null,
        'coupon_code' => $itemType === 'homepage_campaign' ? (string) ($formState['coupon_code'] ?? '') : '',
        'related_product_scope' => $metadata ?: null,
        'target_surface' => (string) ($formState['target_surface'] ?? 'above_hero'),
        'audience_scope' => (string) ($formState['audience_scope'] ?? 'all_visitors'),
        'start_at' => girffonAdminHomepageRomeInputToUtcValue($formState['start_at_local'] ?? ''),
        'end_at' => girffonAdminHomepageRomeInputToUtcValue($formState['end_at_local'] ?? ''),
        'auto_expire' => !empty($formState['auto_expire']),
        'priority' => (string) ($formState['priority'] ?? '50'),
        'internal_notes' => (string) ($formState['internal_notes'] ?? ''),
        'is_enabled' => !empty($formState['is_enabled']),
    ];
}

function girffonAdminHomepageRenderHiddenFields(array $values, array $exclude = []): string
{
    $html = '';
    foreach ($values as $key => $value) {
        if (in_array((string) $key, $exclude, true)) {
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $html .= '<input type="hidden" name="' . girffonAdminHomepageEscape((string) $key) . '[]" value="' . girffonAdminHomepageEscape((string) $nestedValue) . '">';
            }
            continue;
        }

        $html .= '<input type="hidden" name="' . girffonAdminHomepageEscape((string) $key) . '" value="' . girffonAdminHomepageEscape((string) $value) . '">';
    }

    return $html;
}

function girffonAdminHomepageFilterItems(array $items, string $filter): array
{
    return array_values(array_filter($items, static function (array $item) use ($filter): bool {
        $workflowStatus = strtolower((string) ($item['workflow_status'] ?? 'draft'));
        $publicState = strtolower((string) ($item['public_state'] ?? 'inactive'));

        return match ($filter) {
            'scheduled' => $publicState === 'scheduled',
            'active' => $publicState === 'active',
            'expired' => $publicState === 'expired',
            'archived' => $workflowStatus === 'archived',
            default => $workflowStatus === 'draft' || ($publicState === 'inactive' && $workflowStatus !== 'archived'),
        };
    }));
}

  function girffonAdminHomepagePublicStateClass(string $publicState): string
  {
    return match ($publicState) {
      'active' => ' is-active',
      'scheduled' => ' is-scheduled',
      'expired' => ' is-expired',
      default => '',
    };
  }

  function girffonAdminHomepageExcerpt(string $value, int $limit = 92): string
  {
    $normalized = trim($value);
    if ($normalized === '') {
      return '';
    }

    if (function_exists('mb_strimwidth')) {
      return mb_strimwidth($normalized, 0, $limit, '...');
    }

    return strlen($normalized) > $limit ? substr($normalized, 0, max(0, $limit - 3)) . '...' : $normalized;
  }

$itemTypeLabels = girffonAdminHomepageItemTypeLabels();
$publicStateLabels = girffonAdminHomepagePublicStateLabels();
$contentSections = girffonAdminHomepageContentSectionMap();

$adminHomepageStatusMessage = trim((string) ($_GET['status'] ?? ''));
$adminHomepageErrorMessage = trim((string) ($_GET['error'] ?? ''));
$adminHomepageNoticeMessage = trim((string) ($_GET['notice'] ?? ''));
$adminHomepageConflictState = null;

$adminHomepageSiteState = girffonAdminHomepageFetchSiteState($pdo);
$adminHomepageSiteForm = [
    'site_status' => (string) ($adminHomepageSiteState['site_status'] ?? 'normal'),
    'maintenance_enabled' => !empty($adminHomepageSiteState['maintenance_enabled']) ? '1' : '0',
    'maintenance_title' => (string) ($adminHomepageSiteState['maintenance_title'] ?? ''),
    'maintenance_message' => (string) ($adminHomepageSiteState['maintenance_message'] ?? ''),
    'maintenance_eta' => (string) ($adminHomepageSiteState['maintenance_eta'] ?? ''),
    'maintenance_starts_at_local' => girffonAdminHomepageUtcToRomeInputValue($adminHomepageSiteState['maintenance_starts_at'] ?? null),
    'maintenance_ends_at_local' => girffonAdminHomepageUtcToRomeInputValue($adminHomepageSiteState['maintenance_ends_at'] ?? null),
    'admin_bypass_enabled' => !empty($adminHomepageSiteState['admin_bypass_enabled']) ? '1' : '0',
];

$adminHomepageEditItemId = max(0, (int) ($_GET['edit_item'] ?? 0));
$adminHomepageHistoryItemId = max(0, (int) ($_GET['history_item'] ?? 0));
$adminHomepagePreviewItemId = max(0, (int) ($_GET['preview_item'] ?? 0));
$adminHomepageActiveFilter = strtolower(trim((string) ($_GET['filter'] ?? 'draft')));
if (!in_array($adminHomepageActiveFilter, ['draft', 'scheduled', 'active', 'expired', 'archived'], true)) {
    $adminHomepageActiveFilter = 'draft';
}

$adminHomepageEditedItem = $adminHomepageEditItemId > 0 ? girffonAdminHomepageFetchContentItemById($pdo, $adminHomepageEditItemId) : null;
$adminHomepageHistoryItem = $adminHomepageHistoryItemId > 0 ? girffonAdminHomepageFetchContentItemById($pdo, $adminHomepageHistoryItemId) : null;
$adminHomepagePreviewItem = $adminHomepagePreviewItemId > 0 ? girffonAdminHomepageFetchContentItemById($pdo, $adminHomepagePreviewItemId) : null;

$adminHomepageFormStates = [];
foreach (array_keys($contentSections) as $sectionType) {
    $adminHomepageFormStates[$sectionType] = girffonAdminHomepageDefaultContentForm($sectionType);
}
if ($adminHomepageEditedItem) {
    $adminHomepageFormStates[(string) $adminHomepageEditedItem['item_type']] = girffonAdminHomepageContentFormFromItem($adminHomepageEditedItem);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
            throw new RuntimeException('Security token mismatch. Please refresh the page and try again.');
        }

        $homepageAction = trim((string) ($_POST['homepage_action'] ?? ''));
        $homepageSectionType = strtolower(trim((string) ($_POST['item_type'] ?? 'announcement_bar')));
        $homepageSectionConfig = girffonAdminHomepageSectionConfig($homepageSectionType);
        $homepageSectionAnchor = (string) ($homepageSectionConfig['anchor'] ?? 'section-announcement-bar');

        if ($homepageAction === 'save_site_state') {
            $siteInput = [
                'site_status' => $_POST['site_status'] ?? 'normal',
                'maintenance_enabled' => array_key_exists('maintenance_enabled', $_POST),
                'maintenance_title' => $_POST['maintenance_title'] ?? '',
                'maintenance_message' => $_POST['maintenance_message'] ?? '',
                'maintenance_eta' => $_POST['maintenance_eta'] ?? '',
                'maintenance_starts_at' => girffonAdminHomepageRomeInputToUtcValue($_POST['maintenance_starts_at_local'] ?? ''),
                'maintenance_ends_at' => girffonAdminHomepageRomeInputToUtcValue($_POST['maintenance_ends_at_local'] ?? ''),
                'admin_bypass_enabled' => array_key_exists('admin_bypass_enabled', $_POST),
            ];

            $adminHomepageSiteState = girffonAdminHomepageUpdateSiteState($pdo, $siteInput, $adminCurrentId, $adminCurrentUsername);
            girffonAdminHomepageRedirect(['status' => 'Homepage site mode updated successfully.'], 'section-site-mode');
        }

        if (in_array($homepageAction, ['save_draft', 'publish_now', 'schedule_publish', 'unpublish', 'archive', 'clone'], true)) {
            $homepageItemId = max(0, (int) ($_POST['item_id'] ?? 0));
            $forceConflict = !empty($_POST['force_conflict']);

            if (isset($adminHomepageFormStates[$homepageSectionType])) {
                $adminHomepageFormStates[$homepageSectionType] = girffonAdminHomepageContentFormFromPost($homepageSectionType, $_POST);
            }

            if ($homepageAction === 'clone') {
                if ($homepageItemId <= 0) {
                    throw new InvalidArgumentException('Select an existing Homepage item to clone.');
                }
                $clonedItem = girffonAdminHomepageCloneContentItem($pdo, $homepageItemId, $adminCurrentId, $adminCurrentUsername);
                $clonedSection = girffonAdminHomepageSectionConfig((string) ($clonedItem['item_type'] ?? 'announcement_bar'));
                girffonAdminHomepageRedirect([
                    'status' => 'Homepage content cloned as a new draft.',
                    'edit_item' => (int) ($clonedItem['id'] ?? 0),
                    'filter' => 'draft',
                ], (string) ($clonedSection['anchor'] ?? 'section-announcement-bar'));
            }

            if ($homepageAction === 'unpublish') {
                if ($homepageItemId <= 0) {
                    throw new InvalidArgumentException('Select an existing Homepage item to unpublish.');
                }
                $unpublishedItem = girffonAdminHomepageUnpublishContentItem($pdo, $homepageItemId, $adminCurrentId, $adminCurrentUsername);
                $targetSection = girffonAdminHomepageSectionConfig((string) ($unpublishedItem['item_type'] ?? 'announcement_bar'));
                girffonAdminHomepageRedirect([
                    'status' => 'Homepage content unpublished successfully.',
                    'edit_item' => (int) ($unpublishedItem['id'] ?? 0),
                    'filter' => 'draft',
                ], (string) ($targetSection['anchor'] ?? 'section-announcement-bar'));
            }

            if ($homepageAction === 'archive') {
                if ($homepageItemId <= 0) {
                    throw new InvalidArgumentException('Select an existing Homepage item to archive.');
                }
                $archivedItem = girffonAdminHomepageArchiveContentItem($pdo, $homepageItemId, $adminCurrentId, $adminCurrentUsername);
                girffonAdminHomepageRedirect([
                    'status' => 'Homepage content archived successfully.',
                    'filter' => 'archived',
                    'history_item' => (int) ($archivedItem['id'] ?? 0),
                ], 'section-schedule-history');
            }

            $contentInput = girffonAdminHomepagePrepareContentInput($homepageSectionType, $adminHomepageFormStates[$homepageSectionType]);

            if ($homepageAction === 'save_draft') {
                if ($homepageItemId > 0) {
                    $updateResult = girffonAdminHomepageUpdateContentItem($pdo, $homepageItemId, $contentInput, $adminCurrentId, $adminCurrentUsername);
                    girffonAdminHomepageRedirect([
                        'status' => 'Homepage draft updated successfully.',
                        'edit_item' => (int) ($updateResult['item']['id'] ?? 0),
                        'filter' => 'draft',
                    ], $homepageSectionAnchor);
                }

                $draftResult = girffonAdminHomepageCreateDraft($pdo, $contentInput, $adminCurrentId, $adminCurrentUsername);
                $draftItem = $draftResult['item'];
                $draftConflicts = $draftResult['conflict_result'] ?? [];
                $redirectParams = [
                    'status' => 'Homepage draft created successfully.',
                    'edit_item' => (int) ($draftItem['id'] ?? 0),
                    'filter' => 'draft',
                ];
                if (!empty($draftConflicts['has_conflicts'])) {
                    $redirectParams['notice'] = 'Draft saved with a soft conflict warning. Review overlapping scheduled or active items before publishing.';
                }
                girffonAdminHomepageRedirect($redirectParams, $homepageSectionAnchor);
            }

            if ($homepageAction === 'publish_now') {
                if ($homepageItemId <= 0) {
                    $draftResult = girffonAdminHomepageCreateDraft($pdo, $contentInput, $adminCurrentId, $adminCurrentUsername);
                    $homepageItemId = (int) (($draftResult['item']['id'] ?? 0));
                    $adminHomepageFormStates[$homepageSectionType] = girffonAdminHomepageContentFormFromItem($draftResult['item']);
                }

                $publishResult = girffonAdminHomepagePublishNow($pdo, $homepageItemId, $contentInput, $adminCurrentId, $adminCurrentUsername, $forceConflict);
                if (!empty($publishResult['conflict_result']['has_conflicts']) && !$forceConflict) {
                    $adminHomepageConflictState = [
                        'action' => 'publish_now',
                        'label' => 'Publish conflict detected',
                        'message' => 'Another active or scheduled Homepage item overlaps this publish window. Review the conflicts below and confirm if you still want to publish.',
                        'item_type' => $homepageSectionType,
                        'payload' => array_merge($_POST, ['item_id' => (int) (($publishResult['item']['id'] ?? $homepageItemId))]),
                        'conflict_result' => $publishResult['conflict_result'],
                        'anchor' => $homepageSectionAnchor,
                    ];
                    $adminHomepageNoticeMessage = 'Draft preserved. Publishing requires explicit confirmation because a conflict was detected.';
                } else {
                    girffonAdminHomepageRedirect([
                        'status' => 'Homepage content published successfully.',
                        'edit_item' => (int) ($publishResult['item']['id'] ?? 0),
                        'filter' => 'active',
                    ], $homepageSectionAnchor);
                }
            }

            if ($homepageAction === 'schedule_publish') {
                if ($homepageItemId <= 0) {
                    $draftResult = girffonAdminHomepageCreateDraft($pdo, $contentInput, $adminCurrentId, $adminCurrentUsername);
                    $homepageItemId = (int) (($draftResult['item']['id'] ?? 0));
                    $adminHomepageFormStates[$homepageSectionType] = girffonAdminHomepageContentFormFromItem($draftResult['item']);
                }

                $scheduleResult = girffonAdminHomepageSchedulePublish($pdo, $homepageItemId, $contentInput, $adminCurrentId, $adminCurrentUsername, $forceConflict);
                if (!empty($scheduleResult['conflict_result']['has_conflicts']) && !$forceConflict) {
                    $adminHomepageConflictState = [
                        'action' => 'schedule_publish',
                        'label' => 'Schedule conflict detected',
                        'message' => 'Another active or scheduled Homepage item overlaps this schedule. Review the conflicts below and confirm if you still want to schedule it.',
                        'item_type' => $homepageSectionType,
                        'payload' => array_merge($_POST, ['item_id' => (int) (($scheduleResult['item']['id'] ?? $homepageItemId))]),
                        'conflict_result' => $scheduleResult['conflict_result'],
                        'anchor' => $homepageSectionAnchor,
                    ];
                    $adminHomepageNoticeMessage = 'Draft preserved. Scheduling requires explicit confirmation because a conflict was detected.';
                } else {
                    girffonAdminHomepageRedirect([
                        'status' => 'Homepage content scheduled successfully.',
                        'edit_item' => (int) ($scheduleResult['item']['id'] ?? 0),
                        'filter' => 'scheduled',
                    ], $homepageSectionAnchor);
                }
            }
        }
    } catch (Throwable $throwable) {
        $adminHomepageErrorMessage = $throwable->getMessage();
        if (($_POST['homepage_action'] ?? '') === 'save_site_state') {
            $adminHomepageSiteForm = [
                'site_status' => trim((string) ($_POST['site_status'] ?? 'normal')),
                'maintenance_enabled' => array_key_exists('maintenance_enabled', $_POST) ? '1' : '0',
                'maintenance_title' => trim((string) ($_POST['maintenance_title'] ?? '')),
                'maintenance_message' => trim((string) ($_POST['maintenance_message'] ?? '')),
                'maintenance_eta' => trim((string) ($_POST['maintenance_eta'] ?? '')),
                'maintenance_starts_at_local' => trim((string) ($_POST['maintenance_starts_at_local'] ?? '')),
                'maintenance_ends_at_local' => trim((string) ($_POST['maintenance_ends_at_local'] ?? '')),
                'admin_bypass_enabled' => array_key_exists('admin_bypass_enabled', $_POST) ? '1' : '0',
            ];
        }
    }
}

$adminHomepageSiteState = girffonAdminHomepageFetchSiteState($pdo);
$adminHomepageAllItems = girffonAdminHomepageListContentItems($pdo, ['include_archived' => true]);
$adminHomepageFilteredItems = girffonAdminHomepageFilterItems($adminHomepageAllItems, $adminHomepageActiveFilter);
$adminHomepageCounts = [
    'draft' => count(girffonAdminHomepageFilterItems($adminHomepageAllItems, 'draft')),
    'scheduled' => count(girffonAdminHomepageFilterItems($adminHomepageAllItems, 'scheduled')),
    'active' => count(girffonAdminHomepageFilterItems($adminHomepageAllItems, 'active')),
    'expired' => count(girffonAdminHomepageFilterItems($adminHomepageAllItems, 'expired')),
    'archived' => count(girffonAdminHomepageFilterItems($adminHomepageAllItems, 'archived')),
];

if ($adminHomepageHistoryItemId > 0) {
    $adminHomepageHistoryItem = girffonAdminHomepageFetchContentItemById($pdo, $adminHomepageHistoryItemId);
}
$adminHomepageHistoryRows = $adminHomepageHistoryItem ? girffonAdminHomepageFetchHistory($pdo, (int) ($adminHomepageHistoryItem['id'] ?? 0), 40) : [];

$adminHomepageActiveEditorType = 'announcement_bar';
if (!empty($_POST['item_type']) && isset($contentSections[(string) $_POST['item_type']])) {
    $adminHomepageActiveEditorType = (string) $_POST['item_type'];
} elseif ($adminHomepageEditedItem) {
    $adminHomepageActiveEditorType = (string) ($adminHomepageEditedItem['item_type'] ?? 'announcement_bar');
} elseif (!empty($_GET['editor']) && isset($contentSections[(string) $_GET['editor']])) {
    $adminHomepageActiveEditorType = (string) $_GET['editor'];
}

if (!$adminHomepagePreviewItem && $adminHomepageEditedItem) {
    $adminHomepagePreviewItem = $adminHomepageEditedItem;
}

$adminHomepagePreviewForm = $adminHomepageFormStates[$adminHomepageActiveEditorType] ?? girffonAdminHomepageDefaultContentForm($adminHomepageActiveEditorType);
$adminHomepagePreviewLabel = $itemTypeLabels[$adminHomepageActiveEditorType] ?? 'Homepage Content';
$adminHomepagePreviewSurface = (string) ($adminHomepagePreviewForm['target_surface'] ?? 'above_hero');
$adminHomepagePreviewState = (string) ($adminHomepagePreviewForm['public_state'] ?? 'inactive');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Homepage</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    body.admin-page {
      overflow-x: hidden;
    }

    .admin-homepage-anchor-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 24px;
    }

    .admin-homepage-anchor-nav a {
      text-decoration: none;
    }

    .admin-homepage-summary-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 16px;
    }

    .admin-homepage-mode-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .admin-homepage-radio-card {
      position: relative;
      border: 1px solid rgba(199, 165, 75, 0.18);
      border-radius: 18px;
      padding: 16px 16px 16px 46px;
      background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(250,244,232,0.9));
    }

    .admin-homepage-radio-card input {
      position: absolute;
      left: 16px;
      top: 18px;
    }

    .admin-homepage-state-chip,
    .admin-homepage-warning-chip,
    .admin-homepage-preview-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 999px;
      padding: 8px 12px;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .admin-homepage-state-chip {
      background: rgba(199, 165, 75, 0.14);
      color: #5d4722;
    }

    .admin-homepage-state-chip.is-active,
    .admin-homepage-preview-badge.is-active {
      background: rgba(47, 125, 74, 0.12);
      color: #24613a;
    }

    .admin-homepage-state-chip.is-scheduled,
    .admin-homepage-preview-badge.is-scheduled {
      background: rgba(183, 121, 31, 0.14);
      color: #8d5e18;
    }

    .admin-homepage-state-chip.is-expired,
    .admin-homepage-preview-badge.is-expired {
      background: rgba(159, 47, 47, 0.12);
      color: #8c2d2d;
    }

    .admin-homepage-warning-chip {
      background: rgba(159, 47, 47, 0.1);
      color: #8f3c2d;
    }

    .admin-homepage-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.05fr) minmax(300px, 0.95fr);
      gap: 22px;
    }

    .admin-homepage-grid .admin-field-wide {
      grid-column: 1 / -1;
    }

    .admin-homepage-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .admin-homepage-inline-options {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 8px;
    }

    .admin-homepage-inline-options label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(255, 250, 244, 0.84);
      border: 1px solid rgba(55, 43, 30, 0.1);
      font-weight: 600;
      color: #433526;
    }

    .admin-homepage-toggle-row {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      align-items: center;
    }

    .admin-homepage-toggle-row label,
    .admin-homepage-field label,
    .admin-homepage-form-grid label {
      display: grid;
      gap: 8px;
      color: #433526;
      font-weight: 600;
    }

    .admin-homepage-form-grid input,
    .admin-homepage-form-grid select,
    .admin-homepage-form-grid textarea,
    .admin-homepage-site-grid input,
    .admin-homepage-site-grid textarea,
    .admin-homepage-site-grid select {
      width: 100%;
      border: 1px solid rgba(55, 43, 30, 0.12);
      border-radius: 14px;
      padding: 12px 14px;
      background: rgba(255, 250, 244, 0.92);
      font: inherit;
      color: #1f1a14;
    }

    .admin-homepage-form-grid textarea,
    .admin-homepage-site-grid textarea {
      min-height: 116px;
      resize: vertical;
    }

    .admin-homepage-site-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
      margin-top: 18px;
    }

    .admin-homepage-site-grid .admin-field-wide {
      grid-column: 1 / -1;
    }

    .admin-homepage-note {
      padding: 14px 16px;
      border-radius: 16px;
      background: rgba(199, 165, 75, 0.12);
      border: 1px solid rgba(199, 165, 75, 0.24);
      color: #5e4724;
    }

    .admin-homepage-preview-shell {
      display: grid;
      gap: 18px;
    }

    .admin-homepage-preview-actions,
    .admin-homepage-preview-widths {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .admin-homepage-preview-widths .admin-chip {
      border: 1px solid rgba(199, 165, 75, 0.24);
    }

    .admin-homepage-preview-widths .admin-chip.is-active {
      background: linear-gradient(180deg, #fff7e2 0%, #f1dfb0 100%);
      border-color: rgba(169, 131, 34, 0.42);
    }

    .admin-homepage-preview-frame-wrap {
      border-radius: 24px;
      padding: 18px;
      background: linear-gradient(180deg, rgba(43,36,27,0.06), rgba(43,36,27,0.02));
      border: 1px solid rgba(199, 165, 75, 0.18);
      overflow: auto;
    }

    .admin-homepage-preview-frame {
      width: min(100%, var(--preview-width, 1440px));
      min-height: 520px;
      margin: 0 auto;
      border-radius: 24px;
      overflow: hidden;
      background: linear-gradient(180deg, #fffdfa 0%, #f3ebdb 100%);
      box-shadow: 0 18px 40px rgba(99, 78, 31, 0.08);
      border: 1px solid rgba(199, 165, 75, 0.18);
    }

    .admin-homepage-preview-topbar {
      padding: 12px 18px;
      background: #1f1a14;
      color: #fff7e6;
      font-size: 0.84rem;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .admin-homepage-preview-hero {
      min-height: 170px;
      padding: 24px;
      background: linear-gradient(135deg, rgba(199, 165, 75, 0.18), rgba(255,255,255,0.7));
      display: grid;
      gap: 12px;
      align-content: center;
    }

    .admin-homepage-preview-hero h3 {
      margin: 0;
      font-family: Georgia, serif;
      font-size: clamp(1.6rem, 3vw, 2.4rem);
    }

    .admin-homepage-preview-content {
      padding: 24px;
      display: grid;
      gap: 14px;
    }

    .admin-homepage-preview-banner,
    .admin-homepage-preview-card {
      padding: 18px 20px;
      border-radius: 18px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: rgba(255, 255, 255, 0.9);
      display: grid;
      gap: 10px;
    }

    .admin-homepage-preview-banner.is-warning,
    .admin-homepage-preview-card.is-warning {
      border-color: rgba(183, 121, 31, 0.35);
      background: rgba(255, 245, 224, 0.95);
    }

    .admin-homepage-preview-banner.is-critical,
    .admin-homepage-preview-card.is-critical {
      border-color: rgba(159, 47, 47, 0.35);
      background: rgba(255, 243, 243, 0.95);
    }

    .admin-homepage-preview-banner strong,
    .admin-homepage-preview-card strong {
      color: #2b241b;
      font-size: 1rem;
    }

    .admin-homepage-preview-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #8c6927;
      font-weight: 700;
      text-decoration: none;
    }

    .admin-homepage-preview-link::after {
      content: '→';
    }

    .admin-homepage-list-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 18px;
    }

    .admin-homepage-list-tabs .admin-chip {
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .admin-homepage-list-tabs .admin-chip-count {
      display: inline-flex;
      min-width: 22px;
      min-height: 22px;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      background: rgba(43, 36, 27, 0.08);
      font-size: 0.76rem;
    }

    .admin-homepage-table-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .admin-homepage-table-actions form {
      margin: 0;
    }

    .admin-homepage-section-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      flex-wrap: wrap;
    }

    .admin-homepage-form-status {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }

    .admin-homepage-history-list {
      display: grid;
      gap: 12px;
    }

    .admin-homepage-history-row {
      padding: 16px 18px;
      border-radius: 18px;
      border: 1px solid rgba(199, 165, 75, 0.14);
      background: rgba(255,255,255,0.86);
      display: grid;
      gap: 8px;
    }

    .admin-homepage-history-row pre {
      margin: 0;
      padding: 12px 14px;
      border-radius: 14px;
      background: rgba(43, 36, 27, 0.04);
      white-space: pre-wrap;
      word-break: break-word;
      font-size: 0.84rem;
      line-height: 1.5;
    }

    .admin-homepage-conflict-list {
      margin: 0;
      padding-left: 18px;
      color: #7a4c16;
    }

    .admin-homepage-empty-card {
      padding: 18px;
      border-radius: 18px;
      border: 1px dashed rgba(199, 165, 75, 0.28);
      background: rgba(255, 250, 244, 0.75);
      color: #7d715f;
    }

    @media (max-width: 1180px) {
      .admin-homepage-summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      .admin-homepage-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 900px) {
      .admin-homepage-summary-grid,
      .admin-homepage-mode-grid,
      .admin-homepage-site-grid,
      .admin-homepage-form-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 600px) {
      .admin-homepage-preview-frame-wrap {
        padding: 12px;
      }

      .admin-homepage-preview-content,
      .admin-homepage-preview-hero {
        padding: 18px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="homepage">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Homepage status, announcements, campaigns, alerts, app messaging, and scheduling controls.</p>
      </div>

      <?php
      $adminNavCurrentPage = 'homepage';
      $adminNavBasePath = '';
      require __DIR__ . '/includes/admin-nav.php';
      ?>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Homepage Control</strong>
          <p class="admin-panel-note">Manage homepage messaging without editing index.html directly.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Homepage</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <div class="admin-homepage-anchor-nav" aria-label="Homepage admin sections">
        <a class="admin-chip" href="#section-site-mode">Site Mode</a>
        <a class="admin-chip" href="#section-announcement-bar">Announcement Bar</a>
        <a class="admin-chip" href="#section-homepage-campaign">Homepage Campaign</a>
        <a class="admin-chip" href="#section-technical-alerts">Technical Alerts</a>
        <a class="admin-chip" href="#section-app-announcement">App Announcement</a>
        <a class="admin-chip" href="#section-schedule-history">Schedule &amp; History</a>
        <a class="admin-chip" href="#section-preview-publish">Preview &amp; Publish</a>
      </div>

      <?php if ($adminHomepageStatusMessage !== '' || $adminHomepageErrorMessage !== '' || $adminHomepageNoticeMessage !== ''): ?>
        <div class="admin-feedback<?php echo $adminHomepageErrorMessage !== '' ? ' is-error' : ($adminHomepageStatusMessage !== '' ? ' is-success' : ''); ?>" role="status" aria-live="polite">
          <?php echo girffonAdminHomepageEscape($adminHomepageErrorMessage !== '' ? $adminHomepageErrorMessage : ($adminHomepageStatusMessage !== '' ? $adminHomepageStatusMessage : $adminHomepageNoticeMessage)); ?>
        </div>
      <?php endif; ?>

      <?php if ($adminHomepageConflictState): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2><?php echo girffonAdminHomepageEscape($adminHomepageConflictState['label']); ?></h2>
              <p class="admin-panel-note"><?php echo girffonAdminHomepageEscape($adminHomepageConflictState['message']); ?></p>
            </div>
            <span class="admin-homepage-warning-chip">Confirmation Required</span>
          </div>
          <?php if (!empty($adminHomepageConflictState['conflict_result']['conflicts'])): ?>
            <ul class="admin-homepage-conflict-list">
              <?php foreach ($adminHomepageConflictState['conflict_result']['conflicts'] as $conflictItem): ?>
                <li>
                  <?php echo girffonAdminHomepageEscape(($conflictItem['title'] ?? 'Homepage item') . ' · ' . ($publicStateLabels[$conflictItem['public_state'] ?? 'inactive'] ?? ucfirst((string) ($conflictItem['public_state'] ?? 'inactive'))) . ' · ' . girffonAdminHomepageFormatRome($conflictItem['start_at'] ?? null, 'd M Y H:i') . ' to ' . girffonAdminHomepageFormatRome($conflictItem['end_at'] ?? null, 'd M Y H:i')); ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="admin-form-actions">
            <form method="post" action="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['edit_item' => (int) ($adminHomepageConflictState['payload']['item_id'] ?? 0), 'editor' => (string) ($adminHomepageConflictState['item_type'] ?? 'announcement_bar')])); ?>">
              <input type="hidden" name="_csrf" value="<?php echo girffonAdminHomepageEscape($adminHomepageCsrf); ?>">
              <input type="hidden" name="force_conflict" value="1">
              <?php echo girffonAdminHomepageRenderHiddenFields($adminHomepageConflictState['payload'], ['_csrf', 'force_conflict']); ?>
              <button class="admin-button admin-button-accent" type="submit" name="homepage_action" value="<?php echo girffonAdminHomepageEscape($adminHomepageConflictState['action']); ?>">Confirm and Continue</button>
            </form>
            <a class="admin-button admin-button-soft" href="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['edit_item' => (int) ($adminHomepageConflictState['payload']['item_id'] ?? 0), 'editor' => (string) ($adminHomepageConflictState['item_type'] ?? 'announcement_bar')])); ?>#<?php echo girffonAdminHomepageEscape($adminHomepageConflictState['anchor']); ?>">Review Without Publishing</a>
          </div>
        </article>
      <?php endif; ?>

      <section class="admin-card-grid" aria-label="Homepage summary cards">
        <article class="admin-stat-card">
          <span>Current Site Mode</span>
          <strong><?php echo girffonAdminHomepageEscape(ucfirst((string) ($adminHomepageSiteState['site_status'] ?? 'normal'))); ?></strong>
          <p class="admin-status">Maintenance state: <?php echo girffonAdminHomepageEscape($publicStateLabels[$adminHomepageSiteState['maintenance_public_state'] ?? 'inactive'] ?? 'Inactive'); ?></p>
        </article>
        <article class="admin-stat-card">
          <span>Draft / Inactive</span>
          <strong><?php echo girffonAdminHomepageEscape($adminHomepageCounts['draft']); ?></strong>
          <p class="admin-status">Draft items and unpublished content that still needs review.</p>
        </article>
        <article class="admin-stat-card">
          <span>Scheduled</span>
          <strong><?php echo girffonAdminHomepageEscape($adminHomepageCounts['scheduled']); ?></strong>
          <p class="admin-status">Items queued in UTC and shown to admins in Rome time.</p>
        </article>
        <article class="admin-stat-card">
          <span>Active</span>
          <strong><?php echo girffonAdminHomepageEscape($adminHomepageCounts['active']); ?></strong>
          <p class="admin-status">Published content currently visible once the public endpoint is wired.</p>
        </article>
        <article class="admin-stat-card">
          <span>Expired / Archived</span>
          <strong><?php echo girffonAdminHomepageEscape($adminHomepageCounts['expired'] + $adminHomepageCounts['archived']); ?></strong>
          <p class="admin-status">Historical items retained for reuse, audit, and cloning.</p>
        </article>
      </section>

      <section class="admin-page-section" id="section-site-mode">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Site Mode</h2>
              <p class="admin-panel-note">Singleton site-state controller. Maintenance has the highest public priority and keeps Admin access available when bypass is enabled.</p>
            </div>
            <span class="admin-homepage-state-chip<?php echo ($adminHomepageSiteState['maintenance_public_state'] ?? 'inactive') === 'active' ? ' is-active' : (($adminHomepageSiteState['maintenance_public_state'] ?? 'inactive') === 'scheduled' ? ' is-scheduled' : (($adminHomepageSiteState['maintenance_public_state'] ?? 'inactive') === 'expired' ? ' is-expired' : '')); ?>"><?php echo girffonAdminHomepageEscape($publicStateLabels[$adminHomepageSiteState['maintenance_public_state'] ?? 'inactive'] ?? 'Inactive'); ?></span>
          </div>

          <form method="post" action="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl()); ?>">
            <input type="hidden" name="_csrf" value="<?php echo girffonAdminHomepageEscape($adminHomepageCsrf); ?>">
            <div class="admin-homepage-mode-grid">
              <label class="admin-homepage-radio-card">
                <input type="radio" name="site_status" value="normal" <?php echo $adminHomepageSiteForm['site_status'] === 'normal' ? 'checked' : ''; ?>>
                <strong>Normal</strong>
                <span class="admin-panel-note">Standard storefront behavior.</span>
              </label>
              <label class="admin-homepage-radio-card">
                <input type="radio" name="site_status" value="notice" <?php echo $adminHomepageSiteForm['site_status'] === 'notice' ? 'checked' : ''; ?>>
                <strong>Notice</strong>
                <span class="admin-panel-note">Use when public messaging should be elevated without full maintenance.</span>
              </label>
              <label class="admin-homepage-radio-card">
                <input type="radio" name="site_status" value="maintenance" <?php echo $adminHomepageSiteForm['site_status'] === 'maintenance' ? 'checked' : ''; ?>>
                <strong>Maintenance</strong>
                <span class="admin-panel-note">Suppresses normal homepage campaign content when active.</span>
              </label>
            </div>

            <div class="admin-homepage-site-grid">
              <div class="admin-field admin-field-wide">
                <label for="adminHomepageMaintenanceTitle">Maintenance Title</label>
                <input class="admin-input" id="adminHomepageMaintenanceTitle" name="maintenance_title" type="text" maxlength="120" value="<?php echo girffonAdminHomepageEscape($adminHomepageSiteForm['maintenance_title']); ?>" placeholder="We'll be back soon.">
              </div>
              <div class="admin-field admin-field-wide">
                <label for="adminHomepageMaintenanceMessage">Maintenance Message</label>
                <textarea class="admin-textarea" id="adminHomepageMaintenanceMessage" name="maintenance_message" maxlength="1000" placeholder="Tell customers what is happening and when to return."><?php echo girffonAdminHomepageEscape($adminHomepageSiteForm['maintenance_message']); ?></textarea>
              </div>
              <div class="admin-field">
                <label for="adminHomepageMaintenanceEta">ETA</label>
                <input class="admin-input" id="adminHomepageMaintenanceEta" name="maintenance_eta" type="text" maxlength="120" value="<?php echo girffonAdminHomepageEscape($adminHomepageSiteForm['maintenance_eta']); ?>" placeholder="About 2 hours">
              </div>
              <div class="admin-field">
                <label for="adminHomepageMaintenanceStart">Start Date (Rome time)</label>
                <input class="admin-input" id="adminHomepageMaintenanceStart" name="maintenance_starts_at_local" type="datetime-local" value="<?php echo girffonAdminHomepageEscape($adminHomepageSiteForm['maintenance_starts_at_local']); ?>">
              </div>
              <div class="admin-field">
                <label for="adminHomepageMaintenanceEnd">End Date (Rome time)</label>
                <input class="admin-input" id="adminHomepageMaintenanceEnd" name="maintenance_ends_at_local" type="datetime-local" value="<?php echo girffonAdminHomepageEscape($adminHomepageSiteForm['maintenance_ends_at_local']); ?>">
              </div>
              <div class="admin-homepage-toggle-row">
                <label><input type="checkbox" name="maintenance_enabled" value="1" <?php echo $adminHomepageSiteForm['maintenance_enabled'] === '1' ? 'checked' : ''; ?>> Maintenance Enabled</label>
                <label><input type="checkbox" name="admin_bypass_enabled" value="1" <?php echo $adminHomepageSiteForm['admin_bypass_enabled'] === '1' ? 'checked' : ''; ?>> Admin Bypass Enabled</label>
              </div>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit" name="homepage_action" value="save_site_state">Save Site Mode</button>
            </div>
          </form>
        </article>
      </section>

      <?php foreach ($contentSections as $sectionType => $sectionConfig): ?>
        <?php
          $formState = $adminHomepageFormStates[$sectionType] ?? girffonAdminHomepageDefaultContentForm($sectionType);
          $isEditingSection = (int) ($formState['item_id'] ?? 0) > 0;
          $sectionLabel = $sectionConfig['label'];
          $sectionAnchor = $sectionConfig['anchor'];
          $sectionFormId = $sectionConfig['form_id'];
          $publicStateValue = (string) ($formState['public_state'] ?? 'inactive');
          $publicStateClass = $publicStateValue === 'active' ? ' is-active' : ($publicStateValue === 'scheduled' ? ' is-scheduled' : ($publicStateValue === 'expired' ? ' is-expired' : ''));
          $sectionHistoryLink = $isEditingSection ? girffonAdminHomepagePageUrl(['history_item' => (int) $formState['item_id'], 'edit_item' => (int) $formState['item_id'], 'editor' => $sectionType, 'filter' => $adminHomepageActiveFilter]) . '#section-schedule-history' : '';
        ?>
      <section class="admin-page-section" id="<?php echo girffonAdminHomepageEscape($sectionAnchor); ?>">
        <article class="admin-panel">
          <div class="admin-section-head">
            <div class="admin-panel-head">
              <div>
                <h2><?php echo girffonAdminHomepageEscape($sectionLabel); ?></h2>
                <p class="admin-panel-note">
                  <?php if ($sectionType === 'announcement_bar'): ?>
                    Timed homepage notices for top bar or hero-adjacent placements. Draft save allows soft conflict warnings; publishing requires confirmation if overlaps exist.
                  <?php elseif ($sectionType === 'homepage_campaign'): ?>
                    Promotional messaging only. Real product prices remain controlled by Products Admin and are never edited here.
                  <?php elseif ($sectionType === 'technical_alert'): ?>
                    Operational customer-facing alerts for hosting, payment, shipping, or custom technical messaging.
                  <?php else: ?>
                    App rollout messaging for Android, iOS, combined launch, or coming soon announcements.
                  <?php endif; ?>
                </p>
              </div>
              <div class="admin-homepage-form-status">
                <span class="admin-homepage-state-chip<?php echo girffonAdminHomepageEscape($publicStateClass); ?>"><?php echo girffonAdminHomepageEscape($publicStateLabels[$publicStateValue] ?? 'Inactive'); ?></span>
                <?php if ($isEditingSection && $sectionHistoryLink !== ''): ?>
                  <a class="admin-button admin-button-soft" href="<?php echo girffonAdminHomepageEscape($sectionHistoryLink); ?>">View Audit History</a>
                <?php endif; ?>
              </div>
            </div>

            <form id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>" method="post" action="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['edit_item' => (int) ($formState['item_id'] ?? 0), 'editor' => $sectionType, 'filter' => $adminHomepageActiveFilter])); ?>">
              <input type="hidden" name="_csrf" value="<?php echo girffonAdminHomepageEscape($adminHomepageCsrf); ?>">
              <input type="hidden" name="item_type" value="<?php echo girffonAdminHomepageEscape($sectionType); ?>">
              <input type="hidden" name="item_id" value="<?php echo girffonAdminHomepageEscape((string) ($formState['item_id'] ?? 0)); ?>">

              <?php if ($sectionType === 'homepage_campaign'): ?>
                <div class="admin-homepage-note" style="margin-bottom:16px;">
                  <strong>Product prices are controlled by Products Admin.</strong><br>
                  Homepage Campaign only controls promotional display.
                </div>
              <?php endif; ?>

              <div class="admin-homepage-form-grid">
                <?php if ($sectionType === 'homepage_campaign'): ?>
                  <div class="admin-field admin-field-wide">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-title">Campaign Title</label>
                    <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-title" name="title" type="text" maxlength="120" value="<?php echo girffonAdminHomepageEscape($formState['title']); ?>" placeholder="Mother's Day Special">
                  </div>
                <?php endif; ?>

                <div class="admin-field admin-field-wide">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-message">Message</label>
                  <textarea class="admin-textarea" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-message" name="message" maxlength="1000" required><?php echo girffonAdminHomepageEscape($formState['message']); ?></textarea>
                </div>

                <?php if ($sectionType === 'technical_alert'): ?>
                  <div class="admin-field">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-alert-type">Alert Type</label>
                    <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-alert-type" name="alert_type">
                      <?php foreach (['hosting_issue' => 'Hosting Issue', 'payment_issue' => 'Payment Issue', 'shipping_delay' => 'Shipping Delay', 'custom' => 'Custom'] as $alertKey => $alertLabel): ?>
                        <option value="<?php echo girffonAdminHomepageEscape($alertKey); ?>" <?php echo $formState['alert_type'] === $alertKey ? 'selected' : ''; ?>><?php echo girffonAdminHomepageEscape($alertLabel); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php elseif ($sectionType === 'app_announcement'): ?>
                  <div class="admin-field">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-platform">Platform</label>
                    <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-platform" name="platform">
                      <?php foreach (['android' => 'Android', 'ios' => 'iOS', 'android_ios' => 'Android + iOS', 'coming_soon' => 'Coming Soon'] as $platformKey => $platformLabel): ?>
                        <option value="<?php echo girffonAdminHomepageEscape($platformKey); ?>" <?php echo $formState['platform'] === $platformKey ? 'selected' : ''; ?>><?php echo girffonAdminHomepageEscape($platformLabel); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php else: ?>
                  <div class="admin-field">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-title">Title</label>
                    <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-title" name="title" type="text" maxlength="120" value="<?php echo girffonAdminHomepageEscape($formState['title']); ?>" placeholder="Optional internal-facing title">
                  </div>
                <?php endif; ?>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-cta-label">CTA Label</label>
                  <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-cta-label" name="cta_label" type="text" maxlength="50" value="<?php echo girffonAdminHomepageEscape($formState['cta_label']); ?>" placeholder="Shop now">
                </div>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-cta-url"><?php echo $sectionType === 'app_announcement' ? 'Store Link' : ($sectionType === 'homepage_campaign' ? 'Product / Collection Link' : 'CTA Link'); ?></label>
                  <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-cta-url" name="cta_url" type="text" maxlength="255" value="<?php echo girffonAdminHomepageEscape($formState['cta_url']); ?>" placeholder="/shop.html or approved external app-store URL">
                </div>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-severity">Severity</label>
                  <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-severity" name="severity">
                    <?php foreach (girffonAdminHomepageSeverityOptions() as $severityOption): ?>
                      <option value="<?php echo girffonAdminHomepageEscape($severityOption); ?>" <?php echo $formState['severity'] === $severityOption ? 'selected' : ''; ?>><?php echo girffonAdminHomepageEscape(ucfirst($severityOption)); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <?php if ($sectionType === 'homepage_campaign'): ?>
                  <div class="admin-field">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-event-key">Event Preset</label>
                    <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-event-key" name="event_key">
                      <?php foreach (girffonAdminHomepageEventKeyOptions() as $eventOption): ?>
                        <option value="<?php echo girffonAdminHomepageEscape($eventOption); ?>" <?php echo $formState['event_key'] === $eventOption ? 'selected' : ''; ?>><?php echo girffonAdminHomepageEscape(ucwords(str_replace('_', ' ', $eventOption))); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="admin-field admin-field-wide">
                    <label>Discount Display</label>
                    <div class="admin-homepage-inline-options">
                      <?php foreach (['5', '10', '15', '20', '25', '30'] as $quickPercent): ?>
                        <label><input type="radio" name="display_percent_option" value="<?php echo girffonAdminHomepageEscape($quickPercent); ?>" <?php echo $formState['display_percent_option'] === $quickPercent ? 'checked' : ''; ?>> <?php echo girffonAdminHomepageEscape($quickPercent); ?>%</label>
                      <?php endforeach; ?>
                      <label><input type="radio" name="display_percent_option" value="custom" <?php echo $formState['display_percent_option'] === 'custom' ? 'checked' : ''; ?>> Custom %</label>
                      <input class="admin-input" name="display_percent_custom" type="number" min="0" max="100" step="0.01" value="<?php echo girffonAdminHomepageEscape($formState['display_percent_custom']); ?>" placeholder="0-100" style="max-width:120px;">
                    </div>
                  </div>

                  <div class="admin-field">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-display-mode">Display Mode</label>
                    <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-display-mode" name="display_mode">
                      <option value="promotion_only" <?php echo $formState['display_mode'] === 'promotion_only' ? 'selected' : ''; ?>>Promotion Only</option>
                      <option value="linked_product_discounts" <?php echo $formState['display_mode'] === 'linked_product_discounts' ? 'selected' : ''; ?>>Linked to Existing Product Discounts</option>
                    </select>
                  </div>

                  <div class="admin-field">
                    <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-coupon-code">Coupon Code</label>
                    <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-coupon-code" name="coupon_code" type="text" maxlength="50" value="<?php echo girffonAdminHomepageEscape($formState['coupon_code']); ?>" placeholder="GIRFFON20">
                  </div>
                <?php else: ?>
                  <input type="hidden" name="display_mode" value="promotion_only">
                  <input type="hidden" name="event_key" value="none">
                  <input type="hidden" name="coupon_code" value="">
                <?php endif; ?>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-start-date">Start Date (Rome time)</label>
                  <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-start-date" name="start_at_local" type="datetime-local" value="<?php echo girffonAdminHomepageEscape($formState['start_at_local']); ?>">
                </div>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-end-date">End Date (Rome time)</label>
                  <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-end-date" name="end_at_local" type="datetime-local" value="<?php echo girffonAdminHomepageEscape($formState['end_at_local']); ?>">
                </div>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-placement">Placement</label>
                  <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-placement" name="target_surface">
                    <?php foreach (['top_bar' => 'Top Bar', 'above_hero' => 'Above Hero', 'below_hero' => 'Below Hero'] as $surfaceKey => $surfaceLabel): ?>
                      <option value="<?php echo girffonAdminHomepageEscape($surfaceKey); ?>" <?php echo $formState['target_surface'] === $surfaceKey ? 'selected' : ''; ?>><?php echo girffonAdminHomepageEscape($surfaceLabel); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-audience">Audience</label>
                  <select class="admin-select" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-audience" name="audience_scope">
                    <option value="all_visitors" <?php echo $formState['audience_scope'] === 'all_visitors' ? 'selected' : ''; ?>>All Visitors</option>
                    <option value="logged_in" <?php echo $formState['audience_scope'] === 'logged_in' ? 'selected' : ''; ?>>Logged-in Users</option>
                  </select>
                </div>

                <div class="admin-field">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-priority">Priority</label>
                  <input class="admin-input" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-priority" name="priority" type="number" min="0" max="1000" step="1" value="<?php echo girffonAdminHomepageEscape($formState['priority']); ?>">
                </div>

                <div class="admin-field admin-field-wide">
                  <label for="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-notes">Internal Notes</label>
                  <textarea class="admin-textarea" id="<?php echo girffonAdminHomepageEscape($sectionFormId); ?>-notes" name="internal_notes" maxlength="2000" placeholder="Internal notes are never public."><?php echo girffonAdminHomepageEscape($formState['internal_notes']); ?></textarea>
                </div>

                <div class="admin-homepage-toggle-row admin-field-wide">
                  <label><input type="checkbox" name="auto_expire" value="1" <?php echo $formState['auto_expire'] === '1' ? 'checked' : ''; ?>> Auto Expire</label>
                  <label><input type="checkbox" name="is_enabled" value="1" <?php echo $formState['is_enabled'] === '1' ? 'checked' : ''; ?>> ON / OFF intent</label>
                  <span class="admin-panel-note">Publish and unpublish actions remain authoritative.</span>
                </div>
              </div>

              <div class="admin-form-actions">
                <button class="admin-button admin-button-accent" type="submit" name="homepage_action" value="save_draft"><?php echo $isEditingSection && ($formState['workflow_status'] ?? 'draft') !== 'draft' ? 'Save Changes' : 'Save Draft'; ?></button>
                <button class="admin-button" type="submit" name="homepage_action" value="publish_now">Publish Now</button>
                <button class="admin-button admin-button-soft" type="submit" name="homepage_action" value="schedule_publish">Schedule Publish</button>
                <?php if ($isEditingSection): ?>
                  <button class="admin-button admin-button-soft" type="submit" name="homepage_action" value="unpublish">Unpublish</button>
                  <button class="admin-button admin-button-soft" type="submit" name="homepage_action" value="archive">Archive</button>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </article>
      </section>
      <?php endforeach; ?>

      <section class="admin-page-section" id="section-schedule-history">
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Schedule &amp; History</h2>
              <p class="admin-panel-note">Draft includes unpublished inactive items so they remain editable. Active, scheduled, expired, and archived reflect the computed or stored states used by the service layer.</p>
            </div>
          </div>

          <div class="admin-homepage-list-tabs" role="tablist" aria-label="Homepage item filters">
            <?php foreach (['draft' => 'Draft', 'scheduled' => 'Scheduled', 'active' => 'Active', 'expired' => 'Expired', 'archived' => 'Archived'] as $filterKey => $filterLabel): ?>
              <a class="admin-chip<?php echo $adminHomepageActiveFilter === $filterKey ? ' is-active' : ''; ?>" href="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['filter' => $filterKey])); ?>#section-schedule-history" role="tab" aria-selected="<?php echo $adminHomepageActiveFilter === $filterKey ? 'true' : 'false'; ?>">
                <?php echo girffonAdminHomepageEscape($filterLabel); ?>
                <span class="admin-chip-count"><?php echo girffonAdminHomepageEscape($adminHomepageCounts[$filterKey]); ?></span>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Type</th>
                  <th>Computed Public State</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Priority</th>
                  <th>Last Updated By</th>
                  <th>Last Updated At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminHomepageFilteredItems): ?>
                  <?php foreach ($adminHomepageFilteredItems as $row): ?>
                    <?php $rowSection = girffonAdminHomepageSectionConfig((string) ($row['item_type'] ?? 'announcement_bar')); ?>
                    <tr>
                      <td>
                        <strong><?php echo girffonAdminHomepageEscape((string) (($row['title'] ?? '') !== '' ? $row['title'] : ($row['message'] ?? 'Homepage item'))); ?></strong>
                        <div class="admin-panel-note"><?php echo girffonAdminHomepageEscape(girffonAdminHomepageExcerpt((string) ($row['message'] ?? ''), 92)); ?></div>
                      </td>
                      <td><?php echo girffonAdminHomepageEscape($itemTypeLabels[$row['item_type'] ?? 'announcement_bar'] ?? ucfirst((string) ($row['item_type'] ?? 'announcement_bar'))); ?></td>
                      <td><span class="admin-homepage-state-chip<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePublicStateClass((string) ($row['public_state'] ?? 'inactive'))); ?>"><?php echo girffonAdminHomepageEscape($publicStateLabels[$row['public_state'] ?? 'inactive'] ?? ucfirst((string) ($row['public_state'] ?? 'inactive'))); ?></span></td>
                      <td><?php echo girffonAdminHomepageEscape(girffonAdminHomepageFormatRome($row['start_at'] ?? null)); ?></td>
                      <td><?php echo girffonAdminHomepageEscape(girffonAdminHomepageFormatRome($row['end_at'] ?? null)); ?></td>
                      <td><?php echo girffonAdminHomepageEscape((string) ($row['priority'] ?? 0)); ?></td>
                      <td><?php echo girffonAdminHomepageEscape((string) ($row['updated_by_username'] ?? 'GirffoN Admin')); ?></td>
                      <td><?php echo girffonAdminHomepageEscape(girffonAdminHomepageFormatRome($row['updated_at'] ?? null)); ?></td>
                      <td>
                        <div class="admin-homepage-table-actions">
                          <a class="admin-button admin-button-soft" href="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['edit_item' => (int) ($row['id'] ?? 0), 'editor' => (string) ($row['item_type'] ?? 'announcement_bar'), 'filter' => $adminHomepageActiveFilter])); ?>#<?php echo girffonAdminHomepageEscape($rowSection['anchor']); ?>">Edit</a>
                          <a class="admin-button admin-button-soft" href="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['preview_item' => (int) ($row['id'] ?? 0), 'editor' => (string) ($row['item_type'] ?? 'announcement_bar'), 'filter' => $adminHomepageActiveFilter])); ?>#section-preview-publish">Preview</a>
                          <a class="admin-button admin-button-soft" href="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['history_item' => (int) ($row['id'] ?? 0), 'filter' => $adminHomepageActiveFilter])); ?>#section-schedule-history">History</a>
                          <form method="post" action="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['filter' => $adminHomepageActiveFilter])); ?>#section-schedule-history">
                            <input type="hidden" name="_csrf" value="<?php echo girffonAdminHomepageEscape($adminHomepageCsrf); ?>">
                            <input type="hidden" name="item_type" value="<?php echo girffonAdminHomepageEscape((string) ($row['item_type'] ?? 'announcement_bar')); ?>">
                            <input type="hidden" name="item_id" value="<?php echo girffonAdminHomepageEscape((string) ($row['id'] ?? 0)); ?>">
                            <button class="admin-button admin-button-soft" type="submit" name="homepage_action" value="clone">Clone</button>
                          </form>
                          <?php if (!empty($row['is_enabled']) && ($row['workflow_status'] ?? 'draft') !== 'archived'): ?>
                            <form method="post" action="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['filter' => $adminHomepageActiveFilter])); ?>#section-schedule-history">
                              <input type="hidden" name="_csrf" value="<?php echo girffonAdminHomepageEscape($adminHomepageCsrf); ?>">
                              <input type="hidden" name="item_type" value="<?php echo girffonAdminHomepageEscape((string) ($row['item_type'] ?? 'announcement_bar')); ?>">
                              <input type="hidden" name="item_id" value="<?php echo girffonAdminHomepageEscape((string) ($row['id'] ?? 0)); ?>">
                              <button class="admin-button admin-button-soft" type="submit" name="homepage_action" value="unpublish">Unpublish</button>
                            </form>
                          <?php endif; ?>
                          <?php if (($row['workflow_status'] ?? 'draft') !== 'archived'): ?>
                            <form method="post" action="<?php echo girffonAdminHomepageEscape(girffonAdminHomepagePageUrl(['filter' => $adminHomepageActiveFilter])); ?>#section-schedule-history">
                              <input type="hidden" name="_csrf" value="<?php echo girffonAdminHomepageEscape($adminHomepageCsrf); ?>">
                              <input type="hidden" name="item_type" value="<?php echo girffonAdminHomepageEscape((string) ($row['item_type'] ?? 'announcement_bar')); ?>">
                              <input type="hidden" name="item_id" value="<?php echo girffonAdminHomepageEscape((string) ($row['id'] ?? 0)); ?>">
                              <button class="admin-button admin-button-soft" type="submit" name="homepage_action" value="archive">Archive</button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="admin-empty">No Homepage items found for the selected filter.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>History / Audit</h2>
              <p class="admin-panel-note">Audit snapshots are loaded from homepage_content_history and remain admin-only.</p>
            </div>
          </div>
          <?php if ($adminHomepageHistoryItem && $adminHomepageHistoryRows): ?>
            <div class="admin-homepage-history-list">
              <?php foreach ($adminHomepageHistoryRows as $historyRow): ?>
                <article class="admin-homepage-history-row">
                  <strong><?php echo girffonAdminHomepageEscape(ucwords(str_replace('_', ' ', (string) ($historyRow['action_type'] ?? 'updated')))); ?></strong>
                  <div class="admin-panel-note"><?php echo girffonAdminHomepageEscape((string) ($historyRow['changed_by_username'] ?? 'GirffoN Admin')); ?> · <?php echo girffonAdminHomepageEscape(girffonAdminHomepageFormatRome($historyRow['created_at'] ?? null)); ?></div>
                  <pre><?php echo girffonAdminHomepageEscape(json_encode(girffonAdminHomepageDecodeMetadata($historyRow['snapshot_json'] ?? ''), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="admin-homepage-empty-card">Select an item from Schedule &amp; History to inspect its audit trail.</div>
          <?php endif; ?>
        </article>
      </section>

      <section class="admin-page-section" id="section-preview-publish">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Preview &amp; Publish</h2>
              <p class="admin-panel-note">Admin Preview only. This is not the final live homepage render and does not yet connect index.html or the future public endpoint.</p>
            </div>
            <span class="admin-homepage-warning-chip">Admin Preview</span>
          </div>

          <div class="admin-homepage-preview-shell">
            <div class="admin-homepage-preview-widths" role="group" aria-label="Preview widths">
              <button class="admin-chip is-active" type="button" data-preview-width="1440">Desktop 1440</button>
              <button class="admin-chip" type="button" data-preview-width="1024">Tablet Landscape 1024</button>
              <button class="admin-chip" type="button" data-preview-width="768">Tablet Portrait 768</button>
              <button class="admin-chip" type="button" data-preview-width="390">Mobile 390</button>
            </div>

            <div class="admin-homepage-preview-actions">
              <?php $activeSectionConfig = girffonAdminHomepageSectionConfig($adminHomepageActiveEditorType); ?>
              <button class="admin-button admin-button-accent" type="submit" form="<?php echo girffonAdminHomepageEscape($activeSectionConfig['form_id']); ?>" name="homepage_action" value="save_draft">Save Draft</button>
              <button class="admin-button" type="submit" form="<?php echo girffonAdminHomepageEscape($activeSectionConfig['form_id']); ?>" name="homepage_action" value="publish_now">Publish Now</button>
              <button class="admin-button admin-button-soft" type="submit" form="<?php echo girffonAdminHomepageEscape($activeSectionConfig['form_id']); ?>" name="homepage_action" value="schedule_publish">Schedule Publish</button>
            </div>

            <div class="admin-homepage-preview-frame-wrap">
              <div class="admin-homepage-preview-frame" id="adminHomepagePreviewFrame" style="--preview-width:1440px;">
                <div class="admin-homepage-preview-topbar">
                  <span>GirffoN Admin Preview</span>
                  <span>Rome reference time: <?php echo girffonAdminHomepageEscape(girffonAdminHomepageRomeCurrent('d M Y · H:i')); ?></span>
                </div>
                <div class="admin-homepage-preview-hero">
                  <span class="admin-homepage-preview-badge<?php echo $adminHomepagePreviewState === 'active' ? ' is-active' : ($adminHomepagePreviewState === 'scheduled' ? ' is-scheduled' : ($adminHomepagePreviewState === 'expired' ? ' is-expired' : '')); ?>"><?php echo girffonAdminHomepageEscape($publicStateLabels[$adminHomepagePreviewState] ?? 'Inactive'); ?></span>
                  <h3><?php echo girffonAdminHomepageEscape($adminHomepagePreviewLabel); ?></h3>
                  <p class="admin-panel-note">Surface: <?php echo girffonAdminHomepageEscape(ucwords(str_replace('_', ' ', $adminHomepagePreviewSurface))); ?> · Audience: <?php echo girffonAdminHomepageEscape(ucwords(str_replace('_', ' ', (string) ($adminHomepagePreviewForm['audience_scope'] ?? 'all_visitors')))); ?></p>
                </div>
                <div class="admin-homepage-preview-content">
                  <?php if (($adminHomepageSiteState['maintenance_public_state'] ?? 'inactive') === 'active' || ($adminHomepageSiteState['maintenance_public_state'] ?? 'inactive') === 'scheduled'): ?>
                    <article class="admin-homepage-preview-card is-critical">
                      <strong><?php echo girffonAdminHomepageEscape((string) (($adminHomepageSiteState['maintenance_title'] ?? '') !== '' ? $adminHomepageSiteState['maintenance_title'] : 'Maintenance Mode')); ?></strong>
                      <div><?php echo girffonAdminHomepageEscape((string) ($adminHomepageSiteState['maintenance_message'] ?? 'Maintenance content is authoritative while maintenance mode is active.')); ?></div>
                      <?php if (trim((string) ($adminHomepageSiteState['maintenance_eta'] ?? '')) !== ''): ?>
                        <div class="admin-panel-note">ETA: <?php echo girffonAdminHomepageEscape((string) $adminHomepageSiteState['maintenance_eta']); ?></div>
                      <?php endif; ?>
                    </article>
                  <?php endif; ?>
                  <article class="admin-homepage-preview-<?php echo $adminHomepagePreviewSurface === 'top_bar' ? 'banner' : 'card'; ?><?php echo ($adminHomepagePreviewForm['severity'] ?? 'info') === 'warning' ? ' is-warning' : (($adminHomepagePreviewForm['severity'] ?? 'info') === 'critical' ? ' is-critical' : ''); ?>">
                    <strong><?php echo girffonAdminHomepageEscape(($adminHomepagePreviewForm['title'] ?? '') !== '' ? (string) $adminHomepagePreviewForm['title'] : $adminHomepagePreviewLabel); ?></strong>
                    <div><?php echo nl2br(girffonAdminHomepageEscape((string) ($adminHomepagePreviewForm['message'] ?? 'No preview message yet.'))); ?></div>
                    <?php if (trim((string) ($adminHomepagePreviewForm['cta_label'] ?? '')) !== ''): ?>
                      <span class="admin-homepage-preview-link"><?php echo girffonAdminHomepageEscape((string) $adminHomepagePreviewForm['cta_label']); ?></span>
                    <?php endif; ?>
                    <div class="admin-panel-note">Schedule: <?php echo girffonAdminHomepageEscape(($adminHomepagePreviewForm['start_at_local'] ?? '') !== '' ? (string) $adminHomepagePreviewForm['start_at_local'] : 'Now'); ?> → <?php echo girffonAdminHomepageEscape(($adminHomepagePreviewForm['end_at_local'] ?? '') !== '' ? (string) $adminHomepagePreviewForm['end_at_local'] : 'Open End'); ?> (Rome time)</div>
                  </article>
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    (function () {
      const previewFrame = document.getElementById('adminHomepagePreviewFrame');
      const widthButtons = document.querySelectorAll('[data-preview-width]');

      widthButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          const width = String(button.getAttribute('data-preview-width') || '1440').trim();
          if (previewFrame) {
            previewFrame.style.setProperty('--preview-width', width + 'px');
          }

          widthButtons.forEach(function (candidate) {
            candidate.classList.toggle('is-active', candidate === button);
          });
        });
      });
    }());
  </script>
</body>
</html>
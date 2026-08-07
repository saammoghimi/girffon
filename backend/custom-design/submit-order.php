<?php
require_once __DIR__ . '/../profile/common.php';
require_once __DIR__ . '/../admin/custom-design-orders-data.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    girffonProfileJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

$userId = girffonProfileCurrentUserId();
$user = $userId > 0 ? girffonProfileFetchUserById($pdo, $userId) : null;
if ($userId > 0 && !$user) {
    girffonProfileJsonResponse(404, [
        'success' => false,
        'message' => 'Customer account not found.',
    ]);
}

$request = girffonProfileRequestData();
$payload = is_array($request['payload'] ?? null) ? $request['payload'] : $request;

if (is_array($payload)) {
    if (!is_array($payload['size_requests'] ?? null) && is_array($payload['snapshot']['sizeRequests'] ?? null)) {
        $payload['size_requests'] = $payload['snapshot']['sizeRequests'];
    }

    if (!is_array($payload['designSelections'] ?? null) && is_array($payload['items']['add_design'] ?? null)) {
        $payload['designSelections'] = $payload['items']['add_design'];
    }
}

if (!is_array($payload) || !is_array($payload['snapshot'] ?? null)) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'The custom design payload is incomplete.',
    ]);
}

$sessionOwner = girffonProfileSessionOwnerToken();
$payload['session_owner'] = $sessionOwner;
$payload['customer'] = array_merge(
    is_array($payload['customer'] ?? null) ? $payload['customer'] : [],
    ['session_owner' => $sessionOwner]
);
$payload['status'] = 'pending_payment';

$customer = $user
    ? girffonProfileNormalizeUserRow($user)
    : [
        'id' => 0,
        'name' => '',
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'country' => '',
        'postcode' => '',
        'postal_code' => '',
        'preferred_language' => '',
        'date_of_birth' => '',
        'gender' => '',
        'avatar' => '',
        'username' => '',
        'created_at' => '',
        'updated_at' => '',
        'last_login_at' => '',
        'session_owner' => $sessionOwner,
    ];

$result = girffonAdminCreateCustomDesignOrder($pdo, $customer, $payload);
if (!($result['success'] ?? false)) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => (string) ($result['message'] ?? 'Unable to save the custom design order.'),
    ]);
}

girffonProfileJsonResponse(200, [
    'success' => true,
    'order_id' => (int) ($result['order_id'] ?? 0),
    'order_code' => (string) ($result['order_code'] ?? ''),
    'redirect' => '/GirffoN/custom-design-checkout.php?order=' . (int) ($result['order_id'] ?? 0),
]);
<?php
require_once __DIR__ . '/common.php';

function girffonProfileAvatarDebugLog(array $context): void
{
    $logDirectory = dirname(__DIR__) . '/logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
        return;
    }

    $logFile = $logDirectory . '/avatar-debug.log';
    $lines = [
        '[' . date('Y-m-d H:i:s') . ']',
        'user_id=' . (string) ($context['user_id'] ?? 0),
        'avatarPath=' . (string) ($context['avatarPath'] ?? ''),
        'rows_affected=' . (string) ($context['rows_affected'] ?? 0),
        'avatar_in_db=' . json_encode((string) ($context['avatar_in_db'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        str_repeat('-', 80),
    ];

    @file_put_contents($logFile, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND);
}

$userId = girffonProfileRequireUserId();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

if (!isset($_FILES['avatar'])) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Avatar file is required.',
    ]);
}

$file = $_FILES['avatar'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Choose an avatar file first.',
    ]);
}

if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    girffonProfileJsonResponse(400, [
        'success' => false,
        'message' => 'Avatar upload failed.',
    ]);
}

$tmpName = (string) ($file['tmp_name'] ?? '');
if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    girffonProfileJsonResponse(400, [
        'success' => false,
        'message' => 'Invalid uploaded avatar file.',
    ]);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = (string) $finfo->file($tmpName);
$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

if (!isset($extensionMap[$mimeType])) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Please upload a JPG, PNG, WEBP, or GIF image.',
    ]);
}

if (((int) ($file['size'] ?? 0)) > 5 * 1024 * 1024) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Avatar file must be smaller than 5 MB.',
    ]);
}

$uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to create the avatar uploads folder.',
    ]);
}

$fileName = 'avatar-' . $userId . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extensionMap[$mimeType];
$destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
if (!move_uploaded_file($tmpName, $destination)) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to save the uploaded avatar.',
    ]);
}

$avatarPath = 'uploads/avatars/' . $fileName;

try {
    $availableColumns = girffonProfileTableColumns($pdo, 'users');
    if (!isset($availableColumns['avatar'])) {
        girffonProfileJsonResponse(500, [
            'success' => false,
            'message' => 'The avatar column is not available on users.',
        ]);
    }

    $stmt = $pdo->prepare('UPDATE users SET avatar = :avatar WHERE id = :user_id');

    $stmt->execute([
        ':avatar' => $avatarPath,
        ':user_id' => $userId,
    ]);

    $rowsAffected = (int) $stmt->rowCount();

    $check = $pdo->prepare(
        'SELECT avatar
        FROM users
        WHERE id = ?'
    );
    $check->execute([$userId]);
    $avatarInDb = (string) ($check->fetchColumn() ?: '');

    girffonProfileAvatarDebugLog([
        'user_id' => $userId,
        'avatarPath' => $avatarPath,
        'rows_affected' => $rowsAffected,
        'avatar_in_db' => $avatarInDb,
    ]);

    $freshUser = girffonProfileFetchUserById($pdo, $userId) ?: [];
    $normalizedUser = girffonProfileNormalizeUserRow($freshUser);

    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Profile photo uploaded successfully.',
        'saved_avatar' => (string) ($normalizedUser['avatar'] ?? $avatarInDb ?: $avatarPath),
        'avatar' => $avatarPath,
        'avatar_in_db' => $avatarInDb,
        'rows_affected' => $rowsAffected,
        'user' => $normalizedUser,
    ]);
} catch (PDOException $exception) {
    girffonProfileAvatarDebugLog([
        'user_id' => $userId,
        'avatarPath' => $avatarPath,
        'rows_affected' => 0,
        'avatar_in_db' => 'PDO_ERROR: ' . $exception->getMessage(),
    ]);

    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to save the uploaded avatar right now.',
    ]);
}
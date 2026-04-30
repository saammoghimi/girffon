<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . "/../config/database.php";

function girffonAuthWantsJson(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function girffonAuthRespond(int $statusCode, bool $ok, string $message, ?array $user = null, ?string $redirectPath = null): void
{
    if (girffonAuthWantsJson()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
            'user' => $user,
            'redirect' => $redirectPath,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($ok && $redirectPath) {
        header('Location: ' . $redirectPath);
        exit;
    }

    http_response_code($statusCode);
    exit($message);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identifier = trim($_POST["identifier"] ?? $_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($identifier === '' || $password === '') {
        girffonAuthRespond(422, false, 'Username, email, or password is missing.');
    }

    $stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, email, phone, password_hash, role
        FROM users
        WHERE username = ? OR email = ? OR phone = ?
        LIMIT 1
    ");

    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        girffonAuthRespond(404, false, 'User not found.');
    }

    if (!password_verify($password, $user["password_hash"])) {
        girffonAuthRespond(401, false, 'Wrong password.');
    }

    $_SESSION["user_id"] = (int) $user["id"];
    $_SESSION["girffon_user_id"] = (int) $user["id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["role"] = $user["role"];

    $redirectPath = '/GirffoN/ProfilePage.php';
    if (!empty($_SESSION['post_login_redirect'])) {
        $candidateRedirect = (string) $_SESSION['post_login_redirect'];
        if (strncmp($candidateRedirect, '/GirffoN/', 9) === 0) {
            $redirectPath = $candidateRedirect;
        }
        unset($_SESSION['post_login_redirect']);
    }

    girffonAuthRespond(200, true, 'Signed in successfully.', [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'first_name' => (string) ($user['first_name'] ?? ''),
        'last_name' => (string) ($user['last_name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
    ], $redirectPath);
}
?>
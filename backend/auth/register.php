<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "../config/database.php";

function girffonRegisterWantsJson(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function girffonRegisterRespond(int $statusCode, bool $ok, string $message, ?array $user = null): void
{
    if (girffonRegisterWantsJson()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
            'user' => $user,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($ok) {
        echo $message;
        exit;
    }

    http_response_code($statusCode);
    exit($message);
}

function girffonGenerateUsername(PDO $pdo, string $email, string $firstName, string $lastName): string
{
    $seed = trim($firstName . '.' . $lastName);
    if ($seed === '.') {
        $seed = strstr($email, '@', true) ?: $email;
    }

    $base = strtolower($seed);
    $base = (string) preg_replace('/[^a-z0-9]+/', '.', $base);
    $base = trim($base, '.');
    if ($base === '') {
        $base = 'girffon.user';
    }

    $candidate = $base;
    $counter = 1;
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');

    while (true) {
        $check->execute([$candidate]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            return $candidate;
        }

        $counter += 1;
        $candidate = $base . $counter;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $phone      = trim($_POST["phone"] ?? "");
    $password   = $_POST["password"] ?? "";

    if (!$first_name || !$last_name || !$email || !$password) {
        girffonRegisterRespond(422, false, 'All fields required.');
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        girffonRegisterRespond(409, false, 'Email already exists.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $username = girffonGenerateUsername($pdo, $email, $first_name, $last_name);

    $stmt = $pdo->prepare("
        INSERT INTO users
        (username, first_name, last_name, email, password_hash, role, phone, status)
        VALUES (?, ?, ?, ?, ?, 'customer', ?, 'active')
    ");

    $stmt->execute([
        $username,
        $first_name,
        $last_name,
        $email,
        $hash,
        $phone !== '' ? $phone : null
    ]);

    $userId = (int) $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['girffon_user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'customer';

    girffonRegisterRespond(200, true, 'Register success', [
        'id' => $userId,
        'username' => $username,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
    ]);
}
?>
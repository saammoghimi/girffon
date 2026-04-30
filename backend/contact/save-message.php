<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonContactJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonContactRequestData(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function girffonContactColumnExists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
    $statement->execute([':column' => $column]);
    return (bool) $statement->fetch(PDO::FETCH_ASSOC);
}

function girffonContactEnsureTable(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'unread',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!girffonContactColumnExists($pdo, 'contact_messages', 'subject')) {
        $pdo->exec("ALTER TABLE contact_messages ADD subject VARCHAR(190) NOT NULL DEFAULT '' AFTER email");
    }
    if (!girffonContactColumnExists($pdo, 'contact_messages', 'status')) {
        $pdo->exec("ALTER TABLE contact_messages ADD status VARCHAR(50) NOT NULL DEFAULT 'unread' AFTER message");
    }

    $checked = true;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonContactJson(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$payload = girffonContactRequestData();
$name = trim((string) ($payload['fullName'] ?? $payload['name'] ?? ''));
$email = strtolower(trim((string) ($payload['email'] ?? '')));
$subject = trim((string) ($payload['subject'] ?? ''));
$message = trim((string) ($payload['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    girffonContactJson(422, ['ok' => false, 'message' => 'Please complete all contact fields.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonContactJson(422, ['ok' => false, 'message' => 'Please enter a valid email address.']);
}

try {
    girffonContactEnsureTable($pdo);

    $statement = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, subject, message, status)
         VALUES (:name, :email, :subject, :message, :status)'
    );
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':status' => 'unread',
    ]);

    girffonContactJson(200, [
        'ok' => true,
        'message' => 'Your message was sent successfully.',
        'contactMessage' => [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'status' => 'unread',
        ],
    ]);
} catch (PDOException $exception) {
    girffonContactJson(500, ['ok' => false, 'message' => 'Unable to send your message right now.']);
}
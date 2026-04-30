<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/messages-data.php";

function girffonAdminRedirectMessage(string $type, string $message): void
{
    header("Location: /GirffoN/admin-messages.php?" . $type . "=" . rawurlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    girffonAdminRedirectMessage('error', 'Invalid request method.');
}

$customerName = trim((string) ($_POST['customerName'] ?? $_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$status = strtolower(trim((string) ($_POST['status'] ?? 'unread')));

if ($customerName === '' || $email === '' || $subject === '' || $message === '') {
    girffonAdminRedirectMessage('error', 'Please complete all message fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonAdminRedirectMessage('error', 'Please enter a valid email address.');
}

try {
    girffonAdminEnsureContactMessagesTable($pdo);
    $statement = $pdo->prepare(
        "INSERT INTO contact_messages (name, email, subject, message, status)
         VALUES (:name, :email, :subject, :message, :status)"
    );
    $statement->execute([
        ':name' => $customerName,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':status' => $status !== '' ? $status : 'unread',
    ]);

    girffonAdminRedirectMessage('status', 'Message saved successfully.');
} catch (PDOException $exception) {
    girffonAdminRedirectMessage('error', 'Unable to save the message right now.');
}
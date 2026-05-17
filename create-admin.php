<?php

require_once __DIR__ . '/backend/config/database.php';

$username = 'gizeta17';
$password = 'giorgia17!';
$role = 'admin';

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
INSERT INTO users (username, password_hash, role)
VALUES (?, ?, ?)
");

$stmt->execute([$username, $passwordHash, $role]);

echo "Admin created successfully!";
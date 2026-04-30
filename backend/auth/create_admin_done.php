<?php
require_once "../config/database.php";

$username = "seyedsam";
$password = "S4571359m!";

$first_name = "Seyedsam";
$last_name  = "Moghimi";
$email      = "sam@girffon.local";

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users 
    (username, first_name, last_name, email, password_hash, role, status)
    VALUES (?, ?, ?, ?, ?, 'admin', 'active')
");

try {
    $stmt->execute([$username, $first_name, $last_name, $email, $hash]);
    echo "Admin created successfully";
} catch (PDOException $e) {
    echo "Admin already exists or error";
}
?>
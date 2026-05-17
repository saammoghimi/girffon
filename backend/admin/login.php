<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/dashboard-data.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /GirffoN/admin-login.html");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";
$errorRedirect = "/GirffoN/admin-login.html?error=" . rawurlencode("Wrong username or password");

if ($username === "" || $password === "") {
    header("Location: " . $errorRedirect);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, username, password_hash, role
     FROM users
     WHERE username = ? AND role = 'admin'
     LIMIT 1"
);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password_hash"])) {
    header("Location: " . $errorRedirect);
    exit;
}

session_regenerate_id(true);
$_SESSION["admin_id"] = (int) $user["id"];
$_SESSION["admin_user_id"] = (int) $user["id"];
$_SESSION["girffon_admin_id"] = (int) $user["id"];
$_SESSION["admin_username"] = $user["username"];
$_SESSION["admin_role"] = "admin";
$_SESSION["admin_logged_in"] = true;

girffonAdminRecordLoginActivity((int) $user['id'], (string) $user['username']);

header("Location: /GirffoN/admin-dashboard.php");
exit;

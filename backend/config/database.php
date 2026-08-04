<?php
$connections = [
    [
        'host' => 'localhost',
        'db' => 'girfiwkk_samdb',
        'user' => 'girfiwkk_samuser',
        'pass' => 'Sam@2026Db!',
    ],
    [
        'host' => 'localhost',
        'db' => 'girffon_db',
        'user' => 'root',
        'pass' => '',
    ],
];

$pdo = null;

foreach ($connections as $connection) {
    try {
        $pdo = new PDO(
            'mysql:host=' . $connection['host'] . ';dbname=' . $connection['db'] . ';charset=utf8mb4',
            $connection['user'],
            $connection['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break;
    } catch (PDOException $e) {
        $pdo = null;
    }
}

if (!$pdo instanceof PDO) {
    die('Database connection failed');
}
?>
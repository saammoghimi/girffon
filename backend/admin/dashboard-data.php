<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminCountMembers(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchRecentMembers(PDO $pdo, int $limit = 5): array
{
    try {
        $sql = "SELECT first_name, last_name, email, created_at
                FROM users
                WHERE role = 'customer'
                ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $exception) {
        return [];
    }
}
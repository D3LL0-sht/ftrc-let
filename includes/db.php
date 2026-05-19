<?php
// ============================================================
// FTRC LET Review System — Database Connection (Neon.tech)
// ============================================================
require_once __DIR__ . '/../config/config.php';

try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
        DB_HOST, DB_PORT, DB_NAME, DB_SSL
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'error'   => 'Database connection failed',
        'message' => $e->getMessage()
    ]));
}
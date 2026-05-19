<?php
// ============================================================
// FTRC LET Review System — Auth Guard
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /pages/login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: /pages/dashboard.php');
        exit;
    }
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user(): array {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['role']      ?? null,
    ];
}
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

/**
 * Restricts a page to a single role (e.g. 'admin'). Cashiers hitting an
 * admin-only page (product edit, purchases, reports) get a 403 page -
 * this is what keeps a cashier from editing product prices or deleting bills.
 */
function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit;
    }
}

function currentUser(): array
{
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

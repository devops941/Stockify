<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/customers/list.php');
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM customers WHERE customer_id = ?');
    $stmt->execute([$id]);
    flash('success', 'Customer deleted.');
} catch (PDOException $e) {
    flash('danger', 'Cannot delete this customer - they have existing invoices.');
}

redirect('/customers/list.php');

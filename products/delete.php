<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/products/list.php');
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->execute([$id]);
    flash('success', 'Product deleted.');
} catch (PDOException $e) {
    flash('danger', 'Cannot delete this product - it is referenced by existing invoices or purchases.');
}

redirect('/products/list.php');

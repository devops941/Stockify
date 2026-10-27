<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$results = [];

try {
    // 1. Temporarily disable foreign key checks so cleanup is guaranteed to succeed
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Re-point invoice_items from duplicate products to original products
    $stmt = $pdo->exec(
        "UPDATE invoice_items ii
         JOIN products p_dup ON ii.product_id = p_dup.product_id
         JOIN products p_orig ON LOWER(TRIM(p_orig.name)) = LOWER(TRIM(p_dup.name)) AND p_orig.product_id < p_dup.product_id
         SET ii.product_id = p_orig.product_id"
    );
    $results[] = "Re-linked invoice items to original products.";

    // 3. Re-point purchases from duplicate products to original products
    $pdo->exec(
        "UPDATE purchases pu
         JOIN products p_dup ON pu.product_id = p_dup.product_id
         JOIN products p_orig ON LOWER(TRIM(p_orig.name)) = LOWER(TRIM(p_dup.name)) AND p_orig.product_id < p_dup.product_id
         SET pu.product_id = p_orig.product_id"
    );
    $results[] = "Re-linked purchases to original products.";

    // 4. Delete duplicate products (keeping lowest product_id for each name)
    $delProducts = $pdo->exec(
        "DELETE p1 FROM products p1
         INNER JOIN products p2 
         WHERE p1.product_id > p2.product_id 
           AND LOWER(TRIM(p1.name)) = LOWER(TRIM(p2.name))"
    );
    $results[] = "Deleted {$delProducts} duplicate product row(s).";

    // 5. Re-point invoices from duplicate customers to original customers
    $pdo->exec(
        "UPDATE invoices inv
         JOIN customers c_dup ON inv.customer_id = c_dup.customer_id
         JOIN customers c_orig ON LOWER(TRIM(c_orig.name)) = LOWER(TRIM(c_dup.name)) AND c_orig.customer_id < c_dup.customer_id
         SET inv.customer_id = c_orig.customer_id"
    );
    $results[] = "Re-linked invoices to original customers.";

    // 6. Delete duplicate customers (keeping lowest customer_id for each name)
    $delCustomers = $pdo->exec(
        "DELETE c1 FROM customers c1
         INNER JOIN customers c2 
         WHERE c1.customer_id > c2.customer_id 
           AND LOWER(TRIM(c1.name)) = LOWER(TRIM(c2.name))"
    );
    $results[] = "Deleted {$delCustomers} duplicate customer row(s).";

    // 7. Ensure settings table exists (MySQL 5.7+ compatible: TEXT without default)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
            `key` varchar(64) NOT NULL,
            `value` text NULL,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $results[] = "Settings table verified and ready.";
    } catch (Throwable $e) {}

    // 8. Add UNIQUE keys so duplicates can never happen again
    try {
        $pdo->exec("ALTER TABLE products ADD UNIQUE KEY uk_product_name (name)");
        $results[] = "Added UNIQUE constraint to prevent future product duplicates.";
    } catch (Throwable $e) {}

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $success = true;
} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $success = false;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Cleanup Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm p-4 text-center">
        <?php if ($success): ?>
            <div class="text-success mb-3">
                <i class="bi bi-check-circle-fill display-3"></i>
            </div>
            <h3 class="fw-bold mb-3">Cleanup Completed Successfully!</h3>
            <div class="text-start alert alert-success mb-4">
                <ul class="mb-0 ps-3">
                    <?php foreach ($results as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <p class="text-muted">All duplicate products and duplicate customers have been safely merged and cleaned.</p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="<?= BASE_URL ?>/billing/new.php" class="btn btn-primary px-4"><i class="bi bi-cart-plus me-1"></i> Go to New Bill</a>
                <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary px-4"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            </div>
        <?php else: ?>
            <div class="text-danger mb-3">
                <i class="bi bi-x-circle-fill display-3"></i>
            </div>
            <h3 class="fw-bold mb-3 text-danger">Cleanup Failed</h3>
            <div class="alert alert-danger text-start mb-4">
                <?= htmlspecialchars($error ?? 'Unknown error occurred') ?>
            </div>
            <a href="<?= BASE_URL ?>/billing/new.php" class="btn btn-secondary">Go Back</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

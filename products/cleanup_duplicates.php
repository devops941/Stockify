<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

try {
    $pdo->beginTransaction();

    // Find all products that have duplicate names
    $stmt = $pdo->query(
        "SELECT name, COUNT(*) as cnt 
         FROM products 
         GROUP BY TRIM(LOWER(name)) 
         HAVING cnt > 1"
    );
    $duplicateGroups = $stmt->fetchAll();

    $mergedCount = 0;

    foreach ($duplicateGroups as $group) {
        // Fetch all rows for this product name, ordered by updated_at DESC (latest first)
        $pStmt = $pdo->prepare(
            "SELECT * FROM products 
             WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) 
             ORDER BY updated_at DESC, product_id ASC"
        );
        $pStmt->execute([$group['name']]);
        $rows = $pStmt->fetchAll();

        if (count($rows) <= 1) continue;

        // Keep the first one (most recently updated or has lowest ID)
        $keep = $rows[0];
        $keepId = (int) $keep['product_id'];

        for ($i = 1; $i < count($rows); $i++) {
            $dupId = (int) $rows[$i]['product_id'];

            // If the duplicate has lower stock (was billed), preserve the updated lower stock
            if ((float) $rows[$i]['stock_qty'] < (float) $keep['stock_qty']) {
                $keep['stock_qty'] = $rows[$i]['stock_qty'];
                $updStock = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE product_id = ?");
                $updStock->execute([$keep['stock_qty'], $keepId]);
            }

            // Re-point any invoice_items referencing the duplicate
            $updItems = $pdo->prepare("UPDATE invoice_items SET product_id = ? WHERE product_id = ?");
            $updItems->execute([$keepId, $dupId]);

            // Re-point any purchases referencing the duplicate
            $updPurchases = $pdo->prepare("UPDATE purchases SET product_id = ? WHERE product_id = ?");
            $updPurchases->execute([$keepId, $dupId]);

            // Delete the duplicate product
            $delStmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $delStmt->execute([$dupId]);

            $mergedCount++;
        }
    }

    $pdo->commit();

    if ($mergedCount > 0) {
        flash('success', "Successfully cleaned up and merged {$mergedCount} duplicate product(s)!");
    } else {
        flash('info', "No duplicate products were found.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('danger', "Failed to clean duplicates: " . $e->getMessage());
}

header('Location: ' . BASE_URL . '/products/list.php');
exit;

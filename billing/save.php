<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/billing/new.php');
}

$customerId  = (int) ($_POST['customer_id'] ?? 0);
$paymentMode = $_POST['payment_mode'] ?? 'cash';
$productIds  = $_POST['product_id'] ?? [];
$qtys        = $_POST['qty'] ?? [];

if (!in_array($paymentMode, ['cash', 'upi', 'card'], true)) {
    $paymentMode = 'cash';
}

if (!$customerId || !is_array($productIds) || count($productIds) === 0) {
    flash('danger', 'Please select a customer and add at least one item.');
    redirect('/billing/new.php');
}

// Merge duplicate product rows (same product added twice) by summing quantities.
$lines = [];
foreach ($productIds as $i => $pid) {
    $pid = (int) $pid;
    $qty = (float) ($qtys[$i] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        continue;
    }
    $lines[$pid] = ($lines[$pid] ?? 0) + $qty;
}

if (!$lines) {
    flash('danger', 'Please add at least one valid item with a quantity greater than zero.');
    redirect('/billing/new.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = ?');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    if (!$customer) {
        throw new RuntimeException('Selected customer was not found.');
    }

    $intra = isIntraState($customer['state']);

    $taxableTotal = 0.0;
    $gstTotal     = 0.0;
    $itemRows     = [];

    foreach ($lines as $productId => $qty) {
        // Lock the product row so concurrent bills can't both oversell the same stock.
        $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new RuntimeException("Product #$productId was not found.");
        }
        if ((float) $product['stock_qty'] < $qty) {
            throw new RuntimeException(
                'Insufficient stock for "' . $product['name'] . '". Available: ' .
                rtrim(rtrim($product['stock_qty'], '0'), '.') . ' ' . $product['unit'] . '.'
            );
        }

        $rate      = (float) $product['selling_price'];
        $gstRate   = (float) $product['gst_rate'];
        $taxable   = round($qty * $rate, 2);
        $gstAmount = round($taxable * $gstRate / 100, 2);

        $taxableTotal += $taxable;
        $gstTotal     += $gstAmount;

        $itemRows[] = [
            'product_id'    => $productId,
            'qty'           => $qty,
            'rate'          => $rate,
            'taxable_value' => $taxable,
            'gst_rate'      => $gstRate,
            'gst_amount'    => $gstAmount,
        ];

        $upd = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE product_id = ? AND stock_qty >= ?');
        $upd->execute([$qty, $productId, $qty]);
        if ($upd->rowCount() === 0) {
            throw new RuntimeException('Insufficient stock for "' . $product['name'] . '".');
        }
    }

    $cgst = $intra ? round($gstTotal / 2, 2) : 0.0;
    $sgst = $intra ? round($gstTotal / 2, 2) : 0.0;
    $igst = $intra ? 0.0 : round($gstTotal, 2);

    $rawTotal   = $taxableTotal + $cgst + $sgst + $igst;
    $grandTotal = round($rawTotal);
    $roundOff   = round($grandTotal - $rawTotal, 2);

    $fy = getFinancialYear();
    $invoiceNo = nextInvoiceNumber($pdo, $fy);

    $stmt = $pdo->prepare(
        'INSERT INTO invoices (invoice_no, customer_id, invoice_date, taxable_total, cgst, sgst, igst, round_off, grand_total, payment_mode, created_by)
         VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $invoiceNo, $customerId, $taxableTotal, $cgst, $sgst, $igst, $roundOff, $grandTotal, $paymentMode,
        currentUser()['id'],
    ]);
    $invoiceId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO invoice_items (invoice_id, product_id, qty, rate, taxable_value, gst_rate, gst_amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($itemRows as $row) {
        $itemStmt->execute([
            $invoiceId, $row['product_id'], $row['qty'], $row['rate'],
            $row['taxable_value'], $row['gst_rate'], $row['gst_amount'],
        ]);
    }

    $pdo->commit();

    flash('success', 'Invoice ' . $invoiceNo . ' saved successfully.');
    redirect('/billing/view.php?id=' . $invoiceId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Could not save the bill. Please try again.');
    redirect('/billing/new.php');
}

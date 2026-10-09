<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$products = $pdo->query('SELECT product_id, name, unit, purchase_price FROM products ORDER BY name')->fetchAll();

$errors = [];
$form = ['supplier_name' => '', 'product_id' => '', 'qty' => '', 'rate' => '', 'purchase_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['supplier_name'] === '') $errors[] = 'Supplier name is required.';
    if (!$form['product_id']) $errors[] = 'Please select a product.';
    if (!is_numeric($form['qty']) || $form['qty'] <= 0) $errors[] = 'Quantity must be a positive number.';
    if (!is_numeric($form['rate']) || $form['rate'] < 0) $errors[] = 'Rate must be a valid non-negative number.';
    if ($form['purchase_date'] === '') $errors[] = 'Purchase date is required.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ? FOR UPDATE');
            $stmt->execute([$form['product_id']]);
            $product = $stmt->fetch();
            if (!$product) {
                throw new RuntimeException('Selected product was not found.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO purchases (supplier_name, product_id, qty, rate, purchase_date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $form['supplier_name'], $form['product_id'], $form['qty'], $form['rate'],
                $form['purchase_date'], currentUser()['id'],
            ]);

            $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ?, purchase_price = ? WHERE product_id = ?')
                ->execute([$form['qty'], $form['rate'], $form['product_id']]);

            $pdo->commit();
            flash('success', 'Purchase recorded and stock updated for "' . $product['name'] . '".');
            redirect('/purchases/list.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Could not record the purchase. Please try again.';
        }
    }
}

$pageTitle = 'Record Purchase';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card p-4">
      <h4 class="mb-3"><i class="bi bi-truck"></i> Record Purchase</h4>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">Supplier Name</label>
          <input type="text" name="supplier_name" class="form-control" value="<?= h($form['supplier_name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Product</label>
          <select name="product_id" class="form-select" required>
            <option value="">-- Select Product --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= (int) $p['product_id'] ?>" <?= (string) $form['product_id'] === (string) $p['product_id'] ? 'selected' : '' ?>>
                <?= h($p['name']) ?> (last purchase rate: <?= money($p['purchase_price']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Quantity Received</label>
            <input type="number" step="0.01" min="0.01" name="qty" class="form-control" value="<?= h($form['qty']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Purchase Rate (&#8377;)</label>
            <input type="number" step="0.01" min="0" name="rate" class="form-control" value="<?= h($form['rate']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="purchase_date" class="form-control" value="<?= h($form['purchase_date']) ?>" required>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Purchase</button>
          <a href="<?= BASE_URL ?>/purchases/list.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

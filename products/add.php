<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$errors = [];
$form = [
    'name' => '', 'hsn_code' => '', 'unit' => 'nos',
    'purchase_price' => '', 'selling_price' => '', 'gst_rate' => '0',
    'stock_qty' => '0', 'reorder_level' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['name'] === '') $errors[] = 'Product name is required.';
    if ($form['hsn_code'] === '') $errors[] = 'HSN code is required.';
    if (!is_numeric($form['purchase_price']) || $form['purchase_price'] < 0) $errors[] = 'Purchase price must be a valid non-negative number.';
    if (!is_numeric($form['selling_price']) || $form['selling_price'] < 0) $errors[] = 'Selling price must be a valid non-negative number.';
    if (!in_array((string) $form['gst_rate'], ['0', '5', '12', '18', '28'], true)) $errors[] = 'GST rate must be one of 0, 5, 12, 18 or 28 percent.';
    if (!is_numeric($form['stock_qty']) || $form['stock_qty'] < 0) $errors[] = 'Opening stock must be a valid non-negative number.';
    if (!is_numeric($form['reorder_level']) || $form['reorder_level'] < 0) $errors[] = 'Reorder level must be a valid non-negative number.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO products (name, hsn_code, unit, purchase_price, selling_price, gst_rate, stock_qty, reorder_level)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $form['name'], $form['hsn_code'], $form['unit'],
            $form['purchase_price'], $form['selling_price'], $form['gst_rate'],
            $form['stock_qty'], $form['reorder_level'],
        ]);
        flash('success', 'Product "' . $form['name'] . '" added.');
        redirect('/products/list.php');
    }
}

$pageTitle = 'Add Product';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card p-4">
      <h4 class="mb-3"><i class="bi bi-plus-lg"></i> Add Product</h4>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" value="<?= h($form['name']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">HSN Code</label>
            <input type="text" name="hsn_code" class="form-control" value="<?= h($form['hsn_code']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Unit</label>
            <select name="unit" class="form-select">
              <?php foreach (['nos', 'kg', 'litre', 'pack', 'box'] as $u): ?>
                <option value="<?= $u ?>" <?= $form['unit'] === $u ? 'selected' : '' ?>><?= $u ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Purchase Price (&#8377;)</label>
            <input type="number" step="0.01" min="0" name="purchase_price" class="form-control" value="<?= h($form['purchase_price']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Selling Price (&#8377;, before GST)</label>
            <input type="number" step="0.01" min="0" name="selling_price" class="form-control" value="<?= h($form['selling_price']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">GST Rate (%)</label>
            <select name="gst_rate" class="form-select">
              <?php foreach (['0', '5', '12', '18', '28'] as $r): ?>
                <option value="<?= $r ?>" <?= (string) $form['gst_rate'] === $r ? 'selected' : '' ?>><?= $r ?>%</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Opening Stock</label>
            <input type="number" step="0.01" min="0" name="stock_qty" class="form-control" value="<?= h($form['stock_qty']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Reorder Level</label>
            <input type="number" step="0.01" min="0" name="reorder_level" class="form-control" value="<?= h($form['reorder_level']) ?>">
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Product</button>
          <a href="<?= BASE_URL ?>/products/list.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

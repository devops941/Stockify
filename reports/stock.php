<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$products = $pdo->query('SELECT * FROM products ORDER BY name')->fetchAll();
$lowStock = array_filter($products, fn ($p) => $p['stock_qty'] <= $p['reorder_level']);

$stockValue = array_sum(array_map(fn ($p) => $p['stock_qty'] * $p['purchase_price'], $products));

$pageTitle = 'Stock Report';
require __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-box-seam"></i> Stock Report</h4>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card p-3"><div class="text-muted small">Total Products</div><div class="stat-value"><?= count($products) ?></div></div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card p-3"><div class="text-muted small">Low Stock Items</div><div class="stat-value <?= count($lowStock) ? 'text-danger' : '' ?>"><?= count($lowStock) ?></div></div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card p-3"><div class="text-muted small">Stock Value (at cost)</div><div class="stat-value">&#8377; <?= money($stockValue) ?></div></div>
  </div>
</div>

<?php if ($lowStock): ?>
<div class="card p-3 mb-3 border-danger">
  <h6 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Low Stock Alert - Reorder Needed</h6>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>Product</th><th>HSN</th><th class="text-end">Stock</th><th class="text-end">Reorder Level</th></tr></thead>
      <tbody>
      <?php foreach ($lowStock as $p): ?>
        <tr class="low-stock-row">
          <td><?= h($p['name']) ?></td>
          <td><?= h($p['hsn_code']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <?= h($p['unit']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card p-3">
  <h6>Current Stock - All Products</h6>
  <div class="table-responsive">
    <table class="table table-sm table-hover">
      <thead><tr><th>Product</th><th>HSN</th><th class="text-end">Stock</th><th class="text-end">Reorder Level</th><th class="text-end">Purchase Price</th><th class="text-end">Selling Price</th></tr></thead>
      <tbody>
      <?php foreach ($products as $p): $low = $p['stock_qty'] <= $p['reorder_level']; ?>
        <tr class="<?= $low ? 'low-stock-row' : '' ?>">
          <td><?= h($p['name']) ?></td>
          <td><?= h($p['hsn_code']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <?= h($p['unit']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
          <td class="text-end"><?= money($p['purchase_price']) ?></td>
          <td class="text-end"><?= money($p['selling_price']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

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
  <div class="col-sm-6 col-md-4">
    <div class="card stat-card sc-blue">
      <div class="stat-icon"><i class="bi bi-boxes"></i></div>
      <div class="stat-label">Total Products</div>
      <div class="stat-value"><?= count($products) ?></div>
      <div class="stat-sub">active items in catalog</div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4">
    <div class="card stat-card <?= count($lowStock) ? 'sc-red' : 'sc-green' ?>">
      <div class="stat-icon"><i class="bi bi-<?= count($lowStock) ? 'exclamation-triangle' : 'check-circle' ?>"></i></div>
      <div class="stat-label">Low Stock Items</div>
      <div class="stat-value <?= count($lowStock) ? 'text-danger' : '' ?>"><?= count($lowStock) ?></div>
      <div class="stat-sub"><?= count($lowStock) ? 'require urgent reorder' : 'all stock levels healthy' ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4">
    <div class="card stat-card sc-purple">
      <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-label">Stock Value (at cost)</div>
      <div class="stat-value">&#8377; <?= money($stockValue) ?></div>
      <div class="stat-sub">total inventory asset value</div>
    </div>
  </div>
</div>

<?php if ($lowStock): ?>
<div class="card p-3 mb-3 border-danger shadow-sm">
  <h6 class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Low Stock Alert - Reorder Needed</h6>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th><i class="bi bi-box-seam me-1"></i>Product</th>
          <th><i class="bi bi-upc me-1"></i>HSN</th>
          <th class="text-end"><i class="bi bi-boxes me-1"></i>Stock</th>
          <th class="text-end"><i class="bi bi-arrow-down-square me-1"></i>Reorder Level</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($lowStock as $p): ?>
        <tr class="low-stock-row">
          <td><span class="fw-semibold"><i class="bi bi-box-seam text-danger me-1"></i><?= h($p['name']) ?></span></td>
          <td><code><?= h($p['hsn_code']) ?></code></td>
          <td class="text-end text-danger fw-bold"><?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <?= h($p['unit']) ?></td>
          <td class="text-end text-muted"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card p-3">
  <h6 class="fw-bold mb-3"><i class="bi bi-card-checklist me-2 text-primary"></i>Current Stock - All Products</h6>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead>
        <tr>
          <th><i class="bi bi-box-seam me-1"></i>Product</th>
          <th><i class="bi bi-upc me-1"></i>HSN</th>
          <th class="text-end"><i class="bi bi-boxes me-1"></i>Stock</th>
          <th class="text-end"><i class="bi bi-arrow-down-square me-1"></i>Reorder Level</th>
          <th class="text-end"><i class="bi bi-cart-arrow-down me-1"></i>Purchase Price</th>
          <th class="text-end"><i class="bi bi-currency-rupee me-1"></i>Selling Price</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): $low = $p['stock_qty'] <= $p['reorder_level']; ?>
        <tr class="<?= $low ? 'low-stock-row' : '' ?>">
          <td><span class="fw-semibold"><i class="bi bi-box-seam text-muted me-1"></i><?= h($p['name']) ?></span></td>
          <td><code><?= h($p['hsn_code']) ?></code></td>
          <td class="text-end <?= $low ? 'text-danger fw-bold' : '' ?>">
            <?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <small><?= h($p['unit']) ?></small>
            <?= $low ? '<i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Low stock"></i>' : '' ?>
          </td>
          <td class="text-end text-muted"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
          <td class="text-end">&#8377; <?= money($p['purchase_price']) ?></td>
          <td class="text-end fw-semibold">&#8377; <?= money($p['selling_price']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">
          <i class="bi bi-box-seam text-muted fs-2 d-block mb-2"></i>
          No products in inventory.
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

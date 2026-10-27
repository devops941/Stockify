<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR hsn_code LIKE ? ORDER BY name");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $products = $stmt->fetchAll();
} else {
    $products = $pdo->query('SELECT * FROM products ORDER BY name')->fetchAll();
}

$pageTitle = 'Products';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-box-seam"></i> Product Master</h4>
  <a href="<?= BASE_URL ?>/products/add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
</div>

<div class="card p-3">
  <form method="get" class="row g-2 mb-3">
    <div class="col-sm-6 col-md-4">
      <input type="text" name="q" class="form-control" placeholder="Search by name or HSN code" value="<?= h($search) ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Search</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th><i class="bi bi-tag me-1"></i>Product Name</th>
          <th><i class="bi bi-upc me-1"></i>HSN</th>
          <th><i class="bi bi-rulers me-1"></i>Unit</th>
          <th class="text-end"><i class="bi bi-cart-arrow-down me-1"></i>Purchase Price</th>
          <th class="text-end"><i class="bi bi-currency-rupee me-1"></i>Selling Price</th>
          <th class="text-end"><i class="bi bi-percent me-1"></i>Tax %</th>
          <th class="text-end"><i class="bi bi-boxes me-1"></i>Stock</th>
          <th class="text-end"><i class="bi bi-arrow-down-square me-1"></i>Reorder Level</th>
          <th class="text-end"><i class="bi bi-gear me-1"></i>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): $low = $p['stock_qty'] <= $p['reorder_level']; ?>
        <tr class="<?= $low ? 'low-stock-row' : '' ?>">
          <td><span class="fw-semibold"><i class="bi bi-box-seam text-muted me-1"></i><?= h($p['name']) ?></span></td>
          <td><code><?= h($p['hsn_code']) ?></code></td>
          <td><span class="badge bg-light text-dark border"><?= h($p['unit']) ?></span></td>
          <td class="text-end">&#8377; <?= money($p['purchase_price']) ?></td>
          <td class="text-end fw-semibold">&#8377; <?= money($p['selling_price']) ?></td>
          <td class="text-end"><span class="badge bg-primary bg-opacity-10 text-primary"><?= rtrim(rtrim($p['gst_rate'], '0'), '.') ?>%</span></td>
          <td class="text-end <?= $low ? 'text-danger fw-bold' : '' ?>">
            <?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?>
            <?= $low ? '<i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Low stock alert"></i>' : '' ?>
          </td>
          <td class="text-end text-muted"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
          <td class="text-end text-nowrap">
            <a href="<?= BASE_URL ?>/products/edit.php?id=<?= (int) $p['product_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Product"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= BASE_URL ?>/products/delete.php" class="d-inline" onsubmit="return confirm('Delete this product?');">
              <input type="hidden" name="id" value="<?= (int) $p['product_id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">
          <i class="bi bi-box-seam text-muted fs-2 d-block mb-2"></i>
          No products found.
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

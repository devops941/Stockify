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
          <th>Name</th><th>HSN</th><th>Unit</th>
          <th class="text-end">Purchase Price</th><th class="text-end">Selling Price</th>
          <th class="text-end">GST %</th><th class="text-end">Stock</th><th class="text-end">Reorder Level</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): $low = $p['stock_qty'] <= $p['reorder_level']; ?>
        <tr class="<?= $low ? 'low-stock-row' : '' ?>">
          <td><?= h($p['name']) ?></td>
          <td><?= h($p['hsn_code']) ?></td>
          <td><?= h($p['unit']) ?></td>
          <td class="text-end"><?= money($p['purchase_price']) ?></td>
          <td class="text-end"><?= money($p['selling_price']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['gst_rate'], '0'), '.') ?>%</td>
          <td class="text-end"><?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <?= $low ? '<i class="bi bi-exclamation-triangle-fill text-danger" title="Low stock"></i>' : '' ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
          <td class="text-nowrap">
            <a href="<?= BASE_URL ?>/products/edit.php?id=<?= (int) $p['product_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= BASE_URL ?>/products/delete.php" class="d-inline" onsubmit="return confirm('Delete this product?');">
              <input type="hidden" name="id" value="<?= (int) $p['product_id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <tr><td colspan="9" class="text-center text-muted">No products found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

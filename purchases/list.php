<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$purchases = $pdo->query(
    "SELECT pu.*, p.name AS product_name, p.unit FROM purchases pu
     JOIN products p ON p.product_id = pu.product_id
     ORDER BY pu.purchase_id DESC"
)->fetchAll();

$pageTitle = 'Purchases';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-truck"></i> Purchase Entry</h4>
  <a href="<?= BASE_URL ?>/purchases/add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Record Purchase</a>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr><th>Date</th><th>Supplier</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr>
      </thead>
      <tbody>
      <?php foreach ($purchases as $p): ?>
        <tr>
          <td><?= h($p['purchase_date']) ?></td>
          <td><?= h($p['supplier_name']) ?></td>
          <td><?= h($p['product_name']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['qty'], '0'), '.') ?> <?= h($p['unit']) ?></td>
          <td class="text-end"><?= money($p['rate']) ?></td>
          <td class="text-end"><?= money($p['qty'] * $p['rate']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$purchases): ?>
        <tr><td colspan="6" class="text-center text-muted">No purchase records yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

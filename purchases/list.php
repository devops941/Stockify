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
        <tr>
          <th><i class="bi bi-calendar-event me-1"></i>Date</th>
          <th><i class="bi bi-building me-1"></i>Supplier</th>
          <th><i class="bi bi-box-seam me-1"></i>Product</th>
          <th class="text-end"><i class="bi bi-boxes me-1"></i>Qty</th>
          <th class="text-end"><i class="bi bi-currency-rupee me-1"></i>Rate</th>
          <th class="text-end"><i class="bi bi-cash-stack me-1"></i>Amount</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($purchases as $p): ?>
        <tr>
          <td><i class="bi bi-calendar3 text-muted me-1"></i><?= h($p['purchase_date']) ?></td>
          <td><span class="fw-semibold"><i class="bi bi-building text-muted me-1"></i><?= h($p['supplier_name']) ?></span></td>
          <td><i class="bi bi-box-seam text-muted me-1"></i><?= h($p['product_name']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($p['qty'], '0'), '.') ?> <?= h($p['unit']) ?></td>
          <td class="text-end">&#8377; <?= money($p['rate']) ?></td>
          <td class="text-end fw-semibold">&#8377; <?= money($p['qty'] * $p['rate']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$purchases): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">
          <i class="bi bi-truck text-muted fs-2 d-block mb-2"></i>
          No purchase records yet.
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

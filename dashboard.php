<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(grand_total),0) AS total FROM invoices WHERE invoice_date = ?");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

$monthStart = date('Y-m-01');
$stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total),0) AS total FROM invoices WHERE invoice_date BETWEEN ? AND ?");
$stmt->execute([$monthStart, $today]);
$monthTotal = $stmt->fetchColumn();

$lowStock = $pdo->query("SELECT * FROM products WHERE stock_qty <= reorder_level ORDER BY name")->fetchAll();

$recentInvoices = $pdo->query(
    "SELECT i.*, c.name AS customer_name FROM invoices i
     JOIN customers c ON c.customer_id = i.customer_id
     ORDER BY i.invoice_id DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="text-muted small">Today's Sales</div>
      <div class="stat-value">&#8377; <?= money($todayStats['total']) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="text-muted small">Bills Today</div>
      <div class="stat-value"><?= (int) $todayStats['cnt'] ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="text-muted small">This Month's Sales</div>
      <div class="stat-value">&#8377; <?= money($monthTotal) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="text-muted small">Low Stock Items</div>
      <div class="stat-value <?= count($lowStock) ? 'text-danger' : '' ?>"><?= count($lowStock) ?></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3">
      <h5 class="mb-3"><i class="bi bi-receipt-cutoff"></i> Recent Invoices</h5>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead><tr><th>Invoice No</th><th>Date</th><th>Customer</th><th class="text-end">Amount</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($recentInvoices as $inv): ?>
            <tr>
              <td><?= h($inv['invoice_no']) ?></td>
              <td><?= h($inv['invoice_date']) ?></td>
              <td><?= h($inv['customer_name']) ?></td>
              <td class="text-end">&#8377; <?= money($inv['grand_total']) ?></td>
              <td><a href="<?= BASE_URL ?>/billing/view.php?id=<?= (int) $inv['invoice_id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentInvoices): ?>
            <tr><td colspan="5" class="text-center text-muted">No invoices yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-3">
      <h5 class="mb-3 text-danger"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h5>
      <?php if (!$lowStock): ?>
        <p class="text-muted mb-0">All products are above their reorder level.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Reorder Level</th></tr></thead>
            <tbody>
            <?php foreach ($lowStock as $p): ?>
              <tr class="low-stock-row">
                <td><?= h($p['name']) ?></td>
                <td class="text-end"><?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <?= h($p['unit']) ?></td>
                <td class="text-end"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

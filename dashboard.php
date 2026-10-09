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

<div class="page-header">
  <h1 class="page-title">
    Dashboard
    <small>Welcome back, <?= h($_SESSION['full_name'] ?? 'User') ?></small>
  </h1>
  <a href="<?= BASE_URL ?>/billing/new.php" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> New Bill
  </a>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card sc-blue">
      <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
      <div class="stat-label">Today's Sales</div>
      <div class="stat-value">&#8377; <?= money($todayStats['total']) ?></div>
      <div class="stat-sub"><?= date('d M Y') ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card sc-green">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="stat-label">Bills Today</div>
      <div class="stat-value"><?= (int) $todayStats['cnt'] ?></div>
      <div class="stat-sub">invoices generated</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card sc-purple">
      <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
      <div class="stat-label">This Month</div>
      <div class="stat-value">&#8377; <?= money($monthTotal) ?></div>
      <div class="stat-sub"><?= date('F Y') ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card <?= count($lowStock) ? 'sc-red' : 'sc-green' ?>">
      <div class="stat-icon"><i class="bi bi-<?= count($lowStock) ? 'exclamation-triangle' : 'check-circle' ?>"></i></div>
      <div class="stat-label">Low Stock</div>
      <div class="stat-value"><?= count($lowStock) ?></div>
      <div class="stat-sub"><?= count($lowStock) ? 'items need reorder' : 'all stocked up' ?></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body p-0">
        <div class="section-header px-4 pt-3">
          <i class="bi bi-receipt-cutoff icon-blue"></i>
          Recent Invoices
          <a href="<?= BASE_URL ?>/billing/list.php" class="btn btn-sm btn-outline-primary ms-auto">View All</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Customer</th>
                <th class="text-end">Amount</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($recentInvoices as $inv): ?>
              <tr>
                <td><span class="fw-semibold text-primary"><?= h($inv['invoice_no']) ?></span></td>
                <td class="text-muted"><?= date('d M', strtotime($inv['invoice_date'])) ?></td>
                <td><?= h($inv['customer_name']) ?></td>
                <td class="text-end fw-semibold">&#8377; <?= money($inv['grand_total']) ?></td>
                <td class="text-end">
                  <a href="<?= BASE_URL ?>/billing/view.php?id=<?= (int) $inv['invoice_id'] ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recentInvoices): ?>
              <tr><td colspan="5">
                <div class="empty-state">
                  <i class="bi bi-receipt d-block"></i>
                  <p>No invoices yet. <a href="<?= BASE_URL ?>/billing/new.php">Create one</a></p>
                </div>
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-body p-0">
        <div class="section-header px-4 pt-3">
          <i class="bi bi-exclamation-triangle icon-red"></i>
          Low Stock Alerts
          <?php if ($lowStock): ?>
            <span class="badge bg-danger ms-1"><?= count($lowStock) ?></span>
          <?php endif; ?>
        </div>
        <?php if (!$lowStock): ?>
          <div class="empty-state">
            <i class="bi bi-check-circle-fill text-success d-block"></i>
            <p>All products are well stocked!</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Product</th>
                  <th class="text-end">Stock</th>
                  <th class="text-end">Min</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($lowStock as $p): ?>
                <tr class="low-stock-row">
                  <td class="fw-medium"><?= h($p['name']) ?></td>
                  <td class="text-end text-danger fw-semibold">
                    <?= rtrim(rtrim($p['stock_qty'], '0'), '.') ?> <small><?= h($p['unit']) ?></small>
                  </td>
                  <td class="text-end text-muted"><?= rtrim(rtrim($p['reorder_level'], '0'), '.') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

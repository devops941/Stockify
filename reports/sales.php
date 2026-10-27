<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare(
    'SELECT invoice_date, COUNT(*) AS bill_count, SUM(taxable_total) AS taxable, SUM(cgst+sgst+igst) AS tax, SUM(grand_total) AS total
     FROM invoices WHERE invoice_date BETWEEN ? AND ?
     GROUP BY invoice_date ORDER BY invoice_date DESC'
);
$stmt->execute([$from, $to]);
$daily = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT p.name, p.unit, SUM(ii.qty) AS qty_sold, SUM(ii.taxable_value) AS taxable, SUM(ii.gst_amount) AS gst_amount
     FROM invoice_items ii
     JOIN invoices i ON i.invoice_id = ii.invoice_id
     JOIN products p ON p.product_id = ii.product_id
     WHERE i.invoice_date BETWEEN ? AND ?
     GROUP BY ii.product_id, p.name, p.unit ORDER BY qty_sold DESC'
);
$stmt->execute([$from, $to]);
$byProduct = $stmt->fetchAll();

$grandTotal = array_sum(array_column($daily, 'total'));
$billCount  = array_sum(array_column($daily, 'bill_count'));

$pageTitle = 'Sales Report';
require __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-bar-chart"></i> Sales Report</h4>

<div class="card p-3 mb-3">
  <form method="get" class="row g-2">
    <div class="col-sm-4">
      <label class="form-label small mb-0"><i class="bi bi-calendar-event me-1"></i>From</label>
      <input type="date" name="from" class="form-control" value="<?= h($from) ?>">
    </div>
    <div class="col-sm-4">
      <label class="form-label small mb-0"><i class="bi bi-calendar-event me-1"></i>To</label>
      <input type="date" name="to" class="form-control" value="<?= h($to) ?>">
    </div>
    <div class="col-sm-2 d-flex align-items-end">
      <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-funnel me-1"></i> Apply</button>
    </div>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card sc-blue">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="stat-label">Bills</div>
      <div class="stat-value"><?= (int) $billCount ?></div>
      <div class="stat-sub">in selected period</div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card sc-green">
      <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
      <div class="stat-label">Total Sales</div>
      <div class="stat-value">&#8377; <?= money($grandTotal) ?></div>
      <div class="stat-sub">grand total</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <h6 class="fw-bold mb-3"><i class="bi bi-calendar-range me-2 text-primary"></i>Daily Summary</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead>
            <tr>
              <th><i class="bi bi-calendar3 me-1"></i>Date</th>
              <th class="text-end"><i class="bi bi-receipt me-1"></i>Bills</th>
              <th class="text-end"><i class="bi bi-cash me-1"></i>Taxable</th>
              <th class="text-end"><i class="bi bi-percent me-1"></i>Tax</th>
              <th class="text-end"><i class="bi bi-currency-rupee me-1"></i>Total</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($daily as $d): ?>
            <tr>
              <td><i class="bi bi-calendar-event text-muted me-1"></i><?= h($d['invoice_date']) ?></td>
              <td class="text-end"><?= (int) $d['bill_count'] ?></td>
              <td class="text-end"><?= money($d['taxable']) ?></td>
              <td class="text-end"><?= money($d['tax']) ?></td>
              <td class="text-end fw-semibold">&#8377; <?= money($d['total']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$daily): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">
              <i class="bi bi-calendar-x text-muted fs-2 d-block mb-2"></i>
              No sales in this period.
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2 text-primary"></i>Product-wise Sales</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead>
            <tr>
              <th><i class="bi bi-box-seam me-1"></i>Product</th>
              <th class="text-end"><i class="bi bi-boxes me-1"></i>Qty Sold</th>
              <th class="text-end"><i class="bi bi-cash me-1"></i>Taxable</th>
              <th class="text-end"><i class="bi bi-percent me-1"></i>Tax</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($byProduct as $p): ?>
            <tr>
              <td><span class="fw-medium"><i class="bi bi-box-seam text-muted me-1"></i><?= h($p['name']) ?></span></td>
              <td class="text-end"><?= rtrim(rtrim($p['qty_sold'], '0'), '.') ?> <?= h($p['unit']) ?></td>
              <td class="text-end">&#8377; <?= money($p['taxable']) ?></td>
              <td class="text-end">&#8377; <?= money($p['gst_amount']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$byProduct): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">
              <i class="bi bi-pie-chart text-muted fs-2 d-block mb-2"></i>
              No sales in this period.
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

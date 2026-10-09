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
     GROUP BY ii.product_id ORDER BY qty_sold DESC'
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
      <label class="form-label small mb-0">From</label>
      <input type="date" name="from" class="form-control" value="<?= h($from) ?>">
    </div>
    <div class="col-sm-4">
      <label class="form-label small mb-0">To</label>
      <input type="date" name="to" class="form-control" value="<?= h($to) ?>">
    </div>
    <div class="col-sm-2 d-flex align-items-end">
      <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-search"></i> Apply</button>
    </div>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card p-3"><div class="text-muted small">Bills</div><div class="stat-value"><?= (int) $billCount ?></div></div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card stat-card p-3"><div class="text-muted small">Total Sales</div><div class="stat-value">&#8377; <?= money($grandTotal) ?></div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <h6>Daily Summary</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead><tr><th>Date</th><th class="text-end">Bills</th><th class="text-end">Taxable</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
          <tbody>
          <?php foreach ($daily as $d): ?>
            <tr>
              <td><?= h($d['invoice_date']) ?></td>
              <td class="text-end"><?= (int) $d['bill_count'] ?></td>
              <td class="text-end"><?= money($d['taxable']) ?></td>
              <td class="text-end"><?= money($d['tax']) ?></td>
              <td class="text-end fw-semibold"><?= money($d['total']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$daily): ?><tr><td colspan="5" class="text-center text-muted">No sales in this period.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <h6>Product-wise Sales</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead><tr><th>Product</th><th class="text-end">Qty Sold</th><th class="text-end">Taxable</th><th class="text-end">GST</th></tr></thead>
          <tbody>
          <?php foreach ($byProduct as $p): ?>
            <tr>
              <td><?= h($p['name']) ?></td>
              <td class="text-end"><?= rtrim(rtrim($p['qty_sold'], '0'), '.') ?> <?= h($p['unit']) ?></td>
              <td class="text-end"><?= money($p['taxable']) ?></td>
              <td class="text-end"><?= money($p['gst_amount']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$byProduct): ?><tr><td colspan="4" class="text-center text-muted">No sales in this period.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

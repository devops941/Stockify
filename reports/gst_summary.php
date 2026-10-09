<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT ii.gst_rate,
            SUM(ii.taxable_value) AS taxable,
            SUM(CASE WHEN i.igst = 0 THEN ii.gst_amount / 2 ELSE 0 END) AS cgst,
            SUM(CASE WHEN i.igst = 0 THEN ii.gst_amount / 2 ELSE 0 END) AS sgst,
            SUM(CASE WHEN i.igst > 0 THEN ii.gst_amount ELSE 0 END) AS igst,
            SUM(ii.gst_amount) AS total_gst
     FROM invoice_items ii
     JOIN invoices i ON i.invoice_id = ii.invoice_id
     WHERE i.invoice_date BETWEEN ? AND ?
     GROUP BY ii.gst_rate ORDER BY ii.gst_rate"
);
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

$totals = [
    'taxable' => array_sum(array_column($rows, 'taxable')),
    'cgst'    => array_sum(array_column($rows, 'cgst')),
    'sgst'    => array_sum(array_column($rows, 'sgst')),
    'igst'    => array_sum(array_column($rows, 'igst')),
    'total_gst' => array_sum(array_column($rows, 'total_gst')),
];

$pageTitle = 'GST Summary';
require __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-receipt"></i> GST Slab-wise Summary</h4>

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

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr><th>GST Rate</th><th class="text-end">Taxable Value</th><th class="text-end">CGST</th><th class="text-end">SGST</th><th class="text-end">IGST</th><th class="text-end">Total GST</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= rtrim(rtrim($r['gst_rate'], '0'), '.') ?>%</td>
          <td class="text-end"><?= money($r['taxable']) ?></td>
          <td class="text-end"><?= money($r['cgst']) ?></td>
          <td class="text-end"><?= money($r['sgst']) ?></td>
          <td class="text-end"><?= money($r['igst']) ?></td>
          <td class="text-end fw-semibold"><?= money($r['total_gst']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">No GST data in this period.</td></tr><?php endif; ?>
      </tbody>
      <?php if ($rows): ?>
      <tfoot>
        <tr class="fw-bold table-light">
          <td>Total</td>
          <td class="text-end"><?= money($totals['taxable']) ?></td>
          <td class="text-end"><?= money($totals['cgst']) ?></td>
          <td class="text-end"><?= money($totals['sgst']) ?></td>
          <td class="text-end"><?= money($totals['igst']) ?></td>
          <td class="text-end"><?= money($totals['total_gst']) ?></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

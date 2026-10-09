<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT i.*, c.name AS customer_name, c.phone, c.state, c.gstin
     FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
     WHERE i.invoice_id = ?'
);
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    flash('danger', 'Invoice not found.');
    redirect('/billing/list.php');
}

$stmt = $pdo->prepare(
    'SELECT ii.*, p.name, p.hsn_code, p.unit FROM invoice_items ii
     JOIN products p ON p.product_id = ii.product_id
     WHERE ii.invoice_id = ?'
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$pageTitle = 'Invoice ' . $invoice['invoice_no'];
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h4 class="mb-0"><i class="bi bi-receipt"></i> Invoice <?= h($invoice['invoice_no']) ?></h4>
  <div>
    <a href="<?= BASE_URL ?>/billing/pdf.php?id=<?= $id ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
    <a href="<?= BASE_URL ?>/billing/list.php" class="btn btn-outline-secondary">Back to Invoices</a>
  </div>
</div>

<div class="card p-4">
  <div class="row mb-3">
    <div class="col-md-6">
      <h5><?= h(SHOP_NAME) ?></h5>
      <div class="text-muted small"><?= h(SHOP_ADDRESS) ?></div>
      <div class="text-muted small">GSTIN: <?= h(SHOP_GSTIN) ?> &middot; State: <?= h(SHOP_STATE) ?></div>
    </div>
    <div class="col-md-6 text-md-end">
      <div><strong>Invoice No:</strong> <?= h($invoice['invoice_no']) ?></div>
      <div><strong>Date:</strong> <?= h($invoice['invoice_date']) ?></div>
      <div><strong>Payment Mode:</strong> <?= h(strtoupper($invoice['payment_mode'])) ?></div>
    </div>
  </div>

  <div class="mb-3">
    <strong>Bill To:</strong> <?= h($invoice['customer_name']) ?>
    <?php if ($invoice['phone']): ?> &middot; <?= h($invoice['phone']) ?><?php endif; ?><br>
    State: <?= h($invoice['state']) ?>
    <?php if ($invoice['gstin']): ?> &middot; GSTIN: <?= h($invoice['gstin']) ?><?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-sm">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Product</th><th>HSN</th><th class="text-end">Qty</th><th class="text-end">Rate</th>
          <th class="text-end">Taxable</th><th class="text-end">GST%</th><th class="text-end">GST Amt</th><th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i => $it): $lineTotal = $it['taxable_value'] + $it['gst_amount']; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= h($it['name']) ?></td>
          <td><?= h($it['hsn_code']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($it['qty'], '0'), '.') ?> <?= h($it['unit']) ?></td>
          <td class="text-end"><?= money($it['rate']) ?></td>
          <td class="text-end"><?= money($it['taxable_value']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($it['gst_rate'], '0'), '.') ?>%</td>
          <td class="text-end"><?= money($it['gst_amount']) ?></td>
          <td class="text-end"><?= money($lineTotal) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="row justify-content-end">
    <div class="col-md-5">
      <table class="table table-sm mb-0">
        <tr><td>Taxable Total</td><td class="text-end"><?= money($invoice['taxable_total']) ?></td></tr>
        <?php if ($invoice['cgst'] > 0 || $invoice['sgst'] > 0): ?>
          <tr><td>CGST</td><td class="text-end"><?= money($invoice['cgst']) ?></td></tr>
          <tr><td>SGST</td><td class="text-end"><?= money($invoice['sgst']) ?></td></tr>
        <?php else: ?>
          <tr><td>IGST</td><td class="text-end"><?= money($invoice['igst']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Round Off</td><td class="text-end"><?= money($invoice['round_off']) ?></td></tr>
        <tr class="fw-bold fs-5"><td>Grand Total</td><td class="text-end">&#8377; <?= money($invoice['grand_total']) ?></td></tr>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

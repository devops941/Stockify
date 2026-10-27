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
    <a href="<?= BASE_URL ?>/billing/pdf.php?id=<?= $id ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i> Download PDF</a>
    <a href="<?= BASE_URL ?>/billing/list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Invoices</a>
  </div>
</div>

<div class="card p-4">
  <div class="row mb-3">
    <div class="col-md-6">
      <h5 class="fw-bold text-primary"><i class="bi bi-shop me-2"></i><?= h(shopSetting('shop_name')) ?></h5>
      <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= h(shopSetting('shop_address')) ?></div>
      <div class="text-muted small"><i class="bi bi-shield-check me-1"></i>GSTIN: <?= h(shopSetting('shop_gstin')) ?> &middot; <i class="bi bi-geo-fill me-1"></i>State: <?= h(shopSetting('shop_state')) ?></div>
    </div>
    <div class="col-md-6 text-md-end">
      <div><strong><i class="bi bi-receipt me-1"></i>Invoice No:</strong> <?= h($invoice['invoice_no']) ?></div>
      <div><strong><i class="bi bi-calendar3 me-1"></i>Date:</strong> <?= h($invoice['invoice_date']) ?></div>
      <div><strong><i class="bi bi-credit-card me-1"></i>Payment Mode:</strong> <?= h(strtoupper($invoice['payment_mode'])) ?></div>
    </div>
  </div>

  <div class="mb-3">
    <strong><i class="bi bi-person-circle me-1 text-primary"></i>Bill To:</strong> <?= h($invoice['customer_name']) ?>
    <?php if ($invoice['phone']): ?> &middot; <i class="bi bi-telephone me-1"></i><?= h($invoice['phone']) ?><?php endif; ?><br>
    <i class="bi bi-geo-alt me-1"></i>State: <?= h($invoice['state']) ?>
    <?php if ($invoice['gstin']): ?> &middot; <i class="bi bi-shield-check me-1"></i>GSTIN: <?= h($invoice['gstin']) ?><?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th><i class="bi bi-box-seam me-1"></i>Product</th>
          <th><i class="bi bi-upc me-1"></i>HSN</th>
          <th class="text-end"><i class="bi bi-boxes me-1"></i>Qty</th>
          <th class="text-end"><i class="bi bi-currency-rupee me-1"></i>Rate</th>
          <th class="text-end"><i class="bi bi-cash me-1"></i>Taxable</th>
          <th class="text-end"><i class="bi bi-percent me-1"></i>Tax%</th>
          <th class="text-end"><i class="bi bi-percent me-1"></i>Tax Amt</th>
          <th class="text-end"><i class="bi bi-currency-rupee me-1"></i>Total</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i => $it): $lineTotal = $it['taxable_value'] + $it['gst_amount']; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="fw-medium"><?= h($it['name']) ?></td>
          <td><?= h($it['hsn_code']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($it['qty'], '0'), '.') ?> <?= h($it['unit']) ?></td>
          <td class="text-end"><?= money($it['rate']) ?></td>
          <td class="text-end"><?= money($it['taxable_value']) ?></td>
          <td class="text-end"><?= rtrim(rtrim($it['gst_rate'], '0'), '.') ?>%</td>
          <td class="text-end"><?= money($it['gst_amount']) ?></td>
          <td class="text-end fw-semibold"><?= money($lineTotal) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="row justify-content-end">
    <div class="col-md-5">
      <table class="table table-sm mb-0">
        <tr><td class="text-muted"><i class="bi bi-cash me-1"></i>Taxable Total</td><td class="text-end"><?= money($invoice['taxable_total']) ?></td></tr>
        <?php if ($invoice['cgst'] > 0 || $invoice['sgst'] > 0): ?>
          <tr><td class="text-muted"><i class="bi bi-receipt me-1"></i>Tax (CGST+SGST)</td><td class="text-end"><?= money($invoice['cgst'] + $invoice['sgst']) ?></td></tr>
        <?php else: ?>
          <tr><td class="text-muted"><i class="bi bi-globe me-1"></i>IGST</td><td class="text-end"><?= money($invoice['igst']) ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted"><i class="bi bi-arrow-left-right me-1"></i>Round Off</td><td class="text-end"><?= money($invoice['round_off']) ?></td></tr>
        <tr class="fw-bold fs-5 border-top"><td><i class="bi bi-currency-rupee me-1"></i>Grand Total</td><td class="text-end text-primary">&#8377; <?= money($invoice['grand_total']) ?></td></tr>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

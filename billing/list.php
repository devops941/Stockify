<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');
$q    = trim($_GET['q'] ?? '');

$conditions = [];
$params = [];

if ($from !== '') { $conditions[] = 'i.invoice_date >= ?'; $params[] = $from; }
if ($to !== '')   { $conditions[] = 'i.invoice_date <= ?'; $params[] = $to; }
if ($q !== '')    { $conditions[] = '(i.invoice_no LIKE ? OR c.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$stmt = $pdo->prepare(
    "SELECT i.*, c.name AS customer_name FROM invoices i
     JOIN customers c ON c.customer_id = i.customer_id
     $where
     ORDER BY i.invoice_id DESC"
);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$pageTitle = 'Invoices';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Invoices</h4>
  <a href="<?= BASE_URL ?>/billing/new.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Bill</a>
</div>

<div class="card p-3">
  <form method="get" class="row g-2 mb-3">
    <div class="col-sm-3">
      <label class="form-label small mb-0">From</label>
      <input type="date" name="from" class="form-control" value="<?= h($from) ?>">
    </div>
    <div class="col-sm-3">
      <label class="form-label small mb-0">To</label>
      <input type="date" name="to" class="form-control" value="<?= h($to) ?>">
    </div>
    <div class="col-sm-4">
      <label class="form-label small mb-0">Search Invoice No / Customer</label>
      <input type="text" name="q" class="form-control" value="<?= h($q) ?>">
    </div>
    <div class="col-sm-2 d-flex align-items-end">
      <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr><th>Invoice No</th><th>Date</th><th>Customer</th><th>Payment</th><th class="text-end">Taxable</th><th class="text-end">Tax</th><th class="text-end">Grand Total</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($invoices as $inv): $tax = $inv['cgst'] + $inv['sgst'] + $inv['igst']; ?>
        <tr>
          <td><?= h($inv['invoice_no']) ?></td>
          <td><?= h($inv['invoice_date']) ?></td>
          <td><?= h($inv['customer_name']) ?></td>
          <td class="text-uppercase"><?= h($inv['payment_mode']) ?></td>
          <td class="text-end"><?= money($inv['taxable_total']) ?></td>
          <td class="text-end"><?= money($tax) ?></td>
          <td class="text-end fw-semibold">&#8377; <?= money($inv['grand_total']) ?></td>
          <td class="text-nowrap">
            <a href="<?= BASE_URL ?>/billing/view.php?id=<?= (int) $inv['invoice_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            <a href="<?= BASE_URL ?>/billing/pdf.php?id=<?= (int) $inv['invoice_id'] ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$invoices): ?>
        <tr><td colspan="8" class="text-center text-muted">No invoices found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

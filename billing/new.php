<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$products  = $pdo->query('SELECT product_id, name, hsn_code, unit, selling_price, gst_rate, stock_qty FROM products ORDER BY name')->fetchAll();
$customers = $pdo->query('SELECT customer_id, name, phone, state FROM customers ORDER BY name')->fetchAll();

$selectedCustomerId = (int) ($_GET['customer_id'] ?? 0);

$pageTitle = 'New Bill';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-cart-plus"></i> New Bill</h4>
  <div class="text-muted small">Shop State: <strong><?= h(SHOP_STATE) ?></strong> &middot; FY <?= h(getFinancialYear()) ?></div>
</div>

<form method="post" action="<?= BASE_URL ?>/billing/save.php" id="billForm">
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card p-3 h-100">
        <label class="form-label">Customer</label>
        <div class="input-group">
          <select name="customer_id" id="customerSelect" class="form-select" required>
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int) $c['customer_id'] ?>" data-state="<?= h($c['state']) ?>"
                <?= $selectedCustomerId === (int) $c['customer_id'] ? 'selected' : '' ?>>
                <?= h($c['name']) ?><?= $c['phone'] ? ' - ' . h($c['phone']) : '' ?> (<?= h($c['state']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <a href="<?= BASE_URL ?>/customers/add.php?return_to=billing" class="btn btn-outline-secondary" title="Add new customer"><i class="bi bi-person-plus"></i></a>
        </div>
        <div class="form-text" id="taxTypeHint">Select a customer to see tax type.</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3 h-100">
        <label class="form-label">Payment Mode</label>
        <select name="payment_mode" class="form-select">
          <option value="cash">Cash</option>
          <option value="upi">UPI</option>
          <option value="card">Card</option>
        </select>
        <label class="form-label mt-2">Invoice Date</label>
        <input type="text" class="form-control" value="<?= date('d-M-Y') ?>" disabled>
      </div>
    </div>
  </div>

  <div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Items</h6>
      <button type="button" class="btn btn-sm btn-primary" id="addRowBtn"><i class="bi bi-plus-lg"></i> Add Row</button>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle" id="itemsTable">
        <thead>
          <tr>
            <th style="min-width:220px;">Product</th>
            <th>HSN</th>
            <th style="width:90px;">Qty</th>
            <th class="text-end">Rate</th>
            <th class="text-end">Taxable</th>
            <th class="text-end">GST%</th>
            <th class="text-end">GST Amt</th>
            <th class="text-end">Line Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
      </table>
    </div>
  </div>

  <div class="row justify-content-end">
    <div class="col-md-5">
      <div class="card p-3">
        <table class="table table-sm mb-0">
          <tr><td>Taxable Total</td><td class="text-end" id="sumTaxable">0.00</td></tr>
          <tr id="rowCgst"><td>CGST</td><td class="text-end" id="sumCgst">0.00</td></tr>
          <tr id="rowSgst"><td>SGST</td><td class="text-end" id="sumSgst">0.00</td></tr>
          <tr id="rowIgst"><td>IGST</td><td class="text-end" id="sumIgst">0.00</td></tr>
          <tr><td>Round Off</td><td class="text-end" id="sumRound">0.00</td></tr>
          <tr class="fw-bold fs-5"><td>Grand Total</td><td class="text-end" id="sumGrand">&#8377; 0.00</td></tr>
        </table>
      </div>
      <button type="submit" class="btn btn-success w-100 mt-3 py-2"><i class="bi bi-save"></i> Save &amp; Generate Invoice</button>
    </div>
  </div>
</form>

<script>
  window.PRODUCTS = <?= json_encode($products, JSON_NUMERIC_CHECK) ?>;
  window.SHOP_STATE = <?= json_encode(SHOP_STATE) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/billing.js"></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

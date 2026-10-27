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
        <label class="form-label"><i class="bi bi-person me-1"></i>Customer</label>
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
        <div class="form-text" id="taxTypeHint"><i class="bi bi-info-circle me-1"></i>Select a customer to see tax type.</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3 h-100">
        <label class="form-label"><i class="bi bi-credit-card me-1"></i>Payment Mode</label>
        <select name="payment_mode" class="form-select">
          <option value="cash">Cash</option>
          <option value="upi">UPI</option>
          <option value="card">Card</option>
        </select>
        <label class="form-label mt-2"><i class="bi bi-calendar-event me-1"></i>Invoice Date</label>
        <input type="text" class="form-control" value="<?= date('d-M-Y') ?>" disabled>
      </div>
    </div>
  </div>

  <div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0"><i class="bi bi-box-seam me-1"></i>Items</h6>
      <button type="button" class="btn btn-sm btn-primary" id="addRowBtn"><i class="bi bi-plus-lg me-1"></i> Add Row</button>
    </div>

    <!-- Duplicate Product Alert Banner -->
    <div id="duplicateAlert" class="alert alert-warning py-2 px-3 mb-2 d-none align-items-center justify-content-between" role="alert">
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-6"></i>
        <span id="duplicateAlertMsg">This product is already added in the bill. Please increase its quantity instead.</span>
      </div>
      <button type="button" class="btn-close btn-sm ms-2" id="duplicateAlertClose" aria-label="Close"></button>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle billing-items-table" id="itemsTable">
        <thead>
          <tr>
            <th class="col-product"><i class="bi bi-box-seam me-1"></i>Product</th>
            <th class="col-hsn"><i class="bi bi-upc me-1"></i>HSN</th>
            <th class="col-qty text-center"><i class="bi bi-boxes me-1"></i>Qty</th>
            <th class="col-rate text-end"><i class="bi bi-currency-rupee me-1"></i>Rate</th>
            <th class="col-taxable text-end"><i class="bi bi-cash me-1"></i>Taxable</th>
            <th class="col-gstrate text-end"><i class="bi bi-percent me-1"></i>Tax%</th>
            <th class="col-gstamt text-end"><i class="bi bi-percent me-1"></i>Tax Amt</th>
            <th class="col-linetotal text-end"><i class="bi bi-currency-rupee me-1"></i>Line Total</th>
            <th class="col-action text-center"></th>
          </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
      </table>
    </div>
  </div>

  <div class="row justify-content-end">
    <div class="col-md-5">
      <div class="card p-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-1"></i>Bill Summary</h6>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted"><i class="bi bi-cash me-1"></i>Taxable Total</td><td class="text-end" id="sumTaxable">0.00</td></tr>
          <tr id="rowTax"><td class="text-muted"><i class="bi bi-receipt me-1"></i>Tax (CGST+SGST)</td><td class="text-end" id="sumTax">0.00</td></tr>
          <tr id="rowIgst"><td class="text-muted"><i class="bi bi-globe me-1"></i>IGST</td><td class="text-end" id="sumIgst">0.00</td></tr>
          <tr><td class="text-muted"><i class="bi bi-arrow-left-right me-1"></i>Round Off</td><td class="text-end" id="sumRound">0.00</td></tr>
          <tr class="fw-bold fs-5 border-top"><td><i class="bi bi-currency-rupee me-1"></i>Grand Total</td><td class="text-end text-primary" id="sumGrand">&#8377; 0.00</td></tr>
        </table>
      </div>
      <button type="submit" class="btn btn-success w-100 mt-3 py-2"><i class="bi bi-check-circle me-1"></i> Save &amp; Generate Invoice</button>
    </div>
  </div>
</form>

<script>
  window.PRODUCTS = <?= json_encode($products, JSON_NUMERIC_CHECK) ?>;
  window.SHOP_STATE = <?= json_encode(shopSetting('shop_state') ?: SHOP_STATE) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/billing.js?v=<?= filemtime(__DIR__ . '/../assets/js/billing.js') ?>"></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

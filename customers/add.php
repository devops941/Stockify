<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$errors = [];
$form = ['name' => '', 'phone' => '', 'state' => SHOP_STATE, 'gstin' => ''];
$returnTo = $_GET['return_to'] ?? $_POST['return_to'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['name', 'phone', 'state', 'gstin'] as $key) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['name'] === '') $errors[] = 'Customer name is required.';
    if ($form['state'] === '') $errors[] = 'State is required (used to decide CGST/SGST vs IGST).';

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO customers (name, phone, state, gstin) VALUES (?, ?, ?, ?)');
        $stmt->execute([$form['name'], $form['phone'] ?: null, $form['state'], $form['gstin'] ?: null]);
        $newId = (int) $pdo->lastInsertId();
        flash('success', 'Customer "' . $form['name'] . '" added.');
        if ($returnTo === 'billing') {
            redirect('/billing/new.php?customer_id=' . $newId);
        }
        redirect('/customers/list.php');
    }
}

$pageTitle = 'Add Customer';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card p-4">
      <h4 class="mb-3"><i class="bi bi-person-plus-fill text-primary me-2"></i> Add Customer</h4>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-person me-1"></i>Name</label>
          <input type="text" name="name" class="form-control" value="<?= h($form['name']) ?>" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-telephone me-1"></i>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= h($form['phone']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-geo-alt me-1"></i>State</label>
          <input type="text" name="state" class="form-control" value="<?= h($form['state']) ?>" required>
          <div class="form-text"><i class="bi bi-info-circle me-1"></i>Shop state is <strong><?= h(shopSetting('shop_state')) ?></strong>. Same state &rarr; Tax (intra-state), different state &rarr; IGST.</div>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-file-earmark-text me-1"></i>GSTIN <span class="text-muted fw-normal">(optional)</span></label>
          <input type="text" name="gstin" class="form-control" value="<?= h($form['gstin']) ?>" maxlength="15" style="text-transform:uppercase">
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Customer</button>
          <a href="<?= BASE_URL ?>/customers/list.php" class="btn btn-outline-secondary"><i class="bi bi-x me-1"></i>Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

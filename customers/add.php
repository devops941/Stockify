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
      <h4 class="mb-3"><i class="bi bi-plus-lg"></i> Add Customer</h4>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" value="<?= h($form['name']) ?>" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= h($form['phone']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">State</label>
          <input type="text" name="state" class="form-control" value="<?= h($form['state']) ?>" required>
          <div class="form-text">Shop state is <?= h(SHOP_STATE) ?>. Same state &rarr; CGST+SGST, different state &rarr; IGST.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">GSTIN (optional)</label>
          <input type="text" name="gstin" class="form-control" value="<?= h($form['gstin']) ?>" maxlength="15">
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Customer</button>
          <a href="<?= BASE_URL ?>/customers/list.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

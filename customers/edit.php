<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    flash('danger', 'Customer not found.');
    redirect('/customers/list.php');
}

$errors = [];
$form = $customer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['name', 'phone', 'state', 'gstin'] as $key) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['name'] === '') $errors[] = 'Customer name is required.';
    if ($form['state'] === '') $errors[] = 'State is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE customers SET name=?, phone=?, state=?, gstin=? WHERE customer_id=?');
        $stmt->execute([$form['name'], $form['phone'] ?: null, $form['state'], $form['gstin'] ?: null, $id]);
        flash('success', 'Customer updated.');
        redirect('/customers/list.php');
    }
}

$pageTitle = 'Edit Customer';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card p-4">
      <h4 class="mb-3"><i class="bi bi-person-gear text-primary me-2"></i> Edit Customer</h4>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-person me-1"></i>Name</label>
          <input type="text" name="name" class="form-control" value="<?= h($form['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-telephone me-1"></i>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= h($form['phone']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-geo-alt me-1"></i>State</label>
          <input type="text" name="state" class="form-control" value="<?= h($form['state']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-file-earmark-text me-1"></i>GSTIN <span class="text-muted fw-normal">(optional)</span></label>
          <input type="text" name="gstin" class="form-control" value="<?= h($form['gstin']) ?>" maxlength="15" style="text-transform:uppercase">
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Customer</button>
          <a href="<?= BASE_URL ?>/customers/list.php" class="btn btn-outline-secondary"><i class="bi bi-x me-1"></i>Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

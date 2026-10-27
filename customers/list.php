<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name');
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $customers = $stmt->fetchAll();
} else {
    $customers = $pdo->query('SELECT * FROM customers ORDER BY name')->fetchAll();
}

$pageTitle = 'Customers';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-people"></i> Customer Master</h4>
  <a href="<?= BASE_URL ?>/customers/add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Customer</a>
</div>

<div class="card p-3">
  <form method="get" class="row g-2 mb-3">
    <div class="col-sm-6 col-md-4">
      <input type="text" name="q" class="form-control" placeholder="Search by name or phone" value="<?= h($search) ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Search</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th><i class="bi bi-person me-1"></i>Name</th>
          <th><i class="bi bi-telephone me-1"></i>Phone</th>
          <th><i class="bi bi-geo-alt me-1"></i>State</th>
          <th><i class="bi bi-file-earmark-text me-1"></i>GSTIN</th>
          <?php if (isAdmin()): ?><th class="text-end"><i class="bi bi-gear me-1"></i>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><span class="fw-semibold"><i class="bi bi-person-circle text-muted me-1"></i><?= h($c['name']) ?></span></td>
          <td><?= $c['phone'] ? '<i class="bi bi-telephone-fill text-muted me-1"></i>' . h($c['phone']) : '<span class="text-muted">—</span>' ?></td>
          <td><i class="bi bi-geo-fill text-muted me-1"></i><?= h($c['state']) ?></td>
          <td><?= $c['gstin'] ? '<code class="small">' . h($c['gstin']) . '</code>' : '<span class="text-muted">—</span>' ?></td>
          <?php if (isAdmin()): ?>
          <td class="text-end text-nowrap">
            <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= (int) $c['customer_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Customer"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= BASE_URL ?>/customers/delete.php" class="d-inline" onsubmit="return confirm('Delete this customer?');">
              <input type="hidden" name="id" value="<?= (int) $c['customer_id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Customer"><i class="bi bi-trash"></i></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">
          <i class="bi bi-people text-muted fs-2 d-block mb-2"></i>
          No customers found.
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

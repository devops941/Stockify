<?php
$user = currentUser();
$scriptPath = $_SERVER['SCRIPT_NAME'];
$isActive = fn (string $needle): bool => str_contains($scriptPath, $needle);
$initials = strtoupper(substr(trim($user['name']) ?: '?', 0, 1));
?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand app-brand" href="<?= BASE_URL ?>/dashboard.php">
      <span class="app-brand-badge"><i class="bi bi-receipt-cutoff"></i></span>
      <span class="app-brand-text">
        <span class="app-brand-name"><?= h(SHOP_NAME) ?></span>
        <span class="app-brand-tagline">GST Billing &amp; Stock</span>
      </span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?= $isActive('/dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/billing/new.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/billing/new.php"><i class="bi bi-cart-plus"></i> New Bill</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/billing/list.php') || $isActive('/billing/view.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/billing/list.php"><i class="bi bi-receipt-cutoff"></i> Invoices</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/customers/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/customers/list.php"><i class="bi bi-people"></i> Customers</a></li>
        <?php if (isAdmin()): ?>
        <li class="nav-item"><a class="nav-link <?= $isActive('/products/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/products/list.php"><i class="bi bi-box-seam"></i> Products</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/purchases/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/purchases/list.php"><i class="bi bi-truck"></i> Purchases</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $isActive('/reports/') ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-bar-chart"></i> Reports</a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/sales.php"><i class="bi bi-graph-up me-2"></i>Sales Report</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/stock.php"><i class="bi bi-boxes me-2"></i>Stock Report</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/gst_summary.php"><i class="bi bi-receipt me-2"></i>GST Summary</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <span class="app-user-chip">
            <span class="app-user-avatar"><?= h($initials) ?></span>
            <span class="app-user-meta">
              <span class="app-user-name"><?= h($user['name']) ?></span>
              <span class="app-user-role"><?= h($user['role']) ?></span>
            </span>
          </span>
        </li>
        <li class="nav-item"><a class="nav-link app-logout" href="<?= BASE_URL ?>/logout.php" title="Logout"><i class="bi bi-box-arrow-right"></i> <span class="d-lg-none">Logout</span></a></li>
      </ul>
    </div>
  </div>
</nav>

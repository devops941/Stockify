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
        <span class="app-brand-name"><?= h(shopSetting('shop_name')) ?></span>
        <span class="app-brand-tagline">Billing &amp; Stock</span>
      </span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
        <li class="nav-item"><a class="nav-link <?= $isActive('/dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/billing/new.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/billing/new.php"><i class="bi bi-cart-plus"></i> New Bill</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/billing/list.php') || $isActive('/billing/view.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/billing/list.php"><i class="bi bi-receipt-cutoff"></i> Invoices</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/customers/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/customers/list.php"><i class="bi bi-people"></i> Customers</a></li>
        <?php if (isAdmin()): ?>
        <li class="nav-item app-nav-divider d-none d-lg-block"></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/products/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/products/list.php"><i class="bi bi-box-seam"></i> Products</a></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/purchases/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/purchases/list.php"><i class="bi bi-truck"></i> Purchases</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $isActive('/reports/') ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-bar-chart"></i> Reports</a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/sales.php"><i class="bi bi-graph-up me-2"></i>Sales Report</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/stock.php"><i class="bi bi-boxes me-2"></i>Stock Report</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/reports/gst_summary.php"><i class="bi bi-receipt me-2"></i>Tax Summary</a></li>
          </ul>
        </li>
        <li class="nav-item app-nav-divider d-none d-lg-block"></li>
        <li class="nav-item"><a class="nav-link <?= $isActive('/profile/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/profile/index.php"><i class="bi bi-shop"></i> Profile</a></li>
        <?php endif; ?>
      </ul>
      <div class="app-nav-end d-flex align-items-center gap-2">
        <span class="app-user-chip">
          <span class="app-user-avatar"><?= h($initials) ?></span>
          <span class="app-user-meta">
            <span class="app-user-name"><?= h($user['name']) ?></span>
            <span class="app-user-role"><?= h($user['role']) ?></span>
          </span>
        </span>
        <a class="btn app-logout-btn" href="<?= BASE_URL ?>/logout.php" title="Logout">
          <i class="bi bi-box-arrow-right"></i>
          <span class="d-lg-none ms-1">Logout</span>
        </a>
      </div>
    </div>
  </div>
</nav>

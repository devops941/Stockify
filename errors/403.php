<?php
// Included directly by requireRole() with headers already partly sent in some flows,
// so this stays a minimal standalone fragment, not a full header()/footer() page.
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Access Denied</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendors/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
<div class="container py-5 text-center">
    <div class="mb-4">
        <i class="bi bi-shield-lock-fill text-danger" style="font-size: 4rem;"></i>
    </div>
    <h1 class="display-5 fw-bold text-danger mb-3">403 - Access Denied</h1>
    <p class="lead text-muted mb-4">You do not have permission to view or access this page.</p>
    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary px-4 py-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>
</body>
</html>

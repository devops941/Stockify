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
<title>Access Denied</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center">
    <h1 class="display-5 text-danger">403 - Access Denied</h1>
    <p class="lead">You do not have permission to view this page.</p>
    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
</div>
</body>
</html>

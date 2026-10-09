<?php
/**
 * Expects $pageTitle to be set by the including page.
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'GST Billing') ?> - <?= h(SHOP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="container-fluid px-3 px-md-4 py-4">
<?php foreach (getFlashes() as $f): ?>
    <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= h($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>

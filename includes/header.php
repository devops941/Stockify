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
<title><?= h($pageTitle ?? 'Billing') ?> — <?= h(shopSetting('shop_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendors/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="container-fluid px-3 px-md-4 py-4">
<?php foreach (getFlashes() as $f): 
    $alertIcons = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill',
        'primary' => 'bi-info-circle-fill'
    ];
    $alertIcon = $alertIcons[$f['type']] ?? 'bi-info-circle-fill';
?>
    <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi <?= $alertIcon ?> flex-shrink-0"></i>
        <div><?= h($f['message']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>

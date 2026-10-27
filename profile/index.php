<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

// Auto-create settings table on first visit
$pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
    `key`   varchar(64) NOT NULL,
    `value` text        NOT NULL DEFAULT '',
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed defaults if table is empty
$count = (int) $pdo->query('SELECT COUNT(*) FROM `settings`')->fetchColumn();
if ($count === 0) {
    $seed = $pdo->prepare("INSERT IGNORE INTO `settings` (`key`, `value`) VALUES (?, ?)");
    foreach ([
        'shop_name'    => SHOP_NAME,
        'shop_address' => SHOP_ADDRESS,
        'shop_state'   => SHOP_STATE,
        'shop_gstin'   => SHOP_GSTIN,
        'shop_phone'   => SHOP_PHONE,
    ] as $k => $v) {
        $seed->execute([$k, $v]);
    }
    // Reload globals after seeding
    $GLOBALS['_shopSettings'] = [];
    foreach ($pdo->query('SELECT `key`, `value` FROM `settings`')->fetchAll() as $r) {
        $GLOBALS['_shopSettings'][$r['key']] = $r['value'];
    }
}

$fields = ['shop_name', 'shop_address', 'shop_state', 'shop_gstin', 'shop_phone'];
$errors  = [];
$form    = [];
foreach ($fields as $f) {
    $form[$f] = shopSetting($f);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        $form[$f] = trim($_POST[$f] ?? '');
    }

    if ($form['shop_name']  === '') $errors[] = 'Shop name is required.';
    if ($form['shop_state'] === '') $errors[] = 'State is required (used to determine tax type on invoices).';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($form as $k => $v) {
            $stmt->execute([$k, $v]);
        }
        // Update globals so the rest of the page reflects new values immediately
        foreach ($form as $k => $v) {
            $GLOBALS['_shopSettings'][$k] = $v;
        }
        flash('success', 'Store profile saved successfully.');
        redirect('/profile/index.php');
    }
}

$pageTitle = 'Store Profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="profile-hero mb-4">
  <div class="d-flex align-items-center gap-3">
    <div class="profile-hero-icon">
      <i class="bi bi-shop"></i>
    </div>
    <div>
      <h4 class="mb-0 fw-bold">Store Profile</h4>
      <p class="mb-0 text-muted small">Manage your store details, tax info, and branding used on invoices.</p>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card profile-card">
      <div class="card-accent-top"></div>
      <div class="card-body p-4">
        <div class="section-label mb-4">
          <i class="bi bi-building"></i> Business Information
        </div>

        <?php if ($errors): ?>
          <div class="alert alert-danger rounded-3">
            <i class="bi bi-exclamation-circle me-2"></i>
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="mb-4">
            <label class="form-label">
              <i class="bi bi-shop me-1 text-primary"></i> Shop / Business Name
            </label>
            <input type="text" name="shop_name" class="form-control form-control-lg"
                   value="<?= h($form['shop_name']) ?>" placeholder="e.g. Kaizen General Store" required>
          </div>

          <div class="mb-4">
            <label class="form-label">
              <i class="bi bi-geo-alt me-1 text-primary"></i> Address
            </label>
            <textarea name="shop_address" class="form-control" rows="3"
                      placeholder="Full business address"><?= h($form['shop_address']) ?></textarea>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">
                <i class="bi bi-map me-1 text-primary"></i> State
              </label>
              <input type="text" name="shop_state" class="form-control"
                     value="<?= h($form['shop_state']) ?>" placeholder="e.g. Maharashtra" required>
              <div class="form-text">Used to determine Tax type on invoices.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="bi bi-file-earmark-text me-1 text-primary"></i> GSTIN
              </label>
              <input type="text" name="shop_gstin" class="form-control"
                     value="<?= h($form['shop_gstin']) ?>" placeholder="e.g. 27AAAAA0000A1Z5"
                     maxlength="15" style="text-transform:uppercase">
            </div>
            <div class="col-md-4">
              <label class="form-label">
                <i class="bi bi-telephone me-1 text-primary"></i> Phone
              </label>
              <input type="text" name="shop_phone" class="form-control"
                     value="<?= h($form['shop_phone']) ?>" placeholder="+91-9876543210">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-x me-1"></i>Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card profile-card">
      <div class="card-accent-top" style="background:linear-gradient(90deg,#16a34a,#059669)"></div>
      <div class="card-body p-4">
        <div class="section-label mb-3">
          <i class="bi bi-eye"></i> Live Preview
        </div>
        <div class="invoice-preview">
          <div class="invoice-preview-brand">
            <i class="bi bi-receipt-cutoff"></i>
            <span><?= h(shopSetting('shop_name')) ?></span>
          </div>
          <div class="invoice-preview-row"><i class="bi bi-geo-alt-fill"></i> <?= h(shopSetting('shop_address') ?: 'Address not set') ?></div>
          <div class="invoice-preview-row"><i class="bi bi-shield-check"></i> GSTIN: <?= h(shopSetting('shop_gstin') ?: 'Not set') ?></div>
          <div class="invoice-preview-row"><i class="bi bi-geo-fill"></i> State: <?= h(shopSetting('shop_state') ?: 'Not set') ?></div>
          <div class="invoice-preview-row"><i class="bi bi-telephone-fill"></i> <?= h(shopSetting('shop_phone') ?: 'Phone not set') ?></div>
        </div>
        <div class="mt-3 text-muted" style="font-size:.78rem">
          <i class="bi bi-info-circle me-1"></i>
          This info appears on every invoice and PDF.
        </div>
      </div>
    </div>

    <div class="card profile-card mt-3">
      <div class="card-accent-top" style="background:linear-gradient(90deg,#7c3aed,#6d28d9)"></div>
      <div class="card-body p-4">
        <div class="section-label mb-3">
          <i class="bi bi-percent"></i> Tax Configuration
        </div>
        <div class="tax-info-row">
          <span class="tax-badge tax-badge-blue">Same State</span>
          <span class="text-muted small">→ Tax applied (intra-state)</span>
        </div>
        <div class="tax-info-row mt-2">
          <span class="tax-badge tax-badge-orange">Different State</span>
          <span class="text-muted small">→ IGST applied (inter-state)</span>
        </div>
        <div class="mt-3 text-muted" style="font-size:.78rem">
          <i class="bi bi-info-circle me-1"></i>
          Tax type is auto-detected from customer state vs. your store state.
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

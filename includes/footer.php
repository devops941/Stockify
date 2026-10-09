</main>
<footer class="app-footer no-print">
  <div class="container-fluid">
    <div class="app-footer-row">
      <div class="app-footer-brand">
        <i class="bi bi-receipt-cutoff"></i>
        <div>
          <div class="app-footer-shop"><?= h(SHOP_NAME) ?></div>
          <div class="app-footer-sub">GST Billing &amp; Stock Management System</div>
        </div>
      </div>
      <div class="app-footer-meta">
        <span><i class="bi bi-calendar3"></i> FY <?= h(getFinancialYear()) ?></span>
        <span><i class="bi bi-geo-alt"></i> <?= h(SHOP_STATE) ?></span>
        <span>&copy; <?= date('Y') ?> All rights reserved</span>
      </div>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

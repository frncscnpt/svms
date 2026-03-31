    </div><!-- /.mobile-content -->

    <!-- Bottom Navigation -->
    <?php if ($isTeacher && empty($hideScanNav)): ?>
    <nav class="m-bottom-nav">
        <a href="<?= BASE_PATH ?>/teacher/index.php" class="m-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i>
            <span>Home</span>
        </a>
        <!-- Center FAB -->
        <div class="m-nav-fab-wrap">
            <a href="<?= BASE_PATH ?>/teacher/scan.php" class="m-nav-fab <?= $currentPage === 'scan' ? 'active' : '' ?>">
                <i class="bi bi-qr-code-scan"></i>
            </a>
            <span class="m-nav-fab-label">Scan</span>
        </div>
        <a href="<?= BASE_PATH ?>/teacher/profile.php" class="m-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
    </nav>
    <?php elseif ($isStudent && empty($hideScanNav)): ?>
    <nav class="m-bottom-nav">
        <a href="<?= BASE_PATH ?>/student/index.php" class="m-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_PATH ?>/student/violations.php" class="m-nav-item <?= $currentPage === 'violations' ? 'active' : '' ?>">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Violations</span>
        </a>
        <a href="<?= BASE_PATH ?>/student/uniform_pass.php" class="m-nav-item <?= $currentPage === 'uniform_pass' ? 'active' : '' ?>">
            <i class="bi bi-card-checklist"></i>
            <span>Pass</span>
        </a>
        <a href="<?= BASE_PATH ?>/student/profile.php" class="m-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
    </nav>
    <?php endif; ?>

</div><!-- /.mobile-app -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($extraJS)) echo $extraJS; ?>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= BASE_PATH ?>/sw.js', { scope: '<?= BASE_PATH ?>/' })
            .then(reg => console.log('Service Worker registered:', reg.scope))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
</script>
</body>
</html>

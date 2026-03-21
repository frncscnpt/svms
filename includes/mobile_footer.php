    </div><!-- /.mobile-content -->

    <!-- Bottom Navigation -->
    <?php if ($isTeacher): ?>
    <nav class="m-bottom-nav">
        <a href="<?= BASE_PATH ?>/teacher/index.php" class="m-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_PATH ?>/teacher/scan.php" class="m-nav-item <?= $currentPage === 'scan' ? 'active' : '' ?>">
            <i class="bi bi-qr-code-scan"></i>
            <span>Scan</span>
        </a>
        <a href="<?= BASE_PATH ?>/notifications.php" class="m-nav-item <?= $currentPage === 'notifications' ? 'active' : '' ?> position-relative">
            <i class="bi bi-bell-fill"></i>
            <span>Alerts</span>
            <?php if ($unreadCount > 0): ?>
            <span class="m-nav-badge-dot"></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_PATH ?>/teacher/profile.php" class="m-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
    </nav>
    <?php elseif ($isStudent): ?>
    <nav class="m-bottom-nav">
        <a href="<?= BASE_PATH ?>/student/index.php" class="m-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_PATH ?>/student/violations.php" class="m-nav-item <?= $currentPage === 'violations' ? 'active' : '' ?>">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Violations</span>
        </a>
        <a href="<?= BASE_PATH ?>/notifications.php" class="m-nav-item <?= $currentPage === 'notifications' ? 'active' : '' ?> position-relative">
            <i class="bi bi-bell-fill"></i>
            <span>Alerts</span>
            <?php if ($unreadCount > 0): ?>
            <span class="m-nav-badge-dot"></span>
            <?php endif; ?>
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
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
</script>
</body>
</html>

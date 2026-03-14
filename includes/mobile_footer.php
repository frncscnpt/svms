        </div><!-- /.mobile-content -->

        <!-- Bottom Navigation -->
        <?php if ($isTeacher): ?>
        <nav class="bottom-nav">
            <?php $unreadCount = getUnreadNotificationCount($_SESSION['user_id']); ?>
            <a href="<?= BASE_PATH ?>/teacher/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="bi bi-house-fill"></i>
                <span>Home</span>
            </a>
            <a href="<?= BASE_PATH ?>/teacher/my_reports.php" class="nav-item <?= $currentPage === 'my_reports' ? 'active' : '' ?>">
                <i class="bi bi-file-text-fill"></i>
                <span>Reports</span>
            </a>
            <a href="<?= BASE_PATH ?>/teacher/scan.php" class="scan-btn">
                <i class="bi bi-qr-code-scan"></i>
            </a>
            <a href="<?= BASE_PATH ?>/notifications.php" class="nav-item <?= $currentPage === 'notifications' ? 'active' : '' ?> position-relative">
                <i class="bi bi-bell-fill"></i>
                <span>Inbox</span>
                <?php if ($unreadCount > 0): ?>
                <span class="position-absolute translate-middle p-1 bg-danger border border-light rounded-circle" style="top: 25%; left: 60%;"></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_PATH ?>/teacher/profile.php" class="nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <div style="width: 24px; height: 24px; margin: 0 auto 4px;">
                    <?= getAvatarHtml($_SESSION['avatar'] ?? null, $_SESSION['full_name'], 'profile-avatar', 'width: 100%; height: 100%; font-size: 10px; margin: 0;') ?>
                </div>
                <span>Account</span>
            </a>
        </nav>
        <?php elseif ($isStudent): ?>
        <nav class="bottom-nav">
            <?php $unreadCount = getUnreadNotificationCount($_SESSION['user_id']); ?>
            <a href="<?= BASE_PATH ?>/student/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="bi bi-house-fill"></i>
                <span>Home</span>
            </a>
            <a href="<?= BASE_PATH ?>/student/violations.php" class="nav-item <?= $currentPage === 'violations' ? 'active' : '' ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Violations</span>
            </a>
            <a href="<?= BASE_PATH ?>/notifications.php" class="nav-item <?= $currentPage === 'notifications' ? 'active' : '' ?> position-relative">
                <i class="bi bi-bell-fill"></i>
                <span>Inbox</span>
                <?php if ($unreadCount > 0): ?>
                <span class="position-absolute translate-middle p-1 bg-danger border border-light rounded-circle" style="top: 25%; left: 60%;"></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_PATH ?>/student/profile.php" class="nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <div style="width: 24px; height: 24px; margin: 0 auto 4px;">
                    <?= getAvatarHtml($_SESSION['avatar'] ?? null, $_SESSION['full_name'], 'profile-avatar', 'width: 100%; height: 100%; font-size: 10px; margin: 0;') ?>
                </div>
                <span>Profile</span>
            </a>
        </nav>
        <?php endif; ?>
    </div><!-- /.mobile-app -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraJS)) echo $extraJS; ?>
    <script>
        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>

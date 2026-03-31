            </div><!-- /.mobile-content -->
        </div><!-- /.content-wrapper -->
    </main>

    <!-- Mobile Bottom Navigation (hidden on desktop) -->
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
<script src="<?= BASE_PATH ?>/assets/js/app.js"></script>
<?php if (isset($extraJS)) echo $extraJS; ?>

<script>
// Service Worker Registration
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_PATH ?>/sw.js', { scope: '<?= BASE_PATH ?>/' })
        .then(reg => console.log('Service Worker registered:', reg.scope))
        .catch(err => console.error('Service Worker registration failed:', err));
}

// Sidebar toggle for mobile
const sidebar   = document.getElementById('sidebar');
const backdrop  = document.getElementById('sidebarBackdrop');

function openSidebar()  { sidebar.classList.add('show');    backdrop.classList.add('show'); }
function closeSidebar() { sidebar.classList.remove('show'); backdrop.classList.remove('show'); }

document.getElementById('sidebarToggle')?.addEventListener('click', openSidebar);
document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);
backdrop?.addEventListener('click', closeSidebar);

function confirmLogout(e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
}

document.getElementById('logoutCancel')?.addEventListener('click', function() {
    document.getElementById('logoutModal').style.display = 'none';
});

// Close on backdrop click
document.getElementById('logoutModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" style="display:none; position:fixed; inset:0; background:rgba(19,1,23,0.45); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:18px; padding:32px 28px; max-width:360px; width:90%; box-shadow:0 20px 60px rgba(19,1,23,0.18); text-align:center; animation:modalEnter 0.25s cubic-bezier(0.34,1.56,0.64,1);">
        <div style="width:52px;height:52px;background:rgba(220,38,38,0.08);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="bi bi-box-arrow-left" style="font-size:22px;color:#dc2626;"></i>
        </div>
        <h5 style="font-family:'Chillax','Inter',sans-serif;font-size:18px;font-weight:700;color:#130117;margin-bottom:8px;letter-spacing:-0.02em;">Log out?</h5>
        <p style="font-size:13px;color:#7e747c;margin-bottom:24px;line-height:1.5;">You'll be signed out of your session. Any unsaved changes will be lost.</p>
        <div style="display:flex;gap:10px;">
            <button id="logoutCancel" style="flex:1;padding:11px;border:1.5px solid #ede9ee;border-radius:12px;background:#fff;font-family:'Chillax','Inter',sans-serif;font-size:14px;font-weight:600;color:#4c444b;cursor:pointer;">Cancel</button>
            <a href="<?= BASE_PATH ?>/api/logout.php" style="flex:1;padding:11px;border:none;border-radius:12px;background:#dc2626;font-family:'Chillax','Inter',sans-serif;font-size:14px;font-weight:600;color:#fff;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;">Log out</a>
        </div>
    </div>
</div>
</body>
</html>

</div><!-- .page-content -->
</main><!-- .main-content -->
</div><!-- .app-container -->

<!-- Modal Backdrop -->
<div class="modal-backdrop"></div>

<!-- Scripts -->
<script src="/assets/js/app.js?v=<?= time() ?>"></script>
<script src="/assets/js/notifications.js?v=<?= time() ?>"></script>
<script>
    (function () {
        // Elements
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const themeToggle = document.getElementById('themeToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');

        // Sidebar toggle (desktop collapse)
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Mobile menu toggle
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function () {
                sidebar.classList.add('mobile-open');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        // Close mobile sidebar
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                closeMobileSidebar();
            });
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // User menu dropdown
        if (userMenu) {
            userMenu.addEventListener('click', function (e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (userDropdown && userMenu && !userMenu.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });

        // Theme toggle
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                const html = document.documentElement;
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }

        // Restore sidebar state
        if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth > 1024) {
            sidebar.classList.add('collapsed');
        }

        // Restore theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        // Close mobile sidebar on nav click
        document.querySelectorAll('.sidebar .nav-item').forEach(function (item) {
            item.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    closeMobileSidebar();
                }
            });
        });
    })();

    // Logout function
    function logout() {
        if (typeof ERP !== 'undefined') {
            ERP.api.post('/logout').then(function () {
                window.location.href = '/login';
            }).catch(function () {
                window.location.href = '/login';
            });
        } else {
            window.location.href = '/login';
        }
    }
</script>
</body>

</html>

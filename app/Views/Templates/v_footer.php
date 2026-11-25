<!-- Footer -->
<footer class="sticky-footer bg-transparent">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Copyright &copy; PT. Nusantara Bona Pasogit 2026</span>
        </div>
    </div>
</footer>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal">
                    <span>×</span>
                </button>
            </div>
            <div class="modal-body">
                Select "Logout" below if you are ready to end your current session.
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="/logout">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- ================= JS LOCAL (NO CDN) ================= -->

<!-- jQuery (WAJIB pertama) -->
<script src="/asset/vendor/jquery/jquery.min.js"></script>

<!-- Bootstrap Bundle (include Popper) -->
<script src="/asset/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Plugin -->
<script src="/asset/vendor/jquery-easing/jquery.easing.min.js"></script>
<?php if (!empty($useDropify)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>

    <script>
        $(function () {
            if ($('.dropify').length) {
                $('.dropify').dropify();
            }
        });
    </script>
<?php endif; ?>

<script src="/asset/js/sb-admin-2.min.js"></script>

<script>
    // Sidebar & topbar (vanilla JS, tanpa listener resize/scroll terus-menerus)
    (function () {
        'use strict';
        if (window.__appSidebarInit) return;
        window.__appSidebarInit = true;

        var body = document.body,
            sb = document.getElementById('appSidebar'),
            overlay = document.getElementById('appSbOverlay'),
            toggle = document.getElementById('appSbToggle'),
            closeBtn = document.getElementById('appSbClose');
        if (!sb || !toggle) return;

        var mq = window.matchMedia('(max-width: 991.98px)');
        var isMobile = function () { return mq.matches; };

        function openDrawer() {
            body.classList.add('sb-open');
            overlay.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            var first = sb.querySelector('.app-sb-link.is-active') || sb.querySelector('.app-sb-link');
            if (first) first.focus({ preventScroll: true });
        }
        function closeDrawer(returnFocus) {
            if (!body.classList.contains('sb-open')) return;
            body.classList.remove('sb-open');
            overlay.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (returnFocus) toggle.focus();
        }
        function setMini(on) {
            body.classList.toggle('sb-mini', on);
            try { localStorage.setItem('sbMini', on ? '1' : '0'); } catch (e) { }
        }

        toggle.addEventListener('click', function () {
            if (isMobile()) {
                body.classList.contains('sb-open') ? closeDrawer(true) : openDrawer();
            } else {
                setMini(!body.classList.contains('sb-mini'));
            }
        });
        if (closeBtn) closeBtn.addEventListener('click', function () { closeDrawer(true); });
        overlay.addEventListener('click', function () { closeDrawer(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(true); });
        sb.addEventListener('click', function (e) { if (isMobile() && e.target.closest('a[href]:not([href="#"])')) closeDrawer(false); });

        // Pindah ke desktop -> pastikan laci tertutup (event hanya saat melewati batas 992px)
        var onChange = function () { if (!isMobile()) closeDrawer(false); };
        mq.addEventListener ? mq.addEventListener('change', onChange) : mq.addListener(onChange);
    })();
</script>
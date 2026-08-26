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
    $(function () {
        const $sidebar = $('#accordionSidebar');
        const $overlay = $('#sidebarOverlay');

        function closeSidebar() {
            $sidebar.removeClass('active');
            $overlay.removeClass('active');
            $('body').css('overflow', 'auto');
        }

        $('#sidebarToggleBtn').on('click', function () {
            $sidebar.addClass('active');
            $overlay.addClass('active');
            $('body').css('overflow', 'hidden');
        });

        $('#sidebarClose, #sidebarOverlay').on('click', closeSidebar);

        $('.nav-link.has-submenu').on('click', function (e) {
            e.preventDefault();

            const $this = $(this);
            const $submenu = $this.next('.submenu');

            $('.nav-link.has-submenu').not($this).removeClass('open');
            $('.submenu').not($submenu).removeClass('open');

            $this.toggleClass('open');
            $submenu.toggleClass('open');
        });

        if ($(window).width() <= 991) {
            $('.submenu-link').on('click', function () {
                setTimeout(closeSidebar, 300);
            });
        }

        $(window).on('resize', function () {
            if ($(window).width() > 991) {
                closeSidebar();
            }
        });

        const currentUrl = window.location.href;

        $('.sidebar .submenu-link').each(function () {
            const linkUrl = $(this).attr('href');

            if (linkUrl && linkUrl !== '#' && currentUrl.includes(linkUrl)) {
                $(this).addClass('active');
                $(this).closest('.submenu')
                    .addClass('open')
                    .prev('.nav-link.has-submenu')
                    .addClass('open');
            }
        });

        $('.sidebar .nav-link').not('.has-submenu').each(function () {
            const linkUrl = $(this).attr('href');

            if (linkUrl && linkUrl !== '#' && currentUrl.includes(linkUrl)) {
                $(this).addClass('active');
            }
        });

    });
</script>
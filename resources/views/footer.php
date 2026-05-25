<!-- Angular Toaster Container -->
<toaster-container toaster-options="{
    'time-out': 3000,
    'position-class': 'toast-top-right'
}">
</toaster-container>

<!-- Bootstrap JS (CDN version is in header, but keeping local bundle if needed for some plugins) -->
<!-- If using CDN for Bootstrap in header, we don't need this. But if we need the bundle for some specific local logic: -->
<!-- <script src="<?= APP_DIR ?>/assets/js/bootstrap.bundle.min.js"></script> -->

<script>
    // Global UI Interactions for the new design
    $(document).ready(function() {
        // Handle sidebar toggle for mobile
        $('.zt-sidebar-toggle').on('click', function() {
            $('.zt-shell').toggleClass('sidebar-open');
        });
    });
</script>

</body>
</html>

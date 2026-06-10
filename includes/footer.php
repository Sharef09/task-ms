<?php
/**
 * Footer Include
 * Outputs JS includes, closing body and html tags.
 */
?>
<!-- Bootstrap 5.3 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<!-- Custom Scripts -->
<script src="<?= asset('js/app.js') ?>"></script>
<script>
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.getAttribute('data-confirm'))) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
});
</script>
</body>
</html>

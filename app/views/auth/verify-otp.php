<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="text-center mb-4">
    <h4 class="fw-bold">Verify OTP</h4>
    <p class="text-muted small">Enter the 6-digit code sent to your email</p>
</div>

<form method="POST" action="<?= rtrim($app['url'], '/') ?>/verify-otp" id="otpForm">
    <?= csrf_field() ?>
    <input type="hidden" name="otp" id="otpHidden">
    <input type="hidden" name="user_id" value="<?= e($userId ?? '') ?>">

    <div class="d-flex gap-2 gap-md-3 justify-content-center mb-4">
        <?php for ($i = 1; $i <= 6; $i++): ?>
            <input type="text"
                   class="form-control otp-digit text-center fw-bold"
                   id="otp_<?= $i ?>"
                   data-index="<?= $i ?>"
                   maxlength="1"
                   inputmode="numeric"
                   pattern="[0-9]"
                   autocomplete="off"
                   style="width: 52px; height: 58px; font-size: 1.5rem; border-radius: 10px;">
        <?php endfor; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-medium mb-3">Verify OTP</button>

    <div class="text-center">
        <a href="<?= rtrim($app['url'], '/') ?>/forgot-password" class="small text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Forgot Password</a>
    </div>
</form>

<script>
(function() {
    const inputs = document.querySelectorAll('.otp-digit');
    const hidden = document.getElementById('otpHidden');

    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 1);
            if (this.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHidden();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
                updateHidden();
            }
            if (e.key === 'ArrowLeft' && index > 0) {
                inputs[index - 1].focus();
            }
            if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('focus', function() {
            this.select();
        });
    });

    function updateHidden() {
        let otp = '';
        inputs.forEach(input => { otp += input.value; });
        hidden.value = otp;
    }

    document.getElementById('otpForm').addEventListener('submit', function() {
        updateHidden();
    });

    inputs[0].focus();
})();
</script>

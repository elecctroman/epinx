<?php
$recaptcha = $recaptcha ?? [];
if (empty($recaptcha['enabled']) || empty($recaptcha['site_key'])) {
    return;
}
$type = strtolower($recaptcha['type'] ?? 'v2');
$siteKey = escape((string) $recaptcha['site_key']);
?>
<div class="recaptcha-wrapper mt-3">
    <?php if ($type === 'v3'): ?>
        <input type="hidden" name="g-recaptcha-response" id="recaptchaToken">
        <?php if (!defined('RECAPTCHA_V3_LOADED')): define('RECAPTCHA_V3_LOADED', true); ?>
            <script src="https://www.google.com/recaptcha/api.js?render=<?= $siteKey; ?>" async defer></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof grecaptcha === 'undefined') {
                        return;
                    }
                    grecaptcha.ready(function () {
                        grecaptcha.execute('<?= $siteKey; ?>', {action: 'submit'}).then(function (token) {
                            var el = document.getElementById('recaptchaToken');
                            if (el) {
                                el.value = token;
                            }
                        });
                    });
                });
            </script>
        <?php endif; ?>
    <?php else: ?>
        <div class="g-recaptcha" data-sitekey="<?= $siteKey; ?>"></div>
        <?php if (!defined('RECAPTCHA_V2_LOADED')): define('RECAPTCHA_V2_LOADED', true); ?>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
/**
 * Browser-side OAuth callback completion for proxy mode.
 *
 * @var string $retrieve_url
 * @var string $ajax_url
 * @var string $complete_nonce
 * @var string $redirect_url
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Connecting Google Search Console', 'rankiva-seo-insights-for-gsc'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f0f0f1;
            margin: 0;
            padding: 40px 20px;
        }
        .scso-oauth-box {
            max-width: 520px;
            margin: 80px auto;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 32px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        }
        .scso-oauth-box h1 {
            margin: 0 0 12px;
            font-size: 20px;
            line-height: 1.3;
        }
        .scso-oauth-box p {
            margin: 0;
            color: #50575e;
        }
        .scso-oauth-error {
            color: #b32d2e;
            margin-top: 16px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="scso-oauth-box">
        <h1><?php esc_html_e('Connecting Google Search Console', 'rankiva-seo-insights-for-gsc'); ?></h1>
        <p id="scso-oauth-status"><?php esc_html_e('Completing authentication. Please wait…', 'rankiva-seo-insights-for-gsc'); ?></p>
        <p class="scso-oauth-error" id="scso-oauth-error"></p>
    </div>
    <script>
    (function () {
        var retrieveUrl = <?php echo wp_json_encode($retrieve_url); ?>;
        var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
        var nonce = <?php echo wp_json_encode($complete_nonce); ?>;
        var redirectUrl = <?php echo wp_json_encode($redirect_url); ?>;
        var statusEl = document.getElementById('scso-oauth-status');
        var errorEl = document.getElementById('scso-oauth-error');

        function showError(message) {
            statusEl.textContent = <?php echo wp_json_encode(__('Connection failed.', 'rankiva-seo-insights-for-gsc')); ?>;
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        fetch(retrieveUrl, {
            method: 'GET',
            credentials: 'omit',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error((data && data.message) ? data.message : <?php echo wp_json_encode(__('Invalid OAuth response.', 'rankiva-seo-insights-for-gsc')); ?>);
                    }
                    return data;
                });
            })
            .then(function (tokens) {
                if (!tokens || !tokens.access_token) {
                    throw new Error((tokens && tokens.message) ? tokens.message : <?php echo wp_json_encode(__('Invalid OAuth response.', 'rankiva-seo-insights-for-gsc')); ?>);
                }

                var body = new URLSearchParams();
                body.set('action', 'scso_complete_oauth');
                body.set('nonce', nonce);
                body.set('tokens', JSON.stringify(tokens));

                return fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                });
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (!result || !result.success) {
                    throw new Error((result && result.data && result.data.message) ? result.data.message : <?php echo wp_json_encode(__('Failed to save OAuth tokens.', 'rankiva-seo-insights-for-gsc')); ?>);
                }

                window.location.href = (result.data && result.data.redirect) ? result.data.redirect : redirectUrl;
            })
            .catch(function (error) {
                showError(error && error.message ? error.message : <?php echo wp_json_encode(__('Invalid OAuth response.', 'rankiva-seo-insights-for-gsc')); ?>);
            });
    }());
    </script>
</body>
</html>

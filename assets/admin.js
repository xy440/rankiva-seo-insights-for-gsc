jQuery(function ($) {
    'use strict';

    var progressWrap = $('#scso-sync-progress');
    var progressBar  = $('#scso-progress-bar');
    var progressText = $('#scso-progress-text');

    function startSync($btn) {
        $btn.prop('disabled', true).text(scsoData.i18n.starting_sync);

        progressWrap.show();
        progressBar.css('width', '0%');
        progressText.text(scsoData.i18n.initializing);

        $.post(scsoData.ajax_url, {
            action: 'scso_sync_start',
            nonce:  scsoData.nonce
        })
        .done(function(res) {
            if (res.success) {
                // Add delay before polling to allow sync to start
                setTimeout(pollStatus, 1000);
            } else {
                stopWithError(res.data || 'Sync failed to start');
            }
        })
        .fail(function(xhr) {
            stopWithError('Sync request failed. Status: ' + xhr.status);
        });
    }

    $(document).on('click', '.scso-sync-trigger', function (e) {
        e.preventDefault();
        startSync($(this));
    });

    $(document).on('click', '#scso-disconnect-btn', function () {
        if (!confirm(scsoData.i18n.disconnect)) {
            return;
        }

        $.post(scsoData.ajax_url, {
            action: 'scso_disconnect',
            nonce: scsoData.nonce
        }).done(function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Failed to disconnect');
            }
        });
    });

    function pollStatus() {
        $.post(scsoData.ajax_url, {
            action: 'scso_sync_status',
            nonce: scsoData.nonce
        })
        .done(function(res) {
            if (!res.success) {
                stopWithError('Sync error');
                return;
            }

            var data = res.data;
            var processed = data.processed || 0;

            var percent = Math.min(100, Math.round(processed / 20)); // Assuming ~2000 posts max
            progressBar.css('width', percent + '%');

            if (data.done) {
                if (data.error) {
                    stopWithError(data.error);
                } else {
                    progressText.text('Sync completed! Processed ' + processed + ' posts.');
                    $('.scso-sync-trigger')
                        .prop('disabled', false)
                        .html('<span class="dashicons dashicons-update"></span> Sync Now');
                    setTimeout(function() { location.reload(); }, 1500);
                }
            } else {
                progressText.text('Syncing… processed ' + processed + ' posts');
                setTimeout(pollStatus, 2000);
            }
        })
        .fail(function(xhr) {
            stopWithError('Status check failed. Status: ' + xhr.status);
        });
    }

    function stopWithError(message) {
        // Just reload the page - error will be shown on the page itself
        location.reload();
    }

    $(document).on('click', '.scso-snooze', function () {
        var btn = $(this).prop('disabled', true).text('Snoozing…');

        $.post(scsoData.ajax_url, {
            action: 'scso_snooze',
            nonce: scsoData.nonce,
            post_id: btn.data('id')
        }).always(function() { location.reload(); });
    });

    $(document).on('click', '.scso-mark-updated', function () {
        var btn = $(this).prop('disabled', true).text('Updating…');

        $.post(scsoData.ajax_url, {
            action: 'scso_mark_updated',
            nonce: scsoData.nonce,
            post_id: btn.data('id')
        }).always(function() { location.reload(); });
    });

    $(document).on('click', '.scso-unsnooze', function () {
        var btn = $(this).prop('disabled', true).text('Unsnoozing…');

        $.post(scsoData.ajax_url, {
            action: 'scso_unsnooze',
            nonce: scsoData.nonce,
            post_id: btn.data('id')
        }).always(function() { location.reload(); });
    });

    $(document).on('change', '[data-scso-per-page]', function () {
        const url = $(this).val();

        if (url && typeof url === 'string') {
            window.location.href = url;
        }
    });

    // =========================================================
    // OAuth Settings Toggle - FIXED VERSION
    // =========================================================
    
    // Toggle between proxy and custom OAuth options
    var $proxyRadio = $('input[name="scso_auth_method"][value="proxy"]');
    var $customRadio = $('input[name="scso_auth_method"][value="custom"]');
    var $proxyOption = $('#proxy-option');
    var $customOption = $('#custom-option');
    var $customSection = $('#custom-credentials');
    var $saveBtn = $('#scso_save_oauth_settings');
    
    function updateOAuthSelection() {
        if ($proxyRadio.is(':checked')) {
            $proxyOption.addClass('active');
            $customOption.removeClass('active');
            $customSection.removeClass('visible');
            // Enable save button
            if ($saveBtn.length) {
                $saveBtn.prop('disabled', false);
            }
        } else if ($customRadio.is(':checked')) {
            $proxyOption.removeClass('active');
            $customOption.addClass('active');
            $customSection.addClass('visible');
            // Enable save button
            if ($saveBtn.length) {
                $saveBtn.prop('disabled', false);
            }
        }
    }
    
    // Listen for radio button changes
    $proxyRadio.on('change', updateOAuthSelection);
    $customRadio.on('change', updateOAuthSelection);
    
    // Click on label/container to select radio
    $proxyOption.on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            $proxyRadio.prop('checked', true).trigger('change');
        }
    });
    
    $customOption.on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            $customRadio.prop('checked', true).trigger('change');
        }
    });
    
    // Validate custom credentials before allowing save
    if ($saveBtn.length) {
        $saveBtn.on('click', function(e) {
            if ($customRadio.is(':checked')) {
                var clientId = $('#scso_oauth_client_id').val().trim();
                var clientSecret = $('#scso_oauth_client_secret').val().trim();
            }
        });
    }
    
    // Initialize on page load
    if ($proxyRadio.length || $customRadio.length) {
        updateOAuthSelection();
    }

});
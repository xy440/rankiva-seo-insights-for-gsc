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
                        .prop('disabled', true)
                        .text('✓ Redirecting...');
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

    // Posts per page dropdown
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



    // =========================================================
    // KEYWORDS TOGGLE FUNCTIONALITY
    // =========================================================
    
    $(document).on('click', '.scso-keywords-toggle', function(e) {
        e.preventDefault();
        
        const $toggle = $(this);
        const $list = $toggle.next('.scso-keywords-list');
        const isExpanded = $toggle.attr('aria-expanded') === 'true';
        
        if (isExpanded) {
            // Collapse
            $list.slideUp(250, 'swing');
            $toggle.attr('aria-expanded', 'false');
        } else {
            // Expand
            $list.slideDown(250, 'swing');
            $toggle.attr('aria-expanded', 'true');
            
            // Track expansion (optional analytics)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'keywords_expanded', {
                    event_category: 'engagement',
                    event_label: 'Keywords Section'
                });
            }
        }
    });

    // =========================================================
    // KEYBOARD SHORTCUTS
    // =========================================================
    
    // Alt + K: Toggle all keywords sections
    $(document).on('keydown', function(e) {
        if (e.altKey && e.keyCode === 75) {
            e.preventDefault();
            
            const $toggles = $('.scso-keywords-toggle');
            const anyExpanded = $toggles.filter('[aria-expanded="true"]').length > 0;
            
            $toggles.each(function() {
                const $toggle = $(this);
                const $list = $toggle.next('.scso-keywords-list');
                
                if (anyExpanded) {
                    // Collapse all
                    $list.slideUp(200);
                    $toggle.attr('aria-expanded', 'false');
                } else {
                    // Expand all
                    $list.slideDown(200);
                    $toggle.attr('aria-expanded', 'true');
                }
            });
        }
    });

    // =========================================================
    // ENHANCED TOOLTIPS FOR METRICS
    // =========================================================
    
    // Add tooltips to keyword metrics on hover
    $('.scso-keywords-table tbody tr').each(function() {
        const $row = $(this);
        
        // Add click-to-copy functionality for keywords
        $row.find('.scso-keyword-text').on('click', function(e) {
            if (e.target.tagName !== 'SPAN') { // Don't trigger on badge click
                const keyword = $(this).text().trim();
                const cleanKeyword = keyword.replace(/^(BEST|GREAT|GOOD|LOW)\s+/, '');
                
                copyToClipboard(cleanKeyword);
                showCopyNotification($(this));
            }
        });
    });

    // =========================================================
    // COPY TO CLIPBOARD HELPER
    // =========================================================
    
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                console.log('Keyword copied:', text);
            }).catch(function(err) {
                console.error('Copy failed:', err);
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                console.log('Keyword copied (fallback):', text);
            } catch (err) {
                console.error('Copy failed:', err);
            }
            
            document.body.removeChild(textArea);
        }
    }

    // =========================================================
    // COPY NOTIFICATION
    // =========================================================
    
    function showCopyNotification($element) {
        const $notification = $('<span class="scso-copy-notification">✓ Copied</span>');
        
        $element.css('position', 'relative');
        $element.append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 1500);
    }

    // =========================================================
    // HIGHLIGHT PRIORITY KEYWORD
    // =========================================================
    
    // Add subtle pulse animation to priority keyword
    $('.scso-priority-keyword').each(function() {
        const $priority = $(this);
        
        // Add hover effect
        $priority.on('mouseenter', function() {
            $(this).css('transform', 'translateX(5px)');
        }).on('mouseleave', function() {
            $(this).css('transform', 'translateX(0)');
        });
    });

    // =========================================================
    // TABLE ROW INTERACTIONS
    // =========================================================
    
    // Highlight row on hover with smooth transition
    $('.scso-keywords-table tbody tr').hover(
        function() {
            $(this).find('td').css('background', 'rgba(255, 255, 255, 0.08)');
        },
        function() {
            $(this).find('td').css('background', '');
        }
    );

    // =========================================================
    // SORT FUNCTIONALITY (OPTIONAL)
    // =========================================================
    
    // Add click-to-sort on table headers
    $('.scso-keywords-table th').on('click', function() {
        const $th = $(this);
        const $table = $th.closest('table');
        const columnIndex = $th.index();
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        const isAscending = $th.hasClass('sort-asc');
        
        // Remove sort classes from all headers
        $table.find('th').removeClass('sort-asc sort-desc');
        
        // Sort rows
        $rows.sort(function(a, b) {
            const aValue = $(a).find('td').eq(columnIndex).text().trim();
            const bValue = $(b).find('td').eq(columnIndex).text().trim();
            
            // Try to parse as numbers
            const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? bNum - aNum : aNum - bNum;
            }
            
            // String comparison
            return isAscending 
                ? bValue.localeCompare(aValue)
                : aValue.localeCompare(bValue);
        });
        
        // Apply new sort order
        $tbody.html($rows);
        
        // Toggle sort direction
        $th.addClass(isAscending ? 'sort-desc' : 'sort-asc');
    });

    // =========================================================
    // ACCESSIBILITY ENHANCEMENTS
    // =========================================================
    
    // Add aria labels for better screen reader support
    $('.scso-keywords-table').attr('role', 'table');
    $('.scso-keywords-table thead').attr('role', 'rowgroup');
    $('.scso-keywords-table tbody').attr('role', 'rowgroup');
    $('.scso-keywords-table tr').attr('role', 'row');
    $('.scso-keywords-table th').attr('role', 'columnheader');
    $('.scso-keywords-table td').attr('role', 'cell');

    // =========================================================
    // AUTO-EXPAND FIRST OPPORTUNITY
    // =========================================================
    
    // Optional: Auto-expand the first opportunity's keywords on page load
    // Uncomment if desired:
    /*
    const $firstToggle = $('.scso-keywords-toggle').first();
    if ($firstToggle.length) {
        setTimeout(function() {
            $firstToggle.trigger('click');
        }, 500);
    }
    */

    // =========================================================
    // PERFORMANCE: LAZY LOAD KEYWORDS
    // =========================================================
    
    // If you have many opportunities, you can lazy-load keywords when expanded
    $('.scso-keywords-toggle').one('click', function() {
        const $toggle = $(this);
        const $list = $toggle.next('.scso-keywords-list');
        
        // If list is empty, you could load via AJAX here
        if ($list.find('table tbody tr').length === 0) {
            // Example AJAX call (implement on backend):
            /*
            const postId = $toggle.data('post-id');
            $.post(scsoData.ajax_url, {
                action: 'scso_load_keywords',
                nonce: scsoData.nonce,
                post_id: postId
            }).done(function(response) {
                if (response.success) {
                    $list.find('table tbody').html(response.data.html);
                }
            });
            */
        }
    });



});
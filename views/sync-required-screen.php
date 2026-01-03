<?php

if (!defined('ABSPATH')) exit;

/**
 * Sync required screen.
 *
 * Displays sync errors and prompts user
 * to run a one-time sync or disconnect.
 *
 * Expected variables:
 * @var string|false $sync_error
 *
 * @package SearchConsoleSEOOpportunities
 */
?>

<div class="scso-container">
        <div class="scso-card">
            <div class="scso-logo-section">
                <div class="scso-logo">
                    <div class="scso-logo-icon">🔍</div>
                    <div class="scso-logo-text"><?php esc_html_e('Rankiva', 'rankiva-seo-insights-for-gsc'); ?></div>
                </div>
            </div>

            <h1><?php esc_html_e('Sync Required', 'rankiva-seo-insights-for-gsc'); ?></h1>
            <p class="scso-subtitle"><?php esc_html_e('Let\'s make sure everything is up to date', 'rankiva-seo-insights-for-gsc'); ?></p>

            <?php if ($sync_error): ?>
                <div class="scso-warning-box">
                        <span class="scso-warning-icon">⚠️</span>
                        <div class="scso-warning-content">
                            <div class="scso-warning-title"><?php esc_html_e('Property Not Found', 'rankiva-seo-insights-for-gsc'); ?></div>
                            <div class="scso-warning-text">
                                <?php echo esc_html($sync_error); ?>
                            </div>
                        </div>
                </div>
            <?php endif; ?>

            <div class="scso-info-box">
                <div class="scso-info-title">
                    <span>ℹ️</span>
                    <?php esc_html_e('What\'s happening?', 'rankiva-seo-insights-for-gsc'); ?>
                </div>
                <div class="scso-info-text">
                    <?php esc_html_e('We found existing Google Search Console data on this site, but it hasn\'t been confirmed for the currently connected Google account yet.
                    To make sure we load the correct property and show accurate SEO opportunities, please run a one-time sync.', 'rankiva-seo-insights-for-gsc'); ?>
                </div>
            </div>

            <div class="scso-btn-group">
                <button class="scso-btn scso-btn-primary">
                    <span>🔄</span>
                    <a href="#" class="scso-link scso-sync-trigger"><?php esc_html_e('Sync Now', 'rankiva-seo-insights-for-gsc'); ?></a>
                </button>
                <button id="scso-disconnect-btn" class="scso-btn scso-btn-secondary">
                    <span>🔌</span>
                    <?php esc_html_e('Disconnect', 'rankiva-seo-insights-for-gsc'); ?>
                </button>
            </div>

            <div class="scso-footer">
                <p class="scso-footer-text">
                    <span>🔐</span>
                    <span><?php esc_html_e('You can disconnect if you want to connect a different Google account.', 'rankiva-seo-insights-for-gsc'); ?></span>
                </p>
            </div> 

        </div>
    </div>
<?php
if ( ! defined('ABSPATH') ) exit;
/**
 * Admin page header.
 *
 * Displays the page title, description,
 * and disconnect button.
 *
 * @package SearchConsoleSEOOpportunities
 */

$scso_last_sync = get_option('scso_last_sync_time');
$scso_hours_ago = $scso_last_sync ? (time() - strtotime($scso_last_sync)) / 3600 : 999;
$scso_can_sync = $scso_hours_ago >= 24;
?>
<div class="scso-container-opportunities">
<div class="scso-header">
    <div>
        <h1><?php esc_html_e('SEO Opportunities', 'rankiva-seo-insights-for-gsc'); ?></h1>
        <p class="scso-subtitle"><?php esc_html_e('Posts Google already likes but are underperforming', 'rankiva-seo-insights-for-gsc'); ?></p>
    </div>
    <div class="scso-header-actions">
        <button class="scso-sync-btn scso-sync-trigger" <?php echo !$scso_can_sync ? 'disabled title="' . esc_attr__('Re-sync available in ', 'rankiva-seo-insights-for-gsc') . esc_attr( round(24 - $scso_hours_ago) ) . 'h"' : ''; ?>>
        🔄 <?php esc_html_e('Re-Sync', 'rankiva-seo-insights-for-gsc'); ?>
        </button>
        <button id="scso-disconnect-btn" class="scso-disconnect-btn">🚫 <?php esc_html_e('Disconnect', 'rankiva-seo-insights-for-gsc'); ?></button>
    </div>
</div>
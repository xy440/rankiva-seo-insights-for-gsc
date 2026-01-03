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

?>
<div class="scso-container-opportunities">
<div class="scso-header">
    <div>
        <h1><?php esc_html_e('SEO Opportunities', 'rankiva-seo-insights-for-gsc'); ?></h1>
        <p class="scso-subtitle"><?php esc_html_e('Posts Google already likes but are underperforming', 'rankiva-seo-insights-for-gsc'); ?></p>
    </div>
    <button id="scso-disconnect-btn" class="scso-disconnect-btn"><?php esc_html_e('Disconnect', 'rankiva-seo-insights-for-gsc'); ?></button>
</div>
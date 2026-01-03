<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Filters form for opportunities list.
 *
 * Expected variables:
 * @var array $scso_filters {
 *   @type string $search
 *   @type bool   $money_only
 *   @type bool   $ctr_gap_only
 *   @type bool   $show_snoozed
 * }
 *
 * @package SearchConsoleSEOOpportunities
 */

?>
<form method="get">
<div class="scso-filters-section">
    <div class="scso-filters-row">
        
        <div class="scso-search-wrapper">
            <input type="hidden" name="page" value="scso-opportunities">
            <input type="search" class="scso-search-input" name="s" placeholder="<?php esc_html_e('Search post title', 'rankiva-seo-insights-for-gsc'); ?>" value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="scso-filter-group">
            <div class="scso-filter-checkbox">
                <input type="checkbox" name="money_only" value="1" <?php checked($money_only); ?>>
                <label for="money-posts">💰 <?php esc_html_e('Money posts only', 'rankiva-seo-insights-for-gsc'); ?><span class="scso-tooltip" data-tip="<?php esc_html_e('Money Posts are pages that already receive strong impressions and rank between positions 5–20 in Google. These posts usually deliver the fastest SEO wins.', 'rankiva-seo-insights-for-gsc'); ?>">ⓘ</span></label>
            </div>
            <div class="scso-filter-checkbox">
                <input type="checkbox" name="show_snoozed" value="1" <?php checked($show_snoozed); ?>>
                <label for="snoozed">😴 <?php esc_html_e('Show snoozed only', 'rankiva-seo-insights-for-gsc'); ?></label>
            </div>
            <div class="scso-filter-checkbox">
                <input type="checkbox" name="ctr_gap_only" value="1" <?php checked($ctr_gap_only); ?>>
                <label for="ctr-gap">📊 <?php esc_html_e('CTR gap only', 'rankiva-seo-insights-for-gsc'); ?></label>
            </div>
        </div>
        <button class="scso-apply-btn"><?php esc_html_e('Submit', 'rankiva-seo-insights-for-gsc'); ?></button>
    </div>
</div>
</form>
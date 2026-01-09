<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Stats cards section.
 *
 * Displays:
 * - High opportunity count
 * - Posts with traffic
 * - Total keywords tracked (NEW in v1.1)
 * - Last sync time
 *
 * @var array $scso_stats
 * @package Rankiva
 * @since 1.0.0
 * @since 1.1.0 Added keywords count
 */
?>

<div class="scso-stats-grid">
    <div class="scso-stat-card">
        <div class="scso-stat-number"><span class="scso-stat-icon">💎 </span><?php echo intval($stats['high_opportunity']); ?></div>
        <div class="scso-stat-label"><?php esc_html_e('High Opportunity', 'rankiva-seo-insights-for-gsc'); ?>
            <span class="scso-tooltip" data-tip="<?php esc_html_e('Opportunity Score (0–100). Calculated from impressions, ranking position, and CTR gap. Higher score means faster SEO improvement potential.', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span>
        </div>
    </div>
    <div class="scso-stat-card">
        <div class="scso-stat-number"><span class="scso-stat-icon">🚀 </span><?php echo intval($stats['posts_with_traffic']); ?></div>
        <div class="scso-stat-label"><?php esc_html_e('Posts with Traffic', 'rankiva-seo-insights-for-gsc'); ?>
            <span class="scso-tooltip" data-tip="<?php esc_html_e('Number of published posts that received impressions from Google Search Console during the last sync period. Posts with zero impressions are excluded.', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span>
        </div>
    </div>
    <?php if (isset($stats['total_keywords']) && $stats['total_keywords'] > 0): ?>
    <div class="scso-stat-card scso-stat-keywords">
        <div class="scso-stat-number"><span class="scso-stat-icon">🔑 </span><?php echo intval($stats['total_keywords']); ?></div>
        <div class="scso-stat-label"><?php esc_html_e('Keywords Tracked', 'rankiva-seo-insights-for-gsc'); ?>
            <span class="scso-tooltip" data-tip="<?php esc_html_e('Total unique keywords from Google Search Console that are driving traffic to your posts. See top keywords for each post in the cards below.', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span>
        </div>
    </div>
    <?php endif; ?>
    <div class="scso-stat-card">
        <div class="scso-stat-number"><span class="scso-stat-icon">⏱️ </span><?php echo esc_html(scso_short_time_diff($stats['last_synced'])); ?>
        </div>
        <div class="scso-stat-label">
            <?php esc_html_e('Last Sync', 'rankiva-seo-insights-for-gsc'); ?>
        </div>
    </div>
</div>

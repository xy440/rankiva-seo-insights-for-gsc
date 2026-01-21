<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Stats cards section.
 *
 * Displays:
 * - High opportunity count
 * - Positions Improved (NEW in v1.2)
 * - Positions Dropped (NEW in v1.2)
 * - Posts with traffic
 * - Total keywords tracked
 * - Last sync time
 *
 * @var array $scso_stats
 * @package Rankiva
 * @since 1.0.0
 * @since 1.1.0 Added keywords count
 * @since 1.2.0 Added position change tracking cards
 */
?>

<div class="scso-stats-grid">
    <div class="scso-stat-card">
        <div class="scso-stat-number"><span class="scso-stat-icon">💎 </span><?php echo intval($stats['high_opportunity']); ?></div>
        <div class="scso-stat-label"><?php esc_html_e('High Opportunity', 'rankiva-seo-insights-for-gsc'); ?>
            <span class="scso-tooltip" data-tip="<?php esc_html_e('Opportunity Score (0–100). Calculated from impressions, ranking position, and CTR gap. Higher score means faster SEO improvement potential.', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span>
        </div>
    </div>
    
    <?php if (isset($stats['positions_improved'])): ?>
    <div class="scso-stat-card scso-stat-improved">
        <div class="scso-stat-number"><span class="scso-stat-icon">📈 </span><?php echo intval($stats['positions_improved']); ?></div>
        <div class="scso-stat-label"><?php esc_html_e('Improved', 'rankiva-seo-insights-for-gsc'); ?>
            <span class="scso-tooltip" data-tip="<?php esc_html_e('Posts that moved up in Google rankings since the last sync. These are your SEO wins!', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($stats['positions_dropped'])): ?>
    <div class="scso-stat-card scso-stat-dropped">
        <div class="scso-stat-number"><span class="scso-stat-icon">📉 </span><?php echo intval($stats['positions_dropped']); ?></div>
        <div class="scso-stat-label"><?php esc_html_e('Dropped', 'rankiva-seo-insights-for-gsc'); ?>
            <span class="scso-tooltip" data-tip="<?php esc_html_e('Posts that moved down in Google rankings since the last sync. These may need attention.', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span>
        </div>
    </div>
    <?php endif; ?>
    
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
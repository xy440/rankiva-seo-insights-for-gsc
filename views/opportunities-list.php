<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template variables (explicit for PHPCS):
 *
 * @var array           $scso_rows
 * @var bool            $scso_ctr_gap_only
 * @var bool            $scso_show_snoozed
 * @var SCSO_Admin_Page $scso_admin
 */

$scso_rows         = $rows;
$scso_ctr_gap_only = $ctr_gap_only;
$scso_show_snoozed = $show_snoozed;
$scso_search       = isset($search) ? $search : '';

if (empty($scso_rows)): 
    if (!empty($scso_search)): ?>
        <div class="scso-alert-box">
            <span class="scso-alert-icon">🔍</span>
            <span>
                <?php 
                    printf(
                        /* translators: %s: search query entered by user */
                        esc_html__('No results found for "%s"', 'rankiva-seo-insights-for-gsc'),
                        esc_html($scso_search)
                    ); 
                    ?>  
            </span>
        </div>
    <?php else: ?>
        <div class="scso-alert-box">
            <span class="scso-alert-icon">ℹ️</span>
            <span><?php esc_html_e('No opportunities found. No posts from your site match the Google Search Console property, please check your google search console property first.', 'rankiva-seo-insights-for-gsc'); ?></span>
        </div>
    <?php endif;
    else: ?>
    <div class="scso-container-opportunities">
    <?php foreach ($scso_rows as $scso_row):

    $scso_score = (int) $scso_row->opportunity_score;

    if ($scso_score >= 70) {
        $scso_score_class = 'scso-score-high';
    } elseif ($scso_score >= 40) {
        $scso_score_class = 'scso-score-medium';
    } else {
        $scso_score_class = 'scso-score-low';
    }

    $scso_expected_ctr = scso_expected_ctr((float) $scso_row->avg_position);
    $scso_actual_ctr   = (float) $scso_row->ctr;

    $scso_has_ctr_gap = (
        $scso_row->impressions >= 50 &&
        $scso_actual_ctr > 0 &&
        $scso_actual_ctr < ($scso_expected_ctr * 0.7)
    );

    if ($scso_ctr_gap_only && !$scso_has_ctr_gap && ! $scso_show_snoozed) {
        continue;
    }
?>
    <div class="scso-post-card">
        <div class="scso-post-header">
            <div class="scso-post-score" title="Opportunity Score:  <?php echo esc_attr($scso_score); ?>/100">
                <div class="scso-score-label"><?php esc_html_e('Score', 'rankiva-seo-insights-for-gsc'); ?></div>
                    <?php echo esc_html($scso_score); ?>
                </div>
                <div class="scso-post-title-section">
                    <h3 class="scso-post-title"><?php echo esc_html(get_the_title($scso_row->post_id)); ?></h3>
                    <?php if ( $scso_admin->is_money_post( $scso_row ) ) : ?>
                    <div class="scso-badges">
                        <span class="scso-money-badge">💰 <?php esc_html_e('Money Post', 'rankiva-seo-insights-for-gsc'); ?><span class="scso-tooltip" data-tip="<?php esc_html_e('This post already performs well in Google and is close to top rankings. Improving this page usually results in faster traffic and revenue growth.', 'rankiva-seo-insights-for-gsc'); ?>">ⓘ</span></span>
                    </div>
                    <?php endif; ?>
                </div>
        </div>

        <div class="scso-post-stats">
            <div class="scso-stat-item">
                <div class="scso-stat-item-label">
                    <?php esc_html_e('Position', 'rankiva-seo-insights-for-gsc'); ?>
                </div>
                <div class="scso-stat-item-value">
                    #<?php echo esc_html(number_format_i18n($scso_row->avg_position, 1)); ?>
                </div>
            </div>
            <div class="scso-stat-item">
                <div class="scso-stat-item-label">
                    <?php esc_html_e('Impressions', 'rankiva-seo-insights-for-gsc'); ?>
                </div>
                <div class="scso-stat-item-value">
                    <?php echo esc_html(number_format_i18n($scso_row->impressions)); ?>
                </div>
            </div>
            <div class="scso-stat-item">
                <div class="scso-stat-item-label">
                    <?php esc_html_e('Clicks', 'rankiva-seo-insights-for-gsc'); ?>
                </div>
                <div class="scso-stat-item-value">
                    <?php echo esc_html(number_format_i18n($scso_row->clicks)); ?>
                </div>
            </div>
            <div class="scso-stat-item">
                <div class="scso-stat-item-label">
                    <?php esc_html_e('CTR', 'rankiva-seo-insights-for-gsc'); ?>
                </div>
                <div class="scso-stat-item-value">
                    <?php echo esc_html(number_format_i18n($scso_row->ctr, 1)); ?>%
                </div>
            </div>
        </div>

        <?php
        $scso_reason = $scso_row->opportunity_reason;
        if ($scso_has_ctr_gap):
            $scso_reason = sprintf('expected %.1f%%, actual %.1f%%', $scso_expected_ctr, $scso_actual_ctr);
        ?>
        <div class="scso-ctr-warning">
            <span class="warning-icon">⚠️</span>
            <span><strong><?php esc_html_e('CTR gap:', 'rankiva-seo-insights-for-gsc'); ?></strong> <?php echo esc_html($scso_reason); ?></span>
        </div>
        <?php else: ?>
        <div class="scso-post-insight">
            <span class="scso-insight-icon">📈</span>
            <span>
            <?php 
            $scso_reason = scso_ranking_reason((float) $scso_row->avg_position);
            echo esc_html($scso_reason);
            ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if ($scso_has_ctr_gap): ?>
        <div class="scso-post-insight">
            <span class="scso-insight-icon">💡</span>
            <span><?php esc_html_e('High impressions, low CTR', 'rankiva-seo-insights-for-gsc'); ?></span>
        </div>
        <?php endif; ?>

        <div class="scso-post-actions">
             <a class="scso-action-btn scso-btn-primary scso-link" href="<?php echo esc_url(get_edit_post_link($scso_row->post_id)); ?>">✏️ <?php esc_html_e('Edit', 'rankiva-seo-insights-for-gsc'); ?></a>
            <button class="scso-action-btn scso-btn-secondary scso-mark-updated" data-id="<?php echo esc_attr($scso_row->post_id); ?>">✅ <?php esc_html_e('Mark Updated', 'rankiva-seo-insights-for-gsc'); ?></button>
            <?php if ($scso_row->snoozed_until && strtotime($scso_row->snoozed_until) > time()): ?>
    <div class="scso-snooze-badge">
        <span class="scso-snooze-icon">😴</span>
        <span class="scso-snooze-text">
            <?php esc_html_e('Snoozed until ', 'rankiva-seo-insights-for-gsc'); ?>
            <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($scso_row->snoozed_until))); ?></strong>
        </span>
    </div>
    <?php endif; ?>
            <?php if ($scso_row->snoozed_until && strtotime($scso_row->snoozed_until) > time()): ?>
            <button class="scso-action-btn scso-btn-secondary scso-unsnooze" data-id="<?php echo esc_attr($scso_row->post_id); ?>">👁️ <?php esc_html_e('Unsnooze', 'rankiva-seo-insights-for-gsc'); ?></button>
            <?php else: ?>
            <button class="scso-action-btn scso-btn-secondary scso-snooze" data-id="<?php echo esc_attr($scso_row->post_id); ?>">🔔 <?php esc_html_e('Snooze 30d', 'rankiva-seo-insights-for-gsc'); ?></button>
            <?php endif; ?>
        </div>

    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

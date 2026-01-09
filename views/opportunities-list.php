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
 * @var array           $scso_keywords_map  Keywords grouped by post_id (NEW in v1.1)
 * @var SCSO_Admin_Page $scso_admin
 */

$scso_rows         = $rows;
$scso_ctr_gap_only = $ctr_gap_only;
$scso_show_snoozed = $show_snoozed;
$scso_search       = isset($search) ? $search : '';

// Get keywords for all displayed posts (NEW in v1.1)
$scso_post_ids = array_map(function($row) {
    return $row->post_id;
}, $scso_rows);

$scso_keywords_map = [];
if (!empty($scso_post_ids) && method_exists($GLOBALS['scso_db'], 'get_keywords_bulk')) {
    $scso_keywords_map = $GLOBALS['scso_db']->get_keywords_bulk($scso_post_ids, 5);
}

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

    // Get keywords for this post (NEW in v1.1)
    $scso_post_keywords = isset($scso_keywords_map[$scso_row->post_id]) 
        ? $scso_keywords_map[$scso_row->post_id] 
        : [];
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
                        <span class="scso-money-badge">💰 <?php esc_html_e('Money Post', 'rankiva-seo-insights-for-gsc'); ?><span class="scso-tooltip" data-tip="<?php esc_html_e('This post already performs well in Google and is close to top rankings. Improving this page usually results in faster traffic and revenue growth.', 'rankiva-seo-insights-for-gsc'); ?>">ℹ️</span></span>
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

        <?php 
        /**
         * KEYWORDS SECTION (NEW in v1.1)
         * Shows top 5 keywords driving traffic to this post
         */

/**
 * Enhanced Keywords Section for opportunities-list.php
 * Replace the existing keywords section with this improved version
 */

    if (!empty($scso_post_keywords)): 
        // Find the BEST priority keyword (not just highest impressions)
        $scso_priority_kw = null;
        
        foreach ($scso_post_keywords as $scso_kw) {
            $scso_pos = (float) $scso_kw->avg_position;
            $scso_impr = (int) $scso_kw->impressions;
            
            // Skip keywords outside striking distance (position > 30)
            if ($scso_pos > 30) continue;
            
            // Prefer keywords in positions 5-20 with decent impressions
            if ($scso_priority_kw === null) {
                $scso_priority_kw = $scso_kw;
            } else {
                $scso_current_pos = (float) $scso_priority_kw->avg_position;
                $scso_current_impr = (int) $scso_priority_kw->impressions;
                
                // Scoring: prefer position 5-15, then impressions
                $scso_kw_score = ($scso_pos <= 15 ? 100 : 50) + ($scso_impr / 10);
                $scso_current_score = ($scso_current_pos <= 15 ? 100 : 50) + ($scso_current_impr / 10);
                
                if ($scso_kw_score > $scso_current_score) {
                    $scso_priority_kw = $scso_kw;
                }
            }
        }
        
        // Fallback to first keyword if none qualify
        if ($scso_priority_kw === null) {
            $scso_priority_kw = $scso_post_keywords[0];
        }
?>

<!-- Priority Keyword Highlight -->
<?php if ($scso_priority_kw && (float) $scso_priority_kw->avg_position <= 20):
    $scso_priority_pos = (float) $scso_priority_kw->avg_position;
    $scso_priority_ctr = (float) $scso_priority_kw->ctr;
    $scso_expected_ctr = scso_expected_ctr($scso_priority_pos);
?>
<div class="scso-priority-keyword">
    <span class="scso-priority-icon">🔥</span>
    <span class="scso-priority-label"><?php esc_html_e('Priority keyword:', 'rankiva-seo-insights-for-gsc'); ?></span>
    <span class="scso-priority-text">
        <?php esc_html_e('Optimize this page for', 'rankiva-seo-insights-for-gsc'); ?>
        <strong>#<?php echo esc_html(number_format_i18n($scso_priority_pos, 1)); ?></strong>
        "<?php echo esc_html($scso_priority_kw->keyword); ?>",
        <?php echo esc_html(number_format_i18n($scso_priority_kw->clicks)); ?> <?php esc_html_e('clicks and', 'rankiva-seo-insights-for-gsc'); ?>
        <span class="scso-priority-ctr">📊 <?php echo esc_html(number_format_i18n($scso_priority_ctr, 1)); ?>% CTR</span>
    </span>
</div>
<?php endif; ?>

<!-- Keywords Table -->
<div class="scso-keywords-section">
    <button type="button" class="scso-keywords-toggle" aria-expanded="false">
        <span class="scso-keywords-toggle-icon">🔑</span>
        <span class="scso-keywords-toggle-text"><?php esc_html_e('Top Keywords', 'rankiva-seo-insights-for-gsc'); ?></span>
        <span class="scso-keywords-count">(<?php echo count($scso_post_keywords); ?>)</span>
        <span class="scso-keywords-arrow">▼</span>
    </button>
    
    <div class="scso-keywords-list" style="display: none;">
        <table class="scso-keywords-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('KEYWORD', 'rankiva-seo-insights-for-gsc'); ?></th>
                    <th><?php esc_html_e('POS', 'rankiva-seo-insights-for-gsc'); ?></th>
                    <th><?php esc_html_e('IMPR', 'rankiva-seo-insights-for-gsc'); ?></th>
                    <th><?php esc_html_e('CLICKS', 'rankiva-seo-insights-for-gsc'); ?></th>
                    <th><?php esc_html_e('CTR', 'rankiva-seo-insights-for-gsc'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scso_post_keywords as $scso_index => $scso_kw): 
                    $scso_kw_pos = (float) $scso_kw->avg_position;
                    $scso_kw_ctr = (float) $scso_kw->ctr;
                    $scso_kw_impr = (int) $scso_kw->impressions;
                    
                    // Determine keyword quality badge
                    $scso_badge_class = '';
                    $scso_badge_label = '';
                    
                    if ($scso_kw_pos <= 5 && $scso_kw_impr >= 10) {
                        $scso_badge_class = 'scso-badge-best';
                        $scso_badge_label = 'BEST';
                    } elseif ($scso_kw_pos <= 10 && $scso_kw_impr >= 5) {
                        $scso_badge_class = 'scso-badge-great';
                        $scso_badge_label = 'GREAT';
                    } elseif ($scso_kw_pos <= 20) {
                        $scso_badge_class = 'scso-badge-good';
                        $scso_badge_label = 'GOOD';
                    } else {
                        $scso_badge_class = 'scso-badge-low';
                        $scso_badge_label = 'LOW';
                    }
                    
                    // Position styling
                    $scso_pos_class = '';
                    if ($scso_kw_pos <= 3) {
                        $scso_pos_class = 'scso-pos-top3';
                    } elseif ($scso_kw_pos <= 10) {
                        $scso_pos_class = 'scso-pos-page1';
                    } elseif ($scso_kw_pos <= 20) {
                        $scso_pos_class = 'scso-pos-page2';
                    }
                ?>
                <tr>
                    <td class="scso-keyword-text">
                        <?php if ($scso_badge_label): ?>
                        <span class="scso-kw-badge <?php echo esc_attr($scso_badge_class); ?>">
                            <?php echo esc_html($scso_badge_label); ?>
                        </span>
                        <?php endif; ?>
                        <?php echo esc_html($scso_kw->keyword); ?>
                    </td>
                    <td class="scso-keyword-pos">
                        <?php 
                        $scso_pos_tip = '';
                        if ($scso_kw_pos <= 3) {
                            $scso_pos_tip = __('Top 3 - Excellent!', 'rankiva-seo-insights-for-gsc');
                        } elseif ($scso_kw_pos <= 5) {
                            $scso_pos_tip = __('Almost top 3', 'rankiva-seo-insights-for-gsc');
                        } elseif ($scso_kw_pos <= 10) {
                            $scso_pos_tip = __('Page 1 potential', 'rankiva-seo-insights-for-gsc');
                        } else {
                            $scso_pos_tip = __('Needs work', 'rankiva-seo-insights-for-gsc');
                        }
                        ?>
                        <span class="<?php echo esc_attr($scso_pos_class); ?>" title="<?php echo esc_attr($scso_pos_tip); ?>">
                            #<?php echo esc_html(number_format_i18n($scso_kw_pos, 1)); ?>
                        </span>
                    </td>
                    <td class="scso-keyword-impr" 
                        title="<?php esc_attr_e('Number of times this keyword appeared in search results', 'rankiva-seo-insights-for-gsc'); ?>">
                        <?php echo esc_html(number_format_i18n($scso_kw_impr)); ?>
                    </td>
                    <td class="scso-keyword-clicks" 
                        title="<?php esc_attr_e('Number of clicks this keyword generated', 'rankiva-seo-insights-for-gsc'); ?>">
                        <span class="scso-click-count">
                            <?php echo esc_html(number_format_i18n($scso_kw->clicks)); ?>
                        </span>
                    </td>
                    <td class="scso-keyword-ctr" 
                        title="<?php esc_attr_e('Click-through rate for this keyword', 'rankiva-seo-insights-for-gsc'); ?>">
                        <?php echo esc_html(number_format_i18n($scso_kw_ctr, 1)); ?>%
                        <?php 
                        // Show CTR performance indicator
                        $scso_expected = scso_expected_ctr($scso_kw_pos);
                        if ($scso_kw_ctr >= $scso_expected * 0.9) {
                            echo '<span class="scso-ctr-indicator scso-ctr-good" title="' . esc_attr__('Good CTR', 'rankiva-seo-insights-for-gsc') . '">↗</span>';
                        } elseif ($scso_kw_ctr < $scso_expected * 0.6) {
                            echo '<span class="scso-ctr-indicator scso-ctr-low" title="' . esc_attr__('Low CTR - needs improvement', 'rankiva-seo-insights-for-gsc') . '">↘</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Expected variables:
 * @var int    $scso_page
 * @var int    $scso_total_pages
 * @var string $scso_search
 * @var bool   $scso_money_only
 * @var bool   $scso_ctr_gap_only
 * @var bool   $scso_show_snoozed
 * @var int    $scso_limit
 */

if ( $scso_total_pages <= 1 ) {
    return;
}

/**
 * Helper function to build pagination URL
 * 
 * @param int    $scso_p Page number
 * @param string $scso_s Search term
 * @param bool   $scso_mo Money only filter
 * @param bool   $scso_cg CTR gap only filter
 * @param bool   $scso_ss Show snoozed filter
 * @param int    $scso_pp Posts per page
 * @return string Escaped URL
 */
function scso_build_pagination_url($scso_p, $scso_s, $scso_mo, $scso_cg, $scso_ss, $scso_pp = null) {
    $scso_query_args = [
        'page' => 'scso-opportunities',
        'p'    => $scso_p,
    ];
    
    if ( $scso_s !== '' ) {
        $scso_query_args['s'] = $scso_s;
    }
    if ( $scso_mo ) {
        $scso_query_args['money_only'] = 1;
    }
    if ( $scso_cg ) {
        $scso_query_args['ctr_gap_only'] = 1;
    }
    if ( $scso_ss ) {
        $scso_query_args['show_snoozed'] = 1;
    }
    if ( $scso_pp ) {
        $scso_query_args['per_page'] = $scso_pp;
    }
    
    return esc_url( add_query_arg( $scso_query_args, admin_url( 'admin.php' ) ) );
}

// Calculate page range to show
$scso_range = 2; // Show 2 pages before and after current
$scso_start = max(1, $scso_page - $scso_range);
$scso_end = min($scso_total_pages, $scso_page + $scso_range);

// Get current per page setting (validated and sanitized)
$scso_current_limit = isset($scso_limit) ? $scso_limit : 20;
if ( isset( $_GET['per_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter
    $scso_current_limit = intval( $_GET['per_page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $scso_current_limit = in_array( $scso_current_limit, [10, 20, 50, 100], true ) ? $scso_current_limit : 20;
}
?>

<div class="scso-pagination-wrapper">
    <!-- Left: Page Navigation -->
    <div class="scso-pagination">
        <!-- Previous Button -->
        <?php if ($scso_page > 1): ?>
            <a href="<?php echo esc_url( scso_build_pagination_url($scso_page - 1, $scso_search, $scso_money_only, $scso_ctr_gap_only, $scso_show_snoozed) ); ?>" 
               class="scso-page-btn scso-page-arrow">
                ←
            </a>
        <?php else: ?>
            <span class="scso-page-btn scso-page-arrow scso-page-disabled">←</span>
        <?php endif; ?>

        <!-- First Page -->
        <?php if ($scso_start > 1): ?>
            <a href="<?php echo esc_url( scso_build_pagination_url(1, $scso_search, $scso_money_only, $scso_ctr_gap_only, $scso_show_snoozed) ); ?>" 
               class="scso-page-btn">
                1
            </a>
            <?php if ($scso_start > 2): ?>
                <span class="scso-page-dots">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php for ($scso_i = $scso_start; $scso_i <= $scso_end; $scso_i++): ?>
            <a href="<?php echo esc_url( scso_build_pagination_url($scso_i, $scso_search, $scso_money_only, $scso_ctr_gap_only, $scso_show_snoozed) ); ?>" 
               class="scso-page-btn <?php echo $scso_i === $scso_page ? 'scso-page-active' : ''; ?>">
                <?php echo esc_html($scso_i); ?>
            </a>
        <?php endfor; ?>

        <!-- Last Page -->
        <?php if ($scso_end < $scso_total_pages): ?>
            <?php if ($scso_end < $scso_total_pages - 1): ?>
                <span class="scso-page-dots">...</span>
            <?php endif; ?>
            <a href="<?php echo esc_url( scso_build_pagination_url($scso_total_pages, $scso_search, $scso_money_only, $scso_ctr_gap_only, $scso_show_snoozed) ); ?>" 
               class="scso-page-btn">
                <?php echo esc_html($scso_total_pages); ?>
            </a>
        <?php endif; ?>

        <!-- Next Button -->
        <?php if ($scso_page < $scso_total_pages): ?>
            <a href="<?php echo esc_url( scso_build_pagination_url($scso_page + 1, $scso_search, $scso_money_only, $scso_ctr_gap_only, $scso_show_snoozed) ); ?>" 
               class="scso-page-btn scso-page-arrow">
                →
            </a>
        <?php else: ?>
            <span class="scso-page-btn scso-page-arrow scso-page-disabled">→</span>
        <?php endif; ?>

    </div>

    <!-- Right: Posts per page & Page info -->
    <div class="scso-pagination-info">
        <span class="scso-pagination-page-info">
            <?php esc_html_e('Page ', 'rankiva-seo-insights-for-gsc'); ?><?php echo esc_html($scso_page); ?> <?php esc_html_e('of', 'rankiva-seo-insights-for-gsc'); ?> <?php echo esc_html($scso_total_pages); ?>
        </span>
        
        <label class="scso-pagination-post-perpage">
            <?php esc_html_e('Posts per page:', 'rankiva-seo-insights-for-gsc'); ?>
            <select class="scso-per-page-select" data-scso-per-page>
                <?php
                $scso_per_page_options = [10, 20, 50, 100];
                foreach ($scso_per_page_options as $scso_option):
                    // Build URL with per_page and reset to page 1
                    $scso_args = ['page' => 'scso-opportunities', 'per_page' => $scso_option, 'p' => 1];
                    if ($scso_search !== '') {
                        $scso_args['s'] = $scso_search;
                    }
                    if ($scso_money_only) {
                        $scso_args['money_only'] = 1;
                    }
                    if ($scso_ctr_gap_only) {
                        $scso_args['ctr_gap_only'] = 1;
                    }
                    if ($scso_show_snoozed) {
                        $scso_args['show_snoozed'] = 1;
                    }
                    $scso_url = esc_url(add_query_arg($scso_args, admin_url('admin.php')));
                ?>
                    <option value="<?php echo esc_url( $scso_url ); ?>" <?php selected($scso_current_limit, $scso_option); ?>>
                        <?php echo esc_html($scso_option); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
</div>
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Expected variables:
 * @var string $property
 * @var bool   $is_localhost
 */
?>

<div class="scso-connection-banner">
    <div class="scso-status-dot"></div>
    <div class="scso-connection-text">
        <h3><?php esc_html_e('Connected to Google Search Console', 'rankiva-seo-insights-for-gsc'); ?></strong></h3>
        <?php if ($property): ?>
        <p><?php esc_html_e('Property:', 'rankiva-seo-insights-for-gsc'); ?> <span><?php echo esc_html($property); ?></span></p>
        <?php else: ?>
            <p><?php esc_html_e('Data pending verification. Please run a sync.', 'rankiva-seo-insights-for-gsc'); ?></p>
        <?php endif; ?>
    </div>
    <?php if ($this->is_localhost() && $property): ?>
        <div class="scso-connection-text">
            <p><?php esc_html_e('Localhost detected.', 'rankiva-seo-insights-for-gsc'); ?></p>
            <p>
                <?php esc_html_e('Showing data from verified property:', 'rankiva-seo-insights-for-gsc'); ?>
                <span><?php echo esc_html($property); ?></span>
            </p>
        </div>
    <?php endif; ?>
</div>
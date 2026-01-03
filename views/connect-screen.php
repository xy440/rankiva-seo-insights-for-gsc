<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Google Search Console connection screen.
 *
 * Expected variables:
 * @var SCSO_Auth $auth
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

            <h1><?php esc_html_e('SEO Insights for', 'rankiva-seo-insights-for-gsc'); ?><br><?php esc_html_e('Google Search Console', 'rankiva-seo-insights-for-gsc'); ?></h1>
            <p class="scso-subtitle"><?php esc_html_e('See which posts Google already ranks but are not getting enough clicks.', 'rankiva-seo-insights-for-gsc'); ?></p>

            <div class="scso-features-list">
                <div class="scso-feature-item">
                    <div class="scso-feature-icon">📊</div>
                    <div class="scso-feature-text">
                        <div class="scso-feature-title"><?php esc_html_e('Discover Hidden Opportunities', 'rankiva-seo-insights-for-gsc'); ?></div>
                        <div class="scso-feature-desc"><?php esc_html_e('Find posts ranking well but underperforming', 'rankiva-seo-insights-for-gsc'); ?></div>
                    </div>
                </div>
                <div class="scso-feature-item">
                    <div class="scso-feature-icon">🎯</div>
                    <div class="scso-feature-text">
                        <div class="scso-feature-title"><?php esc_html_e('Smart Recommendations', 'rankiva-seo-insights-for-gsc'); ?></div>
                        <div class="scso-feature-desc"><?php esc_html_e('Get actionable insights to improve CTR and rankings', 'rankiva-seo-insights-for-gsc'); ?></div>
                    </div>
                </div>
                <div class="scso-feature-item">
                    <div class="scso-feature-icon">⚡</div>
                    <div class="scso-feature-text">
                        <div class="scso-feature-title"><?php esc_html_e('Real-time Data Sync', 'rankiva-seo-insights-for-gsc'); ?></div>
                        <div class="scso-feature-desc"><?php esc_html_e('Always up-to-date with your latest search performance', 'rankiva-seo-insights-for-gsc'); ?></div>
                    </div>
                </div>
            </div>

            <button class="scso-btn">
                <span>🔗</span>
                <a class="scso-link" href="<?php echo esc_url($this->auth->get_connect_url()); ?>"><?php esc_html_e('Connect Google Search Console', 'rankiva-seo-insights-for-gsc'); ?></a>
            </button>

            <div class="scso-security-badge">
                <span class="scso-security-icon">🔒</span>
                <span class="scso-security-text"><?php esc_html_e('Read-only access. You can disconnect at any time.', 'rankiva-seo-insights-for-gsc'); ?></span>
            </div>

            <div class="scso-footer">
                <p class="scso-footer-text">
                    <span>🔐</span>
                    <span><?php esc_html_e('Google Search Console is a trademark of Google LLC. This plugin is not affiliated with Google.', 'rankiva-seo-insights-for-gsc'); ?></span>
                </p>
            </div>    
                
            </div>
        </div>
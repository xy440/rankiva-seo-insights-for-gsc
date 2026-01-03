<?php
if (!defined('ABSPATH')) exit;

/**
 * OAuth Settings view - FIXED VERSION
 *
 * Expected variables:
 * @var bool   $use_proxy
 * @var string $client_id
 * @var string $client_secret
 */
?>

<div class="scso-container-opportunities">
    <form method="post" action="">
        <?php wp_nonce_field('scso_oauth_settings'); ?>
        
        <div class="scso-header">
            <h1><?php esc_html_e('OAuth Settings', 'rankiva-seo-insights-for-gsc'); ?></h1>
        </div>

        <div class="scso-info-card">
            <h3><?php esc_html_e('Choose how to authenticate with Google Search Console:', 'rankiva-seo-insights-for-gsc'); ?></h3>
            <div class="scso-info-item">
                <strong><?php esc_html_e('Option 1 (Recommended):', 'rankiva-seo-insights-for-gsc'); ?></strong> <?php esc_html_e('Use our secure OAuth proxy - Easy setup, no configuration needed.', 'rankiva-seo-insights-for-gsc'); ?>
            </div>
            <div class="scso-info-item">
                <strong><?php esc_html_e('Option 2 (Advanced):', 'rankiva-seo-insights-for-gsc'); ?></strong> <?php esc_html_e('Use your own Google OAuth app - More control, but requires Google Cloud setup.', 'rankiva-seo-insights-for-gsc'); ?>
            </div>
        </div>

        <div class="scso-settings-card">
            <div class="scso-form-group">
                <label class="scso-form-label"><?php esc_html_e('Authentication Method', 'rankiva-seo-insights-for-gsc'); ?></label>
                    
                <label class="scso-radio-option <?php echo $use_proxy ? 'active' : ''; ?>" id="proxy-option">
                    <div class="scso-radio-header">
                        <input type="radio" 
                               name="scso_auth_method" 
                               value="proxy" 
                               class="scso-radio-input" 
                               <?php checked($use_proxy, 1); ?>>
                        <div class="scso-radio-content">
                            <div class="scso-radio-title"><?php esc_html_e('Use OAuth Proxy (Recommended for most users)', 'rankiva-seo-insights-for-gsc'); ?></div>
                            <div class="scso-radio-desc">
                                <?php esc_html_e('Our secure proxy handles OAuth authentication. No setup required.', 'rankiva-seo-insights-for-gsc'); ?>
                            </div>
                            <div class="scso-privacy-note">
                                <strong>🔒 <?php esc_html_e('Privacy:', 'rankiva-seo-insights-for-gsc'); ?></strong>
                                <span><?php esc_html_e('Only OAuth tokens flow through our proxy. Your Search Console data stays between Google and your site.', 'rankiva-seo-insights-for-gsc'); ?></span>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="scso-radio-option <?php echo !$use_proxy ? 'active' : ''; ?>" id="custom-option">
                    <div class="scso-radio-header">
                        <input type="radio" 
                               name="scso_auth_method" 
                               value="custom" 
                               class="scso-radio-input"
                               <?php checked($use_proxy, 0); ?>>
                        <div class="scso-radio-content">
                            <div class="scso-radio-title"><?php esc_html_e('Use Custom OAuth Credentials (Advanced)', 'rankiva-seo-insights-for-gsc'); ?></div>
                            <div class="scso-radio-desc">
                                <?php esc_html_e('Use your own Google OAuth app for complete control.', 'rankiva-seo-insights-for-gsc'); ?>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <div class="scso-custom-section <?php echo !$use_proxy ? 'visible' : ''; ?>" id="custom-credentials">
                <h3><?php esc_html_e('Custom OAuth Credentials', 'rankiva-seo-insights-for-gsc'); ?></h3>
                <p class="scso-custom-intro">
                    <?php 
                    printf(
                        /* translators: %s: link to Google Cloud Console */
                        esc_html__('To use your own OAuth app, %s and add these redirect URIs:', 'rankiva-seo-insights-for-gsc'),
                        '<a href="https://console.cloud.google.com" target="_blank" rel="noopener noreferrer">' . esc_html__('create a Google Cloud project', 'rankiva-seo-insights-for-gsc') . '</a>'
                    );
                    ?>
                </p>

                <div class="scso-redirect-url">
                    <?php echo esc_url(admin_url('admin.php?page=scso-opportunities&action=scso_callback')); ?>
                </div>

                <div class="scso-input-group">
                    <label class="scso-input-label" for="scso_oauth_client_id"><?php esc_html_e('Client ID', 'rankiva-seo-insights-for-gsc'); ?></label>
                    <input type="text"
                           class="scso-input"
                           id="scso_oauth_client_id" 
                           name="scso_oauth_client_id" 
                           value="<?php echo esc_attr($client_id); ?>"
                           required
                           placeholder="<?php esc_attr_e('Enter your Google OAuth Client ID', 'rankiva-seo-insights-for-gsc'); ?>">

                </div>

                <div class="scso-input-group">
                    <label class="scso-input-label" for="scso_oauth_client_secret"><?php esc_html_e('Client Secret', 'rankiva-seo-insights-for-gsc'); ?></label>
                    <input type="password"
                           class="scso-input"
                           id="scso_oauth_client_secret" 
                           name="scso_oauth_client_secret" 
                           value="<?php echo esc_attr($client_secret); ?>" 
                           placeholder="<?php esc_attr_e('Enter your Google OAuth Client Secret', 'rankiva-seo-insights-for-gsc');?>"
                           required
                           autocomplete="off">
                    <?php if (!empty($client_secret)): ?>
                    <small style="color: rgba(255, 255, 255, 0.5); font-size: 12px; margin-top: 8px; display: block;">
                        <?php esc_html_e('Secret is saved. Leave blank to keep current value.', 'rankiva-seo-insights-for-gsc'); ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>

            <input type="submit" 
                   name="scso_save_oauth_settings" 
                   value="<?php esc_attr_e('Save Settings', 'rankiva-seo-insights-for-gsc'); ?>" 
                   class="scso-save-btn" 
                   id="scso_save_oauth_settings">
        </div>
            
    </form>   
</div>
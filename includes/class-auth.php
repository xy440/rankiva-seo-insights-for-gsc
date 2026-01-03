<?php
if (!defined('ABSPATH')) exit;

class SCSO_GSC_Auth {

    private $proxy_url = 'https://auth.wpfixfree.com/oauth/';
    private $use_proxy = true;
    
    private $client_id = '';
    private $client_secret = '';

    public function __construct() {
        
        $this->use_proxy = get_option('scso_use_proxy', true);
        
        if (!$this->use_proxy) {
            $this->client_id = get_option('scso_oauth_client_id', '');
            $this->client_secret = get_option('scso_oauth_client_secret', '');
        }
        
        if (defined('SCSO_PROXY_URL')) {
            $this->proxy_url = rtrim(SCSO_PROXY_URL, '/') . '/';
        }
        
        if (defined('SCSO_OAUTH_CLIENT_ID') && defined('SCSO_OAUTH_CLIENT_SECRET')) {
            $this->use_proxy = false;
            $this->client_id = SCSO_OAUTH_CLIENT_ID;
            $this->client_secret = SCSO_OAUTH_CLIENT_SECRET;
        }

        add_action('admin_init', [$this, 'handle_callback']);
        add_action('wp_ajax_scso_disconnect', [$this, 'ajax_disconnect']);
        add_action('admin_menu', [$this, 'add_settings_page'], 99);
    }

    public function add_settings_page() {
        add_submenu_page(
            'scso-opportunities',
            esc_html__('OAuth Settings', 'rankiva-seo-insights-for-gsc'),
            esc_html__('OAuth Settings', 'rankiva-seo-insights-for-gsc'),
            'manage_options',
            'scso-oauth-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        if (isset($_POST['scso_save_oauth_settings'])) {
            check_admin_referer('scso_oauth_settings');
            
            $auth_method = isset($_POST['scso_auth_method']) 
                ? sanitize_text_field(wp_unslash($_POST['scso_auth_method'])) 
                : 'proxy';
            
            $use_proxy = ($auth_method === 'proxy') ? 1 : 0;
            update_option('scso_use_proxy', $use_proxy);
            
            if (!$use_proxy) {
                $client_id = isset($_POST['scso_oauth_client_id']) 
                    ? sanitize_text_field(wp_unslash($_POST['scso_oauth_client_id'])) 
                    : '';
                
                $client_secret = isset($_POST['scso_oauth_client_secret']) 
                    ? sanitize_text_field(wp_unslash($_POST['scso_oauth_client_secret'])) 
                    : '';
                
                update_option('scso_oauth_client_id', $client_id);
                
                if (!empty($client_secret)) {
                    update_option('scso_oauth_client_secret', $client_secret);
                    $this->client_secret = $client_secret;
                }
                
                $this->client_id = $client_id;
                
                $existing_secret = get_option('scso_oauth_client_secret', '');
                if (empty($client_id) || empty($existing_secret)) {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Warning: Custom OAuth selected but credentials are incomplete.', 'rankiva-seo-insights-for-gsc') . '</p></div>';
                } else {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'rankiva-seo-insights-for-gsc') . '</p></div>';
                }
            } else {
                update_option('scso_oauth_client_id', '');
                update_option('scso_oauth_client_secret', '');
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully! Using OAuth proxy.', 'rankiva-seo-insights-for-gsc') . '</p></div>';
            }
            
            $this->use_proxy = $use_proxy;
            
            if (get_option('scso_gsc_token')) {
                echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('OAuth method changed. Please reconnect your Google Search Console account.', 'rankiva-seo-insights-for-gsc') . '</p></div>';
                $this->disconnect();
            }
        }
        
        $use_proxy = (int) get_option('scso_use_proxy', 1);
        $client_id = get_option('scso_oauth_client_id', '');
        $client_secret = get_option('scso_oauth_client_secret', '');
        
        require SCSO_DIR . 'views/oauth-settings.php';
    }

    public function is_connected() {
        $token = get_option('scso_gsc_token');
        return !empty($token['access_token']);
    }

    public function get_connect_url() {
        if ($this->use_proxy) {
            return $this->get_proxy_connect_url();
        } else {
            return $this->get_direct_connect_url();
        }
    }
    
    private function get_proxy_connect_url() {
        $nonce = wp_create_nonce('scso_oauth_callback');
        
        $return_url = add_query_arg([
            'page'   => 'scso-opportunities',
            'action' => 'scso_callback',
            'nonce'  => $nonce,
        ], admin_url('admin.php'));
        
        return $this->proxy_url . '?return_url=' . urlencode($return_url);
    }
    
    private function get_direct_connect_url() {
        if (empty($this->client_id)) {
            return admin_url('admin.php?page=scso-oauth-settings');
        }
        
        $nonce = wp_create_nonce('scso_oauth_callback');
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->get_redirect_uri($nonce),
            'response_type' => 'code',
            'scope'         => implode(' ', [
                'openid',
                'email',
                'https://www.googleapis.com/auth/webmasters.readonly',
            ]),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
    }

    private function get_redirect_uri($nonce = '') {
        $args = [
            'page'   => 'scso-opportunities',
            'action' => 'scso_callback',
        ];
        
        if ($nonce) {
            $args['nonce'] = $nonce;
        }
        
        return add_query_arg($args, admin_url('admin.php'));
    }

    public function handle_callback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

        if ($action !== 'scso_callback') {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'scso_oauth_callback')) {
            wp_die(
                esc_html__('Security check failed.', 'rankiva-seo-insights-for-gsc'),
                esc_html__('Security Error', 'rankiva-seo-insights-for-gsc'),
                ['response' => 403]
            );
        }

        if ($this->use_proxy) {
            $this->handle_proxy_callback();
        } else {
            $this->handle_direct_callback();
        }
    }
    
    private function handle_direct_callback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        
        if (empty($code)) {
            wp_safe_redirect(admin_url('admin.php?page=scso-opportunities&oauth=cancelled'));
            exit;
        }
        
        $tokens = $this->exchange_code_direct($code);
        
        if (!$tokens) {
            wp_die('Failed to exchange OAuth code for tokens.');
        }
        
        update_option('scso_gsc_token', $tokens, false);
        
        if (!empty($tokens['id_token'])) {
            $parts = explode('.', $tokens['id_token']);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                update_option('scso_gsc_account_id', $payload['sub'] ?? '', false);
                update_option('scso_gsc_account_email', $payload['email'] ?? '', false);
            }
        }
        
        delete_transient('scso_sync_error');
        
        wp_safe_redirect(admin_url('admin.php?page=scso-opportunities'));
        exit;
    }
    
    private function exchange_code_direct($code) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 30,
            'body' => [
                'code'          => $code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $tokens = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($tokens['access_token'])) {
            return false;
        }
        
        $tokens['created_at'] = time();
        return $tokens;
    }

    private function handle_proxy_callback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $temp_token = isset($_GET['scso_token']) ? sanitize_text_field(wp_unslash($_GET['scso_token'])) : '';

        if (empty($temp_token)) {
            wp_safe_redirect(admin_url('admin.php?page=scso-opportunities&oauth=cancelled'));
            exit;
        }

        $retrieve_url = rtrim($this->proxy_url, '/') . '/retrieve.php?token=' . urlencode($temp_token);

        $response = wp_remote_get($retrieve_url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            wp_die(esc_html('OAuth token retrieval failed: ' . $response->get_error_message()));
        }

        $tokens = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($tokens['access_token'])) {
            wp_die('Invalid OAuth response.');
        }

        $tokens['created_at'] = $tokens['created_at'] ?? time();
        $account_id = $tokens['account_id'] ?? $tokens['sub'] ?? $tokens['email'] ?? ('proxy-' . md5($tokens['access_token']));

        update_option('scso_gsc_account_id', $account_id, false);
        update_option('scso_gsc_account_email', $tokens['email'] ?? '', false);
        update_option('scso_gsc_token', $tokens, false);
        delete_transient('scso_sync_error');

        wp_safe_redirect(admin_url('admin.php?page=scso-opportunities'));
        exit;
    }

    public function get_access_token() {
        $token = get_option('scso_gsc_token');
        if (empty($token['access_token'])) {
            return false;
        }

        $created = (int) ($token['created_at'] ?? 0);
        $expires = (int) ($token['expires_in'] ?? 3600);

        if (time() < ($created + $expires - 300)) {
            return $token['access_token'];
        }

        if ($this->use_proxy) {
            return false;
        }

        if (!empty($token['refresh_token']) && !empty($this->client_id) && !empty($this->client_secret)) {
            $refreshed = $this->refresh_access_token($token['refresh_token']);
            if ($refreshed && !empty($refreshed['access_token'])) {
                if (empty($refreshed['refresh_token'])) {
                    $refreshed['refresh_token'] = $token['refresh_token'];
                }
                $refreshed['created_at'] = time();
                update_option('scso_gsc_token', $refreshed, false);
                return $refreshed['access_token'];
            }
        }

        return false;
    }

    private function refresh_access_token($refresh_token) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 30,
            'body' => [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh_token,
            ],
        ]);

        if (is_wp_error($response)) {
            // Only log when WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SCSO: Token refresh failed: ' . $response->get_error_message());
            }
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $tokens = json_decode($body, true);

        if (empty($tokens['access_token'])) {
            // Only log when WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SCSO: Token refresh returned no access_token');
            }
            return false;
        }

        if (empty($tokens['refresh_token'])) {
            $tokens['refresh_token'] = $refresh_token;
        }

        return $tokens;
    }

    public function ajax_disconnect() {
        check_ajax_referer('scso_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        $this->disconnect();
        wp_send_json_success();
    }

    public function disconnect() {
        delete_option('scso_gsc_token');
        delete_option('scso_gsc_account_id');
        delete_option('scso_gsc_account_email');
        delete_option('scso_gsc_binding');
        delete_option('scso_gsc_property');
        delete_option('scso_sync_state');
        delete_option('scso_last_sync_time');
        delete_transient('scso_sync_lock');
        delete_transient('scso_sync_error');
        wp_clear_scheduled_hook('scso_run_sync_batch');
    }
}
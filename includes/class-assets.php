<?php
/**
 * Assets handler for admin pages.
 *
 * Handles enqueuing of CSS and JavaScript files
 * for the plugin's admin interface.
 *
 * @package SearchConsoleSEOOpportunities
 */

if (!defined('ABSPATH')) exit;

/**
 * Class SCSO_Assets
 *
 * Manages plugin assets (CSS and JavaScript).
 */
class SCSO_Assets {

    /**
     * Constructor.
     *
     * Registers asset hooks.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin CSS and JavaScript.
     *
     * Only loads on plugin pages.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Load only on our plugin pages (main page + oauth settings)
        if (strpos($hook, 'scso-opportunities') === false && strpos($hook, 'scso-oauth-settings') === false) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
    }

    /**
     * Enqueue admin styles.
     *
     * @return void
     */
    private function enqueue_styles() {
        wp_enqueue_style(
            'scso-admin',
            SCSO_URL . 'assets/admin.min.css',
            [],
            SCSO_VERSION
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @return void
     */
    private function enqueue_scripts() {
        wp_enqueue_script(
            'scso-admin',
            SCSO_URL . 'assets/admin.min.js',
            ['jquery'],
            SCSO_VERSION,
            true
        );

        // Localize script with AJAX data and translations
        wp_localize_script('scso-admin', 'scsoData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scso_sync_nonce'),
            'i18n'     => $this->get_translations(),
        ]);
    }

    /**
     * Get translated strings for JavaScript.
     *
     * @return array Associative array of translation keys and values.
     */
    private function get_translations() {
        return [
            'starting_sync'    => esc_html__('Starting sync…', 'rankiva-seo-insights-for-gsc'),
            'syncing'          => esc_html__('Syncing…', 'rankiva-seo-insights-for-gsc'),
            'sync_completed'   => esc_html__('Sync completed!', 'rankiva-seo-insights-for-gsc'),
            'processed_posts'  => esc_html__('processed posts', 'rankiva-seo-insights-for-gsc'),
            'sync_failed'      => esc_html__('Sync failed', 'rankiva-seo-insights-for-gsc'),
            'disconnect'       => esc_html__('Disconnect Google Search Console?', 'rankiva-seo-insights-for-gsc'),
            'initializing'     => esc_html__('Initializing…', 'rankiva-seo-insights-for-gsc'),
        ];
    }
}
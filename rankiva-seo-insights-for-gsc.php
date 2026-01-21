<?php
/**
 * Plugin Name: Rankiva – SEO & Keyword Insights for Google Search Console
 * Plugin URI: https://wordpress.org/plugins/rankiva-seo-insights-for-gsc/
 * Description: Get actionable SEO insights from Google Search Console to identify underperforming content with keyword-level insights!
 * Version: 1.2.0
 * Author: theme-x
 * Author URI: https://theme-x.org/
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: rankiva-seo-insights-for-gsc
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('SCSO_VERSION', '1.2.0');
define('SCSO_DB_VERSION', '1.2.0');
define('SCSO_FILE', __FILE__);
define('SCSO_DIR', plugin_dir_path(__FILE__));
define('SCSO_URL', plugin_dir_url(__FILE__));

// Load core files
require_once SCSO_DIR . 'includes/helpers.php';
require_once SCSO_DIR . 'includes/class-db.php';
require_once SCSO_DIR . 'includes/class-auth.php';
require_once SCSO_DIR . 'includes/class-sync.php';
require_once SCSO_DIR . 'includes/class-admin-page.php';
require_once SCSO_DIR . 'includes/class-assets.php';

/**
 * Plugin activation hook.
 *
 * Creates database tables and sets activation redirect flag.
 */
register_activation_hook(__FILE__, function () {
    SCSO_DB::install();
    update_option('scso_db_version', SCSO_DB_VERSION);
    add_option('scso_activation_redirect', 1);
});

/**
 * Plugin deactivation hook.
 *
 * Clears scheduled cron jobs but keeps synced data.
 */
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('scso_auto_sync');
    wp_clear_scheduled_hook('scso_run_sync_batch');
});

/**
 * Check for database upgrades.
 *
 * Runs on plugins_loaded to ensure tables are up to date.
 *
 * @since 1.1.0
 */
function scso_maybe_upgrade_db() {
    $installed_version = get_option('scso_db_version', '1.0.0');
    
    if (version_compare($installed_version, SCSO_DB_VERSION, '<')) {
        SCSO_DB::install();
        update_option('scso_db_version', SCSO_DB_VERSION);
        
        // Clear caches after upgrade
        if (isset($GLOBALS['scso_db'])) {
            $GLOBALS['scso_db']->clear_caches();
        }
    }
}

/**
 * Redirect to plugin page after activation.
 */
add_action('admin_init', function () {
    if (!get_option('scso_activation_redirect')) {
        return;
    }

    delete_option('scso_activation_redirect');

    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    wp_safe_redirect(admin_url('admin.php?page=scso-opportunities'));
    exit;
});

/**
 * Bootstrap plugin.
 *
 * Initializes core components when WordPress loads.
 */
add_action('plugins_loaded', function () {
    // Check for database upgrades first
    scso_maybe_upgrade_db();
    
    // Initialize database handler
    $GLOBALS['scso_db'] = new SCSO_DB();

    // Initialize authentication handler
    $GLOBALS['scso_auth'] = new SCSO_GSC_Auth();

    // Initialize sync handler (only when connected)
    if ($GLOBALS['scso_auth']->is_connected()) {
        $GLOBALS['scso_sync'] = new SCSO_GSC_Sync(
            $GLOBALS['scso_auth'],
            $GLOBALS['scso_db']
        );
    }

    // Initialize admin UI
    if (is_admin()) {
        new SCSO_Admin_Page(
            $GLOBALS['scso_auth'],
            $GLOBALS['scso_db']
        );
        
        // Initialize assets handler
        new SCSO_Assets();
    }
});

/**
 * Add settings link on plugin page.
 *
 * @since 1.1.0
 * @param array $links Plugin action links.
 * @return array Modified links.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=scso-opportunities') . '">' . 
        esc_html__('View Opportunities', 'rankiva-seo-insights-for-gsc') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

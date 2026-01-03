<?php

/**
 * Admin page controller for Rankiva – SEO Insights for Google Search Console.
 *
 * Responsible for:
 * - Registering the admin menu
 * - Handling page routing (connect, sync required, main dashboard)
 * - Fetching data from DB layer
 * - Passing data to view templates
 * - Handling AJAX actions (mark updated, snooze, unsnooze)
 *
 * @package SearchConsoleSEOOpportunities
 */

if (!defined('ABSPATH')) exit;


/**
 * Class SCSO_Admin_Page
 *
 * Orchestrates the admin UI for SEO Opportunities.
 * Acts as a controller between:
 * - Auth layer
 * - Database layer
 * - View templates
 */
class SCSO_Admin_Page {

    /**
     * Google authentication handler.
     *
     * @var SCSO_Auth
     */
    private $auth;
        
    /**
     * Database handler for SEO opportunities.
     *
     * @var SCSO_DB
     */
    private $db;

    /**
     * Constructor.
     *
     * @param SCSO_Auth $auth Auth handler instance.
     * @param SCSO_DB   $db   Database handler instance.
     */
    public function __construct( $auth, $db ) {
        $this->auth = $auth;
        $this->db   = $db;

        add_action('admin_menu', [$this, 'register_menu']);

        add_action('wp_ajax_scso_mark_updated', [$this, 'ajax_mark_updated']);
        add_action('wp_ajax_scso_snooze', [$this, 'ajax_snooze']);
        add_action('wp_ajax_scso_unsnooze', [$this, 'ajax_unsnooze']);
    }

    /**
     * Registers the admin menu page.
     *
     * @return void
     */
    public function register_menu() {
        add_menu_page(
            esc_html__('Rankiva', 'rankiva-seo-insights-for-gsc'),
            esc_html__('Rankiva', 'rankiva-seo-insights-for-gsc'),
            'manage_options',
            'scso-opportunities',
            [$this, 'render'],
            'dashicons-chart-line',
            30
        );
    }

    /**
     * Renders the admin page.
     *
     * Routing logic:
     * - If not connected → Connect screen
     * - If sync required → Sync required screen
     * - Otherwise → Main dashboard
     *
     * @return void
     */
    public function render() {

        if (!$this->auth->is_connected()) {
            // Clear any error when viewing connect screen
            delete_transient('scso_sync_error');
            $this->render_connect_screen();
            return;
        }

        if ($this->is_sync_required()) {
            $this->render_need_sync_screen();
            return;
        }

        // Clear error when successfully viewing main page
        delete_transient('scso_sync_error');

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only filter form
        $page         = isset($_GET['p']) ? absint($_GET['p']) : 1;
        $page         = max(1, $page);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter
        $limit        = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20; // Add per_page support
        $limit        = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Validate
        $offset       = ($page - 1) * $limit;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only filter form
        $search       = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only filter form
        $money_only   = ! empty($_GET['money_only']);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only filter form
        $ctr_gap_only = !empty($_GET['ctr_gap_only']);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only filter form
        $show_snoozed = !empty($_GET['show_snoozed']);

        $rows = $this->db->get_opportunities([
            'limit'        => $limit,
            'offset'       => $offset,
            'money_only'   => $money_only,
            'search'       => $search,
            'show_snoozed' => $show_snoozed,
            'ctr_gap_only' => $ctr_gap_only,
        ]);

        $total_rows  = $this->db->count_opportunities([
            'money_only'   => $money_only,
            'search'       => $search,
            'show_snoozed' => $show_snoozed,
            'ctr_gap_only' => $ctr_gap_only,
        ]);

        $total_pages = max(1, ceil($total_rows / $limit));

        $stats    = $this->db->stats();
        $property = get_option('scso_gsc_property');
        ?>

        <div class="wrap">

            <?php
            /**
             * Admin page header.
             *
             * Displays the page title, description,
             * and disconnect button.
             */
            require_once SCSO_DIR . 'views/header.php';
            ?>

            <?php
            /**
             * Connection status banner.
             *
             * Shows:
             * - Connected property
             * - Pending verification notice
             * - Localhost warning (if applicable)
             *
             * Expected variables:
             * @var string|null $property
             *
             */
            require_once SCSO_DIR . 'views/connection-status.php';
            ?>

            <?php
            /**
             * Stats cards section.
             *
             * Displays:
             * - High opportunity count
             * - Posts with traffic
             * - Last sync time
             *
             * Expected variables:
             * @var array $scso_stats
             */
            $scso_stats = $stats;
            require_once SCSO_DIR . 'views/stats-cards.php';
            ?>

            <?php
            /**
             * Filters form for opportunities list.
             *
             * Expected variables:
             * @var array $scso_filters {
             *   @type string $search
             *   @type bool   $money_only
             *   @type bool   $ctr_gap_only
             *   @type bool   $show_snoozed
             * }
             *
             */
            $scso_filters = [
                'search'       => $search,
                'money_only'   => $money_only,
                'ctr_gap_only' => $ctr_gap_only,
                'show_snoozed' => $show_snoozed,
            ];

            require_once SCSO_DIR . 'views/filters-form.php';
            ?>

            <?php
            /**
             * Opportunities list view.
             *
             * Renders each opportunity card.
             *
             * Expected variables:
             * @var array            $rows
             * @var bool             $ctr_gap_only
             * @var bool             $show_snoozed
             * @var SCSO_Admin_Page  $scso_admin
             *
             */
            $scso_admin = $this;
            require_once SCSO_DIR . 'views/opportunities-list.php';
            ?>
            

            <?php
            /**
             * Pagination controls for opportunities list.
             *
             * Expected variables:
             * @var int    $scso_page
             * @var int    $scso_total_pages
             * @var string $scso_search
             * @var bool   $scso_money_only
             * @var bool   $scso_ctr_gap_only
             * @var bool   $scso_show_snoozed
             * @var bool   $scso_limit
             */
            $scso_page         = $page;
            $scso_total_pages  = $total_pages;
            $scso_search       = $search;
            $scso_money_only   = $money_only;
            $scso_ctr_gap_only = $ctr_gap_only;
            $scso_show_snoozed = $show_snoozed;
            $scso_limit        = $limit;

            require_once SCSO_DIR . 'views/pagination.php';
            ?>

        </div>
        <?php
    }

    /**
     * Google Search Console connection screen.
     *
     * Expected variables:
     * @var SCSO_Auth $auth
     *
     * @package SearchConsoleSEOOpportunities
     */
    private function render_connect_screen() {
        $auth = $this->auth;
        require_once SCSO_DIR . 'views/connect-screen.php';
    }

    /**
     * Renders the sync required screen.
     *
     * Displays sync errors (if any) and allows
     * the user to run a one-time sync or disconnect.
     *
     * @return void
     */
    private function render_need_sync_screen() {
        // Check for sync error from transient
        $sync_error = get_transient('scso_sync_error');
        require_once SCSO_DIR . 'views/sync-required-screen.php';
    ?>
    
    <?php
    }

    /**
     * AJAX: Mark a post as updated.
     *
     * @return void Sends JSON response.
     */
    public function ajax_mark_updated() {
        check_ajax_referer('scso_sync_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        if (!isset($_POST['post_id'])) {
            wp_send_json_error('Missing post_id');
        }

        $this->db->mark_updated(absint($_POST['post_id']));
        wp_send_json_success();
    }

    /**
     * AJAX: Snooze a post for 30 days.
     *
     * @return void Sends JSON response.
     */
    public function ajax_snooze() {
        check_ajax_referer('scso_sync_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        if (!isset($_POST['post_id'])) {
            wp_send_json_error('Missing post_id');
        }

        $this->db->snooze(absint($_POST['post_id']), 30);
        wp_send_json_success();
    }

    /**
     * AJAX: Remove snooze from a post.
     *
     * @return void Sends JSON response.
     */
    public function ajax_unsnooze() {
        check_ajax_referer('scso_sync_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        if (!isset($_POST['post_id'])) {
            wp_send_json_error('Missing post_id');
        }

        $this->db->unsnooze(absint($_POST['post_id']));
        wp_send_json_success();
    }

    /**
     * Determines whether a sync is required.
     *
     * Checks:
     * - Sync state
     * - Property presence
     * - Account binding hash
     *
     * @return bool True if sync is required.
     */
    private function is_sync_required() {

        $state      = get_option('scso_sync_state', []);
        $binding    = get_option('scso_gsc_binding');
        $account_id = get_option('scso_gsc_account_id');
        $property   = get_option('scso_gsc_property');

        if (empty($state['done'])) {
            return true;
        }

        if (! $property) {
            return true;
        }

        if (!$binding) {
            return true;
        }

        if (!$account_id) {
            $account_id = 'property-' . md5($property);
        }

        $current_binding = md5($account_id . '|' . $property);

        if (! hash_equals($binding, $current_binding)) {
            return true;
        }

        return false;
    }

    /**
     * Determines if a post qualifies as a "Money Post".
     *
     * @param object $row Opportunity row object.
     * @return bool
     */
    public function is_money_post( $row ) {
        return (
            $row->impressions >= 100 &&
            $row->avg_position >= 5 &&
            $row->avg_position <= 20
        );
    }

    /**
     * Detects if the site is running on localhost.
     *
     * @return bool
     */
    private function is_localhost() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $host = isset($_SERVER['HTTP_HOST']) 
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) 
            : '';
        
        // More comprehensive localhost detection
        return in_array($host, [
            'localhost',
            '127.0.0.1',
            'local.test',
            '::1',
        ], true) || preg_match('/\.local$/', $host);
    }
}
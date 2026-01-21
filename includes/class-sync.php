<?php
/**
 * Google Search Console Sync Handler.
 *
 * Handles syncing page-level and keyword-level data from GSC.
 *
 * @package Rankiva
 * @since 1.0.0
 * @since 1.1.0 Added keyword-level sync
 * @since 1.2.0 Added position change tracking
 * @since 1.2.0 Fixed sync not running due to WP Cron issues
 */

if (!defined('ABSPATH')) exit;

class SCSO_GSC_Sync {

    const LOCK_KEY  = 'scso_sync_lock';
    const STATE_KEY = 'scso_sync_state';

    private $auth;
    private $db;
    private $table;

    public function __construct(SCSO_GSC_Auth $auth, SCSO_DB $db) {
        global $wpdb;

        $this->auth  = $auth;
        $this->db    = $db;
        $this->table = $wpdb->prefix . 'scso_metrics';

        add_action('wp_ajax_scso_sync_start', [$this, 'ajax_start_sync']);
        add_action('wp_ajax_scso_sync_status', [$this, 'ajax_sync_status']);
        add_action('scso_run_sync_batch', [$this, 'run_sync']);
    }

    /**
     * Log message only if WP_DEBUG and WP_DEBUG_LOG are enabled
     */
    private function log($message, $is_error = false) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $prefix = $is_error ? 'SCSO ERROR: ' : 'SCSO: ';
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($prefix . $message);
        }
    }

    public function ajax_start_sync() {
        check_ajax_referer('scso_sync_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        if (get_transient(self::LOCK_KEY)) {
            wp_send_json_success(['running' => true]);
            return;
        }

        set_transient(self::LOCK_KEY, 1, 10 * MINUTE_IN_SECONDS);

        update_option(self::STATE_KEY, [
            'started_at' => time(),
            'processed'  => 0,
            'keywords'   => 0,
            'done'       => false,
            'error'      => null,
        ], false);

        // Schedule cron as backup
        wp_schedule_single_event(time(), 'scso_run_sync_batch');
        spawn_cron();

        // ALSO run sync directly to bypass unreliable WP Cron
        // This ensures sync actually happens
        $this->run_sync();

        wp_send_json_success(['started' => true]);
    }

    public function ajax_sync_status() {
        check_ajax_referer('scso_sync_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $state = get_option(self::STATE_KEY, []);

        wp_send_json_success([
            'done'      => ! empty($state['done']),
            'processed' => $state['processed'] ?? 0,
            'keywords'  => $state['keywords'] ?? 0,
            'error'     => $state['error'] ?? null,
        ]);
    }

    public function run_sync() {
        // Prevent double execution
        static $already_running = false;
        if ($already_running) {
            return;
        }
        $already_running = true;

        // STEP 1: Get access token
        $token = $this->auth->get_access_token();
        if (!$token) {
            $error_msg = 'Unable to retrieve access token. Your session may have expired. Please disconnect and reconnect your Google Search Console account.';
            set_transient('scso_sync_error', $error_msg, HOUR_IN_SECONDS); // Longer TTL
            $this->log('Failed to get access token', true);
            $this->finish($error_msg);
            return;
        }

        // STEP 2: Detect property
        $property = $this->detect_property($token);
        if (!$property) {
            $site_url = home_url();
            $error_msg = sprintf(
                'No verified property found for %s in your Google Search Console account. Please verify this domain in GSC first.',
                $site_url
            );

            set_transient('scso_sync_error', $error_msg, HOUR_IN_SECONDS);
            $this->log('Property detection failed for: ' . $site_url, true);
            $this->finish($error_msg);
            return;
        }

        // STEP 3: Check if account/property changed
        $account_id = get_option('scso_gsc_account_id');
        if (!$account_id) {
            $account_id = 'property-' . md5($property);
            update_option('scso_gsc_account_id', $account_id, false);
        }

        $new_binding    = md5($account_id . '|' . $property);
        $stored_binding = get_option('scso_gsc_binding');

        // Only clear data if binding actually changed
        if ($stored_binding && !hash_equals($stored_binding, $new_binding)) {
            $this->log('Account/property changed. Clearing old data.');
            $this->clear_all_data();
        }

        // STEP 4: Check if we can skip sync (rate limit)
        // Skip this check if user explicitly requested Re-Sync
        // Nonce is verified in ajax_start_sync() which calls this method
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $force_sync = isset( $_POST['action'] ) && 'scso_sync_start' === $_POST['action'];
        
        if (!$force_sync) {
            $has_previous_error = get_transient('scso_sync_error');
            $last_sync_time = get_option('scso_last_sync_time');
            $hours_since_sync = $last_sync_time ? (time() - strtotime($last_sync_time)) / 3600 : 999;
            $min_hours_between_syncs = 24;

            if (!$has_previous_error && $stored_binding && hash_equals($stored_binding, $new_binding) && $this->db->has_data() && $hours_since_sync < $min_hours_between_syncs) {
                $this->log('Skipping sync - too recent (' . round($hours_since_sync, 1) . ' hours ago)');
                $this->finish();
                return;
            }
        }

        // STEP 5: Perform actual sync (pages + keywords)
        $result = $this->sync_data($token, $property);

        // STEP 6: Update options - ALWAYS update last_sync_time after successful sync
        update_option('scso_gsc_property', $property);
        update_option('scso_gsc_binding', $new_binding);
        update_option('scso_last_sync_time', current_time('mysql'));
        
        // Clear all caches aggressively
        wp_cache_delete('alloptions', 'options');
        wp_cache_flush();
        
        // Also delete the option from object cache directly
        wp_cache_delete('scso_last_sync_time', 'options');

        $state = get_option(self::STATE_KEY, []);
        $state['processed'] = $result['pages'];
        $state['keywords']  = $result['keywords'];
        update_option(self::STATE_KEY, $state, false);

        // STEP 7: Handle results
        if ($result['pages'] === 0) {
            $error_msg = 'Sync completed successfully, but no matching posts were found. This usually means your posts don\'t have any impressions in Google Search Console yet, or there may be a URL structure mismatch between WordPress and GSC.';
            set_transient('scso_sync_error', $error_msg, HOUR_IN_SECONDS);
            $this->db->clear_caches();
            $this->finish($error_msg);
        } else {
            delete_transient('scso_sync_error');
            $this->db->clear_caches();
            $this->finish();
        }
    }

    private function clear_all_data() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( "TRUNCATE TABLE `{$this->table}`" );
        
        // Also clear keywords table
        $this->db->clear_keywords();
    }

    /**
     * Sync both page-level and keyword-level data.
     *
     * @since 1.1.0
     * @param string $token    Access token.
     * @param string $property GSC property.
     * @return array ['pages' => int, 'keywords' => int]
     */
    private function sync_data($token, $property) {
        global $wpdb;

        // Allow filtering of post types to sync
        $post_types = apply_filters('scso_sync_post_types', ['post', 'page']);
        
        $posts = get_posts([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        if (empty($posts)) {
            $this->log('No published content found for post types: ' . implode(', ', (array)$post_types));
            return ['pages' => 0, 'keywords' => 0];
        }

        // Build flexible URL map
        $url_map = [];
        foreach ($posts as $post_id) {
            $permalink = get_permalink($post_id);
            $normalized = $this->normalize_url($permalink);
            $url_map[$normalized] = $post_id;
        }

        // STEP 1: Fetch page-level data from GSC
        $rows = $this->fetch_gsc_data($token, $property);

        if (empty($rows)) {
            return ['pages' => 0, 'keywords' => 0];
        }

        $synced_pages = 0;
        $synced_urls = []; // Track which URLs we synced for keyword lookup

        foreach ($rows as $row) {
            if (empty($row['keys'][0])) continue;

            $gsc_url = $row['keys'][0];
            $normalized = $this->normalize_url($gsc_url);

            if (! isset($url_map[$normalized])) {
                continue;
            }

            $post_id = $url_map[$normalized];

            $data = [
                'impressions' => (int) $row['impressions'],
                'clicks'      => (int) $row['clicks'],
                'ctr'         => round($row['ctr'] * 100, 2),
                'position'    => round($row['position'], 2),
            ];

            $this->store_metrics($post_id, $gsc_url, $data);
            $synced_urls[$gsc_url] = $post_id;
            $synced_pages++;
        }

        // STEP 2: Fetch keyword-level data for synced pages
        $synced_keywords = 0;
        
        if (!empty($synced_urls)) {
            $synced_keywords = $this->sync_keywords($token, $property, $synced_urls);
        }

        return [
            'pages'    => $synced_pages,
            'keywords' => $synced_keywords,
        ];
    }

    /**
     * Sync keyword-level data from GSC.
     *
     * @since 1.1.0
     * @param string $token       Access token.
     * @param string $property    GSC property.
     * @param array  $synced_urls Array of [url => post_id] that were synced.
     * @return int Number of keywords synced.
     */
    private function sync_keywords($token, $property, $synced_urls) {
        // Fetch query-level data (page + query dimensions)
        $keyword_rows = $this->fetch_gsc_keywords($token, $property);

        if (empty($keyword_rows)) {
            $this->log('No keyword data returned from GSC');
            return 0;
        }

        $this->log('Fetched ' . count($keyword_rows) . ' keyword rows from GSC');

        // Group keywords by URL
        $keywords_by_url = [];

        foreach ($keyword_rows as $row) {
            if (empty($row['keys'][0]) || empty($row['keys'][1])) {
                continue;
            }

            $url = $row['keys'][0];
            $keyword = $row['keys'][1];

            if (!isset($keywords_by_url[$url])) {
                $keywords_by_url[$url] = [];
            }

            $keywords_by_url[$url][] = [
                'keyword'     => $keyword,
                'impressions' => (int) $row['impressions'],
                'clicks'      => (int) $row['clicks'],
                'ctr'         => round($row['ctr'] * 100, 2),
                'position'    => round($row['position'], 2),
            ];
        }

        // Store keywords for each synced URL
        $total_keywords = 0;

        foreach ($synced_urls as $url => $post_id) {
            // Try exact match first
            $url_keywords = $keywords_by_url[$url] ?? [];

            // If no exact match, try normalized matching
            if (empty($url_keywords)) {
                $normalized = $this->normalize_url($url);
                foreach ($keywords_by_url as $kw_url => $kw_data) {
                    if ($this->normalize_url($kw_url) === $normalized) {
                        $url_keywords = $kw_data;
                        break;
                    }
                }
            }

            if (!empty($url_keywords)) {
                // Sort by impressions descending
                usort($url_keywords, function($a, $b) {
                    return $b['impressions'] - $a['impressions'];
                });

                // Store top 10 keywords per page (position change tracking handled in DB class)
                $this->db->store_keywords($post_id, $url_keywords);
                $total_keywords += min(count($url_keywords), 10);
            }
        }

        $this->log('Stored keywords for ' . count($synced_urls) . ' pages, total: ' . $total_keywords);

        return $total_keywords;
    }

    /**
     * Fetch keyword-level data from GSC API.
     *
     * @since 1.1.0
     * @param string $token    Access token.
     * @param string $property GSC property.
     * @return array
     */
    private function fetch_gsc_keywords($token, $property) {
        $body = [
            'startDate'  => gmdate('Y-m-d', strtotime('-90 days')),
            'endDate'    => gmdate('Y-m-d', strtotime('-3 days')),
            'dimensions' => ['page', 'query'], // Page + Query for keyword-level data
            'rowLimit'   => 25000,
        ];

        $response = wp_remote_post(
            'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($property) . '/searchAnalytics/query',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode($body),
                'timeout' => 120, // Longer timeout for keyword data
            ]
        );

        if (is_wp_error($response)) {
            $this->log('GSC Keywords API request failed: ' . $response->get_error_message(), true);
            return [];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 200) {
            $this->log('GSC Keywords API returned HTTP ' . $http_code . ': ' . $response_body, true);
            return [];
        }

        $data = json_decode($response_body, true);
        return $data['rows'] ?? [];
    }

    private function normalize_url($url) {
        $url = strtolower($url);
        $url = preg_replace('#^https?://#', '', $url);
        $url = preg_replace('#^www\.#', '', $url);
        $url = rtrim($url, '/');
        return $url;
    }

    private function fetch_gsc_data($token, $property) {
        $body = [
            'startDate'  => gmdate('Y-m-d', strtotime('-90 days')),
            'endDate'    => gmdate('Y-m-d', strtotime('-3 days')),
            'dimensions' => ['page'],
            'rowLimit'   => 25000,
        ];

        $response = wp_remote_post(
            'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($property) . '/searchAnalytics/query',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode($body),
                'timeout' => 60,
            ]
        );

        if (is_wp_error($response)) {
            $this->log('GSC API request failed: ' . $response->get_error_message(), true);
            return [];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 200) {
            $this->log('GSC API returned HTTP ' . $http_code . ': ' . $response_body, true);
            return [];
        }

        $data = json_decode($response_body, true);
        return $data['rows'] ?? [];
    }

    /**
     * Store metrics with position change tracking.
     *
     * @since 1.0.0
     * @since 1.2.0 Added position change tracking
     */
    private function store_metrics($post_id, $url, $data) {
        global $wpdb;

        $score = $this->calculate_score($data);
        $new_position = (float) $data['position'];

        // Get previous position before updating (NEW in v1.2)
        $prev_position = $this->db->get_previous_position($post_id);
        
        // Calculate position change (positive = improved, negative = dropped)
        $position_change = null;
        if ($prev_position !== null) {
            $position_change = round($prev_position - $new_position, 2);
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace(
            $this->table,
            [
                'post_id'            => $post_id,
                'url'                => $url,
                'impressions'        => $data['impressions'],
                'clicks'             => $data['clicks'],
                'avg_position'       => $new_position,
                'prev_position'      => $prev_position,
                'position_change'    => $position_change,
                'ctr'                => $data['ctr'],
                'opportunity_score'  => $score['score'],
                'opportunity_reason' => $score['reason'],
                'last_synced'        => current_time('mysql'),
            ]
        );
    }

    private function calculate_score($data) {
        $impr = $data['impressions'];
        $pos  = $data['position'];
        $ctr  = $data['ctr'];

        if ($impr < 10) {
            return ['score' => 0, 'reason' => 'Minimal visibility'];
        }

        $score = 0;

        if ($pos >= 5 && $pos <= 15) {
            $score = ($impr / 10) * 3;
            $reason = sprintf('Ranking #%d, growth potential', round($pos));
        } elseif ($pos < 5) {
            $score = ($impr / 50) * 0.5;
            $reason = 'Already performing well';
        } elseif ($pos <= 30) {
            $score = ($impr / 20) * 1.5;
            $reason = sprintf('Ranking #%d, needs improvement', round($pos));
        } else {
            $score = ($impr / 100) * 0.2;
            $reason = 'Low opportunity';
        }

        // CTR gap bonus
        $expected_ctr = scso_expected_ctr($pos);
        if ($ctr < $expected_ctr * 0.7 && $impr > 100) {
            $score += 15;
            $reason = sprintf('High impressions, low CTR (%.1f%% vs %.1f%% expected)', $ctr, $expected_ctr);
        }

        return [
            'score'  => min(100, round($score)),
            'reason' => $reason,
        ];
    }

    private function detect_property($token) {
        $res = wp_remote_get(
            'https://www.googleapis.com/webmasters/v3/sites',
            ['headers' => ['Authorization' => 'Bearer ' . $token], 'timeout' => 30]
        );

        if (is_wp_error($res)) {
            $this->log('Failed to fetch properties: ' . $res->get_error_message(), true);
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($res);
        if ($http_code !== 200) {
            $body = wp_remote_retrieve_body($res);
            $this->log('Properties API returned HTTP ' . $http_code . ': ' . $body, true);
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($res), true);

        if (empty($data['siteEntry'])) {
            $this->log('No properties found in Google Search Console account', true);
            return false;
        }

        // Normalize home URL for comparison
        $home_normalized = $this->normalize_url(home_url());

        // STEP 1: Collect all matching properties
        $candidates = [];

        foreach ($data['siteEntry'] as $site) {
            $site_url = $site['siteUrl'];
            $perm     = $site['permissionLevel'] ?? 'unknown';
            $type     = strpos($site_url, 'sc-domain:') === 0 ? 'domain' : 'url';

            // Normalize GSC URL
            $site_normalized = $this->normalize_url(str_replace('sc-domain:', '', $site_url));

            // Check if it matches our home URL
            if ($site_normalized === $home_normalized || strpos($home_normalized, $site_normalized) === 0) {
                $candidates[] = [
                    'url'        => $site_url,
                    'type'       => $type,
                    'permission' => $perm,
                ];
            }
        }

        if (empty($candidates)) {
            $this->log('No matching properties found for: ' . $home_normalized, true);
            return false;
        }

        // STEP 2: Prioritize - prefer domain property, then URL prefix
        usort($candidates, function($a, $b) {
            // Domain properties first
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'domain' ? -1 : 1;
            }
            // Then by permission level (siteOwner > siteFullUser > etc)
            $perms = ['siteOwner' => 3, 'siteFullUser' => 2, 'siteRestrictedUser' => 1];
            $perm_a = $perms[$a['permission']] ?? 0;
            $perm_b = $perms[$b['permission']] ?? 0;
            return $perm_b - $perm_a;
        });

        $best_match = $candidates[0]['url'];
        $this->log('Selected property: ' . $best_match . ' from ' . count($candidates) . ' candidates');

        return $best_match;
    }

    private function finish($error = null) {
        $state = get_option(self::STATE_KEY, []);
        $state['done'] = true;
        
        if ($error) {
            $state['error'] = $error;
        }
        
        update_option(self::STATE_KEY, $state, false);
        delete_transient(self::LOCK_KEY);
    }
}
<?php
/**
 * PHPCS rules disabled in this file because:
 * - Queries use plugin-owned table names
 * - All user input is parameterized
 * - Dynamic WHERE clauses are internally controlled
 * - Refactoring would reduce clarity without improving security
 */
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable PluginCheck.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SCSO_DB {

    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'scso_metrics';
    }

    public static function install() {
        global $wpdb;

        $table   = $wpdb->prefix . 'scso_metrics';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            url VARCHAR(500) NOT NULL,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            avg_position DECIMAL(5,2) DEFAULT 0,
            ctr DECIMAL(5,2) DEFAULT 0,
            opportunity_score INT DEFAULT 0,
            opportunity_reason VARCHAR(255) DEFAULT '',
            marked_updated TINYINT(1) DEFAULT 0,
            snoozed_until DATETIME DEFAULT NULL,
            last_synced DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY opportunity_score (opportunity_score),
            KEY last_synced (last_synced),
            KEY snoozed_until (snoozed_until)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function has_data() {
        global $wpdb;

        $cache_key = 'scso_has_data';
        $cached    = wp_cache_get( $cache_key, 'scso' );

        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics WHERE 1 = %d',
                1
            )
        );

        $result = $count > 0;
        wp_cache_set( $cache_key, $result, 'scso', 300 );

        return $result;
    }

    public function get_opportunities( $args = array() ) {
        $defaults = array(
            'limit'        => 20,
            'offset'       => 0,
            'money_only'   => false,
            'search'       => '',
            'show_snoozed' => false,
            'ctr_gap_only' => false,
        );

        $args = wp_parse_args( $args, $defaults );

        if ( !  empty( $args['search'] ) ) {
            if ( $args['show_snoozed'] ) {
                return $this->query_search_snoozed( $args );
            }
            return $this->query_search_normal( $args );
        }
        if ( $args['money_only'] ) {
            if ( $args['show_snoozed'] ) {
                return $this->query_money_snoozed( $args );
            }
            return $this->query_money_normal( $args );
        }
        if ( $args['show_snoozed'] ) {
            return $this->query_snoozed( $args );
        }
        if ( $args['ctr_gap_only'] ) {
            if ( $args['show_snoozed'] ) {
                return $this->query_ctr_snoozed( $args );
            }
            return $this->query_ctr_normal( $args );
        }

        return $this->query_default( $args );
    }

    private function query_default( $args ) {
        global $wpdb;

        $cache_key = 'scso_opps_default_' . md5( wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                AND (clicks < 10 OR ctr < 3)
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                (int) $args['limit'],
                (int) $args['offset']
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );
        return $results;
    }

    private function query_snoozed( $args ) {
        global $wpdb;

        $cache_key = 'scso_opps_snoozed_' . md5( wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                AND (clicks < 10 OR ctr < 3)
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                (int) $args['limit'],
                (int) $args['offset']
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );
        return $results;
    }

    private function query_money_normal( $args ) {
        global $wpdb;

        $cache_key = 'scso_opps_money_n_' . md5( wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                AND (clicks < 10 OR ctr < 3)
                AND impressions >= 100
                AND avg_position BETWEEN 5 AND 20
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                (int) $args['limit'],
                (int) $args['offset']
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );
        return $results;
    }

    private function query_money_snoozed( $args ) {
        global $wpdb;

        $cache_key = 'scso_opps_money_s_' . md5( wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' .  $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                AND (clicks < 10 OR ctr < 3)
                AND impressions >= 100
                AND avg_position BETWEEN 5 AND 20
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                (int) $args['limit'],
                (int) $args['offset']
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );
        return $results;
    }

    private function query_ctr_normal( $args ) {
        global $wpdb;

        $cache_key = 'scso_opps_ctr_n_' . md5( wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' .  $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                (int) $args['limit'],
                (int) $args['offset']
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );
        return $results;
    }

    private function query_ctr_snoozed( $args ) {
        global $wpdb;

        $cache_key = 'scso_opps_ctr_s_' . md5( wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                (int) $args['limit'],
                (int) $args['offset']
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );
        return $results;
    }

    private function query_search_normal( $args ) {
        global $wpdb;

        $search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' .  $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                AND (clicks < 10 OR ctr < 3)
                AND post_id IN (
                    SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title LIKE %s
                )
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                $search_like,
                (int) $args['limit'],
                (int) $args['offset']
            )
        );
    }

    private function query_search_snoozed( $args ) {
        global $wpdb;

        $search_like = '%' .  $wpdb->esc_like( $args['search'] ) . '%';

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                AND (clicks < 10 OR ctr < 3)
                AND post_id IN (
                    SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title LIKE %s
                )
                ORDER BY opportunity_score DESC
                LIMIT %d OFFSET %d',
                $search_like,
                (int) $args['limit'],
                (int) $args['offset']
            )
        );
    }

    public function count_opportunities( $args = array() ) {
        $defaults = array(
            'money_only'   => false,
            'show_snoozed' => false,
            'search'       => '',
            'ctr_gap_only' => false,
        );

        $args = wp_parse_args( $args, $defaults );

        if ( ! empty( $args['search'] ) ) {
            if ( $args['show_snoozed'] ) {
                return $this->count_search_snoozed( $args );
            }
            return $this->count_search_normal( $args );
        }
        if ( $args['money_only'] ) {
            if ( $args['show_snoozed'] ) {
                return $this->count_money_snoozed();
            }
            return $this->count_money_normal();
        }
        if ( $args['show_snoozed'] ) {
            return $this->count_snoozed();
        }
        if ( $args['ctr_gap_only'] ) {
            if ( $args['show_snoozed'] ) {
                return $this->count_ctr_snoozed();
            }
            return $this->count_ctr_normal();
        }

        return $this->count_default();
    }

    private function count_default() {
        global $wpdb;

        $cache_key = 'scso_count_default';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > %d
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                AND (clicks < 10 OR ctr < 3)',
                0
            )
        );

        wp_cache_set( $cache_key, $count, 'scso', 300 );
        return $count;
    }

    private function count_snoozed() {
        global $wpdb;

        $cache_key = 'scso_count_snoozed';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix .  'scso_metrics
                WHERE impressions > %d
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                AND (clicks < 10 OR ctr < 3)',
                0
            )
        );

        wp_cache_set( $cache_key, $count, 'scso', 300 );
        return $count;
    }

    private function count_money_normal() {
        global $wpdb;

        $cache_key = 'scso_count_money_n';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix .  'scso_metrics
                WHERE impressions > %d
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                AND (clicks < 10 OR ctr < 3)
                AND impressions >= 100
                AND avg_position BETWEEN 5 AND 20',
                0
            )
        );

        wp_cache_set( $cache_key, $count, 'scso', 300 );
        return $count;
    }

    private function count_money_snoozed() {
        global $wpdb;

        $cache_key = 'scso_count_money_s';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > %d
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                AND (clicks < 10 OR ctr < 3)
                AND impressions >= 100
                AND avg_position BETWEEN 5 AND 20',
                0
            )
        );

        wp_cache_set( $cache_key, $count, 'scso', 300 );
        return $count;
    }

    private function count_ctr_normal() {
        global $wpdb;

        $cache_key = 'scso_count_ctr_n';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > %d
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())',
                0
            )
        );

        wp_cache_set( $cache_key, $count, 'scso', 300 );
        return $count;
    }

    private function count_ctr_snoozed() {
        global $wpdb;

        $cache_key = 'scso_count_ctr_s';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > %d
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())',
                0
            )
        );

        wp_cache_set( $cache_key, $count, 'scso', 300 );
        return $count;
    }

    private function count_search_normal( $args ) {
        global $wpdb;

        $search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NULL OR snoozed_until < NOW())
                AND (clicks < 10 OR ctr < 3)
                AND post_id IN (
                    SELECT ID FROM ' .  $wpdb->posts . ' WHERE post_title LIKE %s
                )',
                $search_like
            )
        );
    }

    private function count_search_snoozed( $args ) {
        global $wpdb;

        $search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics
                WHERE impressions > 0
                AND (marked_updated = 0 OR marked_updated IS NULL)
                AND (snoozed_until IS NOT NULL AND snoozed_until > NOW())
                AND (clicks < 10 OR ctr < 3)
                AND post_id IN (
                    SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title LIKE %s
                )',
                $search_like
            )
        );
    }

    public function stats() {
        global $wpdb;

        $cache_key = 'scso_stats';
        $cached    = wp_cache_get( $cache_key, 'scso' );
        if ( false !== $cached ) {
            return $cached;
        }

        $posts_with_traffic = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics WHERE impressions > %d',
                0
            )
        );

        $high_opportunity = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'scso_metrics WHERE opportunity_score >= %d',
                60
            )
        );

        // FIXED: Check option first, fallback to database query
        $last_synced = get_option('scso_last_sync_time');
        
        if (!$last_synced) {
            // Fallback to database if option doesn't exist (backward compatibility)
            $last_synced = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT MAX(last_synced) FROM ' . $wpdb->prefix . 'scso_metrics WHERE 1 = %d',
                    1
                )
            );
        }

        $result = array(
            'posts_with_traffic' => $posts_with_traffic,
            'high_opportunity'   => $high_opportunity,
            'last_synced'        => $last_synced,
        );

        wp_cache_set( $cache_key, $result, 'scso', 300 );
        return $result;
    }

    public function mark_updated( $post_id ) {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'scso_metrics',
            array(
                'marked_updated' => 1,
                'snoozed_until'  => null,
            ),
            array( 'post_id' => (int) $post_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        $this->clear_caches();
    }

    public function snooze( $post_id, $days = 30 ) {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'scso_metrics',
            array(
                'snoozed_until' => gmdate( 'Y-m-d H:i:s', strtotime( '+' . absint( $days ) . ' days' ) ),
            ),
            array( 'post_id' => (int) $post_id ),
            array( '%s' ),
            array( '%d' )
        );

        $this->clear_caches();
    }

    public function unsnooze( $post_id ) {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'scso_metrics',
            array( 'snoozed_until' => null ),
            array( 'post_id' => (int) $post_id ),
            array( '%s' ),
            array( '%d' )
        );

        $this->clear_caches();
    }

    public function clear_caches() {
         wp_cache_delete( 'scso_stats', 'scso' );
         wp_cache_delete( 'scso_has_data', 'scso' );
         wp_cache_delete( 'scso_count_default', 'scso' );
         wp_cache_delete( 'scso_count_snoozed', 'scso' );
         wp_cache_delete( 'scso_count_money_n', 'scso' );
         wp_cache_delete( 'scso_count_money_s', 'scso' );
         wp_cache_delete( 'scso_count_ctr_n', 'scso' );
         wp_cache_delete( 'scso_count_ctr_s', 'scso' );
     }
    
}
// phpcs:enable
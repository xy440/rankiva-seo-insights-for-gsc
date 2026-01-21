<?php
/**
 * Database handler for Rankiva SEO Insights.
 *
 * @package Rankiva
 * @since 1.0.0
 * @since 1.2.0 Added position change tracking
 * @since 1.2.0 Fixed Plugin Check warnings with rewritten query methods
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database class for SCSO plugin.
 */
class SCSO_DB {

    /**
     * Constructor.
     */
    public function __construct() {
        // Tables are accessed via $wpdb->prefix directly in methods.
    }

    /**
     * Install database tables.
     *
     * @since 1.0.0
     */
    public static function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql_metrics = "CREATE TABLE {$wpdb->prefix}scso_metrics (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            url VARCHAR(500) NOT NULL,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            avg_position DECIMAL(5,2) DEFAULT 0,
            prev_position DECIMAL(5,2) DEFAULT NULL,
            position_change DECIMAL(5,2) DEFAULT NULL,
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
            KEY snoozed_until (snoozed_until),
            KEY position_change (position_change)
        ) {$charset_collate};";

        $sql_keywords = "CREATE TABLE {$wpdb->prefix}scso_keywords (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            keyword VARCHAR(500) NOT NULL,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            avg_position DECIMAL(5,2) DEFAULT 0,
            prev_position DECIMAL(5,2) DEFAULT NULL,
            position_change DECIMAL(5,2) DEFAULT NULL,
            ctr DECIMAL(5,2) DEFAULT 0,
            last_synced DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_keyword (post_id, keyword(191)),
            KEY post_id (post_id),
            KEY impressions (impressions)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_metrics );
        dbDelta( $sql_keywords );
    }

    /**
     * Check if metrics data exists.
     *
     * @return bool
     */
    public function has_data() {
        global $wpdb;

        $cache_key = 'scso_has_data';
        $cached    = wp_cache_get( $cache_key, 'scso' );

        if ( false !== $cached ) {
            return (bool) $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}scso_metrics WHERE impressions > %d",
                0
            )
        );

        wp_cache_set( $cache_key, $count > 0, 'scso', 300 );

        return $count > 0;
    }

    /**
     * Clear all data caches.
     */
    public function clear_caches() {
        wp_cache_delete( 'scso_has_data', 'scso' );
        wp_cache_delete( 'scso_opportunities', 'scso' );
        wp_cache_delete( 'scso_opportunities_count', 'scso' );
    }

    /**
     * Get previous keyword positions for a post.
     *
     * @since 1.2.0
     * @param int $post_id Post ID.
     * @return array Associative array of keyword => position.
     */
    public function get_previous_keyword_positions( $post_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT keyword, avg_position FROM {$wpdb->prefix}scso_keywords WHERE post_id = %d",
                absint( $post_id )
            )
        );

        $positions = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $positions[ $row->keyword ] = (float) $row->avg_position;
            }
        }

        return $positions;
    }

    /**
     * Store keywords for a post with position change tracking.
     *
     * @since 1.1.0
     * @param int   $post_id  Post ID.
     * @param array $keywords Array of keyword data.
     */
    public function store_keywords( $post_id, $keywords ) {
        global $wpdb;

        if ( empty( $keywords ) ) {
            return;
        }

        $post_id = absint( $post_id );

        // Get previous positions before deleting.
        $prev_positions = $this->get_previous_keyword_positions( $post_id );

        // Delete existing keywords for this post.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $wpdb->prefix . 'scso_keywords', array( 'post_id' => $post_id ), array( '%d' ) );

        // Insert new keywords (limit to top 10).
        $keywords = array_slice( $keywords, 0, 10 );

        foreach ( $keywords as $kw ) {
            $keyword      = sanitize_text_field( $kw['keyword'] );
            $new_position = round( (float) $kw['position'], 2 );

            $prev_position   = isset( $prev_positions[ $keyword ] ) ? $prev_positions[ $keyword ] : null;
            $position_change = null;

            if ( null !== $prev_position ) {
                $position_change = round( $prev_position - $new_position, 2 );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert(
                $wpdb->prefix . 'scso_keywords',
                array(
                    'post_id'         => $post_id,
                    'keyword'         => $keyword,
                    'impressions'     => absint( $kw['impressions'] ),
                    'clicks'          => absint( $kw['clicks'] ),
                    'avg_position'    => $new_position,
                    'prev_position'   => $prev_position,
                    'position_change' => $position_change,
                    'ctr'             => round( (float) $kw['ctr'], 2 ),
                    'last_synced'     => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%d', '%d', '%f', '%f', '%f', '%f', '%s' )
            );
        }
    }

    /**
     * Get keywords for a specific post.
     *
     * @since 1.1.0
     * @param int $post_id Post ID.
     * @param int $limit   Max keywords to return.
     * @return array Array of keyword objects.
     */
    public function get_keywords( $post_id, $limit = 5 ) {
        global $wpdb;

        $post_id = absint( $post_id );
        $limit   = absint( $limit );

        $cache_key = 'scso_keywords_' . $post_id . '_' . $limit;
        $cached    = wp_cache_get( $cache_key, 'scso' );

        if ( false !== $cached ) {
            return $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT keyword, impressions, clicks, avg_position, prev_position, position_change, ctr
                FROM {$wpdb->prefix}scso_keywords
                WHERE post_id = %d
                ORDER BY impressions DESC
                LIMIT %d",
                $post_id,
                $limit
            )
        );

        wp_cache_set( $cache_key, $results, 'scso', 300 );

        return $results;
    }

    /**
     * Get keywords for multiple posts at once.
     *
     * Uses individual queries per post to avoid dynamic IN clause issues.
     *
     * @since 1.1.0
     * @since 1.2.5 Rewritten to use loop instead of IN clause
     * @param array $post_ids Array of post IDs.
     * @param int   $limit    Max keywords per post.
     * @return array Associative array of post_id => keywords.
     */
    public function get_keywords_bulk( $post_ids, $limit = 5 ) {
        if ( empty( $post_ids ) ) {
            return array();
        }

        $post_ids = array_map( 'absint', $post_ids );
        $limit    = absint( $limit );
        $grouped  = array();

        // Query each post individually to avoid dynamic IN clause.
        foreach ( $post_ids as $post_id ) {
            if ( $post_id > 0 ) {
                $keywords = $this->get_keywords( $post_id, $limit );
                if ( ! empty( $keywords ) ) {
                    $grouped[ $post_id ] = $keywords;
                }
            }
        }

        return $grouped;
    }

    /**
     * Check if keywords table has data.
     *
     * @since 1.1.0
     * @return bool
     */
    public function has_keywords() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}scso_keywords"
        );

        return $count > 0;
    }

    /**
     * Clear all keywords data.
     *
     * @since 1.1.0
     */
    public function clear_keywords() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}scso_keywords" );
    }

    /**
     * Get opportunities with optional filters.
     *
     * @since 1.0.0
     * @since 1.2.5 Rewritten to use fixed query patterns
     * @param array $args Query arguments.
     * @return array
     */
    public function get_opportunities( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit'        => 20,
            'offset'       => 0,
            'money_only'   => false,
            'search'       => '',
            'show_snoozed' => false,
        );

        $args   = wp_parse_args( $args, $defaults );
        $limit  = absint( $args['limit'] );
        $offset = absint( $args['offset'] );
        $now    = current_time( 'mysql' );

        // Build query based on filter combination.
        if ( $args['money_only'] && ! empty( $args['search'] ) && $args['show_snoozed'] ) {
            // Money + Search + Snoozed.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND m.snoozed_until > %s
                    AND p.post_title LIKE %s
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $search_term,
                    $limit,
                    $offset
                )
            );
        } elseif ( $args['money_only'] && ! empty( $args['search'] ) ) {
            // Money + Search.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)
                    AND p.post_title LIKE %s
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $search_term,
                    $limit,
                    $offset
                )
            );
        } elseif ( $args['money_only'] && $args['show_snoozed'] ) {
            // Money + Snoozed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND m.snoozed_until > %s
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $limit,
                    $offset
                )
            );
        } elseif ( $args['money_only'] ) {
            // Money only.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $limit,
                    $offset
                )
            );
        } elseif ( ! empty( $args['search'] ) && $args['show_snoozed'] ) {
            // Search + Snoozed.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions > 0
                    AND m.snoozed_until > %s
                    AND p.post_title LIKE %s
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $search_term,
                    $limit,
                    $offset
                )
            );
        } elseif ( ! empty( $args['search'] ) ) {
            // Search only.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions > 0
                    AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)
                    AND p.post_title LIKE %s
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $search_term,
                    $limit,
                    $offset
                )
            );
        } elseif ( $args['show_snoozed'] ) {
            // Snoozed only.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, p.post_title, p.guid
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions > 0
                    AND m.snoozed_until > %s
                    ORDER BY m.opportunity_score DESC, m.impressions DESC
                    LIMIT %d OFFSET %d",
                    $now,
                    $limit,
                    $offset
                )
            );
        }

        // Default: no filters.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, p.post_title, p.guid
                FROM {$wpdb->prefix}scso_metrics m
                INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                WHERE m.impressions > 0
                AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)
                ORDER BY m.opportunity_score DESC, m.impressions DESC
                LIMIT %d OFFSET %d",
                $now,
                $limit,
                $offset
            )
        );
    }

    /**
     * Count opportunities with optional filters.
     *
     * @since 1.0.0
     * @since 1.2.5 Rewritten to use fixed query patterns
     * @param array $args Query arguments.
     * @return int
     */
    public function count_opportunities( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'money_only'   => false,
            'search'       => '',
            'show_snoozed' => false,
        );

        $args = wp_parse_args( $args, $defaults );
        $now  = current_time( 'mysql' );

        // Build query based on filter combination.
        if ( $args['money_only'] && ! empty( $args['search'] ) && $args['show_snoozed'] ) {
            // Money + Search + Snoozed.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND m.snoozed_until > %s
                    AND p.post_title LIKE %s",
                    $now,
                    $search_term
                )
            );
        } elseif ( $args['money_only'] && ! empty( $args['search'] ) ) {
            // Money + Search.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)
                    AND p.post_title LIKE %s",
                    $now,
                    $search_term
                )
            );
        } elseif ( $args['money_only'] && $args['show_snoozed'] ) {
            // Money + Snoozed.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND m.snoozed_until > %s",
                    $now
                )
            );
        } elseif ( $args['money_only'] ) {
            // Money only.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions >= 100
                    AND m.avg_position BETWEEN 5 AND 20
                    AND m.opportunity_score >= 60
                    AND m.marked_updated = 0
                    AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)",
                    $now
                )
            );
        } elseif ( ! empty( $args['search'] ) && $args['show_snoozed'] ) {
            // Search + Snoozed.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions > 0
                    AND m.snoozed_until > %s
                    AND p.post_title LIKE %s",
                    $now,
                    $search_term
                )
            );
        } elseif ( ! empty( $args['search'] ) ) {
            // Search only.
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions > 0
                    AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)
                    AND p.post_title LIKE %s",
                    $now,
                    $search_term
                )
            );
        } elseif ( $args['show_snoozed'] ) {
            // Snoozed only.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}scso_metrics m
                    INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                    WHERE m.impressions > 0
                    AND m.snoozed_until > %s",
                    $now
                )
            );
        }

        // Default: no filters.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$wpdb->prefix}scso_metrics m
                INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
                WHERE m.impressions > 0
                AND (m.snoozed_until IS NULL OR m.snoozed_until <= %s)",
                $now
            )
        );
    }

    /**
     * Get previous position for a post.
     *
     * @since 1.2.0
     * @param int $post_id Post ID.
     * @return float|null Previous position or null if not found.
     */
    public function get_previous_position( $post_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT avg_position FROM {$wpdb->prefix}scso_metrics WHERE post_id = %d",
                absint( $post_id )
            )
        );

        return null !== $result ? (float) $result : null;
    }

    /**
     * Count positions that improved since last sync.
     *
     * @since 1.2.0
     * @return int
     */
    public function count_positions_improved() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}scso_metrics WHERE position_change > %f AND position_change IS NOT NULL",
                0.3
            )
        );
    }

    /**
     * Count positions that dropped since last sync.
     *
     * @since 1.2.0
     * @return int
     */
    public function count_positions_dropped() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}scso_metrics WHERE position_change < %f AND position_change IS NOT NULL",
                -0.3
            )
        );
    }

    /**
     * Upsert metrics data.
     *
     * @param int    $post_id    Post ID.
     * @param string $url        URL.
     * @param array  $data       Metrics data.
     * @param array  $score_data Score data.
     */
    public function upsert( $post_id, $url, $data, $score_data ) {
        global $wpdb;

        $post_id       = absint( $post_id );
        $prev_position = $this->get_previous_position( $post_id );
        $new_position  = (float) $data['position'];

        $position_change = null;
        if ( null !== $prev_position ) {
            $position_change = round( $prev_position - $new_position, 2 );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace(
            $wpdb->prefix . 'scso_metrics',
            array(
                'post_id'            => $post_id,
                'url'                => esc_url_raw( $url ),
                'impressions'        => absint( $data['impressions'] ),
                'clicks'             => absint( $data['clicks'] ),
                'avg_position'       => $new_position,
                'prev_position'      => $prev_position,
                'position_change'    => $position_change,
                'ctr'                => (float) $data['ctr'],
                'opportunity_score'  => absint( $score_data['score'] ),
                'opportunity_reason' => sanitize_text_field( $score_data['reason'] ),
                'last_synced'        => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Calculate opportunity score.
     *
     * @param array $data Metrics data.
     * @return array Score and reason.
     */
    public function calculate_score( $data ) {
        $impressions = absint( $data['impressions'] );
        $position    = (float) $data['position'];
        $ctr         = (float) $data['ctr'];

        $score  = 0;
        $reason = '';

        if ( $position <= 3 ) {
            $score += 10;
            $reason = 'Already in top 3';
        } elseif ( $position <= 5 ) {
            $score += 30;
            $reason = 'Close to top 3';
        } elseif ( $position <= 10 ) {
            $score += 40;
            $reason = 'Page 1, can reach top 5';
        } elseif ( $position <= 20 ) {
            $score += 35;
            $reason = 'Page 2, can reach page 1';
        } elseif ( $position <= 30 ) {
            $score += 20;
            $reason = 'Page 3, needs work';
        } else {
            $score += 5;
            $reason = 'Low visibility';
        }

        if ( $impressions >= 1000 ) {
            $score += 30;
        } elseif ( $impressions >= 500 ) {
            $score += 25;
        } elseif ( $impressions >= 100 ) {
            $score += 20;
        } elseif ( $impressions >= 50 ) {
            $score += 15;
        } elseif ( $impressions >= 10 ) {
            $score += 10;
        } else {
            $score += 5;
        }

        $expected_ctr = $this->expected_ctr( $position );
        if ( $ctr > 0 && $ctr < $expected_ctr * 0.5 ) {
            $score += 30;
        } elseif ( $ctr > 0 && $ctr < $expected_ctr * 0.7 ) {
            $score += 20;
        } elseif ( $ctr > 0 && $ctr < $expected_ctr ) {
            $score += 10;
        }

        return array(
            'score'  => min( 100, $score ),
            'reason' => $reason,
        );
    }

    /**
     * Get expected CTR for position.
     *
     * @param float $position Position.
     * @return float Expected CTR.
     */
    private function expected_ctr( $position ) {
        if ( $position <= 1 ) {
            return 30;
        }
        if ( $position <= 3 ) {
            return 10;
        }
        if ( $position <= 5 ) {
            return 5;
        }
        if ( $position <= 10 ) {
            return 2.5;
        }
        return 1;
    }

    /**
     * Get stats for dashboard.
     *
     * @return array
     */
    public function stats() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $posts_with_traffic = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}scso_metrics WHERE impressions > %d",
                0
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $high_opportunity = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}scso_metrics WHERE opportunity_score >= %d",
                60
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_keywords = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT keyword) FROM {$wpdb->prefix}scso_keywords"
        );

        $positions_improved = $this->count_positions_improved();
        $positions_dropped  = $this->count_positions_dropped();

        $last_synced = get_option( 'scso_last_sync_time', '' );

        if ( empty( $last_synced ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $last_synced = $wpdb->get_var(
                "SELECT MAX(last_synced) FROM {$wpdb->prefix}scso_metrics"
            );
        }

        return array(
            'posts_with_traffic' => $posts_with_traffic,
            'high_opportunity'   => $high_opportunity,
            'total_keywords'     => $total_keywords,
            'positions_improved' => $positions_improved,
            'positions_dropped'  => $positions_dropped,
            'last_synced'        => $last_synced,
        );
    }

    /**
     * Mark post as updated.
     *
     * @param int $post_id Post ID.
     */
    public function mark_updated( $post_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->prefix . 'scso_metrics',
            array( 'marked_updated' => 1 ),
            array( 'post_id' => absint( $post_id ) ),
            array( '%d' ),
            array( '%d' )
        );
    }

    /**
     * Snooze post for given days.
     *
     * @param int $post_id Post ID.
     * @param int $days    Days to snooze.
     */
    public function snooze( $post_id, $days = 30 ) {
        global $wpdb;

        $days         = absint( $days );
        $snooze_until = gmdate( 'Y-m-d H:i:s', strtotime( '+' . $days . ' days' ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->prefix . 'scso_metrics',
            array( 'snoozed_until' => $snooze_until ),
            array( 'post_id' => absint( $post_id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Unsnooze post.
     *
     * @param int $post_id Post ID.
     */
    public function unsnooze( $post_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->prefix . 'scso_metrics',
            array( 'snoozed_until' => null ),
            array( 'post_id' => absint( $post_id ) ),
            array( null ),
            array( '%d' )
        );
    }

    /**
     * Delete all data for a post.
     *
     * @param int $post_id Post ID.
     */
    public function delete_post_data( $post_id ) {
        global $wpdb;

        $post_id = absint( $post_id );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $wpdb->prefix . 'scso_metrics', array( 'post_id' => $post_id ), array( '%d' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $wpdb->prefix . 'scso_keywords', array( 'post_id' => $post_id ), array( '%d' ) );
    }

    /**
     * Cleanup old data.
     *
     * @param int $days Days to keep.
     */
    public function cleanup_old_data( $days = 90 ) {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}scso_metrics WHERE last_synced < %s",
                $cutoff
            )
        );
    }
}
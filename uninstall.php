<?php
/**
 * Uninstall script
 * Runs when plugin is deleted (not just deactivated)
 *
 * @package Rankiva
 * @since 1.0.0
 * @since 1.1.0 Added keywords table cleanup
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

/**
 * Drop plugin tables
 */
function scso_drop_tables() {
    global $wpdb;

    $scso_metrics_table = $wpdb->prefix . 'scso_metrics';
    $scso_keywords_table = $wpdb->prefix . 'scso_keywords';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query( "DROP TABLE IF EXISTS {$scso_metrics_table}" );
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query( "DROP TABLE IF EXISTS {$scso_keywords_table}" );
}

// Single site cleanup
scso_drop_tables();

// Delete options
delete_option( 'scso_gsc_token' );
delete_option( 'scso_gsc_property' );
delete_option( 'scso_gsc_email' );
delete_option( 'scso_gsc_account_id' );
delete_option( 'scso_gsc_account_email' );
delete_option( 'scso_gsc_binding' );
delete_option( 'scso_sync_state' );
delete_option( 'scso_activation_redirect' );
delete_option( 'scso_last_sync_time' );
delete_option( 'scso_use_proxy' );
delete_option( 'scso_oauth_client_id' );
delete_option( 'scso_oauth_client_secret' );
delete_option( 'scso_db_version' ); // NEW in v1.1

// Delete transients
delete_transient( 'scso_sync_lock' );
delete_transient( 'scso_hide_dev_warning' );
delete_transient( 'scso_sync_error' );

// Clear scheduled events
wp_clear_scheduled_hook( 'scso_run_sync_batch' );
wp_clear_scheduled_hook( 'scso_auto_sync' );

// Multisite cleanup
if ( is_multisite() ) {
    $scso_sites = get_sites( [ 'fields' => 'ids' ] );

    foreach ( $scso_sites as $scso_site_id ) {
        switch_to_blog( $scso_site_id );

        scso_drop_tables();

        delete_option( 'scso_gsc_token' );
        delete_option( 'scso_gsc_property' );
        delete_option( 'scso_gsc_email' );
        delete_option( 'scso_gsc_account_id' );
        delete_option( 'scso_gsc_account_email' );
        delete_option( 'scso_gsc_binding' );
        delete_option( 'scso_sync_state' );
        delete_option( 'scso_activation_redirect' );
        delete_option( 'scso_last_sync_time' );
        delete_option( 'scso_use_proxy' );
        delete_option( 'scso_oauth_client_id' );
        delete_option( 'scso_oauth_client_secret' );
        delete_option( 'scso_db_version' );

        delete_transient( 'scso_sync_lock' );
        delete_transient( 'scso_hide_dev_warning' );
        delete_transient( 'scso_sync_error' );

        wp_clear_scheduled_hook( 'scso_run_sync_batch' );
        wp_clear_scheduled_hook( 'scso_auto_sync' );

        restore_current_blog();
    }
}

<?php
/**
 * Uninstall script
 * Runs when plugin is deleted (not just deactivated)
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

/**
 * Drop plugin table
 */
function scso_drop_table() {
    global $wpdb;

    $scso_table = $wpdb->prefix . 'scso_metrics';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query( "DROP TABLE IF EXISTS {$scso_table}" );
}

// Single site cleanup
scso_drop_table();

// Delete options
delete_option( 'scso_gsc_token' );
delete_option( 'scso_gsc_property' );
delete_option( 'scso_gsc_email' );
delete_option( 'scso_gsc_account_id' );
delete_option( 'scso_gsc_account_email' );
delete_option( 'scso_gsc_binding' );
delete_option( 'scso_sync_state' );
delete_option( 'scso_activation_redirect' );

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

        scso_drop_table();

        delete_option( 'scso_gsc_token' );
        delete_option( 'scso_gsc_property' );
        delete_option( 'scso_gsc_email' );
        delete_option( 'scso_gsc_account_id' );
        delete_option( 'scso_gsc_account_email' );
        delete_option( 'scso_gsc_binding' );
        delete_option( 'scso_sync_state' );
        delete_option( 'scso_activation_redirect' );

        delete_transient( 'scso_sync_lock' );
        delete_transient( 'scso_hide_dev_warning' );

        wp_clear_scheduled_hook( 'scso_run_sync_batch' );
        wp_clear_scheduled_hook( 'scso_auto_sync' );

        restore_current_blog();
    }
}
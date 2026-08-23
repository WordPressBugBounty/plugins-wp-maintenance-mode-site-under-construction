<?php
/**
 * Fired when the plugin is uninstalled / deleted via WordPress.
 *
 * Removes the custom messages database table if the site owner configured
 * the 'delete_messages_on_uninstall' option in maintenance settings.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = get_option( 'MM_And_SUC_Free_options', array() );

if ( is_array( $options ) && ! empty( $options['delete_messages_on_uninstall'] ) ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mm_suc_messages';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	delete_option( 'mm_suc_p_messages_db_version' );
}

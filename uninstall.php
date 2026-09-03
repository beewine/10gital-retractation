<?php
/**
 * Désinstallation.
 *
 * Par défaut, les déclarations sont conservées : elles constituent une preuve
 * opposable. Pour tout effacer, définissez la constante suivante dans
 * wp-config.php avant de supprimer le plugin :
 *
 *     define( 'RET10G_REMOVE_ALL_DATA', true );
 *
 * @package Dixgital\Retractation
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

wp_clear_scheduled_hook( 'ret10g_daily_maintenance' );
delete_transient( 'ret10g_update_payload' );

if ( ! defined( 'RET10G_REMOVE_ALL_DATA' ) || ! RET10G_REMOVE_ALL_DATA ) {
	return;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ret10g_declaration_items" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ret10g_declarations" );

$options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'ret10g_' ) . '%'
	)
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

foreach ( (array) $options as $option ) {
	delete_option( $option );
}

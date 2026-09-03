<?php
/**
 * Chargement automatique des classes du plugin.
 *
 * Convention : Dixgital\Retractation\Admin\ListTable  =>  includes/Admin/ListTable.php
 *
 * @package Dixgital\Retractation
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Dixgital\\Retractation\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = RET10G_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

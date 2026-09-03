<?php
/**
 * Activation, mise à jour du schéma et désactivation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Core;

use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Cycle de vie du plugin.
 */
class Install {

	/**
	 * Option stockant la version du schéma installé.
	 */
	public const DB_VERSION_OPTION = 'ret10g_db_version';

	/**
	 * Nom court de la table des déclarations.
	 */
	public const TABLE_DECLARATIONS = 'ret10g_declarations';

	/**
	 * Nom court de la table des lignes.
	 */
	public const TABLE_ITEMS = 'ret10g_declaration_items';

	/**
	 * Nom complet d'une table du plugin.
	 *
	 * @param string $short Nom court.
	 * @return string
	 */
	public static function table( $short ) {
		global $wpdb;

		return $wpdb->prefix . $short;
	}

	/**
	 * Exécutée à l'activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		self::seed_defaults();
		self::ensure_page();

		update_option( self::DB_VERSION_OPTION, RET10G_DB_VERSION );

		if ( ! wp_next_scheduled( 'ret10g_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ret10g_daily_maintenance' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Exécutée à la désactivation. Ne supprime aucune donnée.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'ret10g_daily_maintenance' );
		delete_transient( 'ret10g_update_payload' );
		flush_rewrite_rules();
	}

	/**
	 * Met le schéma à niveau si la version stockée diffère.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) === RET10G_DB_VERSION ) {
			return;
		}

		self::create_tables();
		self::seed_defaults();

		update_option( self::DB_VERSION_OPTION, RET10G_DB_VERSION );
	}

	/**
	 * Crée ou met à jour les tables via dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset      = $wpdb->get_charset_collate();
		$declarations = self::table( self::TABLE_DECLARATIONS );
		$items        = self::table( self::TABLE_ITEMS );

		$sql_declarations = "CREATE TABLE {$declarations} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			reference VARCHAR(32) NOT NULL DEFAULT '',
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			order_number VARCHAR(100) NOT NULL DEFAULT '',
			customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			first_name VARCHAR(100) NOT NULL DEFAULT '',
			last_name VARCHAR(100) NOT NULL DEFAULT '',
			contact_email VARCHAR(191) NOT NULL DEFAULT '',
			scope VARCHAR(20) NOT NULL DEFAULT 'full',
			reason TEXT NULL,
			statement LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'received',
			was_eligible TINYINT(1) NOT NULL DEFAULT 1,
			deadline_at DATETIME NULL,
			amount DECIMAL(12,4) NOT NULL DEFAULT 0,
			currency VARCHAR(10) NOT NULL DEFAULT '',
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			acknowledged_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			created_at_gmt DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY reference (reference),
			KEY order_id (order_id),
			KEY customer_id (customer_id),
			KEY status (status),
			KEY created_at_gmt (created_at_gmt)
		) {$charset};";

		$sql_items = "CREATE TABLE {$items} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			declaration_id BIGINT UNSIGNED NOT NULL,
			order_item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			product_name VARCHAR(255) NOT NULL DEFAULT '',
			sku VARCHAR(100) NOT NULL DEFAULT '',
			quantity INT UNSIGNED NOT NULL DEFAULT 0,
			line_total DECIMAL(12,4) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY declaration_id (declaration_id),
			KEY order_item_id (order_item_id)
		) {$charset};";

		dbDelta( $sql_declarations );
		dbDelta( $sql_items );
	}

	/**
	 * Inscrit les valeurs par défaut manquantes.
	 *
	 * @return void
	 */
	public static function seed_defaults() {
		foreach ( Settings::schema() as $key => $field ) {
			$name = Settings::PREFIX . $key;

			if ( false === get_option( $name, false ) ) {
				add_option( $name, $field['default'] );
			}
		}
	}

	/**
	 * Crée la page de rétractation si elle n'existe pas encore.
	 *
	 * @return int Identifiant de la page.
	 */
	public static function ensure_page() {
		$page_id = (int) get_option( Settings::PREFIX . 'page_id', 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			return $page_id;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				's'              => '[retractation',
			)
		);

		if ( ! empty( $existing ) ) {
			update_option( Settings::PREFIX . 'page_id', (int) $existing[0] );

			return (int) $existing[0];
		}

		$page_id = wp_insert_post(
			array(
				'post_title'     => 'Rétractation',
				'post_name'      => 'retractation',
				'post_content'   => '<!-- wp:shortcode -->[retractation]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( Settings::PREFIX . 'page_id', (int) $page_id );

			return (int) $page_id;
		}

		return 0;
	}
}

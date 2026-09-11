<?php
/**
 * Activation, mise à jour du schéma et désactivation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Core;

use Dixgital\Retractation\Multilingual;
use Dixgital\Retractation\Plugin;
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
		$page_id = self::find_or_create_page();

		if ( $page_id ) {
			self::ensure_translations( $page_id );
		}

		return $page_id;
	}

	/**
	 * Crée, dans chaque langue active du site, la traduction manquante de la page.
	 *
	 * Sans extension multilingue, ne fait rien.
	 *
	 * @param int $page_id Page de référence.
	 * @return int[] Identifiants des pages créées, par code langue.
	 */
	public static function ensure_translations( $page_id ) {
		$created = array();
		$page_id = (int) $page_id;
		$missing = Multilingual::missing_translations( $page_id );

		if ( $missing ) {
			// À l'activation, le plugin n'a pas encore déclaré son dossier de
			// traductions : sans cela, le titre resterait en français partout.
			Plugin::instance()->load_textdomain();
		}

		foreach ( $missing as $lang => $locale ) {
			$new_id = self::insert_page( $locale );

			if ( $new_id ) {
				Multilingual::assign_language( $new_id, $lang, $page_id );
				self::restore_slug( $new_id );
				$created[ $lang ] = $new_id;
			}
		}

		return $created;
	}

	/**
	 * Retrouve la page de rétractation, ou la crée dans la langue par défaut.
	 *
	 * @return int Identifiant de la page.
	 */
	private static function find_or_create_page() {
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

		$default = Multilingual::default_language();
		$locales = Multilingual::languages();
		$page_id = self::insert_page( $default && ! empty( $locales[ $default ] ) ? $locales[ $default ] : '' );

		if ( ! $page_id ) {
			return 0;
		}

		// Sans cela, WPML range la page dans la langue affichée à ce moment
		// dans l'administration — l'anglais, par exemple — et la version
		// française du site n'a plus de page de rétractation.
		if ( $default ) {
			Multilingual::assign_language( $page_id, $default );
			self::restore_slug( $page_id );
		}

		update_option( Settings::PREFIX . 'page_id', $page_id );

		return $page_id;
	}

	/**
	 * Réapplique le slug tiré du titre, une fois la langue de la page connue.
	 *
	 * À l'insertion, la page n'a pas encore de langue : si une page d'une autre
	 * langue porte déjà le même slug, WordPress ajoute « -2 ». Une fois la
	 * langue attribuée, l'extension multilingue autorise le slug d'origine.
	 *
	 * @param int $page_id Page.
	 * @return void
	 */
	private static function restore_slug( $page_id ) {
		$wanted = sanitize_title( (string) get_the_title( $page_id ) );

		if ( $wanted && get_post_field( 'post_name', $page_id ) !== $wanted ) {
			wp_update_post(
				array(
					'ID'        => (int) $page_id,
					'post_name' => $wanted,
				)
			);
		}
	}

	/**
	 * Insère une page de rétractation, titrée dans la langue demandée.
	 *
	 * @param string $locale Locale du titre (« en_US ») ; vide = « Rétractation ».
	 * @return int Identifiant de la page, 0 en cas d'échec.
	 */
	private static function insert_page( $locale ) {
		$title = 'Rétractation';

		if ( $locale ) {
			$title = Multilingual::in_locale(
				$locale,
				static function () {
					return __( 'Rétractation', '10gital-retractation' );
				}
			);
		}

		$page_id = wp_insert_post(
			array(
				'post_title'     => $title,
				'post_name'      => sanitize_title( $title ),
				'post_content'   => '<!-- wp:shortcode -->[retractation]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);

		return is_wp_error( $page_id ) ? 0 : (int) $page_id;
	}
}

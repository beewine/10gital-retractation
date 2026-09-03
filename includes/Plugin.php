<?php
/**
 * Amorçage du plugin.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

use Dixgital\Retractation\Admin\DeclarationsPage;
use Dixgital\Retractation\Admin\OrderMetaBox;
use Dixgital\Retractation\Admin\SettingsPage;
use Dixgital\Retractation\Core\Install;
use Dixgital\Retractation\Core\Privacy;
use Dixgital\Retractation\Core\Updater;
use Dixgital\Retractation\Emails\Manager as EmailManager;
use Dixgital\Retractation\Frontend\Assets;
use Dixgital\Retractation\Frontend\Block;
use Dixgital\Retractation\Frontend\Button;
use Dixgital\Retractation\Frontend\Form;

defined( 'ABSPATH' ) || exit;

/**
 * Point d'entrée unique.
 */
class Plugin {

	/**
	 * Instance unique.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Le plugin est-il déjà démarré ?
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Démarre le plugin.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ), 1 );
		add_action( 'admin_init', array( Install::class, 'maybe_upgrade' ) );
		add_action( 'ret10g_daily_maintenance', array( $this, 'run_maintenance' ) );

		( new OrderStatus() )->register();
		( new EmailManager() )->register();

		$button = new Button();

		( new Assets() )->register();
		$button->register();
		( new Form() )->register();
		( new Block( $button ) )->register();

		if ( is_admin() ) {
			( new DeclarationsPage() )->register();
			( new SettingsPage() )->register();
			( new OrderMetaBox() )->register();
			( new Privacy() )->register();

			add_action( 'admin_init', array( SettingsPage::class, 'maybe_create_page' ) );
		}

		( new Updater() )->register();

		/**
		 * Déclenché une fois le plugin entièrement chargé.
		 *
		 * @param Plugin $plugin Instance.
		 */
		do_action( 'ret10g_loaded', $this );
	}

	/**
	 * Charge les traductions.
	 *
	 * Le plugin est rédigé en français ; les fichiers du dossier /languages
	 * fournissent notamment la version anglaise.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'10gital-retractation',
			false,
			dirname( RET10G_BASENAME ) . '/languages'
		);
	}

	/**
	 * Tâche quotidienne : purge éventuelle des déclarations anciennes.
	 *
	 * @return void
	 */
	public function run_maintenance() {
		$days = (int) Settings::get( 'retention_days' );

		if ( $days > 0 ) {
			Repository::purge_older_than( $days );
		}
	}
}

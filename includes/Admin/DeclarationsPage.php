<?php
/**
 * Écran « Rétractations » : liste, détail, actions groupées, export CSV.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Admin;

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Contrôleur de l'écran d'administration.
 */
class DeclarationsPage {

	/**
	 * Identifiant de la page.
	 */
	public const SLUG = 'ret10g-declarations';

	/**
	 * Capacité requise.
	 */
	public const CAPABILITY = 'manage_woocommerce';

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Ajoute l'entrée de menu sous WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu() {
		$counts  = Repository::counts_by_status();
		$pending = isset( $counts[ Declaration::STATUS_RECEIVED ] ) ? (int) $counts[ Declaration::STATUS_RECEIVED ] : 0;

		$title = __( 'Rétractations', '10gital-retractation' );

		if ( $pending > 0 ) {
			$title .= sprintf( ' <span class="awaiting-mod"><span class="pending-count">%d</span></span>', $pending );
		}

		add_submenu_page(
			'woocommerce',
			__( 'Rétractations', '10gital-retractation' ),
			$title,
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Charge les styles de l'écran.
	 *
	 * @param string $hook Suffixe de la page courante.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) && false === strpos( (string) $hook, SettingsPage::SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'ret10g-admin', RET10G_URL . 'assets/css/admin.css', array(), RET10G_VERSION );
	}

	/**
	 * Traite les actions groupées et l'export.
	 *
	 * @return void
	 */
	public function handle_actions() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['page'] ) || self::SLUG !== $_REQUEST['page'] ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$this->maybe_export();
		$this->maybe_update_status();
		$this->maybe_apply_bulk();
	}

	/**
	 * Export CSV.
	 *
	 * @return void
	 */
	private function maybe_export() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['ret10g_export'] ) ) {
			return;
		}

		check_admin_referer( 'ret10g_export' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['statut'] ) ? sanitize_key( wp_unslash( $_GET['statut'] ) ) : '';

		CsvExport::stream( $status );
	}

	/**
	 * Changement de statut depuis l'écran de détail.
	 *
	 * @return void
	 */
	private function maybe_update_status() {
		if ( empty( $_POST['ret10g_set_status'] ) ) {
			return;
		}

		check_admin_referer( 'ret10g_set_status' );

		$id     = isset( $_POST['declaration_id'] ) ? absint( wp_unslash( $_POST['declaration_id'] ) ) : 0;
		$status = sanitize_key( wp_unslash( $_POST['ret10g_set_status'] ) );

		if ( ! $id || ! isset( Declaration::statuses()[ $status ] ) ) {
			return;
		}

		Repository::update( $id, array( 'status' => $status ) );

		$declaration = Repository::find( $id );

		if ( $declaration instanceof Declaration ) {
			$order = $declaration->get_order();

			if ( $order instanceof \WC_Order ) {
				$order->add_order_note(
					sprintf(
						/* translators: 1 : référence, 2 : statut. */
						__( 'Déclaration de rétractation %1$s : %2$s.', '10gital-retractation' ),
						$declaration->get_reference(),
						$declaration->get_status_label()
					)
				);
			}

			/**
			 * Déclenché après un changement de statut de traitement.
			 *
			 * @param Declaration $declaration Déclaration.
			 * @param string      $status      Nouveau statut.
			 */
			do_action( 'ret10g_declaration_status_changed', $declaration, $status );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => self::SLUG,
					'declaration' => $id,
					'maj'         => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Actions groupées de la liste.
	 *
	 * @return void
	 */
	private function maybe_apply_bulk() {
		$action = '';

		foreach ( array( 'action', 'action2' ) as $key ) {
			if ( isset( $_REQUEST[ $key ] ) && '-1' !== $_REQUEST[ $key ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$action = sanitize_key( wp_unslash( $_REQUEST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				break;
			}
		}

		$map = array(
			'mark_accepted' => Declaration::STATUS_ACCEPTED,
			'mark_refunded' => Declaration::STATUS_REFUNDED,
			'mark_rejected' => Declaration::STATUS_REJECTED,
		);

		if ( '' === $action || ( 'delete' !== $action && ! isset( $map[ $action ] ) ) ) {
			return;
		}

		check_admin_referer( 'bulk-retractations' );

		$ids = isset( $_REQUEST['declarations'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['declarations'] ) ) : array();
		$ids = array_filter( $ids );

		if ( ! $ids ) {
			return;
		}

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				Repository::delete( $id );
				continue;
			}

			Repository::update( $id, array( 'status' => $map[ $action ] ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::SLUG,
					'traitees'  => count( $ids ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Rend l'écran.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.', '10gital-retractation' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$declaration_id = isset( $_GET['declaration'] ) ? absint( wp_unslash( $_GET['declaration'] ) ) : 0;

		if ( $declaration_id ) {
			$this->render_detail( $declaration_id );

			return;
		}

		$this->render_list();
	}

	/**
	 * Vue liste.
	 *
	 * @return void
	 */
	private function render_list() {
		$table = new ListTable();
		$table->prepare_items();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$treated = isset( $_GET['traitees'] ) ? absint( wp_unslash( $_GET['traitees'] ) ) : 0;
		$status  = isset( $_GET['statut'] ) ? sanitize_key( wp_unslash( $_GET['statut'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$export_url = wp_nonce_url(
			add_query_arg(
				array_filter(
					array(
						'page'          => self::SLUG,
						'statut'        => $status,
						'ret10g_export' => 1,
					)
				),
				admin_url( 'admin.php' )
			),
			'ret10g_export'
		);
		?>
		<div class="wrap ret10g-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Rétractations', '10gital-retractation' ); ?></h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Exporter en CSV', '10gital-retractation' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SettingsPage::SLUG ) ); ?>" class="page-title-action"><?php esc_html_e( 'Réglages', '10gital-retractation' ); ?></a>
			<hr class="wp-header-end">

			<?php if ( $treated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							/* translators: %d : nombre de déclarations. */
							esc_html( _n( '%d déclaration mise à jour.', '%d déclarations mises à jour.', $treated, '10gital-retractation' ) ),
							$treated
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php $table->views(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<?php if ( $status ) : ?>
					<input type="hidden" name="statut" value="<?php echo esc_attr( $status ); ?>">
				<?php endif; ?>
				<?php $table->search_box( __( 'Rechercher', '10gital-retractation' ), 'ret10g-search' ); ?>
			</form>

			<form method="post">
				<?php
				wp_nonce_field( 'bulk-retractations' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Vue détail.
	 *
	 * @param int $declaration_id Identifiant.
	 * @return void
	 */
	private function render_detail( $declaration_id ) {
		$declaration = Repository::find( $declaration_id );

		if ( ! $declaration instanceof Declaration ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Déclaration introuvable.', '10gital-retractation' ) . '</p></div>';

			return;
		}

		\Dixgital\Retractation\Template::output(
			'admin/detail.php',
			array(
				'declaration' => $declaration,
				'back_url'    => admin_url( 'admin.php?page=' . self::SLUG ),
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'updated'     => ! empty( $_GET['maj'] ),
			)
		);
	}
}

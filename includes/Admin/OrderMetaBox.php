<?php
/**
 * Encart « Rétractations » sur l'écran d'édition d'une commande.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Admin;

use Dixgital\Retractation\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Affiche les déclarations liées à une commande.
 */
class OrderMetaBox {

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 30, 2 );
	}

	/**
	 * Déclare l'encart, en gérant les deux stockages de commandes.
	 *
	 * @param string $screen_id Identifiant d'écran.
	 * @param mixed  $object    Objet courant.
	 * @return void
	 */
	public function add_meta_box( $screen_id, $object = null ) {
		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );

		if ( ! in_array( (string) $screen_id, $screens, true ) ) {
			return;
		}

		$order = $object instanceof \WC_Order ? $object : wc_get_order( $object );

		if ( ! $order instanceof \WC_Order || ! Repository::for_order( $order->get_id() ) ) {
			return;
		}

		add_meta_box(
			'ret10g-order-declarations',
			__( 'Rétractations', '10gital-retractation' ),
			array( $this, 'render' ),
			$screen_id,
			'side',
			'default'
		);
	}

	/**
	 * Rend l'encart.
	 *
	 * @param mixed $object Commande ou publication.
	 * @return void
	 */
	public function render( $object ) {
		$order = $object instanceof \WC_Order ? $object : wc_get_order( $object );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$declarations = Repository::for_order( $order->get_id() );

		if ( ! $declarations ) {
			echo '<p>' . esc_html__( 'Aucune déclaration pour cette commande.', '10gital-retractation' ) . '</p>';

			return;
		}

		echo '<ul class="ret10g-order-declarations">';

		foreach ( $declarations as $declaration ) {
			$url = add_query_arg(
				array(
					'page'        => DeclarationsPage::SLUG,
					'declaration' => $declaration->get_id(),
				),
				admin_url( 'admin.php' )
			);

			printf(
				'<li><a href="%1$s"><strong>%2$s</strong></a><br><span>%3$s — %4$s</span></li>',
				esc_url( $url ),
				esc_html( $declaration->get_reference() ),
				esc_html( $declaration->get_submitted_display() ),
				esc_html( $declaration->get_status_label() )
			);
		}

		echo '</ul>';
	}
}

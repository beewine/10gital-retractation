<?php
/**
 * Emplacements du bouton de rétractation.
 *
 * L'article 11 bis impose une fonction « disponible en permanence » et
 * « aisément accessible » pendant tout le délai de rétractation : le bouton est
 * donc proposé dans l'espace client, dans les e-mails et en pied de page.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Frontend;

use Dixgital\Retractation\Eligibility;
use Dixgital\Retractation\Settings;
use Dixgital\Retractation\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Affichage du bouton aux différents emplacements.
 */
class Button {

	/**
	 * Accroche les hooks d'affichage.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'bouton_retractation', array( $this, 'shortcode' ) );

		if ( ! Settings::has_page() ) {
			return;
		}

		if ( Settings::is_on( 'display_myaccount' ) ) {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_account_action' ), 10, 2 );
		}

		if ( Settings::is_on( 'display_order_detail' ) ) {
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_order_detail' ), 20 );
		}

		if ( Settings::is_on( 'display_footer' ) ) {
			add_action( 'wp_footer', array( $this, 'render_footer_link' ), 20 );
		}

		if ( Settings::is_on( 'display_emails' ) ) {
			add_action( 'woocommerce_email_order_meta', array( $this, 'render_email_link' ), 20, 3 );
		}
	}

	/**
	 * Code court `[bouton_retractation]`.
	 *
	 * @param array $atts Attributs : commande (identifiant), libelle.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'commande' => 0,
				'libelle'  => '',
			),
			(array) $atts,
			'bouton_retractation'
		);

		$order = $atts['commande'] ? wc_get_order( absint( $atts['commande'] ) ) : null;

		if ( $order instanceof \WC_Order && ! OrderLookup::current_user_owns( $order ) ) {
			$order = null;
		}

		return $this->markup(
			$order instanceof \WC_Order ? $order : null,
			$atts['libelle'] ? (string) $atts['libelle'] : null
		);
	}

	/**
	 * Ajoute l'action dans la liste des commandes de l'espace client.
	 *
	 * @param array     $actions Actions existantes.
	 * @param \WC_Order $order   Commande.
	 * @return array
	 */
	public function add_account_action( $actions, $order ) {
		if ( ! $order instanceof \WC_Order || ! Eligibility::should_show_button( $order ) ) {
			return $actions;
		}

		$actions['ret10g'] = array(
			'url'  => Settings::page_url( $order ),
			'name' => (string) Settings::get( 'button_label' ),
		);

		return $actions;
	}

	/**
	 * Affiche le bouton sous le détail d'une commande.
	 *
	 * @param \WC_Order $order Commande.
	 * @return void
	 */
	public function render_order_detail( $order ) {
		if ( ! $order instanceof \WC_Order || ! Eligibility::should_show_button( $order ) ) {
			return;
		}

		echo $this->markup( $order, null, 'ret10g-order-detail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Affiche le lien permanent en pied de page.
	 *
	 * @return void
	 */
	public function render_footer_link() {
		if ( is_admin() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
			return;
		}

		$page_id = (int) Settings::get( 'page_id' );

		if ( $page_id && is_page( $page_id ) ) {
			return;
		}

		printf(
			'<div class="ret10g-footer"><a class="ret10g-footer__link" href="%1$s">%2$s</a></div>',
			esc_url( Settings::page_url() ),
			esc_html( (string) Settings::get( 'button_label' ) )
		);
	}

	/**
	 * Ajoute le lien au bas des e-mails client.
	 *
	 * @param \WC_Order $order         Commande.
	 * @param bool      $sent_to_admin Envoi à l'administrateur.
	 * @param bool      $plain_text    Version texte.
	 * @return void
	 */
	public function render_email_link( $order, $sent_to_admin, $plain_text ) {
		if ( $sent_to_admin || ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! Eligibility::should_show_button( $order ) ) {
			return;
		}

		$url   = Settings::page_url( $order );
		$label = (string) Settings::get( 'button_label' );

		if ( $plain_text ) {
			printf( "\n%s : %s\n", esc_html( $label ), esc_url_raw( $url ) );

			return;
		}

		printf(
			'<p style="margin:16px 0;"><a href="%1$s" style="color:#1f2937;">%2$s</a></p>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/**
	 * Balisage du bouton.
	 *
	 * @param \WC_Order|null $order Commande éventuelle.
	 * @param string|null    $label Libellé personnalisé.
	 * @param string         $class Classe additionnelle.
	 * @return string
	 */
	public function markup( $order = null, $label = null, $class = '' ) {
		return Template::render(
			'button.php',
			array(
				'url'   => Settings::page_url( $order ),
				'label' => null !== $label ? $label : (string) Settings::get( 'button_label' ),
				'class' => $class,
			)
		);
	}
}

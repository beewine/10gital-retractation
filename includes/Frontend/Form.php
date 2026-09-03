<?php
/**
 * Formulaire public de rétractation : code court [retractation].
 *
 * Parcours en deux temps, comme l'exige l'article 11 bis de la directive
 * (UE) 2011/83 modifiée : déclaration, puis confirmation explicite.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Frontend;

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Eligibility;
use Dixgital\Retractation\Repository;
use Dixgital\Retractation\Security;
use Dixgital\Retractation\Settings;
use Dixgital\Retractation\Submission;
use Dixgital\Retractation\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Contrôleur du formulaire public.
 */
class Form {

	/**
	 * Action attendue dans les requêtes POST.
	 */
	private const ACTION = 'ret10g_form';

	/**
	 * Erreurs à afficher.
	 *
	 * @var \WP_Error|null
	 */
	private $error = null;

	/**
	 * Commande résolue pour l'étape de déclaration.
	 *
	 * @var \WC_Order|null
	 */
	private $order = null;

	/**
	 * Valeurs saisies à réafficher après une erreur.
	 *
	 * @var array<string,mixed>
	 */
	private $submitted = array();

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'retractation', array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
	}

	/**
	 * Traite les soumissions avant tout rendu.
	 *
	 * @return void
	 */
	public function handle_request() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		$step = isset( $_POST['ret10g_step'] ) ? sanitize_key( wp_unslash( $_POST['ret10g_step'] ) ) : '';

		if ( ! in_array( $step, array( 'lookup', 'declare' ), true ) ) {
			return;
		}

		if ( ! isset( $_POST['ret10g_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ret10g_nonce'] ) ), self::ACTION ) ) {
			$this->error = new \WP_Error( 'ret10g_nonce', __( 'Votre session a expiré. Merci de recommencer.', '10gital-retractation' ) );

			return;
		}

		if ( 'lookup' === $step ) {
			$this->handle_lookup();

			return;
		}

		$this->handle_declaration();
	}

	/**
	 * Étape 1 : identification de la commande.
	 *
	 * @return void
	 */
	private function handle_lookup() {
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;

		if ( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order instanceof \WC_Order && OrderLookup::current_user_owns( $order ) ) {
				$this->order = $order;

				return;
			}

			$this->error = new \WP_Error( 'ret10g_forbidden', __( 'Cette commande n\'est pas accessible depuis votre compte.', '10gital-retractation' ) );

			return;
		}

		$number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
		$email  = isset( $_POST['order_email'] ) ? sanitize_email( wp_unslash( $_POST['order_email'] ) ) : '';

		$this->submitted = array(
			'order_number' => $number,
			'order_email'  => $email,
		);

		$order = OrderLookup::by_number_and_email( $number, $email );

		if ( is_wp_error( $order ) ) {
			$this->error = $order;

			return;
		}

		$this->order = $order;
	}

	/**
	 * Étape 2 : enregistrement de la déclaration.
	 *
	 * @return void
	 */
	private function handle_declaration() {
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$token    = isset( $_POST['order_token'] ) ? sanitize_text_field( wp_unslash( $_POST['order_token'] ) ) : '';
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order || ! Security::verify_order_token( $order, $token ) ) {
			$this->error = new \WP_Error( 'ret10g_forbidden', __( 'Cette demande n\'a pas pu être authentifiée. Merci de recommencer depuis le début.', '10gital-retractation' ) );

			return;
		}

		$items = array();

		if ( isset( $_POST['items'] ) && is_array( $_POST['items'] ) ) {
			foreach ( wp_unslash( $_POST['items'] ) as $item_id => $quantity ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$items[ absint( $item_id ) ] = absint( $quantity );
			}
		}

		$input = array(
			'first_name'    => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'     => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'contact_email' => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
			'reason'        => isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '',
			'confirm'       => ! empty( $_POST['confirm'] ),
			'items'         => $items,
		);

		$declaration = Submission::process( $order, $input );

		if ( is_wp_error( $declaration ) ) {
			$this->error     = $declaration;
			$this->order     = $order;
			$this->submitted = $input;

			return;
		}

		$redirect = add_query_arg(
			array(
				'retractation' => rawurlencode( $declaration->get_reference() ),
				'jeton'        => rawurlencode( Security::receipt_token( $declaration->get_reference() ) ),
			),
			Settings::page_url()
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Rend le code court.
	 *
	 * @return string
	 */
	public function render() {
		wp_enqueue_style( 'ret10g-front' );
		wp_enqueue_script( 'ret10g-front' );

		$receipt = $this->maybe_render_receipt();

		if ( null !== $receipt ) {
			return $receipt;
		}

		$order = $this->order ? $this->order : $this->order_from_query();

		if ( $order instanceof \WC_Order ) {
			return $this->render_declaration_step( $order );
		}

		return $this->render_lookup_step();
	}

	/**
	 * Affiche la confirmation après dépôt, si la requête la demande.
	 *
	 * @return string|null
	 */
	private function maybe_render_receipt() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['retractation'] ) || empty( $_GET['jeton'] ) ) {
			return null;
		}

		$reference = sanitize_text_field( wp_unslash( $_GET['retractation'] ) );
		$token     = sanitize_text_field( wp_unslash( $_GET['jeton'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! Security::verify_receipt_token( $reference, $token ) ) {
			return null;
		}

		$declaration = Repository::find_by_reference( $reference );

		if ( ! $declaration instanceof Declaration ) {
			return null;
		}

		return Template::render(
			'form/confirmed.php',
			array(
				'declaration' => $declaration,
				'page_url'    => Settings::page_url(),
			)
		);
	}

	/**
	 * Résout une commande depuis les paramètres d'URL d'un lien direct.
	 *
	 * @return \WC_Order|null
	 */
	private function order_from_query() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['cle'] ) ) {
			return null;
		}

		$order = OrderLookup::by_key( sanitize_text_field( wp_unslash( $_GET['cle'] ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $order;
	}

	/**
	 * Étape d'identification.
	 *
	 * @return string
	 */
	private function render_lookup_step() {
		return Template::render(
			'form/lookup.php',
			array(
				'error'     => $this->error,
				'submitted' => $this->submitted,
				'orders'    => is_user_logged_in() ? OrderLookup::eligible_orders_for_user( get_current_user_id() ) : array(),
				'action'    => self::ACTION,
				'intro'     => (string) Settings::get( 'intro_text' ),
				'notice'    => (string) Settings::get( 'legal_notice' ),
			)
		);
	}

	/**
	 * Étape de déclaration pour une commande donnée.
	 *
	 * @param \WC_Order $order Commande.
	 * @return string
	 */
	private function render_declaration_step( \WC_Order $order ) {
		$evaluation = Eligibility::evaluate( $order );
		$can_submit = Eligibility::can_submit( $order );

		if ( is_wp_error( $can_submit ) ) {
			return Template::render(
				'form/blocked.php',
				array(
					'order'    => $order,
					'message'  => $can_submit->get_error_message(),
					'page_url' => Settings::page_url(),
				)
			);
		}

		$customer = array(
			'first_name' => $this->submitted['first_name'] ?? $order->get_billing_first_name(),
			'last_name'  => $this->submitted['last_name'] ?? $order->get_billing_last_name(),
			'email'      => $this->submitted['contact_email'] ?? $order->get_billing_email(),
			'reason'     => $this->submitted['reason'] ?? '',
			'items'      => $this->submitted['items'] ?? array(),
		);

		return Template::render(
			'form/declare.php',
			array(
				'order'         => $order,
				'items'         => $evaluation['items'],
				'error'         => $this->error,
				'customer'      => $customer,
				'action'        => self::ACTION,
				'token'         => Security::order_token( $order ),
				'confirm_label' => (string) Settings::get( 'confirm_label' ),
				'collect_reason' => Settings::is_on( 'collect_reason' ),
				'within_period' => Eligibility::is_within_period( $order ),
				'deadline'      => Eligibility::deadline_display( $order ),
				'notice'        => (string) Settings::get( 'legal_notice' ),
			)
		);
	}
}

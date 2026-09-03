<?php
/**
 * Notification interne adressée au marchand.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Emails;

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * E-mail « Nouvelle rétractation ».
 */
class MerchantNotification extends \WC_Email {

	/**
	 * Déclaration en cours d'envoi.
	 *
	 * @var Declaration|null
	 */
	public $declaration = null;

	/**
	 * Constructeur.
	 */
	public function __construct() {
		$this->id             = Manager::MERCHANT_ID;
		$this->customer_email = false;
		$this->title          = __( 'Rétractation — notification interne', '10gital-retractation' );
		$this->description    = __( 'Prévient la boutique qu\'une déclaration de rétractation vient d\'être déposée.', '10gital-retractation' );
		$this->template_html  = 'emails/ret10g-merchant.php';
		$this->template_plain = 'emails/plain/ret10g-merchant.php';
		$this->template_base  = RET10G_DIR . 'templates/';
		$this->placeholders   = array(
			'{reference}'    => '',
			'{order_number}' => '',
			'{site_title}'   => $this->get_blogname(),
		);

		parent::__construct();
	}

	/**
	 * Objet par défaut.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] Rétractation reçue — commande #{order_number}', '10gital-retractation' );
	}

	/**
	 * Titre par défaut.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Nouvelle déclaration de rétractation', '10gital-retractation' );
	}

	/**
	 * Destinataires : réglage dédié, à défaut l'adresse d'administration.
	 *
	 * @return string
	 */
	public function get_recipient() {
		$configured = $this->get_option( 'recipient' );

		if ( ! $configured ) {
			$configured = implode( ',', Settings::merchant_recipients() );
		}

		return apply_filters( 'woocommerce_email_recipient_' . $this->id, $configured, $this->object, $this );
	}

	/**
	 * Envoie l'e-mail.
	 *
	 * @param Declaration $declaration Déclaration.
	 * @return bool
	 */
	public function trigger( $declaration ) {
		if ( ! $declaration instanceof Declaration ) {
			return false;
		}

		$this->setup_locale();

		$this->declaration                    = $declaration;
		$this->object                         = $declaration;
		$this->placeholders['{reference}']    = $declaration->get_reference();
		$this->placeholders['{order_number}'] = (string) $declaration->get( 'order_number' );

		$sent = false;

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();

		return (bool) $sent;
	}

	/**
	 * Champs de réglage, avec destinataire personnalisable.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		$fields = $this->form_fields;
		$after  = array();

		foreach ( $fields as $key => $field ) {
			$after[ $key ] = $field;

			if ( 'enabled' === $key ) {
				$after['recipient'] = array(
					'title'       => __( 'Destinataires', '10gital-retractation' ),
					'type'        => 'text',
					'description' => sprintf(
						/* translators: %s : adresse par défaut. */
						__( 'Adresses séparées par des virgules. Par défaut : %s', '10gital-retractation' ),
						'<code>' . esc_html( implode( ', ', Settings::merchant_recipients() ) ) . '</code>'
					),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				);
			}
		}

		$this->form_fields = $after;
	}

	/**
	 * Déclaration à rendre : celle en cours d'envoi, ou un exemple.
	 *
	 * WooCommerce rend les e-mails dans l'aperçu des réglages et dans l'envoi
	 * de test sans passer par trigger(). Sans ce repli, le gabarit recevrait
	 * null et l'aperçu échouerait.
	 *
	 * @return Declaration
	 */
	private function resolve_declaration() {
		if ( $this->declaration instanceof Declaration ) {
			return $this->declaration;
		}

		return Declaration::sample( $this->object instanceof \WC_Order ? $this->object : null );
	}

	/**
	 * Contenu HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'declaration'   => $this->resolve_declaration(),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
			),
			\Dixgital\Retractation\Template::THEME_DIR,
			$this->template_base
		);
	}

	/**
	 * Contenu texte.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'declaration'   => $this->resolve_declaration(),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
			),
			\Dixgital\Retractation\Template::THEME_DIR,
			$this->template_base
		);
	}
}

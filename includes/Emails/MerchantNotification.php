<?php
/**
 * Notification interne adressée au marchand.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Emails;

use Dixgital\Retractation\Declaration;

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
	 * Destinataires, tels que réglés dans WooCommerce.
	 *
	 * L'ancien réglage maison a été repris une fois pour toutes dans ce champ
	 * (voir RecipientMigration) : WooCommerce fait désormais seul autorité.
	 *
	 * @return string
	 */
	public function get_recipient() {
		$configured = trim( (string) $this->get_option( 'recipient' ) );

		if ( '' === $configured ) {
			$configured = self::default_recipient();
		}

		return apply_filters( 'woocommerce_email_recipient_' . $this->id, $configured, $this->object, $this );
	}

	/**
	 * Adresse utilisée tant que rien n'a été réglé.
	 *
	 * @return string
	 */
	public static function default_recipient() {
		return (string) get_option( 'admin_email' );
	}

	/**
	 * Les demandes partent-elles encore sur l'adresse par défaut ?
	 *
	 * @param string $recipient Destinataires configurés.
	 * @return bool
	 */
	public static function is_default_recipient( $recipient ) {
		$recipient = trim( (string) $recipient );

		return '' === $recipient || strtolower( $recipient ) === strtolower( self::default_recipient() );
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

		$this->declaration                    = $declaration;
		$this->object                         = $declaration;
		$this->placeholders['{reference}']    = $declaration->get_reference();
		$this->placeholders['{order_number}'] = (string) $declaration->get( 'order_number' );

		$sent = false;

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

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
					'description' => __( 'Adresses séparées par des virgules. C\'est ici, et nulle part ailleurs, que se règle la personne qui reçoit les demandes de rétractation.', '10gital-retractation' ),
					'placeholder' => self::default_recipient(),
					'default'     => self::default_recipient(),
					'desc_tip'    => false,
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

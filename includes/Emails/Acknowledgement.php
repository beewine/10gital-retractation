<?php
/**
 * Accusé de réception adressé au consommateur.
 *
 * Constitue le support durable exigé par l'article 11 bis, § 6, de la
 * directive (UE) 2011/83 modifiée : il reprend le contenu de la déclaration
 * ainsi que la date et l'heure de son envoi.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Emails;

use Dixgital\Retractation\Declaration;

defined( 'ABSPATH' ) || exit;

/**
 * E-mail « Accusé de réception de rétractation ».
 */
class Acknowledgement extends \WC_Email {

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
		$this->id             = Manager::ACK_ID;
		$this->customer_email = true;
		$this->title          = __( 'Rétractation — accusé de réception', '10gital-retractation' );
		$this->description    = __( 'Envoyé au consommateur immédiatement après le dépôt de sa déclaration de rétractation. Cet e-mail constitue l\'accusé de réception sur support durable exigé par la réglementation : il est fortement déconseillé de le désactiver.', '10gital-retractation' );
		$this->template_html  = 'emails/ret10g-acknowledgement.php';
		$this->template_plain = 'emails/plain/ret10g-acknowledgement.php';
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
		return __( 'Accusé de réception de votre rétractation ({reference})', '10gital-retractation' );
	}

	/**
	 * Titre par défaut.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Votre rétractation a bien été reçue', '10gital-retractation' );
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

		$this->declaration                     = $declaration;
		$this->object                          = $declaration;
		$this->recipient                       = (string) $declaration->get( 'contact_email' );
		$this->placeholders['{reference}']     = $declaration->get_reference();
		$this->placeholders['{order_number}']  = (string) $declaration->get( 'order_number' );

		$sent = false;

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();

		return (bool) $sent;
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
				'declaration'   => $this->declaration,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
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
				'declaration'   => $this->declaration,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			\Dixgital\Retractation\Template::THEME_DIR,
			$this->template_base
		);
	}
}

<?php
/**
 * Enregistrement et déclenchement des e-mails du plugin.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Emails;

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Multilingual;
use Dixgital\Retractation\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Branche les e-mails sur le système WooCommerce.
 */
class Manager {

	/**
	 * Identifiant de l'accusé de réception.
	 */
	public const ACK_ID = 'ret10g_acknowledgement';

	/**
	 * Identifiant de la notification marchand.
	 */
	public const MERCHANT_ID = 'ret10g_merchant';

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) );
		add_action( 'ret10g_declaration_created', array( $this, 'dispatch' ), 10, 2 );
	}

	/**
	 * Ajoute les classes d'e-mail à WooCommerce.
	 *
	 * @param array $emails Classes existantes.
	 * @return array
	 */
	public function add_email_classes( $emails ) {
		$emails[ self::ACK_ID ]      = new Acknowledgement();
		$emails[ self::MERCHANT_ID ] = new MerchantNotification();

		return $emails;
	}

	/**
	 * Envoie les deux e-mails après enregistrement d'une déclaration.
	 *
	 * L'accusé part dans la langue de la commande — c'est le consommateur qui
	 * le lit. La notification interne part dans la langue par défaut du site :
	 * c'est la boutique qui la lit, pas le client.
	 *
	 * @param Declaration $declaration Déclaration.
	 * @param \WC_Order   $order       Commande.
	 * @return void
	 */
	public function dispatch( Declaration $declaration, $order ) {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails[ self::ACK_ID ] ) ) {
			$acknowledged = Locale::run(
				Multilingual::order_language( $order ),
				static function () use ( $emails, $declaration ) {
					return $emails[ self::ACK_ID ]->trigger( $declaration );
				}
			);

			if ( $acknowledged ) {
				Repository::update(
					$declaration->get_id(),
					array( 'acknowledged_at' => current_time( 'mysql' ) )
				);
			}
		}

		if ( isset( $emails[ self::MERCHANT_ID ] ) ) {
			Locale::run(
				Multilingual::default_language(),
				static function () use ( $emails, $declaration ) {
					return $emails[ self::MERCHANT_ID ]->trigger( $declaration );
				}
			);
		}
	}
}

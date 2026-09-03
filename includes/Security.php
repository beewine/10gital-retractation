<?php
/**
 * Jetons signés permettant de porter l'autorisation d'une étape à l'autre
 * sans ouvrir de session pour les clients invités.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Signature et vérification de jetons.
 */
class Security {

	/**
	 * Jeton autorisant l'accès au formulaire d'une commande.
	 *
	 * Dérivé de la clé de commande, qui n'est connue que du client.
	 *
	 * @param \WC_Order $order Commande.
	 * @return string
	 */
	public static function order_token( \WC_Order $order ) {
		return self::sign( 'order|' . $order->get_id() . '|' . $order->get_order_key() );
	}

	/**
	 * Vérifie un jeton de commande.
	 *
	 * @param \WC_Order $order Commande.
	 * @param string    $token Jeton reçu.
	 * @return bool
	 */
	public static function verify_order_token( \WC_Order $order, $token ) {
		return hash_equals( self::order_token( $order ), (string) $token );
	}

	/**
	 * Jeton autorisant l'affichage d'une déclaration déposée.
	 *
	 * @param string $reference Référence de la déclaration.
	 * @return string
	 */
	public static function receipt_token( $reference ) {
		return self::sign( 'receipt|' . $reference );
	}

	/**
	 * Vérifie un jeton d'accusé de réception.
	 *
	 * @param string $reference Référence.
	 * @param string $token     Jeton reçu.
	 * @return bool
	 */
	public static function verify_receipt_token( $reference, $token ) {
		return hash_equals( self::receipt_token( $reference ), (string) $token );
	}

	/**
	 * Signature HMAC courte.
	 *
	 * @param string $payload Charge utile.
	 * @return string
	 */
	private static function sign( $payload ) {
		return substr( hash_hmac( 'sha256', $payload, wp_salt( 'ret10g' ) ), 0, 32 );
	}
}

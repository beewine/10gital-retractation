<?php
/**
 * Reprise du destinataire de la notification interne.
 *
 * Jusqu'à la version 1.1.0, l'adresse pouvait être saisie à deux endroits :
 * le réglage maison `ret10g_merchant_email` et le champ « Destinataires » de
 * l'e-mail WooCommerce. Aucun des deux n'affichait l'adresse réellement
 * utilisée, et le repli silencieux envoyait les demandes à l'adresse
 * d'administration du site. WooCommerce fait désormais seul autorité ; cette
 * classe y recopie, une fois, l'adresse des sites déjà configurés.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Migration ponctuelle et idempotente.
 */
class RecipientMigration {

	/**
	 * Option drapeau : la reprise a eu lieu.
	 */
	public const FLAG = 'ret10g_merchant_recipient_migrated';

	/**
	 * Ancien réglage maison. Conservé comme trace, plus jamais lu ensuite.
	 */
	public const LEGACY_OPTION = 'ret10g_merchant_email';

	/**
	 * Réglages de l'e-mail WooCommerce.
	 */
	public const WC_OPTION = 'woocommerce_' . Manager::MERCHANT_ID . '_settings';

	/**
	 * Exécute la reprise si elle n'a pas encore eu lieu.
	 *
	 * @return void
	 */
	public static function run() {
		if ( get_option( self::FLAG ) ) {
			return;
		}

		$settings = get_option( self::WC_OPTION, array() );
		$settings = is_array( $settings ) ? $settings : array();
		$adopted  = self::resolve( $settings, (string) get_option( self::LEGACY_OPTION, '' ) );

		if ( null !== $adopted ) {
			$settings['recipient'] = $adopted;
			update_option( self::WC_OPTION, $settings );
		}

		update_option( self::FLAG, '1' );
	}

	/**
	 * Adresse à recopier dans les réglages WooCommerce.
	 *
	 * @param array  $settings Réglages WooCommerce de l'e-mail.
	 * @param string $legacy   Valeur de l'ancien réglage maison.
	 * @return string|null Adresse à écrire, ou null s'il n'y a rien à faire.
	 */
	public static function resolve( array $settings, $legacy ) {
		$current = isset( $settings['recipient'] ) ? trim( (string) $settings['recipient'] ) : '';

		// Une adresse saisie dans WooCommerce fait foi : on n'y touche pas.
		if ( '' !== $current ) {
			return null;
		}

		$legacy = self::clean_list( $legacy );

		return '' === $legacy ? null : $legacy;
	}

	/**
	 * Normalise une liste d'adresses séparées par des virgules.
	 *
	 * @param string $raw Valeur brute.
	 * @return string
	 */
	public static function clean_list( $raw ) {
		$emails = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ), 'is_email' );

		return implode( ', ', array_unique( $emails ) );
	}
}

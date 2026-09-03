<?php
/**
 * Recherche de commande pour les clients invités.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Frontend;

use Dixgital\Retractation\Eligibility;

defined( 'ABSPATH' ) || exit;

/**
 * Identifie une commande à partir d'un numéro et d'une adresse e-mail,
 * ou à partir de la clé de commande transmise dans un lien.
 */
class OrderLookup {

	/**
	 * Nombre de tentatives autorisées par fenêtre.
	 */
	private const MAX_ATTEMPTS = 8;

	/**
	 * Durée de la fenêtre, en secondes.
	 */
	private const WINDOW = 900;

	/**
	 * Retrouve une commande par sa clé (lien direct depuis un e-mail).
	 *
	 * @param string $order_key Clé de commande WooCommerce.
	 * @return \WC_Order|null
	 */
	public static function by_key( $order_key ) {
		$order_key = sanitize_text_field( (string) $order_key );

		if ( '' === $order_key || 0 !== strpos( $order_key, 'wc_order_' ) ) {
			return null;
		}

		$order_id = wc_get_order_id_by_order_key( $order_key );

		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		return $order instanceof \WC_Order ? $order : null;
	}

	/**
	 * Retrouve une commande par numéro et adresse e-mail de facturation.
	 *
	 * @param string $number Numéro de commande saisi.
	 * @param string $email  Adresse e-mail saisie.
	 * @return \WC_Order|\WP_Error
	 */
	public static function by_number_and_email( $number, $email ) {
		$number = trim( sanitize_text_field( (string) $number ) );
		$email  = sanitize_email( (string) $email );

		if ( '' === $number || ! is_email( $email ) ) {
			return new \WP_Error(
				'ret10g_lookup_invalid',
				__( 'Merci d\'indiquer un numéro de commande et une adresse e-mail valides.', '10gital-retractation' )
			);
		}

		if ( ! self::consume_attempt() ) {
			return new \WP_Error(
				'ret10g_lookup_throttled',
				__( 'Trop de tentatives. Merci de réessayer dans une quinzaine de minutes.', '10gital-retractation' )
			);
		}

		$order = self::resolve_order( $number, $email );

		if ( ! $order instanceof \WC_Order ) {
			return self::not_found_error();
		}

		$billing = strtolower( (string) $order->get_billing_email() );

		if ( '' === $billing || ! hash_equals( $billing, strtolower( $email ) ) ) {
			return self::not_found_error();
		}

		self::clear_attempts();

		return $order;
	}

	/**
	 * Commandes du compte courant sur lesquelles une rétractation est possible.
	 *
	 * @param int $user_id Identifiant utilisateur.
	 * @return \WC_Order[]
	 */
	public static function eligible_orders_for_user( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 50,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'type'        => 'shop_order',
			)
		);

		$eligible = array();

		foreach ( (array) $orders as $order ) {
			if ( $order instanceof \WC_Order && Eligibility::should_show_button( $order ) ) {
				$eligible[] = $order;
			}
		}

		return $eligible;
	}

	/**
	 * L'utilisateur courant peut-il agir sur cette commande ?
	 *
	 * @param \WC_Order $order Commande.
	 * @return bool
	 */
	public static function current_user_owns( \WC_Order $order ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		if ( (int) $order->get_customer_id() === $user_id ) {
			return true;
		}

		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Tente de résoudre la commande à partir du numéro saisi.
	 *
	 * @param string $number Numéro saisi.
	 * @param string $email  Adresse e-mail, utilisée pour restreindre la recherche.
	 * @return \WC_Order|null
	 */
	private static function resolve_order( $number, $email ) {
		$candidate = wc_get_order( absint( preg_replace( '/[^0-9]/', '', $number ) ) );

		if ( $candidate instanceof \WC_Order && self::numbers_match( $candidate, $number ) ) {
			return $candidate;
		}

		// Numérotation personnalisée : on parcourt les commandes de l'adresse.
		$orders = wc_get_orders(
			array(
				'billing_email' => $email,
				'limit'         => 50,
				'orderby'       => 'date',
				'order'         => 'DESC',
				'type'          => 'shop_order',
			)
		);

		foreach ( (array) $orders as $order ) {
			if ( $order instanceof \WC_Order && self::numbers_match( $order, $number ) ) {
				return $order;
			}
		}

		// Aucune correspondance : ne jamais renvoyer une commande dont le
		// numéro diffère de celui saisi, même si elle appartient à la même
		// adresse e-mail. Le client obtiendrait le formulaire d'une autre
		// commande que celle qu'il a demandée.
		return null;
	}

	/**
	 * Compare le numéro saisi à celui de la commande, en tolérant les préfixes.
	 *
	 * @param \WC_Order $order  Commande.
	 * @param string    $number Numéro saisi.
	 * @return bool
	 */
	private static function numbers_match( \WC_Order $order, $number ) {
		$normalize = static function ( $value ) {
			return strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $value ) );
		};

		$given = $normalize( $number );

		if ( '' === $given ) {
			return false;
		}

		return hash_equals( $normalize( $order->get_order_number() ), $given )
			|| hash_equals( $normalize( $order->get_id() ), $given );
	}

	/**
	 * Message générique, identique que la commande existe ou non.
	 *
	 * @return \WP_Error
	 */
	private static function not_found_error() {
		return new \WP_Error(
			'ret10g_lookup_failed',
			__( 'Aucune commande ne correspond à ce numéro et à cette adresse e-mail. Vérifiez les informations figurant sur votre e-mail de confirmation de commande.', '10gital-retractation' )
		);
	}

	/**
	 * Décompte une tentative de recherche.
	 *
	 * @return bool Faux si le quota est dépassé.
	 */
	private static function consume_attempt() {
		$key      = self::throttle_key();
		$attempts = (int) get_transient( $key );

		if ( $attempts >= self::MAX_ATTEMPTS ) {
			return false;
		}

		set_transient( $key, $attempts + 1, self::WINDOW );

		return true;
	}

	/**
	 * Remet le compteur à zéro après une recherche réussie.
	 *
	 * @return void
	 */
	private static function clear_attempts() {
		delete_transient( self::throttle_key() );
	}

	/**
	 * Clé de limitation, dérivée de l'adresse IP (jamais stockée en clair).
	 *
	 * @return string
	 */
	private static function throttle_key() {
		$ip = \WC_Geolocation::get_ip_address();

		return 'ret10g_lookup_' . md5( (string) $ip . wp_salt() );
	}
}

<?php
/**
 * Langue d'envoi des e-mails du plugin.
 *
 * WooCommerce bascule ses e-mails client vers la locale du site
 * (`WC_Email::setup_locale()`), ce qui, sur un site multilingue, envoie
 * l'accusé de réception d'un client anglais en français. Le plugin décide donc
 * lui-même : accusé dans la langue de la commande, notification interne dans
 * la langue par défaut du site.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Emails;

use Dixgital\Retractation\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Exécution d'un envoi dans une langue donnée.
 */
class Locale {

	/**
	 * Exécute un traitement dans la langue demandée, puis rétablit l'état.
	 *
	 * @param string   $lang     Code langue ; vide = comportement WooCommerce
	 *                           d'origine (bascule vers la locale du site).
	 * @param callable $callback Traitement, en général l'envoi d'un e-mail.
	 * @return mixed Valeur renvoyée par le traitement.
	 */
	public static function run( $lang, callable $callback ) {
		$lang = (string) $lang;

		if ( '' === $lang || ! Multilingual::is_active() ) {
			return self::run_in_site_locale( $callback );
		}

		$previous = Multilingual::current_language();
		$locale   = Multilingual::locale_for( $lang );

		if ( $lang !== $previous ) {
			do_action( 'wpml_switch_language', $lang );
		}

		$switched = $locale && function_exists( 'switch_to_locale' ) && switch_to_locale( $locale );

		try {
			return $callback();
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}

			if ( $lang !== $previous && '' !== $previous ) {
				do_action( 'wpml_switch_language', $previous );
			}
		}
	}

	/**
	 * Repli monolingue : la bascule que WooCommerce applique à ses e-mails.
	 *
	 * @param callable $callback Traitement.
	 * @return mixed
	 */
	private static function run_in_site_locale( callable $callback ) {
		$switched = function_exists( 'wc_switch_to_site_locale' );

		if ( $switched ) {
			wc_switch_to_site_locale();
		}

		try {
			return $callback();
		} finally {
			if ( $switched && function_exists( 'wc_restore_locale' ) ) {
				wc_restore_locale();
			}
		}
	}
}

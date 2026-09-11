<?php
/**
 * Compatibilité avec les extensions multilingues (WPML, Polylang).
 *
 * Tout passe par l'API publique de WPML (filtres `wpml_*`), que Polylang
 * implémente également. Sans extension multilingue, ces filtres n'ont aucun
 * écouteur : chaque méthode renvoie alors la valeur d'origine et le plugin se
 * comporte exactement comme sur un site monolingue.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Passerelle vers WPML / Polylang.
 */
class Multilingual {

	/**
	 * Une extension multilingue est-elle active ?
	 *
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'pll_current_language' );
	}

	/**
	 * Code de la langue courante (« fr », « en »…), ou chaîne vide.
	 *
	 * @return string
	 */
	public static function current_language() {
		return (string) apply_filters( 'wpml_current_language', '' );
	}

	/**
	 * Code de la langue par défaut du site, ou chaîne vide.
	 *
	 * @return string
	 */
	public static function default_language() {
		return (string) apply_filters( 'wpml_default_language', '' );
	}

	/**
	 * Langues actives : code => locale WordPress (« en » => « en_US »).
	 *
	 * @return array<string,string>
	 */
	public static function languages() {
		if ( function_exists( 'pll_languages_list' ) ) {
			$codes   = (array) pll_languages_list( array( 'fields' => 'slug' ) );
			$locales = (array) pll_languages_list( array( 'fields' => 'locale' ) );

			return array_combine( $codes, $locales ) ?: array();
		}

		$languages = apply_filters( 'wpml_active_languages', array(), array( 'skip_missing' => 0 ) );
		$list      = array();

		foreach ( (array) $languages as $code => $language ) {
			$list[ (string) $code ] = (string) ( $language['default_locale'] ?? '' );
		}

		return $list;
	}

	/**
	 * Traduction d'un objet dans une langue, ou l'objet lui-même à défaut.
	 *
	 * @param int         $id   Identifiant (post, page, produit, terme).
	 * @param string      $type Type d'objet : « page », « product », « product_cat »…
	 * @param string|null $lang Code langue ; null = langue courante.
	 * @return int
	 */
	public static function translate_id( $id, $type, $lang = null ) {
		$id = (int) $id;

		if ( ! $id ) {
			return 0;
		}

		$translated = apply_filters( 'wpml_object_id', $id, $type, true, $lang ? $lang : null );

		return $translated ? (int) $translated : $id;
	}

	/**
	 * Identifiant de l'objet dans la langue par défaut.
	 *
	 * Sert à comparer des identifiants saisis dans une langue avec des objets
	 * rencontrés dans une autre (produits et catégories exclus, notamment).
	 *
	 * @param int    $id   Identifiant.
	 * @param string $type Type d'objet.
	 * @return int
	 */
	public static function original_id( $id, $type ) {
		$default = self::default_language();

		return $default ? self::translate_id( $id, $type, $default ) : (int) $id;
	}

	/**
	 * Toutes les variantes linguistiques connues d'un ensemble d'identifiants.
	 *
	 * @param int[]  $ids  Identifiants.
	 * @param string $type Type d'objet.
	 * @return int[]
	 */
	public static function with_originals( array $ids, $type ) {
		$all = array();

		foreach ( $ids as $id ) {
			$all[] = (int) $id;
			$all[] = self::original_id( $id, $type );
		}

		return array_values( array_unique( array_filter( $all ) ) );
	}

	/**
	 * Langue dans laquelle une commande a été passée.
	 *
	 * WooCommerce Multilingual l'enregistre dans la méta `wpml_language`.
	 *
	 * @param \WC_Order $order Commande.
	 * @return string Code langue, ou chaîne vide si inconnue.
	 */
	public static function order_language( $order ) {
		$lang = '';

		if ( $order instanceof \WC_Order ) {
			$lang = (string) $order->get_meta( 'wpml_language' );
		}

		/**
		 * Filtre la langue d'une commande.
		 *
		 * @param string    $lang  Code langue.
		 * @param \WC_Order $order Commande.
		 */
		return (string) apply_filters( 'ret10g_order_language', $lang, $order );
	}

	/**
	 * Langue d'une page.
	 *
	 * @param int $page_id Identifiant.
	 * @return string
	 */
	public static function post_language( $page_id ) {
		if ( function_exists( 'pll_get_post_language' ) ) {
			return (string) pll_get_post_language( (int) $page_id );
		}

		$details = apply_filters(
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => (int) $page_id,
				'element_type' => 'post_page',
			)
		);

		return is_object( $details ) && ! empty( $details->language_code ) ? (string) $details->language_code : '';
	}

	/**
	 * Langues actives dans lesquelles la page n'a pas encore de traduction.
	 *
	 * @param int $page_id Page de référence.
	 * @return array<string,string> Code => locale.
	 */
	public static function missing_translations( $page_id ) {
		$page_id = (int) $page_id;

		if ( ! $page_id || ! self::is_active() ) {
			return array();
		}

		$missing = array();

		foreach ( self::languages() as $code => $locale ) {
			$translated = apply_filters( 'wpml_object_id', $page_id, 'page', false, $code );

			if ( ! $translated ) {
				$missing[ $code ] = $locale;
			}
		}

		return $missing;
	}

	/**
	 * Rattache une page nouvellement créée à une langue, et à sa page source.
	 *
	 * @param int    $page_id   Page à rattacher.
	 * @param string $lang      Code langue.
	 * @param int    $source_id Page source dont elle est la traduction (0 = aucune).
	 * @return void
	 */
	public static function assign_language( $page_id, $lang, $source_id = 0 ) {
		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( (int) $page_id, $lang );

			if ( $source_id && function_exists( 'pll_get_post_translations' ) && function_exists( 'pll_save_post_translations' ) ) {
				$translations          = (array) pll_get_post_translations( (int) $source_id );
				$translations[ $lang ] = (int) $page_id;
				pll_save_post_translations( $translations );
			}

			return;
		}

		$trid = false;

		if ( $source_id ) {
			$trid = apply_filters( 'wpml_element_trid', null, (int) $source_id, 'post_page' );
		}

		do_action(
			'wpml_set_element_language_details',
			array(
				'element_id'           => (int) $page_id,
				'element_type'         => 'post_page',
				'trid'                 => $trid ? $trid : false,
				'language_code'        => $lang,
				'source_language_code' => $source_id ? self::post_language( $source_id ) : null,
			)
		);
	}

	/**
	 * Exécute un traitement dans une autre locale (traductions gettext comprises).
	 *
	 * @param string   $locale   Locale WordPress, par ex. « en_US ».
	 * @param callable $callback Traitement.
	 * @return mixed Valeur renvoyée par le traitement.
	 */
	public static function in_locale( $locale, callable $callback ) {
		$switched = $locale && function_exists( 'switch_to_locale' ) && switch_to_locale( $locale );

		try {
			return $callback();
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}
		}
	}
}

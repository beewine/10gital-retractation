<?php
/**
 * Chargement des gabarits, surchargeables depuis le thème.
 *
 * Un thème peut copier un gabarit dans
 * `wp-content/themes/mon-theme/10gital-retractation/<chemin>`.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Localisation et rendu des gabarits.
 */
class Template {

	/**
	 * Sous-dossier recherché dans le thème.
	 */
	public const THEME_DIR = '10gital-retractation';

	/**
	 * Chemin absolu d'un gabarit.
	 *
	 * @param string $relative Chemin relatif, par ex. « form/lookup.php ».
	 * @return string
	 */
	public static function locate( $relative ) {
		$relative = ltrim( (string) $relative, '/' );
		$found    = locate_template( array( self::THEME_DIR . '/' . $relative ) );

		if ( ! $found ) {
			$found = RET10G_DIR . 'templates/' . $relative;
		}

		/**
		 * Filtre le chemin d'un gabarit.
		 *
		 * @param string $found    Chemin absolu.
		 * @param string $relative Chemin relatif.
		 */
		return (string) apply_filters( 'ret10g_locate_template', $found, $relative );
	}

	/**
	 * Rend un gabarit et retourne le résultat.
	 *
	 * @param string $relative Chemin relatif.
	 * @param array  $args     Variables exposées au gabarit.
	 * @return string
	 */
	public static function render( $relative, array $args = array() ) {
		$path = self::locate( $relative );

		if ( ! is_readable( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );

		ob_start();
		include $path;

		return (string) ob_get_clean();
	}

	/**
	 * Rend un gabarit et l'affiche directement.
	 *
	 * @param string $relative Chemin relatif.
	 * @param array  $args     Variables exposées au gabarit.
	 * @return void
	 */
	public static function output( $relative, array $args = array() ) {
		echo self::render( $relative, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

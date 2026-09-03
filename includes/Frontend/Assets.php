<?php
/**
 * Feuilles de style et scripts publics.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Frontend;

use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Déclaration des ressources publiques.
 */
class Assets {

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Enregistre puis met en file les ressources nécessaires.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_script( 'ret10g-front', RET10G_URL . 'assets/js/front.js', array(), RET10G_VERSION, true );

		if ( ! Settings::is_on( 'load_styles' ) ) {
			return;
		}

		wp_register_style( 'ret10g-front', RET10G_URL . 'assets/css/front.css', array(), RET10G_VERSION );
		wp_add_inline_style( 'ret10g-front', $this->inline_variables() );

		// Le bouton peut apparaître partout (pied de page, espace client) :
		// la feuille de style est donc chargée globalement, le script
		// uniquement sur la page contenant le formulaire.
		wp_enqueue_style( 'ret10g-front' );
	}

	/**
	 * Variables CSS reflétant les réglages de couleur.
	 *
	 * @return string
	 */
	private function inline_variables() {
		return sprintf(
			':root{--ret10g-button-bg:%1$s;--ret10g-button-color:%2$s;}',
			esc_attr( (string) Settings::get( 'button_bg' ) ),
			esc_attr( (string) Settings::get( 'button_color' ) )
		);
	}
}

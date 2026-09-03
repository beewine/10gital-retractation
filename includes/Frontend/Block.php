<?php
/**
 * Bloc Gutenberg « Bouton de rétractation ».
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Frontend;

use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Enregistrement du bloc, rendu côté serveur.
 */
class Block {

	/**
	 * Bouton réutilisé pour le rendu.
	 *
	 * @var Button
	 */
	private $button;

	/**
	 * Constructeur.
	 *
	 * @param Button $button Service d'affichage du bouton.
	 */
	public function __construct( Button $button ) {
		$this->button = $button;
	}

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_editor' ) );
	}

	/**
	 * Déclare le bloc auprès de WordPress.
	 *
	 * @return void
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			RET10G_DIR . 'blocks/bouton-retractation',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Rendu côté serveur.
	 *
	 * @param array $attributes Attributs du bloc.
	 * @return string
	 */
	public function render( $attributes ) {
		$label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

		return $this->button->markup( null, '' !== $label ? $label : null, 'ret10g-block' );
	}

	/**
	 * Transmet le libellé par défaut à l'éditeur.
	 *
	 * @return void
	 */
	public function localize_editor() {
		wp_add_inline_script(
			'dixgital-bouton-retractation-editor-script',
			sprintf(
				'window.ret10gBlock = %s;',
				wp_json_encode(
					array(
						'defaultLabel' => (string) Settings::get( 'button_label' ),
						'pageUrl'      => Settings::page_url(),
					)
				)
			),
			'before'
		);
	}
}

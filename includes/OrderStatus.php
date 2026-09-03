<?php
/**
 * Statut de commande « Rétractation demandée ».
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Enregistre et expose le statut de commande dédié.
 */
class OrderStatus {

	/**
	 * Clé du statut, sans le préfixe « wc- ».
	 */
	public const KEY = 'retractation';

	/**
	 * Clé complète telle qu'attendue par WooCommerce.
	 */
	public const FULL_KEY = 'wc-' . self::KEY;

	/**
	 * Accroche les filtres WooCommerce.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_to_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'exclude_from_reports' ) );
	}

	/**
	 * Déclare le statut auprès de WordPress.
	 *
	 * @return void
	 */
	public function register_status() {
		register_post_status(
			self::FULL_KEY,
			array(
				'label'                     => _x( 'Rétractation demandée', 'Statut de commande', '10gital-retractation' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s : nombre de commandes. */
				'label_count'               => _n_noop( 'Rétractation demandée <span class="count">(%s)</span>', 'Rétractation demandée <span class="count">(%s)</span>', '10gital-retractation' ),
			)
		);
	}

	/**
	 * Ajoute le statut à la liste WooCommerce, juste après « En cours ».
	 *
	 * @param array<string,string> $statuses Statuts existants.
	 * @return array<string,string>
	 */
	public function add_to_list( $statuses ) {
		$ordered = array();

		foreach ( $statuses as $key => $label ) {
			$ordered[ $key ] = $label;

			if ( 'wc-processing' === $key ) {
				$ordered[ self::FULL_KEY ] = _x( 'Rétractation demandée', 'Statut de commande', '10gital-retractation' );
			}
		}

		if ( ! isset( $ordered[ self::FULL_KEY ] ) ) {
			$ordered[ self::FULL_KEY ] = _x( 'Rétractation demandée', 'Statut de commande', '10gital-retractation' );
		}

		return $ordered;
	}

	/**
	 * Retire le statut des rapports de vente.
	 *
	 * @param string[] $statuses Statuts comptabilisés.
	 * @return string[]
	 */
	public function exclude_from_reports( $statuses ) {
		return array_diff( (array) $statuses, array( self::KEY ) );
	}
}

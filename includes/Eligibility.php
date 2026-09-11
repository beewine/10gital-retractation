<?php
/**
 * Règles d'éligibilité d'une commande au droit de rétractation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Calcule le délai et les articles concernés.
 */
class Eligibility {

	/**
	 * Horodatage de départ du délai pour une commande.
	 *
	 * @param \WC_Order $order Commande.
	 * @return int Horodatage UNIX.
	 */
	public static function start_timestamp( \WC_Order $order ) {
		$start = null;

		if ( 'completed' === Settings::get( 'period_start' ) ) {
			$completed = $order->get_date_completed();
			$start     = $completed ? $completed->getTimestamp() : null;
		}

		if ( null === $start ) {
			$created = $order->get_date_created();
			$start   = $created ? $created->getTimestamp() : time();
		}

		/**
		 * Filtre le point de départ du délai de rétractation.
		 *
		 * @param int       $start Horodatage UNIX.
		 * @param \WC_Order $order Commande.
		 */
		return (int) apply_filters( 'ret10g_period_start_timestamp', $start, $order );
	}

	/**
	 * Horodatage de fin du délai, tolérance incluse.
	 *
	 * @param \WC_Order $order Commande.
	 * @return int Horodatage UNIX.
	 */
	public static function deadline_timestamp( \WC_Order $order ) {
		$days     = (int) Settings::get( 'period_days' ) + (int) Settings::get( 'period_grace' );
		$deadline = self::start_timestamp( $order ) + ( $days * DAY_IN_SECONDS );

		/**
		 * Filtre la date limite de rétractation.
		 *
		 * @param int       $deadline Horodatage UNIX.
		 * @param \WC_Order $order    Commande.
		 */
		return (int) apply_filters( 'ret10g_deadline_timestamp', $deadline, $order );
	}

	/**
	 * Date limite formatée pour l'affichage.
	 *
	 * @param \WC_Order $order Commande.
	 * @return string
	 */
	public static function deadline_display( \WC_Order $order ) {
		return wp_date( get_option( 'date_format' ), self::deadline_timestamp( $order ) );
	}

	/**
	 * Nombre de jours restants (peut être négatif).
	 *
	 * @param \WC_Order $order Commande.
	 * @return int
	 */
	public static function days_left( \WC_Order $order ) {
		return (int) ceil( ( self::deadline_timestamp( $order ) - time() ) / DAY_IN_SECONDS );
	}

	/**
	 * La commande est-elle encore dans le délai ?
	 *
	 * @param \WC_Order $order Commande.
	 * @return bool
	 */
	public static function is_within_period( \WC_Order $order ) {
		return time() <= self::deadline_timestamp( $order );
	}

	/**
	 * Le statut de la commande autorise-t-il une rétractation ?
	 *
	 * @param \WC_Order $order Commande.
	 * @return bool
	 */
	public static function has_allowed_status( \WC_Order $order ) {
		$allowed = (array) Settings::get( 'allowed_statuses' );

		return in_array( $order->get_status(), $allowed, true ) || OrderStatus::KEY === $order->get_status();
	}

	/**
	 * Un produit est-il exclu du droit de rétractation ?
	 *
	 * @param \WC_Product|null $product Produit.
	 * @return bool
	 */
	public static function is_product_excluded( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$excluded   = false;

		// Sur un site multilingue, les identifiants saisis dans les réglages et
		// ceux de la commande peuvent appartenir à deux langues différentes :
		// on compare donc aussi leurs équivalents dans la langue par défaut.
		$product_ids  = Multilingual::with_originals( array( $product_id ), 'product' );
		$excluded_ids = Multilingual::with_originals( array_map( 'intval', (array) Settings::get( 'excluded_products' ) ), 'product' );

		if ( array_intersect( $product_ids, $excluded_ids ) ) {
			$excluded = true;
		}

		if ( ! $excluded && Settings::is_on( 'exclude_virtual' ) && ( $product->is_virtual() || $product->is_downloadable() ) ) {
			$excluded = true;
		}

		$categories = (array) Settings::get( 'excluded_categories' );

		if ( ! $excluded && $categories ) {
			$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

			if ( ! is_wp_error( $terms ) && array_intersect(
				Multilingual::with_originals( array_map( 'intval', $terms ), 'product_cat' ),
				Multilingual::with_originals( array_map( 'intval', $categories ), 'product_cat' )
			) ) {
				$excluded = true;
			}
		}

		/**
		 * Filtre l'exclusion d'un produit du droit de rétractation.
		 *
		 * @param bool             $excluded Exclu ou non.
		 * @param \WC_Product|null $product  Produit.
		 */
		return (bool) apply_filters( 'ret10g_is_product_excluded', $excluded, $product );
	}

	/**
	 * Articles de la commande sur lesquels une rétractation reste possible.
	 *
	 * @param \WC_Order $order Commande.
	 * @return array<int,array<string,mixed>> Indexé par order_item_id.
	 */
	public static function available_items( \WC_Order $order ) {
		$declared  = Repository::declared_quantities( $order->get_id() );
		$available = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			if ( self::is_product_excluded( $product ) ) {
				continue;
			}

			$ordered  = (int) $item->get_quantity();
			$refunded = (int) abs( $order->get_qty_refunded_for_item( $item_id ) );
			$already  = isset( $declared[ $item_id ] ) ? (int) $declared[ $item_id ] : 0;
			$left     = $ordered - $refunded - $already;

			if ( $left < 1 ) {
				continue;
			}

			$unit_total = $ordered > 0 ? ( (float) $item->get_total() + (float) $item->get_total_tax() ) / $ordered : 0.0;

			$available[ $item_id ] = array(
				'order_item_id' => (int) $item_id,
				'product_id'    => (int) $item->get_product_id(),
				'variation_id'  => (int) $item->get_variation_id(),
				'product_name'  => $item->get_name(),
				'sku'           => $product instanceof \WC_Product ? (string) $product->get_sku() : '',
				'quantity_max'  => $left,
				'unit_total'    => round( $unit_total, wc_get_price_decimals() ),
			);
		}

		/**
		 * Filtre les articles sur lesquels une rétractation reste possible.
		 *
		 * @param array     $available Articles disponibles.
		 * @param \WC_Order $order     Commande.
		 */
		return apply_filters( 'ret10g_available_items', $available, $order );
	}

	/**
	 * Évalue globalement une commande.
	 *
	 * @param \WC_Order $order Commande.
	 * @return array{eligible:bool,code:string,message:string,deadline:int,items:array}
	 */
	public static function evaluate( \WC_Order $order ) {
		$items    = self::available_items( $order );
		$deadline = self::deadline_timestamp( $order );

		$result = array(
			'eligible' => true,
			'code'     => 'ok',
			'message'  => '',
			'deadline' => $deadline,
			'items'    => $items,
		);

		if ( ! self::has_allowed_status( $order ) ) {
			$result['eligible'] = false;
			$result['code']     = 'status';
			$result['message']  = __( 'Le statut actuel de cette commande ne permet pas de déposer une déclaration de rétractation en ligne.', '10gital-retractation' );
		} elseif ( empty( $items ) ) {
			$result['eligible'] = false;
			$result['code']     = 'no_items';
			$result['message']  = __( 'Aucun article de cette commande n\'ouvre droit à rétractation, ou une déclaration a déjà été déposée pour la totalité des articles.', '10gital-retractation' );
		} elseif ( ! self::is_within_period( $order ) ) {
			$result['eligible'] = false;
			$result['code']     = 'expired';
			$result['message']  = sprintf(
				/* translators: %s : date limite. */
				__( 'Le délai de rétractation de cette commande a expiré le %s.', '10gital-retractation' ),
				self::deadline_display( $order )
			);
		}

		/**
		 * Filtre le résultat de l'évaluation d'éligibilité.
		 *
		 * @param array     $result Résultat.
		 * @param \WC_Order $order  Commande.
		 */
		return apply_filters( 'ret10g_evaluate_order', $result, $order );
	}

	/**
	 * Faut-il afficher le bouton pour cette commande ?
	 *
	 * @param \WC_Order $order Commande.
	 * @return bool
	 */
	public static function should_show_button( \WC_Order $order ) {
		$evaluation = self::evaluate( $order );
		$show       = $evaluation['eligible'];

		// Hors délai : on continue d'afficher le bouton sauf si le marchand a
		// choisi de refuser les demandes tardives.
		if ( ! $show && 'expired' === $evaluation['code'] && ! Settings::is_on( 'block_out_of_period' ) ) {
			$show = true;
		}

		/**
		 * Filtre l'affichage du bouton de rétractation pour une commande.
		 *
		 * @param bool      $show  Afficher ou non.
		 * @param \WC_Order $order Commande.
		 */
		return (bool) apply_filters( 'ret10g_should_show_button', $show, $order );
	}

	/**
	 * La déclaration peut-elle être enregistrée pour cette commande ?
	 *
	 * @param \WC_Order $order Commande.
	 * @return true|\WP_Error
	 */
	public static function can_submit( \WC_Order $order ) {
		$evaluation = self::evaluate( $order );

		if ( $evaluation['eligible'] ) {
			return true;
		}

		if ( 'expired' === $evaluation['code'] && ! Settings::is_on( 'block_out_of_period' ) ) {
			return true;
		}

		return new \WP_Error( 'ret10g_' . $evaluation['code'], $evaluation['message'] );
	}
}

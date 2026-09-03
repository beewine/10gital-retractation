<?php
/**
 * Enregistrement d'une déclaration de rétractation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Valide, enregistre et notifie une déclaration.
 */
class Submission {

	/**
	 * Traite une déclaration soumise par un consommateur.
	 *
	 * @param \WC_Order $order Commande concernée.
	 * @param array     $input Champs bruts du formulaire.
	 * @return Declaration|\WP_Error
	 */
	public static function process( \WC_Order $order, array $input ) {
		$can_submit = Eligibility::can_submit( $order );

		if ( is_wp_error( $can_submit ) ) {
			return $can_submit;
		}

		$first_name = sanitize_text_field( (string) ( $input['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( (string) ( $input['last_name'] ?? '' ) );
		$email      = sanitize_email( (string) ( $input['contact_email'] ?? '' ) );
		$reason     = sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) );

		if ( '' === $first_name || '' === $last_name ) {
			return new \WP_Error( 'ret10g_missing_name', __( 'Merci d\'indiquer vos nom et prénom.', '10gital-retractation' ) );
		}

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'ret10g_missing_email', __( 'Merci d\'indiquer une adresse e-mail valide pour recevoir votre accusé de réception.', '10gital-retractation' ) );
		}

		if ( empty( $input['confirm'] ) ) {
			return new \WP_Error( 'ret10g_missing_confirm', __( 'Merci de confirmer votre décision de vous rétracter avant de valider.', '10gital-retractation' ) );
		}

		$selection = self::build_selection( $order, (array) ( $input['items'] ?? array() ) );

		if ( is_wp_error( $selection ) ) {
			return $selection;
		}

		$items     = $selection['items'];
		$amount    = $selection['amount'];
		$is_full   = $selection['is_full'];
		$reference = '';

		$statement_data = array(
			'order'      => $order,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'reason'     => $reason,
			'items'      => $items,
			'amount'     => $amount,
			'is_full'    => $is_full,
		);

		$declaration_id = Repository::create(
			array(
				'order_id'      => $order->get_id(),
				'order_number'  => (string) $order->get_order_number(),
				'customer_id'   => (int) $order->get_customer_id(),
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'contact_email' => $email,
				'scope'         => $is_full ? 'full' : 'partial',
				'reason'        => $reason,
				'statement'     => '',
				'status'        => Declaration::STATUS_RECEIVED,
				'was_eligible'  => Eligibility::is_within_period( $order ) ? 1 : 0,
				'deadline_at'   => gmdate( 'Y-m-d H:i:s', Eligibility::deadline_timestamp( $order ) ),
				'amount'        => $amount,
				'currency'      => $order->get_currency(),
				'ip_address'    => Settings::is_on( 'store_ip' ) ? (string) \WC_Geolocation::get_ip_address() : '',
			),
			$items
		);

		if ( ! $declaration_id ) {
			return new \WP_Error( 'ret10g_save_failed', __( 'Votre déclaration n\'a pas pu être enregistrée. Merci de réessayer ou de nous contacter directement.', '10gital-retractation' ) );
		}

		$declaration = Repository::find( $declaration_id );

		if ( ! $declaration instanceof Declaration ) {
			return new \WP_Error( 'ret10g_save_failed', __( 'Votre déclaration n\'a pas pu être relue après enregistrement.', '10gital-retractation' ) );
		}

		$reference                   = $declaration->get_reference();
		$statement_data['reference'] = $reference;
		$statement                   = self::build_statement( $declaration, $statement_data );

		Repository::update( $declaration_id, array( 'statement' => $statement ) );

		$declaration = Repository::find( $declaration_id );

		self::annotate_order( $order, $declaration );
		self::maybe_change_status( $order, $declaration );

		/**
		 * Déclenché après l'enregistrement d'une déclaration de rétractation.
		 *
		 * @param Declaration $declaration Déclaration.
		 * @param \WC_Order   $order       Commande.
		 */
		do_action( 'ret10g_declaration_created', $declaration, $order );

		return $declaration;
	}

	/**
	 * Valide la sélection d'articles et calcule le montant concerné.
	 *
	 * @param \WC_Order $order     Commande.
	 * @param array     $requested order_item_id => quantité.
	 * @return array{items:array,amount:float,is_full:bool}|\WP_Error
	 */
	private static function build_selection( \WC_Order $order, array $requested ) {
		$available = Eligibility::available_items( $order );
		$items     = array();
		$amount    = 0.0;

		foreach ( $available as $item_id => $available_item ) {
			$quantity = isset( $requested[ $item_id ] ) ? absint( $requested[ $item_id ] ) : 0;

			if ( $quantity < 1 ) {
				continue;
			}

			$quantity   = min( $quantity, (int) $available_item['quantity_max'] );
			$line_total = round( $quantity * (float) $available_item['unit_total'], wc_get_price_decimals() );
			$amount    += $line_total;

			$items[] = array(
				'order_item_id' => (int) $item_id,
				'product_id'    => (int) $available_item['product_id'],
				'variation_id'  => (int) $available_item['variation_id'],
				'product_name'  => (string) $available_item['product_name'],
				'sku'           => (string) $available_item['sku'],
				'quantity'      => $quantity,
				'line_total'    => $line_total,
			);
		}

		if ( empty( $items ) ) {
			return new \WP_Error(
				'ret10g_no_selection',
				__( 'Merci de sélectionner au moins un article sur lequel porte votre rétractation.', '10gital-retractation' )
			);
		}

		$is_full = true;

		foreach ( $available as $item_id => $available_item ) {
			$selected = 0;

			foreach ( $items as $item ) {
				if ( (int) $item['order_item_id'] === (int) $item_id ) {
					$selected = (int) $item['quantity'];
					break;
				}
			}

			if ( $selected < (int) $available_item['quantity_max'] ) {
				$is_full = false;
				break;
			}
		}

		return array(
			'items'   => $items,
			'amount'  => round( $amount, wc_get_price_decimals() ),
			'is_full' => $is_full,
		);
	}

	/**
	 * Compose le contenu intégral de la déclaration, tel qu'il sera repris
	 * dans l'accusé de réception sur support durable.
	 *
	 * @param Declaration $declaration Déclaration enregistrée.
	 * @param array       $data        Données de composition.
	 * @return string
	 */
	public static function build_statement( Declaration $declaration, array $data ) {
		/** @var \WC_Order $order */
		$order = $data['order'];
		$lines = array();

		$lines[] = __( 'DÉCLARATION DE RÉTRACTATION', '10gital-retractation' );
		$lines[] = '';
		$lines[] = sprintf( '%s : %s', __( 'Référence', '10gital-retractation' ), $declaration->get_reference() );
		$lines[] = sprintf( '%s : %s', __( 'Date et heure de l\'envoi', '10gital-retractation' ), $declaration->get_submitted_display() );
		$lines[] = sprintf( '%s : %s', __( 'Professionnel', '10gital-retractation' ), wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) );
		$lines[] = '';
		$lines[] = sprintf( '%s : %s', __( 'Commande', '10gital-retractation' ), '#' . $order->get_order_number() );

		$created = $order->get_date_created();

		if ( $created ) {
			$lines[] = sprintf( '%s : %s', __( 'Date de la commande', '10gital-retractation' ), wp_date( get_option( 'date_format' ), $created->getTimestamp() ) );
		}

		$completed = $order->get_date_completed();

		if ( $completed ) {
			$lines[] = sprintf( '%s : %s', __( 'Date de réception (statut « Terminée »)', '10gital-retractation' ), wp_date( get_option( 'date_format' ), $completed->getTimestamp() ) );
		}

		$lines[] = '';
		$lines[] = sprintf( '%s : %s %s', __( 'Consommateur', '10gital-retractation' ), $data['first_name'], $data['last_name'] );
		$lines[] = sprintf( '%s : %s', __( 'Moyen électronique choisi pour l\'accusé de réception', '10gital-retractation' ), $data['email'] );
		$lines[] = '';
		$lines[] = $data['is_full']
			? __( 'Portée : totalité des articles encore susceptibles de rétractation.', '10gital-retractation' )
			: __( 'Portée : articles listés ci-dessous uniquement.', '10gital-retractation' );
		$lines[] = '';
		$lines[] = __( 'Articles concernés :', '10gital-retractation' );

		foreach ( $data['items'] as $item ) {
			$label = sprintf(
				'- %d × %s%s — %s',
				(int) $item['quantity'],
				$item['product_name'],
				$item['sku'] ? ' (' . $item['sku'] . ')' : '',
				wp_strip_all_tags( wc_price( (float) $item['line_total'], array( 'currency' => $order->get_currency() ) ) )
			);

			$lines[] = $label;
		}

		$lines[] = '';
		$lines[] = sprintf(
			'%s : %s',
			__( 'Montant des articles concernés', '10gital-retractation' ),
			wp_strip_all_tags( wc_price( (float) $data['amount'], array( 'currency' => $order->get_currency() ) ) )
		);

		if ( '' !== trim( (string) $data['reason'] ) ) {
			$lines[] = '';
			$lines[] = sprintf( '%s : %s', __( 'Motif communiqué à titre facultatif', '10gital-retractation' ), $data['reason'] );
		}

		$lines[] = '';
		$lines[] = __( 'Je notifie par la présente ma rétractation du contrat portant sur la vente des biens ou la prestation des services désignés ci-dessus.', '10gital-retractation' );

		if ( ! $declaration->was_eligible() ) {
			$lines[] = '';
			$lines[] = __( 'Remarque : cette déclaration a été déposée après l\'expiration du délai de rétractation calculé par la boutique. Elle a néanmoins été enregistrée et sera examinée.', '10gital-retractation' );
		}

		$statement = implode( "\n", $lines );

		/**
		 * Filtre le contenu de la déclaration.
		 *
		 * @param string      $statement   Contenu.
		 * @param Declaration $declaration Déclaration.
		 * @param array       $data        Données de composition.
		 */
		return apply_filters( 'ret10g_statement_content', $statement, $declaration, $data );
	}

	/**
	 * Ajoute une note privée à la commande.
	 *
	 * @param \WC_Order   $order       Commande.
	 * @param Declaration $declaration Déclaration.
	 * @return void
	 */
	private static function annotate_order( \WC_Order $order, Declaration $declaration ) {
		$note = sprintf(
			/* translators: 1 : référence, 2 : portée, 3 : montant. */
			__( 'Déclaration de rétractation %1$s reçue (%2$s), montant concerné : %3$s.', '10gital-retractation' ),
			$declaration->get_reference(),
			$declaration->is_full() ? __( 'totalité de la commande', '10gital-retractation' ) : __( 'articles sélectionnés', '10gital-retractation' ),
			$declaration->get_amount_display()
		);

		if ( ! $declaration->was_eligible() ) {
			$note .= ' ' . __( 'Attention : demande déposée hors délai.', '10gital-retractation' );
		}

		$order->add_order_note( $note );
	}

	/**
	 * Bascule éventuellement la commande vers le statut dédié.
	 *
	 * @param \WC_Order   $order       Commande.
	 * @param Declaration $declaration Déclaration.
	 * @return void
	 */
	private static function maybe_change_status( \WC_Order $order, Declaration $declaration ) {
		if ( ! Settings::is_on( 'change_order_status' ) || ! $declaration->is_full() ) {
			return;
		}

		if ( OrderStatus::KEY === $order->get_status() ) {
			return;
		}

		$order->update_status(
			OrderStatus::KEY,
			sprintf(
				/* translators: %s : référence de la déclaration. */
				__( 'Rétractation demandée (%s).', '10gital-retractation' ),
				$declaration->get_reference()
			)
		);
	}
}

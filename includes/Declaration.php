<?php
/**
 * Objet représentant une déclaration de rétractation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Déclaration de rétractation et ses lignes.
 */
class Declaration {

	public const STATUS_RECEIVED  = 'received';
	public const STATUS_ACCEPTED  = 'accepted';
	public const STATUS_REFUNDED  = 'refunded';
	public const STATUS_REJECTED  = 'rejected';

	/**
	 * Données brutes de la ligne.
	 *
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * Lignes de la déclaration.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $items;

	/**
	 * Constructeur.
	 *
	 * @param array $data  Ligne de la table des déclarations.
	 * @param array $items Lignes associées.
	 */
	public function __construct( array $data, array $items = array() ) {
		$this->data  = $data;
		$this->items = $items;
	}

	/**
	 * Libellés des statuts de traitement.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			self::STATUS_RECEIVED => __( 'Reçue', '10gital-retractation' ),
			self::STATUS_ACCEPTED => __( 'Acceptée', '10gital-retractation' ),
			self::STATUS_REFUNDED => __( 'Remboursée', '10gital-retractation' ),
			self::STATUS_REJECTED => __( 'Refusée', '10gital-retractation' ),
		);
	}

	/**
	 * Accès générique à un champ.
	 *
	 * @param string $key     Nom de colonne.
	 * @param mixed  $default Valeur de repli.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}

	/**
	 * Identifiant.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->get( 'id', 0 );
	}

	/**
	 * Référence lisible.
	 *
	 * @return string
	 */
	public function get_reference() {
		return (string) $this->get( 'reference' );
	}

	/**
	 * Commande associée.
	 *
	 * @return \WC_Order|null
	 */
	public function get_order() {
		$order = wc_get_order( (int) $this->get( 'order_id', 0 ) );

		return $order instanceof \WC_Order ? $order : null;
	}

	/**
	 * Nom complet du déclarant.
	 *
	 * @return string
	 */
	public function get_full_name() {
		return trim( $this->get( 'first_name' ) . ' ' . $this->get( 'last_name' ) );
	}

	/**
	 * Lignes de la déclaration.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * La déclaration porte-t-elle sur la totalité de la commande ?
	 *
	 * @return bool
	 */
	public function is_full() {
		return 'full' === $this->get( 'scope' );
	}

	/**
	 * La demande était-elle dans les délais au moment de son dépôt ?
	 *
	 * @return bool
	 */
	public function was_eligible() {
		return (bool) (int) $this->get( 'was_eligible', 1 );
	}

	/**
	 * Libellé du statut de traitement.
	 *
	 * @return string
	 */
	public function get_status_label() {
		$statuses = self::statuses();
		$status   = (string) $this->get( 'status', self::STATUS_RECEIVED );

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	}

	/**
	 * Date et heure de dépôt, formatées selon les réglages du site.
	 *
	 * @return string
	 */
	public function get_submitted_display() {
		$timestamp = strtotime( (string) $this->get( 'created_at_gmt' ) . ' UTC' );

		if ( ! $timestamp ) {
			return (string) $this->get( 'created_at' );
		}

		return wp_date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$timestamp
		);
	}

	/**
	 * Montant total des articles concernés, formaté.
	 *
	 * @return string
	 */
	public function get_amount_display() {
		$currency = (string) $this->get( 'currency' );

		return wp_strip_all_tags(
			wc_price(
				(float) $this->get( 'amount', 0 ),
				$currency ? array( 'currency' => $currency ) : array()
			)
		);
	}

	/**
	 * Représentation tableau, pour les filtres et l'export.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return $this->data + array( 'items' => $this->items );
	}
}

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
	protected $data;

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
	 * Construit une déclaration fictive, pour l'aperçu des e-mails.
	 *
	 * WooCommerce rend les e-mails dans l'écran de réglages sans les
	 * déclencher : aucune déclaration réelle n'est alors disponible. Plutôt
	 * que d'afficher une erreur, on compose un exemple représentatif à partir
	 * de la commande de démonstration fournie par WooCommerce.
	 *
	 * @param \WC_Order|null $order Commande de démonstration, si disponible.
	 * @return Declaration
	 */
	public static function sample( $order = null ) {
		$now      = current_time( 'mysql' );
		$now_gmt  = current_time( 'mysql', true );
		$currency = $order instanceof \WC_Order ? $order->get_currency() : get_woocommerce_currency();
		$items    = array();
		$amount   = 0.0;

		if ( $order instanceof \WC_Order ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				$line_total = (float) $item->get_total() + (float) $item->get_total_tax();
				$amount    += $line_total;
				$product    = $item->get_product();

				$items[] = array(
					'order_item_id' => (int) $item_id,
					'product_id'    => (int) $item->get_product_id(),
					'variation_id'  => (int) $item->get_variation_id(),
					'product_name'  => $item->get_name(),
					'sku'           => $product instanceof \WC_Product ? (string) $product->get_sku() : '',
					'quantity'      => (int) $item->get_quantity(),
					'line_total'    => $line_total,
				);
			}
		}

		if ( ! $items ) {
			$items  = array(
				array(
					'order_item_id' => 0,
					'product_id'    => 0,
					'variation_id'  => 0,
					'product_name'  => __( 'Article de démonstration', '10gital-retractation' ),
					'sku'           => 'DEMO-01',
					'quantity'      => 1,
					'line_total'    => 49.9,
				),
			);
			$amount = 49.9;
		}

		$declaration = new self(
			array(
				'id'              => 0,
				'reference'       => 'RET-' . gmdate( 'Y' ) . '-000000',
				'order_id'        => $order instanceof \WC_Order ? $order->get_id() : 0,
				'order_number'    => $order instanceof \WC_Order ? (string) $order->get_order_number() : '0000',
				'first_name'      => $order instanceof \WC_Order && $order->get_billing_first_name() ? $order->get_billing_first_name() : 'Camille',
				'last_name'       => $order instanceof \WC_Order && $order->get_billing_last_name() ? $order->get_billing_last_name() : 'Martin',
				'contact_email'   => $order instanceof \WC_Order && $order->get_billing_email() ? $order->get_billing_email() : 'client@example.com',
				'scope'           => 'full',
				'reason'          => __( 'Le produit ne correspond pas à mon besoin.', '10gital-retractation' ),
				'statement'       => '',
				'status'          => self::STATUS_RECEIVED,
				'was_eligible'    => 1,
				'deadline_at'     => $now_gmt,
				'amount'          => round( $amount, 2 ),
				'currency'        => $currency,
				'ip_address'      => '',
				'acknowledged_at' => $now,
				'created_at'      => $now,
				'created_at_gmt'  => $now_gmt,
				'updated_at'      => $now,
			),
			$items
		);

		if ( $order instanceof \WC_Order ) {
			$declaration->data['statement'] = Submission::build_statement(
				$declaration,
				array(
					'order'      => $order,
					'reference'  => $declaration->get_reference(),
					'first_name' => $declaration->get( 'first_name' ),
					'last_name'  => $declaration->get( 'last_name' ),
					'email'      => $declaration->get( 'contact_email' ),
					'reason'     => $declaration->get( 'reason' ),
					'items'      => $items,
					'amount'     => $declaration->get( 'amount' ),
					'is_full'    => true,
				)
			);
		} else {
			$declaration->data['statement'] = __( 'Aperçu : le contenu intégral de la déclaration du consommateur figure ici, avec sa date et son heure d\'envoi.', '10gital-retractation' );
		}

		return $declaration;
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

<?php
/**
 * Tableau des déclarations de rétractation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Admin;

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Repository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Liste paginée, filtrable et triable des déclarations.
 */
class ListTable extends \WP_List_Table {

	/**
	 * Nombre total de lignes correspondant au filtre courant.
	 *
	 * @var int
	 */
	private $total = 0;

	/**
	 * Constructeur.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'retractation',
				'plural'   => 'retractations',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Colonnes affichées.
	 *
	 * @return array<string,string>
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'reference'      => __( 'Référence', '10gital-retractation' ),
			'created_at_gmt' => __( 'Déposée le', '10gital-retractation' ),
			'order_number'   => __( 'Commande', '10gital-retractation' ),
			'consumer'       => __( 'Consommateur', '10gital-retractation' ),
			'scope'          => __( 'Portée', '10gital-retractation' ),
			'amount'         => __( 'Montant', '10gital-retractation' ),
			'status'         => __( 'Traitement', '10gital-retractation' ),
		);
	}

	/**
	 * Colonnes triables.
	 *
	 * @return array<string,array>
	 */
	protected function get_sortable_columns() {
		return array(
			'reference'      => array( 'reference', false ),
			'created_at_gmt' => array( 'created_at_gmt', true ),
			'order_number'   => array( 'order_number', false ),
			'amount'         => array( 'amount', false ),
			'status'         => array( 'status', false ),
		);
	}

	/**
	 * Actions groupées.
	 *
	 * @return array<string,string>
	 */
	protected function get_bulk_actions() {
		return array(
			'mark_accepted' => __( 'Marquer comme acceptée', '10gital-retractation' ),
			'mark_refunded' => __( 'Marquer comme remboursée', '10gital-retractation' ),
			'mark_rejected' => __( 'Marquer comme refusée', '10gital-retractation' ),
			'delete'        => __( 'Supprimer définitivement', '10gital-retractation' ),
		);
	}

	/**
	 * Liens de filtrage par statut.
	 *
	 * @return array<string,string>
	 */
	protected function get_views() {
		$counts  = Repository::counts_by_status();
		$total   = array_sum( $counts );
		$current = $this->current_status();
		$base    = admin_url( 'admin.php?page=ret10g-declarations' );
		$views   = array();

		$views['all'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( $base ),
			'' === $current ? ' class="current"' : '',
			esc_html__( 'Toutes', '10gital-retractation' ),
			$total
		);

		foreach ( Declaration::statuses() as $key => $label ) {
			$views[ $key ] = sprintf(
				'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
				esc_url( add_query_arg( 'statut', $key, $base ) ),
				$current === $key ? ' class="current"' : '',
				esc_html( $label ),
				isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0
			);
		}

		return $views;
	}

	/**
	 * Statut demandé dans l'URL.
	 *
	 * @return string
	 */
	private function current_status() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['statut'] ) ? sanitize_key( wp_unslash( $_GET['statut'] ) ) : '';

		return isset( Declaration::statuses()[ $status ] ) ? $status : '';
	}

	/**
	 * Prépare les lignes.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 20;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at_gmt';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'desc';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$results = Repository::query(
			array(
				'status'   => $this->current_status(),
				'search'   => $search,
				'orderby'  => $orderby,
				'order'    => $order,
				'per_page' => $per_page,
				'page'     => $this->get_pagenum(),
			)
		);

		$this->items = $results['items'];
		$this->total = (int) $results['total'];

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $this->total / $per_page ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'reference' );
	}

	/**
	 * Message quand la liste est vide.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'Aucune déclaration de rétractation pour le moment.', '10gital-retractation' );
	}

	/**
	 * Case à cocher.
	 *
	 * @param Declaration $item Déclaration.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="declarations[]" value="%d" />', $item->get_id() );
	}

	/**
	 * Colonne référence, avec actions de ligne.
	 *
	 * @param Declaration $item Déclaration.
	 * @return string
	 */
	public function column_reference( $item ) {
		$view_url = add_query_arg(
			array(
				'page'        => 'ret10g-declarations',
				'declaration' => $item->get_id(),
			),
			admin_url( 'admin.php' )
		);

		$actions = array(
			'view' => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'Détail', '10gital-retractation' ) ),
		);

		$order = $item->get_order();

		if ( $order instanceof \WC_Order ) {
			$actions['order'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $order->get_edit_order_url() ),
				esc_html__( 'Commande', '10gital-retractation' )
			);
		}

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong>%3$s',
			esc_url( $view_url ),
			esc_html( $item->get_reference() ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Rendu générique des colonnes.
	 *
	 * @param Declaration $item        Déclaration.
	 * @param string      $column_name Colonne.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at_gmt':
				$label = esc_html( $item->get_submitted_display() );

				if ( ! $item->was_eligible() ) {
					$label .= ' <span class="ret10g-badge ret10g-badge--warning">' . esc_html__( 'hors délai', '10gital-retractation' ) . '</span>';
				}

				return $label;

			case 'order_number':
				$order = $item->get_order();
				$label = esc_html( '#' . $item->get( 'order_number' ) );

				return $order instanceof \WC_Order
					? sprintf( '<a href="%s">%s</a>', esc_url( $order->get_edit_order_url() ), $label )
					: $label;

			case 'consumer':
				return sprintf(
					'%s<br><a href="mailto:%s">%s</a>',
					esc_html( $item->get_full_name() ),
					esc_attr( (string) $item->get( 'contact_email' ) ),
					esc_html( (string) $item->get( 'contact_email' ) )
				);

			case 'scope':
				return $item->is_full()
					? esc_html__( 'Commande entière', '10gital-retractation' )
					: sprintf(
						/* translators: %d : nombre d'articles. */
						esc_html( _n( '%d article', '%d articles', count( $item->get_items() ), '10gital-retractation' ) ),
						count( $item->get_items() )
					);

			case 'amount':
				return esc_html( $item->get_amount_display() );

			case 'status':
				return sprintf(
					'<span class="ret10g-badge ret10g-badge--%1$s">%2$s</span>',
					esc_attr( (string) $item->get( 'status' ) ),
					esc_html( $item->get_status_label() )
				);

			default:
				return esc_html( (string) $item->get( $column_name ) );
		}
	}
}

<?php
/**
 * Accès aux données des déclarations de rétractation.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

use Dixgital\Retractation\Core\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Lecture et écriture des déclarations.
 */
class Repository {

	/**
	 * Table des déclarations.
	 *
	 * @return string
	 */
	public static function table() {
		return Install::table( Install::TABLE_DECLARATIONS );
	}

	/**
	 * Table des lignes.
	 *
	 * @return string
	 */
	public static function items_table() {
		return Install::table( Install::TABLE_ITEMS );
	}

	/**
	 * Enregistre une déclaration et ses lignes.
	 *
	 * @param array $data  Champs de la déclaration.
	 * @param array $items Lignes : order_item_id, product_id, variation_id, product_name, sku, quantity, line_total.
	 * @return int Identifiant créé, 0 en cas d'échec.
	 */
	public static function create( array $data, array $items ) {
		global $wpdb;

		$now     = current_time( 'mysql' );
		$now_gmt = current_time( 'mysql', true );

		$row = wp_parse_args(
			$data,
			array(
				'reference'       => '',
				'order_id'        => 0,
				'order_number'    => '',
				'customer_id'     => 0,
				'first_name'      => '',
				'last_name'       => '',
				'contact_email'   => '',
				'scope'           => 'full',
				'reason'          => '',
				'statement'       => '',
				'status'          => Declaration::STATUS_RECEIVED,
				'was_eligible'    => 1,
				'deadline_at'     => null,
				'amount'          => 0,
				'currency'        => get_woocommerce_currency(),
				'ip_address'      => '',
				'acknowledged_at' => null,
				'created_at'      => $now,
				'created_at_gmt'  => $now_gmt,
				'updated_at'      => $now,
			)
		);

		if ( '' === $row['reference'] ) {
			$row['reference'] = self::generate_reference();
		}

		$inserted = $wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( ! $inserted ) {
			return 0;
		}

		$declaration_id = (int) $wpdb->insert_id;

		foreach ( $items as $item ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				self::items_table(),
				array(
					'declaration_id' => $declaration_id,
					'order_item_id'  => (int) ( $item['order_item_id'] ?? 0 ),
					'product_id'     => (int) ( $item['product_id'] ?? 0 ),
					'variation_id'   => (int) ( $item['variation_id'] ?? 0 ),
					'product_name'   => (string) ( $item['product_name'] ?? '' ),
					'sku'            => (string) ( $item['sku'] ?? '' ),
					'quantity'       => (int) ( $item['quantity'] ?? 0 ),
					'line_total'     => (float) ( $item['line_total'] ?? 0 ),
				)
			);
		}

		return $declaration_id;
	}

	/**
	 * Charge une déclaration par identifiant.
	 *
	 * @param int $id Identifiant.
	 * @return Declaration|null
	 */
	public static function find( $id ) {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );

		if ( ! $row ) {
			return null;
		}

		return new Declaration( $row, self::items_for( (int) $row['id'] ) );
	}

	/**
	 * Charge une déclaration par référence.
	 *
	 * @param string $reference Référence publique.
	 * @return Declaration|null
	 */
	public static function find_by_reference( $reference ) {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE reference = %s", (string) $reference ), ARRAY_A );

		if ( ! $row ) {
			return null;
		}

		return new Declaration( $row, self::items_for( (int) $row['id'] ) );
	}

	/**
	 * Lignes d'une déclaration.
	 *
	 * @param int $declaration_id Identifiant de la déclaration.
	 * @return array<int,array<string,mixed>>
	 */
	public static function items_for( $declaration_id ) {
		global $wpdb;

		$table = self::items_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE declaration_id = %d ORDER BY id ASC", (int) $declaration_id ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Déclarations d'une commande.
	 *
	 * @param int $order_id Identifiant de commande.
	 * @return Declaration[]
	 */
	public static function for_order( $order_id ) {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC", (int) $order_id ), ARRAY_A );

		$declarations = array();

		foreach ( (array) $rows as $row ) {
			$declarations[] = new Declaration( $row, self::items_for( (int) $row['id'] ) );
		}

		return $declarations;
	}

	/**
	 * Quantités déjà déclarées, par ligne de commande.
	 *
	 * Les déclarations refusées ne consomment pas de quantité.
	 *
	 * @param int $order_id Identifiant de commande.
	 * @return array<int,int> order_item_id => quantité cumulée.
	 */
	public static function declared_quantities( $order_id ) {
		global $wpdb;

		$table   = self::table();
		$items   = self::items_table();
		$results = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT i.order_item_id, SUM(i.quantity) AS total
				 FROM {$items} i
				 INNER JOIN {$table} d ON d.id = i.declaration_id
				 WHERE d.order_id = %d AND d.status != %s
				 GROUP BY i.order_item_id",
				(int) $order_id,
				Declaration::STATUS_REJECTED
			),
			ARRAY_A
		);

		foreach ( (array) $rows as $row ) {
			$results[ (int) $row['order_item_id'] ] = (int) $row['total'];
		}

		return $results;
	}

	/**
	 * Recherche paginée pour l'écran d'administration.
	 *
	 * @param array $args status, search, orderby, order, per_page, page.
	 * @return array{items:Declaration[],total:int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'status'   => '',
				'search'   => '',
				'orderby'  => 'created_at_gmt',
				'order'    => 'DESC',
				'per_page' => 20,
				'page'     => 1,
			)
		);

		$table    = self::table();
		$where    = array( '1=1' );
		$params   = array();
		$sortable = array( 'id', 'reference', 'order_number', 'created_at_gmt', 'status', 'amount' );

		if ( $args['status'] && isset( Declaration::statuses()[ $args['status'] ] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( '' !== trim( (string) $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( trim( (string) $args['search'] ) ) . '%';
			$where[]  = '( reference LIKE %s OR order_number LIKE %s OR contact_email LIKE %s OR last_name LIKE %s OR first_name LIKE %s )';
			$params   = array_merge( $params, array( $like, $like, $like, $like, $like ) );
		}

		$orderby = in_array( $args['orderby'], $sortable, true ) ? $args['orderby'] : 'created_at_gmt';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$clause  = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$clause}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = max( 0, ( (int) $args['page'] - 1 ) * $per_page );

		$sql       = "SELECT * FROM {$table} WHERE {$clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$sql_args  = array_merge( $params, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sql_args ), ARRAY_A );

		$declarations = array();

		foreach ( (array) $rows as $row ) {
			$declarations[] = new Declaration( $row, self::items_for( (int) $row['id'] ) );
		}

		return array(
			'items' => $declarations,
			'total' => $total,
		);
	}

	/**
	 * Compte les déclarations par statut.
	 *
	 * @return array<string,int>
	 */
	public static function counts_by_status() {
		global $wpdb;

		$table  = self::table();
		$counts = array_fill_keys( array_keys( Declaration::statuses() ), 0 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", ARRAY_A );

		foreach ( (array) $rows as $row ) {
			$counts[ (string) $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Met à jour des champs d'une déclaration.
	 *
	 * @param int   $id   Identifiant.
	 * @param array $data Champs à écrire.
	 * @return bool
	 */
	public static function update( $id, array $data ) {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
	}

	/**
	 * Supprime une déclaration et ses lignes.
	 *
	 * @param int $id Identifiant.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( self::items_table(), array( 'declaration_id' => (int) $id ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->delete( self::table(), array( 'id' => (int) $id ) );
	}

	/**
	 * Purge les déclarations plus anciennes que le nombre de jours donné.
	 *
	 * @param int $days Ancienneté en jours. 0 désactive la purge.
	 * @return int Nombre de déclarations supprimées.
	 */
	public static function purge_older_than( $days ) {
		global $wpdb;

		$days = (int) $days;

		if ( $days < 1 ) {
			return 0;
		}

		$table  = self::table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE created_at_gmt < %s", $cutoff ) );

		foreach ( (array) $ids as $id ) {
			self::delete( (int) $id );
		}

		return count( (array) $ids );
	}

	/**
	 * Génère une référence unique de la forme RET-2026-000042.
	 *
	 * @return string
	 */
	private static function generate_reference() {
		global $wpdb;

		$table = self::table();
		$year  = gmdate( 'Y' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$next = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE reference LIKE %s", $wpdb->esc_like( 'RET-' . $year . '-' ) . '%' ) );

		do {
			$reference = sprintf( 'RET-%s-%06d', $year, $next );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE reference = %s", $reference ) );
			++$next;
		} while ( $exists > 0 && $next < 999999 );

		return $reference;
	}
}

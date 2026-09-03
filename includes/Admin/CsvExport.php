<?php
/**
 * Export CSV des déclarations.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Admin;

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Génère et envoie un fichier CSV.
 */
class CsvExport {

	/**
	 * Envoie le fichier au navigateur puis arrête l'exécution.
	 *
	 * @param string $status Statut à filtrer, vide pour tout exporter.
	 * @return void
	 */
	public static function stream( $status = '' ) {
		if ( ! current_user_can( DeclarationsPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'Droits insuffisants.', '10gital-retractation' ) );
		}

		$filename = sprintf( 'retractations-%s.csv', gmdate( 'Y-m-d' ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		// BOM UTF-8 : Excel ouvre correctement les accents.
		fwrite( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		fputcsv(
			$output,
			array(
				__( 'Référence', '10gital-retractation' ),
				__( 'Déposée le', '10gital-retractation' ),
				__( 'Commande', '10gital-retractation' ),
				__( 'Prénom', '10gital-retractation' ),
				__( 'Nom', '10gital-retractation' ),
				__( 'E-mail', '10gital-retractation' ),
				__( 'Portée', '10gital-retractation' ),
				__( 'Montant', '10gital-retractation' ),
				__( 'Devise', '10gital-retractation' ),
				__( 'Dans les délais', '10gital-retractation' ),
				__( 'Traitement', '10gital-retractation' ),
				__( 'Accusé envoyé le', '10gital-retractation' ),
				__( 'Motif', '10gital-retractation' ),
				__( 'Articles', '10gital-retractation' ),
			),
			';'
		);

		$page = 1;

		do {
			$results = Repository::query(
				array(
					'status'   => $status,
					'orderby'  => 'created_at_gmt',
					'order'    => 'DESC',
					'per_page' => 200,
					'page'     => $page,
				)
			);

			foreach ( $results['items'] as $declaration ) {
				fputcsv( $output, self::row( $declaration ), ';' );
			}

			++$page;
		} while ( ! empty( $results['items'] ) && ( ( $page - 1 ) * 200 ) < (int) $results['total'] );

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Compose une ligne du fichier.
	 *
	 * @param Declaration $declaration Déclaration.
	 * @return array<int,string>
	 */
	private static function row( Declaration $declaration ) {
		$items = array();

		foreach ( $declaration->get_items() as $item ) {
			$items[] = sprintf(
				'%d x %s%s',
				(int) $item['quantity'],
				$item['product_name'],
				$item['sku'] ? ' (' . $item['sku'] . ')' : ''
			);
		}

		return array(
			$declaration->get_reference(),
			(string) $declaration->get( 'created_at' ),
			(string) $declaration->get( 'order_number' ),
			(string) $declaration->get( 'first_name' ),
			(string) $declaration->get( 'last_name' ),
			(string) $declaration->get( 'contact_email' ),
			$declaration->is_full() ? __( 'Commande entière', '10gital-retractation' ) : __( 'Partielle', '10gital-retractation' ),
			(string) $declaration->get( 'amount' ),
			(string) $declaration->get( 'currency' ),
			$declaration->was_eligible() ? __( 'Oui', '10gital-retractation' ) : __( 'Non', '10gital-retractation' ),
			$declaration->get_status_label(),
			(string) $declaration->get( 'acknowledged_at' ),
			(string) $declaration->get( 'reason' ),
			implode( ' | ', $items ),
		);
	}
}

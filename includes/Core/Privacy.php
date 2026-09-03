<?php
/**
 * Contribution à la politique de confidentialité suggérée par WordPress.
 *
 * Le plugin n'appelle aucun service tiers, mais il conserve des données
 * personnelles à titre de preuve. Le marchand doit pouvoir le documenter sans
 * avoir à lire le code.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Core;

use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Texte suggéré pour la politique de confidentialité.
 */
class Privacy {

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'add_policy_content' ) );
	}

	/**
	 * Ajoute la section suggérée dans Réglages → Confidentialité.
	 *
	 * @return void
	 */
	public function add_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$retention = (int) Settings::get( 'retention_days' );

		// La durée se dit deux fois : au consommateur dans le texte recopié,
		// et à l'administrateur dans l'instruction, lorsqu'il reste une
		// décision à prendre.
		$duration = $retention > 0
			? sprintf(
				/* translators: %d : nombre de jours. */
				__( 'Ces données sont supprimées automatiquement %d jours après le dépôt de votre déclaration.', '10gital-retractation' ),
				$retention
			)
			: __( 'Ces données sont conservées le temps nécessaire à la preuve de votre déclaration et au respect des délais de prescription applicables.', '10gital-retractation' );

		$duration_hint = $retention > 0
			? ''
			: ' ' . __( 'Aucune durée de purge n\'est configurée pour le moment : indiquez dans votre politique la durée que vous appliquez réellement, ou définissez une purge automatique dans les réglages de l\'extension.', '10gital-retractation' );

		$ip = Settings::is_on( 'store_ip' )
			? __( 'Votre adresse IP est également enregistrée, à seule fin de preuve.', '10gital-retractation' )
			: __( 'Aucune adresse IP n\'est enregistrée.', '10gital-retractation' );

		// Le paragraphe « privacy-policy-tutorial » n'est pas repris dans la
		// politique publiée : WordPress ne l'affiche qu'à titre d'instruction
		// pour l'administrateur du site. C'est donc là que se dit le partage
		// des responsabilités, qui ne concerne pas le consommateur.
		$tutorial = __( 'Section suggérée par l\'extension 10gital Rétractation. Adaptez-la à votre situation avant publication.', '10gital-retractation' )
			. ' '
			. __( 'Vous êtes seul responsable du traitement de ces données : elles sont enregistrées dans la base de votre propre site et n\'en sortent pas. 10gital, éditeur de l\'extension, n\'y a aucun accès, n\'en reçoit aucune copie et n\'a donc pas à figurer dans votre politique de confidentialité.', '10gital-retractation' )
			. $duration_hint;

		$content = '<p class="privacy-policy-tutorial">' . esc_html( $tutorial ) . '</p>'
			. '<p><strong>' . esc_html__( 'Déclarations de rétractation', '10gital-retractation' ) . '</strong></p>'
			. '<p>' . esc_html__( 'Lorsque vous exercez votre droit de rétractation depuis notre site, nous enregistrons : vos nom et prénom, l\'adresse e-mail que vous indiquez pour recevoir l\'accusé de réception, l\'identification de la commande concernée, les articles et quantités visés, le motif que vous choisissez éventuellement de communiquer, ainsi que la date et l\'heure de votre déclaration.', '10gital-retractation' ) . '</p>'
			. '<p>' . esc_html( $ip ) . '</p>'
			. '<p>' . esc_html__( 'Ces informations sont nécessaires au traitement de votre rétractation et à la preuve de son dépôt, conformément aux articles L.221-21 et suivants du code de la consommation. Elles restent hébergées avec notre site, ne sont transmises à aucun tiers et ne servent à aucune finalité commerciale.', '10gital-retractation' ) . '</p>'
			. '<p>' . esc_html( $duration ) . '</p>';

		wp_add_privacy_policy_content(
			__( '10gital Rétractation', '10gital-retractation' ),
			wp_kses_post( $content )
		);
	}
}

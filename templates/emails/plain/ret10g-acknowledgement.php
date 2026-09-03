<?php
/**
 * Accusé de réception de rétractation — version texte.
 *
 * @package Dixgital\Retractation
 *
 * @var \Dixgital\Retractation\Declaration $declaration   Déclaration.
 * @var string                             $email_heading Titre de l'e-mail.
 * @var \WC_Email                          $email         Instance d'e-mail.
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

printf(
	/* translators: %s : prénom du consommateur. */
	esc_html__( 'Bonjour %s,', '10gital-retractation' ) . "\n\n",
	esc_html( (string) $declaration->get( 'first_name' ) )
);

printf(
	/* translators: 1 : date et heure, 2 : numéro de commande. */
	esc_html__( 'Nous accusons réception de votre déclaration de rétractation, envoyée le %1$s et portant sur la commande n° %2$s.', '10gital-retractation' ) . "\n\n",
	esc_html( $declaration->get_submitted_display() ),
	esc_html( (string) $declaration->get( 'order_number' ) )
);

echo esc_html__( 'Conservez cet e-mail : il constitue la preuve du contenu de votre déclaration ainsi que de sa date et de son heure d\'envoi.', '10gital-retractation' ) . "\n\n";

echo "----------------------------------------\n\n";
echo esc_html( (string) $declaration->get( 'statement' ) ) . "\n\n";
echo "----------------------------------------\n\n";

echo esc_html__( 'Nous vous rembourserons l\'ensemble des sommes versées, y compris les frais de livraison standard, au plus tard 14 jours après avoir été informés de votre décision. Nous pouvons différer ce remboursement jusqu\'à la récupération des biens ou jusqu\'à ce que vous ayez fourni une preuve de leur expédition.', '10gital-retractation' ) . "\n\n";
echo esc_html__( 'Vous devez nous renvoyer les biens sans retard excessif, et au plus tard 14 jours après l\'envoi de cette déclaration.', '10gital-retractation' ) . "\n\n";

$additional = $email->get_additional_content();

if ( $additional ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional ) ) ) . "\n\n";
}

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );

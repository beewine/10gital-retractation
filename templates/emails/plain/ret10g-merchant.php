<?php
/**
 * Notification marchand — version texte.
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
	/* translators: 1 : référence, 2 : numéro de commande. */
	esc_html__( 'La déclaration %1$s vient d\'être déposée pour la commande n° %2$s.', '10gital-retractation' ) . "\n\n",
	esc_html( $declaration->get_reference() ),
	esc_html( (string) $declaration->get( 'order_number' ) )
);

if ( ! $declaration->was_eligible() ) {
	echo esc_html__( 'Attention : déclaration déposée hors du délai calculé par la boutique.', '10gital-retractation' ) . "\n\n";
}

echo esc_html( (string) $declaration->get( 'statement' ) ) . "\n\n";

echo esc_url_raw( admin_url( 'admin.php?page=ret10g-declarations&declaration=' . $declaration->get_id() ) ) . "\n\n";

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );

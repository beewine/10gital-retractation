<?php
/**
 * Accusé de réception de rétractation — version HTML.
 *
 * Surcharge possible : theme/10gital-retractation/emails/ret10g-acknowledgement.php
 *
 * @package Dixgital\Retractation
 *
 * @var \Dixgital\Retractation\Declaration $declaration   Déclaration.
 * @var string                             $email_heading Titre de l'e-mail.
 * @var \WC_Email                          $email         Instance d'e-mail.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s : prénom du consommateur. */
		esc_html__( 'Bonjour %s,', '10gital-retractation' ),
		esc_html( (string) $declaration->get( 'first_name' ) )
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: 1 : date et heure, 2 : numéro de commande. */
		esc_html__( 'Nous accusons réception de votre déclaration de rétractation, envoyée le %1$s et portant sur la commande n° %2$s.', '10gital-retractation' ),
		'<strong>' . esc_html( $declaration->get_submitted_display() ) . '</strong>',
		'<strong>' . esc_html( (string) $declaration->get( 'order_number' ) ) . '</strong>'
	);
	?>
</p>

<p>
	<?php esc_html_e( 'Conservez cet e-mail : il constitue la preuve du contenu de votre déclaration ainsi que de sa date et de son heure d\'envoi.', '10gital-retractation' ); ?>
</p>

<h2 style="margin-top:24px;"><?php esc_html_e( 'Contenu de votre déclaration', '10gital-retractation' ); ?></h2>

<div style="padding:16px;border:1px solid #e0e0e0;border-radius:6px;background:#f8f9fa;white-space:pre-wrap;font-family:monospace;font-size:13px;line-height:1.6;">
<?php echo esc_html( (string) $declaration->get( 'statement' ) ); ?>
</div>

<h2 style="margin-top:24px;"><?php esc_html_e( 'Et maintenant ?', '10gital-retractation' ); ?></h2>

<p>
	<?php esc_html_e( 'Nous vous rembourserons l\'ensemble des sommes versées, y compris les frais de livraison standard, au plus tard 14 jours après avoir été informés de votre décision. Nous pouvons différer ce remboursement jusqu\'à la récupération des biens ou jusqu\'à ce que vous ayez fourni une preuve de leur expédition.', '10gital-retractation' ); ?>
</p>

<p>
	<?php esc_html_e( 'Vous devez nous renvoyer les biens sans retard excessif, et au plus tard 14 jours après l\'envoi de cette déclaration.', '10gital-retractation' ); ?>
</p>

<?php
$additional = $email->get_additional_content();

if ( $additional ) {
	echo wp_kses_post( wpautop( wptexturize( $additional ) ) );
}

do_action( 'woocommerce_email_footer', $email );

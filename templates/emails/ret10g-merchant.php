<?php
/**
 * Notification marchand — version HTML.
 *
 * @package Dixgital\Retractation
 *
 * @var \Dixgital\Retractation\Declaration $declaration   Déclaration.
 * @var string                             $email_heading Titre de l'e-mail.
 * @var \WC_Email                          $email         Instance d'e-mail.
 */

defined( 'ABSPATH' ) || exit;

$order     = $declaration->get_order();
$order_url = $order instanceof WC_Order ? $order->get_edit_order_url() : '';
$admin_url = admin_url( 'admin.php?page=ret10g-declarations&declaration=' . $declaration->get_id() );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: 1 : référence, 2 : numéro de commande. */
		esc_html__( 'La déclaration %1$s vient d\'être déposée pour la commande n° %2$s.', '10gital-retractation' ),
		'<strong>' . esc_html( $declaration->get_reference() ) . '</strong>',
		'<strong>' . esc_html( (string) $declaration->get( 'order_number' ) ) . '</strong>'
	);
	?>
</p>

<?php if ( ! $declaration->was_eligible() ) : ?>
	<p style="padding:12px;border-left:4px solid #b54708;background:#fffaeb;">
		<?php esc_html_e( 'Attention : cette déclaration a été déposée après l\'expiration du délai calculé par la boutique. Vérifiez la date de livraison réelle avant de refuser.', '10gital-retractation' ); ?>
	</p>
<?php endif; ?>

<ul>
	<li><strong><?php esc_html_e( 'Consommateur', '10gital-retractation' ); ?></strong> : <?php echo esc_html( $declaration->get_full_name() ); ?></li>
	<li><strong><?php esc_html_e( 'Adresse e-mail', '10gital-retractation' ); ?></strong> : <?php echo esc_html( (string) $declaration->get( 'contact_email' ) ); ?></li>
	<li><strong><?php esc_html_e( 'Portée', '10gital-retractation' ); ?></strong> : <?php echo esc_html( $declaration->is_full() ? __( 'totalité de la commande', '10gital-retractation' ) : __( 'articles sélectionnés', '10gital-retractation' ) ); ?></li>
	<li><strong><?php esc_html_e( 'Montant concerné', '10gital-retractation' ); ?></strong> : <?php echo esc_html( $declaration->get_amount_display() ); ?></li>
	<li><strong><?php esc_html_e( 'Déposée le', '10gital-retractation' ); ?></strong> : <?php echo esc_html( $declaration->get_submitted_display() ); ?></li>
</ul>

<h2><?php esc_html_e( 'Articles', '10gital-retractation' ); ?></h2>

<ul>
	<?php foreach ( $declaration->get_items() as $item ) : ?>
		<li>
			<?php echo esc_html( sprintf( '%d × %s', (int) $item['quantity'], $item['product_name'] ) ); ?>
			<?php if ( ! empty( $item['sku'] ) ) : ?>
				<em>(<?php echo esc_html( (string) $item['sku'] ); ?>)</em>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php if ( '' !== trim( (string) $declaration->get( 'reason' ) ) ) : ?>
	<h2><?php esc_html_e( 'Motif communiqué', '10gital-retractation' ); ?></h2>
	<p><?php echo esc_html( (string) $declaration->get( 'reason' ) ); ?></p>
<?php endif; ?>

<p style="margin-top:24px;">
	<a href="<?php echo esc_url( $admin_url ); ?>"><?php esc_html_e( 'Ouvrir la déclaration', '10gital-retractation' ); ?></a>
	<?php if ( $order_url ) : ?>
		&nbsp;·&nbsp;
		<a href="<?php echo esc_url( $order_url ); ?>"><?php esc_html_e( 'Ouvrir la commande', '10gital-retractation' ); ?></a>
	<?php endif; ?>
</p>

<?php
$additional = $email->get_additional_content();

if ( $additional ) {
	echo wp_kses_post( wpautop( wptexturize( $additional ) ) );
}

do_action( 'woocommerce_email_footer', $email );

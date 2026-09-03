<?php
/**
 * Message affiché lorsqu'une déclaration n'est pas possible.
 *
 * @package Dixgital\Retractation
 *
 * @var \WC_Order $order    Commande.
 * @var string    $message  Explication.
 * @var string    $page_url URL de la page de rétractation.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ret10g ret10g--blocked">
	<div class="ret10g__alert ret10g__alert--warning" role="alert">
		<p><strong><?php echo esc_html( '#' . $order->get_order_number() ); ?></strong></p>
		<p><?php echo esc_html( $message ); ?></p>
	</div>

	<p class="ret10g__actions">
		<a class="ret10g__button ret10g__button--ghost" href="<?php echo esc_url( $page_url ); ?>">
			<?php esc_html_e( 'Revenir au début', '10gital-retractation' ); ?>
		</a>
	</p>
</div>

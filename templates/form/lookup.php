<?php
/**
 * Étape 1 : identification de la commande.
 *
 * @package Dixgital\Retractation
 *
 * @var \WP_Error|null $error     Erreur éventuelle.
 * @var array          $submitted Valeurs saisies.
 * @var \WC_Order[]    $orders    Commandes éligibles du compte connecté.
 * @var string         $action    Action de nonce.
 * @var string         $intro     Texte d'introduction.
 * @var string         $notice    Mention légale.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ret10g ret10g--lookup">

	<?php if ( '' !== trim( $intro ) ) : ?>
		<div class="ret10g__intro"><?php echo wp_kses_post( wpautop( $intro ) ); ?></div>
	<?php endif; ?>

	<?php if ( $error instanceof WP_Error ) : ?>
		<div class="ret10g__alert ret10g__alert--error" role="alert">
			<?php echo esc_html( $error->get_error_message() ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $orders ) ) : ?>
		<section class="ret10g__section">
			<h2 class="ret10g__title"><?php esc_html_e( 'Vos commandes concernées', '10gital-retractation' ); ?></h2>

			<ul class="ret10g__orders">
				<?php foreach ( $orders as $order ) : ?>
					<li class="ret10g__order">
						<div class="ret10g__order-meta">
							<strong><?php echo esc_html( '#' . $order->get_order_number() ); ?></strong>
							<span>
								<?php
								$created = $order->get_date_created();
								echo esc_html( $created ? wp_date( get_option( 'date_format' ), $created->getTimestamp() ) : '' );
								?>
							</span>
							<span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
							<span class="ret10g__deadline">
								<?php
								printf(
									/* translators: %s : date limite. */
									esc_html__( 'Jusqu\'au %s', '10gital-retractation' ),
									esc_html( \Dixgital\Retractation\Eligibility::deadline_display( $order ) )
								);
								?>
							</span>
						</div>

						<form method="post" class="ret10g__order-form">
							<?php wp_nonce_field( $action, 'ret10g_nonce' ); ?>
							<input type="hidden" name="ret10g_step" value="lookup">
							<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
							<button type="submit" class="ret10g__button">
								<?php echo esc_html( (string) \Dixgital\Retractation\Settings::get( 'button_label' ) ); ?>
							</button>
						</form>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<section class="ret10g__section">
		<h2 class="ret10g__title">
			<?php
			echo empty( $orders )
				? esc_html__( 'Retrouver votre commande', '10gital-retractation' )
				: esc_html__( 'Une autre commande ?', '10gital-retractation' );
			?>
		</h2>

		<p class="ret10g__help">
			<?php esc_html_e( 'Aucun compte n\'est nécessaire : indiquez le numéro de commande et l\'adresse e-mail utilisée lors de l\'achat.', '10gital-retractation' ); ?>
		</p>

		<form method="post" class="ret10g__form">
			<?php wp_nonce_field( $action, 'ret10g_nonce' ); ?>
			<input type="hidden" name="ret10g_step" value="lookup">

			<p class="ret10g__field">
				<label for="ret10g-order-number"><?php esc_html_e( 'Numéro de commande', '10gital-retractation' ); ?></label>
				<input
					type="text"
					id="ret10g-order-number"
					name="order_number"
					value="<?php echo esc_attr( (string) ( $submitted['order_number'] ?? '' ) ); ?>"
					autocomplete="off"
					required>
			</p>

			<p class="ret10g__field">
				<label for="ret10g-order-email"><?php esc_html_e( 'Adresse e-mail de la commande', '10gital-retractation' ); ?></label>
				<input
					type="email"
					id="ret10g-order-email"
					name="order_email"
					value="<?php echo esc_attr( (string) ( $submitted['order_email'] ?? '' ) ); ?>"
					autocomplete="email"
					required>
			</p>

			<p class="ret10g__actions">
				<button type="submit" class="ret10g__button"><?php esc_html_e( 'Continuer', '10gital-retractation' ); ?></button>
			</p>
		</form>
	</section>

	<?php if ( '' !== trim( $notice ) ) : ?>
		<div class="ret10g__notice"><?php echo wp_kses_post( wpautop( $notice ) ); ?></div>
	<?php endif; ?>
</div>

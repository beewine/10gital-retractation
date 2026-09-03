<?php
/**
 * Étape 2 : déclaration puis confirmation.
 *
 * @package Dixgital\Retractation
 *
 * @var \WC_Order      $order          Commande.
 * @var array          $items          Articles disponibles.
 * @var \WP_Error|null $error          Erreur éventuelle.
 * @var array          $customer       Valeurs pré-remplies.
 * @var string         $action         Action de nonce.
 * @var string         $token          Jeton d'autorisation.
 * @var string         $confirm_label  Libellé du bouton de confirmation.
 * @var bool           $collect_reason Proposer un motif.
 * @var bool           $within_period  Demande dans les délais.
 * @var string         $deadline       Date limite formatée.
 * @var string         $notice         Mention légale.
 */

defined( 'ABSPATH' ) || exit;

$created = $order->get_date_created();
?>
<div class="ret10g ret10g--declare">

	<?php if ( $error instanceof WP_Error ) : ?>
		<div class="ret10g__alert ret10g__alert--error" role="alert">
			<?php echo esc_html( $error->get_error_message() ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $within_period ) : ?>
		<div class="ret10g__alert ret10g__alert--warning" role="status">
			<?php
			printf(
				/* translators: %s : date limite. */
				esc_html__( 'Le délai de rétractation calculé pour cette commande a expiré le %s. Vous pouvez tout de même déposer votre déclaration : elle sera enregistrée, horodatée et examinée.', '10gital-retractation' ),
				esc_html( $deadline )
			);
			?>
		</div>
	<?php endif; ?>

	<section class="ret10g__section ret10g__contract">
		<h2 class="ret10g__title"><?php esc_html_e( 'Contrat concerné', '10gital-retractation' ); ?></h2>
		<dl class="ret10g__summary">
			<div>
				<dt><?php esc_html_e( 'Commande', '10gital-retractation' ); ?></dt>
				<dd><?php echo esc_html( '#' . $order->get_order_number() ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Date', '10gital-retractation' ); ?></dt>
				<dd><?php echo esc_html( $created ? wp_date( get_option( 'date_format' ), $created->getTimestamp() ) : '—' ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Total', '10gital-retractation' ); ?></dt>
				<dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Date limite de rétractation', '10gital-retractation' ); ?></dt>
				<dd><?php echo esc_html( $deadline ); ?></dd>
			</div>
		</dl>
	</section>

	<form method="post" class="ret10g__form ret10g__form--declare"
		data-ret10g-empty-message="<?php esc_attr_e( 'Merci de sélectionner au moins un article sur lequel porte votre rétractation.', '10gital-retractation' ); ?>">
		<?php wp_nonce_field( $action, 'ret10g_nonce' ); ?>
		<input type="hidden" name="ret10g_step" value="declare">
		<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
		<input type="hidden" name="order_token" value="<?php echo esc_attr( $token ); ?>">

		<section class="ret10g__section">
			<h2 class="ret10g__title"><?php esc_html_e( 'Articles sur lesquels porte votre rétractation', '10gital-retractation' ); ?></h2>

			<p class="ret10g__actions ret10g__actions--inline">
				<button type="button" class="ret10g__link" data-ret10g-select-all>
					<?php esc_html_e( 'Tout sélectionner', '10gital-retractation' ); ?>
				</button>
				<button type="button" class="ret10g__link" data-ret10g-select-none>
					<?php esc_html_e( 'Tout désélectionner', '10gital-retractation' ); ?>
				</button>
			</p>

			<ul class="ret10g__items">
				<?php foreach ( $items as $item_id => $item ) : ?>
					<?php
					$selected = isset( $customer['items'][ $item_id ] ) ? (int) $customer['items'][ $item_id ] : (int) $item['quantity_max'];
					$field_id = 'ret10g-item-' . (int) $item_id;
					?>
					<li class="ret10g__item">
						<label class="ret10g__item-label" for="<?php echo esc_attr( $field_id ); ?>">
							<span class="ret10g__item-name">
								<?php echo esc_html( $item['product_name'] ); ?>
								<?php if ( $item['sku'] ) : ?>
									<small><?php echo esc_html( $item['sku'] ); ?></small>
								<?php endif; ?>
							</span>
							<span class="ret10g__item-price">
								<?php echo wp_kses_post( wc_price( (float) $item['unit_total'], array( 'currency' => $order->get_currency() ) ) ); ?>
							</span>
						</label>

						<span class="ret10g__item-qty">
							<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>">
								<?php esc_html_e( 'Quantité', '10gital-retractation' ); ?>
							</label>
							<input
								type="number"
								id="<?php echo esc_attr( $field_id ); ?>"
								name="items[<?php echo esc_attr( (string) $item_id ); ?>]"
								value="<?php echo esc_attr( (string) $selected ); ?>"
								min="0"
								max="<?php echo esc_attr( (string) $item['quantity_max'] ); ?>"
								step="1"
								inputmode="numeric"
								data-ret10g-qty
								data-ret10g-max="<?php echo esc_attr( (string) $item['quantity_max'] ); ?>">
							<span class="ret10g__item-max">
								<?php
								printf(
									/* translators: %d : quantité commandée. */
									esc_html__( 'sur %d', '10gital-retractation' ),
									(int) $item['quantity_max']
								);
								?>
							</span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="ret10g__section">
			<h2 class="ret10g__title"><?php esc_html_e( 'Vos coordonnées', '10gital-retractation' ); ?></h2>

			<div class="ret10g__grid">
				<p class="ret10g__field">
					<label for="ret10g-first-name"><?php esc_html_e( 'Prénom', '10gital-retractation' ); ?></label>
					<input type="text" id="ret10g-first-name" name="first_name" value="<?php echo esc_attr( (string) $customer['first_name'] ); ?>" autocomplete="given-name" required>
				</p>

				<p class="ret10g__field">
					<label for="ret10g-last-name"><?php esc_html_e( 'Nom', '10gital-retractation' ); ?></label>
					<input type="text" id="ret10g-last-name" name="last_name" value="<?php echo esc_attr( (string) $customer['last_name'] ); ?>" autocomplete="family-name" required>
				</p>
			</div>

			<p class="ret10g__field">
				<label for="ret10g-contact-email"><?php esc_html_e( 'Adresse e-mail à laquelle envoyer l\'accusé de réception', '10gital-retractation' ); ?></label>
				<input type="email" id="ret10g-contact-email" name="contact_email" value="<?php echo esc_attr( (string) $customer['email'] ); ?>" autocomplete="email" required>
			</p>

			<?php if ( $collect_reason ) : ?>
				<p class="ret10g__field">
					<label for="ret10g-reason">
						<?php esc_html_e( 'Motif (facultatif)', '10gital-retractation' ); ?>
					</label>
					<textarea id="ret10g-reason" name="reason" rows="3" maxlength="1000"><?php echo esc_textarea( (string) $customer['reason'] ); ?></textarea>
					<small class="ret10g__help">
						<?php esc_html_e( 'Vous n\'avez pas à motiver votre décision. Ce champ nous aide simplement à améliorer nos produits.', '10gital-retractation' ); ?>
					</small>
				</p>
			<?php endif; ?>
		</section>

		<section class="ret10g__section ret10g__section--confirm">
			<p class="ret10g__field ret10g__field--checkbox">
				<label for="ret10g-confirm">
					<input type="checkbox" id="ret10g-confirm" name="confirm" value="1" required>
					<span>
						<?php esc_html_e( 'Je notifie par la présente ma rétractation du contrat portant sur la vente des biens ou la prestation des services désignés ci-dessus.', '10gital-retractation' ); ?>
					</span>
				</label>
			</p>

			<p class="ret10g__actions">
				<button type="submit" class="ret10g__button ret10g__button--confirm">
					<?php echo esc_html( $confirm_label ); ?>
				</button>
			</p>

			<p class="ret10g__help">
				<?php esc_html_e( 'Un accusé de réception horodaté vous sera envoyé immédiatement à l\'adresse indiquée ci-dessus.', '10gital-retractation' ); ?>
			</p>
		</section>
	</form>

	<?php if ( '' !== trim( $notice ) ) : ?>
		<div class="ret10g__notice"><?php echo wp_kses_post( wpautop( $notice ) ); ?></div>
	<?php endif; ?>
</div>

<?php
/**
 * Détail d'une déclaration de rétractation.
 *
 * @package Dixgital\Retractation
 *
 * @var \Dixgital\Retractation\Declaration $declaration Déclaration.
 * @var string                             $back_url    Retour à la liste.
 * @var bool                               $updated     Mise à jour effectuée.
 */

defined( 'ABSPATH' ) || exit;

$order = $declaration->get_order();
?>
<div class="wrap ret10g-admin ret10g-admin--detail">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $declaration->get_reference() ); ?>
	</h1>
	<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action"><?php esc_html_e( 'Retour à la liste', '10gital-retractation' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $updated ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Déclaration mise à jour.', '10gital-retractation' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $declaration->was_eligible() ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Cette déclaration a été déposée après l\'expiration du délai calculé par la boutique. Vérifiez la date de livraison réelle avant de la refuser : le point de départ légal est la réception du bien par le consommateur.', '10gital-retractation' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="ret10g-admin__columns">
		<div class="ret10g-admin__main">
			<div class="ret10g-card">
				<h2><?php esc_html_e( 'Contenu de la déclaration', '10gital-retractation' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Texte exact transmis au consommateur dans son accusé de réception. C\'est cette version qui fait foi.', '10gital-retractation' ); ?></p>
				<pre class="ret10g-statement"><?php echo esc_html( (string) $declaration->get( 'statement' ) ); ?></pre>
			</div>

			<div class="ret10g-card">
				<h2><?php esc_html_e( 'Articles', '10gital-retractation' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Produit', '10gital-retractation' ); ?></th>
							<th><?php esc_html_e( 'UGS', '10gital-retractation' ); ?></th>
							<th><?php esc_html_e( 'Quantité', '10gital-retractation' ); ?></th>
							<th><?php esc_html_e( 'Montant', '10gital-retractation' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $declaration->get_items() as $item ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $item['product_name'] ); ?></td>
								<td><?php echo esc_html( (string) $item['sku'] ); ?></td>
								<td><?php echo esc_html( (string) $item['quantity'] ); ?></td>
								<td>
									<?php
									echo wp_kses_post(
										wc_price(
											(float) $item['line_total'],
											array( 'currency' => (string) $declaration->get( 'currency' ) )
										)
									);
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( '' !== trim( (string) $declaration->get( 'reason' ) ) ) : ?>
				<div class="ret10g-card">
					<h2><?php esc_html_e( 'Motif communiqué', '10gital-retractation' ); ?></h2>
					<p><?php echo esc_html( (string) $declaration->get( 'reason' ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<div class="ret10g-admin__side">
			<div class="ret10g-card">
				<h2><?php esc_html_e( 'Traitement', '10gital-retractation' ); ?></h2>

				<form method="post">
					<?php wp_nonce_field( 'ret10g_set_status' ); ?>
					<input type="hidden" name="declaration_id" value="<?php echo esc_attr( (string) $declaration->get_id() ); ?>">

					<p>
						<label for="ret10g-status"><?php esc_html_e( 'Statut', '10gital-retractation' ); ?></label>
						<select name="ret10g_set_status" id="ret10g-status">
							<?php foreach ( \Dixgital\Retractation\Declaration::statuses() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, (string) $declaration->get( 'status' ) ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer', '10gital-retractation' ); ?></button>
					</p>
				</form>

				<?php if ( $order instanceof WC_Order ) : ?>
					<p>
						<a class="button" href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">
							<?php esc_html_e( 'Ouvrir la commande', '10gital-retractation' ); ?>
						</a>
					</p>
					<p class="description">
						<?php esc_html_e( 'Le remboursement s\'effectue depuis la commande, avec les outils natifs de WooCommerce. Ce plugin ne déclenche jamais de remboursement automatique.', '10gital-retractation' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="ret10g-card">
				<h2><?php esc_html_e( 'Informations', '10gital-retractation' ); ?></h2>
				<table class="ret10g-meta">
					<tr>
						<th><?php esc_html_e( 'Déposée le', '10gital-retractation' ); ?></th>
						<td><?php echo esc_html( $declaration->get_submitted_display() ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Accusé envoyé le', '10gital-retractation' ); ?></th>
						<td>
							<?php
							$ack = (string) $declaration->get( 'acknowledged_at' );
							echo esc_html( $ack ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ack ) : __( 'non envoyé', '10gital-retractation' ) );
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Consommateur', '10gital-retractation' ); ?></th>
						<td><?php echo esc_html( $declaration->get_full_name() ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Adresse e-mail', '10gital-retractation' ); ?></th>
						<td><a href="mailto:<?php echo esc_attr( (string) $declaration->get( 'contact_email' ) ); ?>"><?php echo esc_html( (string) $declaration->get( 'contact_email' ) ); ?></a></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Commande', '10gital-retractation' ); ?></th>
						<td><?php echo esc_html( '#' . $declaration->get( 'order_number' ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Montant', '10gital-retractation' ); ?></th>
						<td><?php echo esc_html( $declaration->get_amount_display() ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Date limite calculée', '10gital-retractation' ); ?></th>
						<td>
							<?php
							$deadline = (string) $declaration->get( 'deadline_at' );
							echo esc_html( $deadline ? mysql2date( get_option( 'date_format' ), $deadline ) : '—' );
							?>
						</td>
					</tr>
					<?php if ( (string) $declaration->get( 'ip_address' ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Adresse IP', '10gital-retractation' ); ?></th>
							<td><code><?php echo esc_html( (string) $declaration->get( 'ip_address' ) ); ?></code></td>
						</tr>
					<?php endif; ?>
				</table>
			</div>
		</div>
	</div>
</div>

<?php
/**
 * Confirmation affichée après le dépôt de la déclaration.
 *
 * @package Dixgital\Retractation
 *
 * @var \Dixgital\Retractation\Declaration $declaration Déclaration déposée.
 * @var string                             $page_url    URL de la page.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ret10g ret10g--confirmed">
	<div class="ret10g__alert ret10g__alert--success" role="status">
		<h2 class="ret10g__title"><?php esc_html_e( 'Votre rétractation a bien été enregistrée', '10gital-retractation' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %s : adresse e-mail. */
				esc_html__( 'Un accusé de réception vient d\'être envoyé à %s. Conservez-le : il fait foi de la date et de l\'heure de votre déclaration.', '10gital-retractation' ),
				'<strong>' . esc_html( (string) $declaration->get( 'contact_email' ) ) . '</strong>'
			);
			?>
		</p>
	</div>

	<dl class="ret10g__summary">
		<div>
			<dt><?php esc_html_e( 'Référence', '10gital-retractation' ); ?></dt>
			<dd><?php echo esc_html( $declaration->get_reference() ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Date et heure', '10gital-retractation' ); ?></dt>
			<dd><?php echo esc_html( $declaration->get_submitted_display() ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Commande', '10gital-retractation' ); ?></dt>
			<dd><?php echo esc_html( '#' . $declaration->get( 'order_number' ) ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Montant concerné', '10gital-retractation' ); ?></dt>
			<dd><?php echo esc_html( $declaration->get_amount_display() ); ?></dd>
		</div>
	</dl>

	<h3 class="ret10g__title ret10g__title--small"><?php esc_html_e( 'Contenu de votre déclaration', '10gital-retractation' ); ?></h3>
	<pre class="ret10g__statement"><?php echo esc_html( (string) $declaration->get( 'statement' ) ); ?></pre>

	<p class="ret10g__actions">
		<a class="ret10g__button ret10g__button--ghost" href="<?php echo esc_url( $page_url ); ?>">
			<?php esc_html_e( 'Déposer une autre déclaration', '10gital-retractation' ); ?>
		</a>
	</p>
</div>

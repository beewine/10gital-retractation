<?php
/**
 * Bouton de rétractation.
 *
 * @package Dixgital\Retractation
 *
 * @var string $url   Destination.
 * @var string $label Libellé.
 * @var string $class Classe additionnelle.
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="ret10g-button-wrap <?php echo esc_attr( $class ); ?>">
	<a class="ret10g__button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
</p>

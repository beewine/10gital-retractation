<?php
/**
 * Exécution des bancs d'essai : `php tests/run.php`.
 *
 * @package Dixgital\Retractation
 */

require __DIR__ . '/bootstrap.php';

use Dixgital\Retractation\Declaration;
use Dixgital\Retractation\Eligibility;
use Dixgital\Retractation\Security;
use Dixgital\Retractation\Settings;
use Dixgital\Retractation\Submission;

$passed = 0;
$failed = array();

/**
 * Assertion élémentaire.
 *
 * @param string $label     Description.
 * @param mixed  $expected  Attendu.
 * @param mixed  $actual    Obtenu.
 * @return void
 */
function check( $label, $expected, $actual ) {
	global $passed, $failed;

	if ( $expected === $actual ) {
		++$passed;

		return;
	}

	$failed[] = sprintf(
		"%s\n    attendu : %s\n    obtenu  : %s",
		$label,
		var_export( $expected, true ),
		var_export( $actual, true )
	);
}

/**
 * Assertion « contient ».
 *
 * @param string $label    Description.
 * @param string $needle   Fragment attendu.
 * @param string $haystack Texte examiné.
 * @return void
 */
function check_contains( $label, $needle, $haystack ) {
	global $passed, $failed;

	if ( false !== strpos( (string) $haystack, (string) $needle ) ) {
		++$passed;

		return;
	}

	$failed[] = sprintf( "%s\n    fragment absent : %s", $label, $needle );
}

/**
 * Construit une commande de test à deux articles.
 *
 * @param int      $order_id  Identifiant.
 * @param int|null $completed Horodatage du passage à « Terminée ».
 * @return WC_Order
 */
function make_order( $order_id, $completed ) {
	$order = new WC_Order( $order_id, 'completed', time() - 30 * DAY_IN_SECONDS, $completed );

	$order->items = array(
		101 => new WC_Order_Item_Product( 'Chemise bleue', 2, 50.00, 10.00, new WC_Product( 501, 'CH-01' ), 501 ),
		102 => new WC_Order_Item_Product( 'Ceinture cuir', 1, 30.00, 6.00, new WC_Product( 502, 'CE-09' ), 502 ),
	);

	return $order;
}

// --- Délai de rétractation -------------------------------------------------.

$recent = make_order( 1001, time() - 10 * DAY_IN_SECONDS );
$old    = make_order( 1002, time() - 20 * DAY_IN_SECONDS );

check( 'Commande livrée il y a 10 jours : dans le délai', true, Eligibility::is_within_period( $recent ) );
check( 'Commande livrée il y a 20 jours : hors délai', false, Eligibility::is_within_period( $old ) );
check( 'Jours restants sur la commande récente', 4, Eligibility::days_left( $recent ) );

$GLOBALS['ret10g_options']['ret10g_period_grace'] = 10;
Settings::flush_cache();
check( 'Tolérance de 10 jours : la commande ancienne redevient éligible', true, Eligibility::is_within_period( $old ) );
unset( $GLOBALS['ret10g_options']['ret10g_period_grace'] );
Settings::flush_cache();

$no_completion = new WC_Order( 1003, 'processing', time() - 5 * DAY_IN_SECONDS, null );
check( 'Sans date de fin, le délai part de la date de commande', true, Eligibility::is_within_period( $no_completion ) );

// --- Articles disponibles --------------------------------------------------.

$available = Eligibility::available_items( $recent );

check( 'Deux articles disponibles', 2, count( $available ) );
check( 'Quantité maximale du premier article', 2, $available[101]['quantity_max'] );
check( 'Prix unitaire TTC du premier article', 30.0, $available[101]['unit_total'] );
check( 'Prix unitaire TTC du second article', 36.0, $available[102]['unit_total'] );
check( 'UGS reprise depuis le produit', 'CH-01', $available[101]['sku'] );

// --- Sélection ------------------------------------------------------------.

$build = new ReflectionMethod( Submission::class, 'build_selection' );

if ( PHP_VERSION_ID < 80100 ) {
	$build->setAccessible( true );
}

$full = $build->invoke( null, $recent, array( 101 => 2, 102 => 1 ) );
check( 'Sélection totale : portée complète', true, $full['is_full'] );
check( 'Sélection totale : montant', 96.0, $full['amount'] );

$partial = $build->invoke( null, $recent, array( 101 => 1 ) );
check( 'Sélection partielle : portée incomplète', false, $partial['is_full'] );
check( 'Sélection partielle : montant', 30.0, $partial['amount'] );
check( 'Sélection partielle : une seule ligne', 1, count( $partial['items'] ) );

$capped = $build->invoke( null, $recent, array( 101 => 99 ) );
check( 'Quantité plafonnée à la quantité commandée', 2, $capped['items'][0]['quantity'] );

$empty = $build->invoke( null, $recent, array( 101 => 0, 102 => 0 ) );
check( 'Sélection vide refusée', true, $empty instanceof WP_Error );

$unknown = $build->invoke( null, $recent, array( 999 => 3 ) );
check( 'Ligne inconnue ignorée', true, $unknown instanceof WP_Error );

// --- Contenu de la déclaration --------------------------------------------.

$declaration = new Declaration(
	array(
		'id'             => 7,
		'reference'      => 'RET-2026-000007',
		'order_number'   => '1001',
		'first_name'     => 'Jean',
		'last_name'      => 'Dupont',
		'contact_email'  => 'jean@example.test',
		'scope'          => 'full',
		'status'         => Declaration::STATUS_RECEIVED,
		'was_eligible'   => 1,
		'amount'         => 96.0,
		'currency'       => 'EUR',
		'created_at'     => gmdate( 'Y-m-d H:i:s' ),
		'created_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
	),
	$full['items']
);

$statement = Submission::build_statement(
	$declaration,
	array(
		'order'      => $recent,
		'reference'  => 'RET-2026-000007',
		'first_name' => 'Jean',
		'last_name'  => 'Dupont',
		'email'      => 'jean@example.test',
		'reason'     => 'Taille inadaptée',
		'items'      => $full['items'],
		'amount'     => 96.0,
		'is_full'    => true,
	)
);

check_contains( 'La déclaration porte la référence', 'RET-2026-000007', $statement );
check_contains( 'La déclaration porte le numéro de commande', '#1001', $statement );
check_contains( 'La déclaration liste les articles', '2 × Chemise bleue (CH-01)', $statement );
check_contains( 'La déclaration porte la formule de rétractation', 'Je notifie par la présente ma rétractation', $statement );
check_contains( 'La déclaration reprend le motif facultatif', 'Taille inadaptée', $statement );
check_contains( 'La déclaration nomme le professionnel', 'Boutique de test', $statement );

$late = new Declaration( array( 'was_eligible' => 0, 'created_at_gmt' => gmdate( 'Y-m-d H:i:s' ) ), array() );
$late_statement = Submission::build_statement(
	$late,
	array(
		'order'      => $old,
		'first_name' => 'Jean',
		'last_name'  => 'Dupont',
		'email'      => 'jean@example.test',
		'reason'     => '',
		'items'      => $partial['items'],
		'amount'     => 30.0,
		'is_full'    => false,
	)
);
check_contains( 'Une déclaration hors délai est signalée dans son contenu', 'après l\'expiration du délai', $late_statement );

// --- Jetons ---------------------------------------------------------------.

$token = Security::order_token( $recent );
check( 'Le jeton de commande se vérifie', true, Security::verify_order_token( $recent, $token ) );
check( 'Un jeton falsifié est rejeté', false, Security::verify_order_token( $recent, $token . 'x' ) );
check( 'Le jeton d\'une autre commande est rejeté', false, Security::verify_order_token( $old, $token ) );

$receipt = Security::receipt_token( 'RET-2026-000007' );
check( 'Le jeton d\'accusé se vérifie', true, Security::verify_receipt_token( 'RET-2026-000007', $receipt ) );
check( 'Le jeton d\'une autre référence est rejeté', false, Security::verify_receipt_token( 'RET-2026-000008', $receipt ) );

// --- Assainissement des réglages ------------------------------------------.

$schema = Settings::schema();

check( 'Case à cocher cochée', 'yes', Settings::sanitize( '1', $schema['display_footer'] ) );
check( 'Case à cocher décochée', 'no', Settings::sanitize( '', $schema['display_footer'] ) );
check( 'Délai borné au minimum', 1, Settings::sanitize( 0, $schema['period_days'] ) );
check( 'Délai borné au maximum', 365, Settings::sanitize( 9999, $schema['period_days'] ) );
check( 'Couleur invalide remplacée par la valeur par défaut', '#1f2937', Settings::sanitize( 'rouge', $schema['button_bg'] ) );
check( 'Couleur valide conservée', '#abcdef', Settings::sanitize( '#abcdef', $schema['button_bg'] ) );
check( 'Liste d\'identifiants normalisée', array( 12, 34, 56 ), Settings::sanitize( '12, 34,56, 12', $schema['excluded_products'] ) );
check( 'Choix de liste invalide replacé par défaut', 'completed', Settings::sanitize( 'jamais', $schema['period_start'] ) );
check( 'Statuts inconnus filtrés', array( 'processing' ), Settings::sanitize( array( 'processing', 'inexistant', 'cancelled' ), $schema['allowed_statuses'] ) );

// --- Résultat -------------------------------------------------------------.

echo sprintf( "%d assertions vérifiées.\n", $passed );

if ( $failed ) {
	echo sprintf( "\n%d ÉCHEC(S) :\n\n- %s\n", count( $failed ), implode( "\n\n- ", $failed ) );
	exit( 1 );
}

echo "Tous les tests passent.\n";

<?php
/**
 * Bancs d'essai sans WordPress.
 *
 * Ces tests couvrent la logique métier pure du plugin : calcul du délai,
 * sélection des articles, composition de la déclaration. Ils s'exécutent avec
 * `php tests/run.php`, sans dépendance externe.
 *
 * Les parcours nécessitant WordPress et WooCommerce (formulaire public,
 * envoi des e-mails, écrans d'administration) doivent être vérifiés sur une
 * installation réelle — voir le README.
 *
 * @package Dixgital\Retractation
 */

define( 'ABSPATH', __DIR__ . '/fake-wp/' );
define( 'RET10G_DIR', dirname( __DIR__ ) . '/' );
define( 'RET10G_URL', 'https://example.test/wp-content/plugins/10gital-retractation/' );
define( 'RET10G_VERSION', '1.0.0' );
define( 'RET10G_DB_VERSION', '1.0.0' );
define( 'RET10G_FILE', RET10G_DIR . '10gital-retractation.php' );
define( 'RET10G_BASENAME', '10gital-retractation/10gital-retractation.php' );
define( 'RET10G_SLUG', '10gital-retractation' );
define( 'RET10G_REPO', 'beewine/10gital-retractation' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'OBJECT', 'OBJECT' );

/** Options simulées. */
$GLOBALS['ret10g_options'] = array(
	'date_format'         => 'd/m/Y',
	'time_format'         => 'H:i',
	'blogname'            => 'Boutique de test',
	'ret10g_period_days'  => 14,
	'ret10g_period_start' => 'completed',
);

// --- Fonctions WordPress utilisées par le code testé ------------------------.

/** Catalogue de traduction simulé : texte source => traduction. */
$GLOBALS['ret10g_translations'] = array();

/** Filtres simulés : nom du filtre => fonction de rappel. */
$GLOBALS['ret10g_test_filters'] = array();

function __( $text, $domain = null ) { return $GLOBALS['ret10g_translations'][ $text ] ?? $text; }
function _x( $text, $context, $domain = null ) { return $text; }
function _n( $single, $plural, $number, $domain = null ) { return $number > 1 ? $plural : $single; }
function _n_noop( $single, $plural, $domain = null ) { return array( $single, $plural ); }
function esc_html__( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function apply_filters( $hook, $value, ...$args ) {
	return isset( $GLOBALS['ret10g_test_filters'][ $hook ] ) ? $GLOBALS['ret10g_test_filters'][ $hook ]( $value, ...$args ) : $value;
}
function do_action( ...$args ) {}
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['ret10g_options'] ) ? $GLOBALS['ret10g_options'][ $name ] : $default;
}
function update_option( $name, $value ) { $GLOBALS['ret10g_options'][ $name ] = $value; return true; }
function get_bloginfo( $key = 'name' ) { return get_option( 'blogname' ); }
function wp_specialchars_decode( $text, $quote_style = null ) { return html_entity_decode( (string) $text, ENT_QUOTES ); }
function wp_salt( $scheme = '' ) { return 'sel-de-test-' . $scheme; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ) { return filter_var( (string) $value, FILTER_SANITIZE_EMAIL ) ?: ''; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ); }
function sanitize_hex_color( $value ) { return preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? $value : null; }
function is_email( $value ) { return (bool) filter_var( (string) $value, FILTER_VALIDATE_EMAIL ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function current_time( $type, $gmt = false ) { return gmdate( 'Y-m-d H:i:s' ); }
function wp_date( $format, $timestamp = null ) { return gmdate( $format, $timestamp ?: time() ); }
function wp_strip_all_tags( $text ) { return strip_tags( (string) $text ); }
function wp_get_post_terms( ...$args ) { return array(); }
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wc_get_price_decimals() { return 2; }
function wc_price( $amount, $args = array() ) { return number_format( (float) $amount, 2, ',', ' ' ) . ' €'; }
function get_woocommerce_currency() { return 'EUR'; }
function wc_get_order( $id ) { return $GLOBALS['ret10g_orders'][ (int) $id ] ?? false; }
function wc_get_order_statuses() {
	return array(
		'wc-pending'    => 'En attente',
		'wc-processing' => 'En cours',
		'wc-on-hold'    => 'En attente de paiement',
		'wc-completed'  => 'Terminée',
		'wc-cancelled'  => 'Annulée',
		'wc-refunded'   => 'Remboursée',
		'wc-failed'     => 'Échouée',
	);
}

// --- Doublures des classes WooCommerce -------------------------------------.

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}

class WC_Product {
	public function __construct( private int $id, private string $sku = '', private bool $virtual = false, private int $parent = 0 ) {}
	public function get_id() { return $this->id; }
	public function get_parent_id() { return $this->parent; }
	public function get_sku() { return $this->sku; }
	public function is_virtual() { return $this->virtual; }
	public function is_downloadable() { return $this->virtual; }
}

class WC_Order_Item_Product {
	public function __construct(
		private string $name,
		private int $quantity,
		private float $total,
		private float $tax,
		private ?WC_Product $product = null,
		private int $product_id = 0
	) {}
	public function get_name() { return $this->name; }
	public function get_quantity() { return $this->quantity; }
	public function get_total() { return $this->total; }
	public function get_total_tax() { return $this->tax; }
	public function get_product() { return $this->product; }
	public function get_product_id() { return $this->product_id; }
	public function get_variation_id() { return 0; }
}

class WC_DateTime_Stub {
	public function __construct( private int $timestamp ) {}
	public function getTimestamp() { return $this->timestamp; }
}

class WC_Order {
	public array $items = array();
	public array $notes = array();
	public function __construct(
		private int $id,
		private string $status = 'completed',
		private ?int $created = null,
		private ?int $completed = null
	) {
		$this->created = $created ?? ( time() - 10 * DAY_IN_SECONDS );
		$GLOBALS['ret10g_orders'][ $id ] = $this;
	}
	public function get_id() { return $this->id; }
	public function get_order_number() { return (string) $this->id; }
	public function get_order_key() { return 'wc_order_test' . $this->id; }
	public function get_status() { return $this->status; }
	public function get_customer_id() { return 0; }
	public function get_currency() { return 'EUR'; }
	public function get_billing_email() { return 'client@example.test'; }
	public function get_billing_first_name() { return 'Jean'; }
	public function get_billing_last_name() { return 'Dupont'; }
	public function get_date_created() { return new WC_DateTime_Stub( $this->created ); }
	public function get_date_completed() { return $this->completed ? new WC_DateTime_Stub( $this->completed ) : null; }
	public function get_items() { return $this->items; }
	public function get_qty_refunded_for_item( $item_id ) { return 0; }
	public function add_order_note( $note ) { $this->notes[] = $note; }
	public function update_status( $status, $note = '' ) { $this->status = $status; $this->notes[] = $note; }
}

class WC_Geolocation {
	public static function get_ip_address() { return '203.0.113.7'; }
}

$GLOBALS['ret10g_orders'] = array();

require RET10G_DIR . 'includes/autoload.php';

/**
 * Doublure minimale de $wpdb : les tests ne couvrent pas la persistance.
 */
class WPDB_Stub {
	public $prefix = 'wp_';
	public $options = 'wp_options';
	public $insert_id = 1;
	public function get_charset_collate() { return ''; }
	public function prepare( $query, ...$args ) { return $query; }
	public function esc_like( $text ) { return $text; }
	public function get_results( ...$args ) { return array(); }
	public function get_row( ...$args ) { return null; }
	public function get_col( ...$args ) { return array(); }
	public function get_var( ...$args ) { return 0; }
	public function insert( ...$args ) { return 1; }
	public function update( ...$args ) { return 1; }
	public function delete( ...$args ) { return 1; }
}

$GLOBALS['wpdb'] = new WPDB_Stub();

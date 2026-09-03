<?php
/**
 * Plugin Name:          10gital Rétractation pour WooCommerce
 * Plugin URI:           https://github.com/beewine/10gital-retractation
 * Description:          Fonction de rétractation électronique conforme à la directive (UE) 2023/2673 et aux articles L.221-21 / D.221-5 du code de la consommation. Aucun traceur, aucune publicité, aucun service tiers.
 * Version:              1.0.1
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               10gital
 * Author URI:           https://10gital.fr
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          10gital-retractation
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      10.0
 *
 * @package Dixgital\Retractation
 */

defined( 'ABSPATH' ) || exit;

define( 'RET10G_VERSION', '1.0.1' );
define( 'RET10G_DB_VERSION', '1.0.0' );
define( 'RET10G_FILE', __FILE__ );
define( 'RET10G_DIR', plugin_dir_path( __FILE__ ) );
define( 'RET10G_URL', plugin_dir_url( __FILE__ ) );
define( 'RET10G_BASENAME', plugin_basename( __FILE__ ) );
define( 'RET10G_SLUG', '10gital-retractation' );
define( 'RET10G_REPO', 'beewine/10gital-retractation' );

require_once RET10G_DIR . 'includes/autoload.php';

register_activation_hook( __FILE__, array( 'Dixgital\\Retractation\\Core\\Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Dixgital\\Retractation\\Core\\Install', 'deactivate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( '10gital Rétractation nécessite WooCommerce actif pour fonctionner.', '10gital-retractation' )
					);
				}
			);

			return;
		}

		Dixgital\Retractation\Plugin::instance()->boot();
	}
);

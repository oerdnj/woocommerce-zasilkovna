<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Zásilkovna Shipping Method
 * Plugin URI:        https://github.com/oerdnj/wc-shipping-zasilkovna
 * Description:       Zásilkovna Shipping Method
 * Version:           0.1
 * Author:            Ondřej Surý
 * Author URI:        https://github.com/oerdnj/
 * Text Domain:       woocommerce-zasilkovna-shipping-method
 * License:           GPL-3+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/oerdnj/wc-shipping-zasilkovna
 */

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_filter('woocommerce_shipping_methods', 'add_zasilkovna_post_method');
    function add_zasilkovna_post_method( $methods ) {
        $methods['zasilkovna'] = 'WC_Zasilkovna_Shipping_Method';
        return $methods;
    }

    add_action('woocommerce_shipping_init', 'init_zasilkovna');
    function init_zasilkovna() {
        require 'class-zasilkovna.php';
    }
}

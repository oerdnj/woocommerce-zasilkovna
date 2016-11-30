<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Zásilkovna Shipping Method
 * Plugin URI:        https://github.com/oerdnj/woocommerce-zasilkovna
 * Description:       Zásilkovna Shipping Method
 * Version:           0.1
 * Author:            Ondřej Surý
 * Author URI:        https://github.com/oerdnj/
 * Text Domain:       woocommerce-zasilkovna
 * License:           GPL-3+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/oerdnj/woocommerce-zasilkovna
 */


/*
 * Helper functions
 */
function wc_zasilkovna_get_pickup_point( $pickup_point_id ) {
    if ( $pickup_point_id > 0 ) {
        $package = WC()->shipping->get_packages()[0];
        $shipping_methods = WC()->shipping->load_shipping_methods( $package );
        $chosen_method_id = wc_get_chosen_shipping_method_instance_ids()[0];
        $shipping_method = $shipping_methods[ $chosen_method_id ];
        $pickup_point = $shipping_method->pickup_point( $pickup_point_id );
        return $pickup_point;
    }
    return null;
}    

/**
 * Gets chosen shipping method instance IDs from chosen_shipping_methods session
 */
function wc_get_chosen_shipping_method_instance_ids() {
	$method_instance_ids     = array();
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
	foreach ( $chosen_methods as $chosen_method ) {
		$chosen_method = explode( ':', $chosen_method );
		$method_instance_ids[]  = next( $chosen_method );
	}
	return $method_instance_ids;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_filter( 'woocommerce_shipping_methods', 'add_zasilkovna_post_method' );
    function add_zasilkovna_post_method( $methods ) {
        $methods['zasilkovna'] = 'WC_Zasilkovna_Shipping_Method';
        return $methods;
    }

    add_action( 'woocommerce_shipping_init', 'wc_zasilkovna_shipping_method_init' );
    function wc_zasilkovna_shipping_method_init() {
        require 'class-zasilkovna.php';
    }
    
    add_action( 'woocommerce_review_order_after_shipping', 'wc_zasilkovna_review_order_after_shipping' );

    function wc_zasilkovna_review_order_after_shipping() {
    
        $package = WC()->shipping->get_packages()[0];

        $shipping_methods = WC()->shipping->load_shipping_methods( $package );

        if ( sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), array( 'zasilkovna') ) ) > 0 ) {
            $chosen_method_id = wc_get_chosen_shipping_method_instance_ids()[0];
            $shipping_country = WC()->customer->get_shipping_country();

            $zasilkovna_pickup_points = $shipping_methods[ $chosen_method_id ]->pickup_points( $shipping_country );
            $zasilkovna_chosen_pickup_point = WC()->session->get( 'zasilkovna_chosen_pickup_point' );
?>
            <tr class="wc-zasilkovna">
              <td><img src="<?php echo $settings['zasilkovna_logo']; ?>" width="160" border="0"></td>
              <td>
                <font size="2"><?php _e('Zásilkovna - choose a pickup point', 'woocommerce-zasilkovna-shipping-method'); ?></font>
                <div id="woocommerce-zasilkovna-branch-select-options">
                  <select name="zasilkovna_pickup_point">
                    <option><?php _e("Choose a pickup point", 'woocommerce-zasilkovna-shipping-method'); ?></option>
<?php
            foreach ( $zasilkovna_pickup_points as $pickup_point_id => $pickup_point ) {
                $selected = ( !empty( $zasilkovna_chosen_pickup_point ) && $zasilkovna_chosen_pickup_point === $pickup_point_id ) ? ' selected="selected"' : '';
                print( '<option value="' . $pickup_point_id . '"${selected}>' . $pickup_point->name . '</option>' );
            }
?>
                  </select>
                </div>
              </td>
            </tr>
<?php
        }
        return true;
    }
    
    add_action( 'woocommerce_add_shipping_order_item', 'wc_zasilkovna_save_pickup_point', 10, 2 );
    function wc_zasilkovna_save_pickup_point( $order_id, $item_id ) {
        if ( isset( $_POST["zasilkovna_pickup_point"] ) ) {
            if ( sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), array( 'zasilkovna') ) ) > 0 ) {
                $pickup_point_id = wc_clean( $_POST['zasilkovna_pickup_point'] );
                $pickup_point = wc_zasilkovna_get_pickup_point( $pickup_point_id );
                $pickup_point_name = wc_clean( $pickup_point->name ) ;
                wc_add_order_item_meta( $item_id, 'zasilkovna-pickup-point-id', $pickup_point_id , true );
                wc_add_order_item_meta( $item_id, 'zasilkovna-pickup-point-name', $pickup_point_name, true );
            }
        }
    }

    add_action( 'woocommerce_checkout_process', 'wc_zasilkovna_validate_pickup_point');
    function wc_zasilkovna_validate_pickup_point() {
        $shipping_method = current( explode( ':', $_POST["shipping_method"][0] ) );
        $pickup_point_id = intval( $_POST["zasilkovna_pickup_point"] );
        if ( $pickup_point_id <= 0 && $shipping_method == "zasilkovna" ) {
            wc_add_notice( __( "You have to pick a pickup point, when Zásilkovna is chosen.", 'woocommerce-zasilkovna-shipping-method' ), 'error' );
        }
    }

    add_action( 'woocommerce_admin_order_data_after_billing_address', 'woocommerce_zasilkovna_show_pickup_point' );
    add_action( 'woocommerce_email_after_order_table', 'woocommerce_zasilkovna_show_pickup_point' );
    add_action( 'woocommerce_order_details_after_order_table', 'woocommerce_zasilkovna_show_pickup_point' );
    function woocommerce_zasilkovna_show_pickup_point( $order ) {
        if ( $order->has_shipping_method( 'zasilkovna' ) ) {
            foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
                $pickup_point_name = $order->get_item_meta( $shipping_item_id, 'zasilkovna-pickup-point-name', true );
                print ( "<p><strong>Zásilkovna:</strong> " . $pickup_point_name . "</p>" );
            }
        }
    }
}

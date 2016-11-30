<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( "WC_ZASILKOVNA_TTL", 24 * 60 * 60 );

class WC_Zasilkovna_Pickup_Points_By_Country extends FilterIterator {
    private $country = '';
    private $pickup_points = array();
    
    public function __construct( $iterator, $shipping_country ) {
        switch ( $shipping_country ) {
        case 'CZ':
        case 'cz':
        case 'CZE':
            $this->country = 'cz';
            break;
        case 'sk':
        case 'SK':
        case 'SVK':
            $this->country = 'sk';
            break;
        default:
            throw new Exception( __METHOD__ . ": invalid country " . $shipping_country );
        }
        parent::__construct( $iterator );
    }

    public function accept() {
        $current = $this->getInnerIterator()->current();
        if ( $current->country === $this->country ) {
            return true;
        }
        return false;

    }
}

class WC_Zasilkovna_Shipping_Method extends WC_Shipping_Method {
    private $pickup_points = array();

    /**
     * Constructor. The instance ID is passed to this.
     */
    
    public function __construct( $instance_id = 0 ) {
        $this->id                  = 'zasilkovna';
        $this->instance_id         = absint( $instance_id );
        $this->method_title        = __( 'Zásilkovna', 'woocommerce-zasilkovna-shipping-method' );
        $this->method_description  = __( 'Zásilkovna shipping method.', 'woocommerce-zasilkovna-shipping-method' );

        $this->init_form_fields();
        $this->init_settings();
        
        $this->supports            = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        );

        $this->enabled             = $this->get_option( 'enabled' );
        $this->title               = $this->get_option( 'title' );

        $this->debug_mode          = $this->get_option( 'debug_mode' );
        
        $this->init_instance_settings();
      
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {

        $this->instance_form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'woocommerce' ),
                'type'		=> 'checkbox',
                'label'		=> __( 'Enable this shipping method', 'woocommerce' ),
                'default'		=> 'yes',
            ),
            'title' => array(
                'title'       => __( 'Method Title', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'     => __( 'Zásilkovna', 'woocommerce' ),
                'desc_tip'    => true
            ),
            'api_key' => array(
                'title'       => __( 'API key', 'woocommerce-zasilkovna-shipping-method' ),
                'type'        => 'text',
                'description' => __( 'Get your API key from Zásilkovna account settings.', 'woocommerce-zasilkovna-shipping-method' ),
                'default'     => '',
                'css'         => 'width: 100px;'
            ),
            'debug_mode' => array(
                'title'       => __( 'Enable Debug Mode', 'woocommerce' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable', 'woocommerce' ),
                'default'     => 'no',
                'description' => __('If debug mode is enabled, the shipping method will be activated just for the administrator.'),
            ),
            'logo'       => array(
                'title'       => 'Logo',
                'type'        => 'text',
                'description' => __( 'Default Zásilkovna logo' ),
                'default'     => '//www.zasilkovna.cz/images/page/Zasilkovna_logo_inverzni_WEB.png',
                'css'         => 'width: 500px;'
            ),
            'base_rate' => array(
                'title'       => __( 'Base rate', 'woocommerce' ),
                'type'        => 'price',
                'description' => __( 'Base delivery rate' ),
                'default'     => '',
                'css'         => 'width: 100px;',
                'placeholder' => wc_format_localized_price( 0 )
            ),
            'cod_rate' => array(
                'title'       => __( 'COD rate', 'woocommerce' ),
                'type'        => 'price',
                'description' => __( 'Collect on Delivery additional rate '),
                'default'     => '',
                'css'         => 'width: 100px;',
                'placeholder' => wc_format_localized_price( 0 )
            )
        );
    }

    public function is_available( $package ) {
        if ( $this->debug_mode === 'yes' ) {
            return current_user_can('administrator');
        }

        return true;
    }
    
    public function admin_options() {
?>
<?php if ( !function_exists( 'curl_version' ) ): ?>
        <div class="error">
          <p><?php _e( 'CURL module not found, fetching pickup points might fail.', 'woocommerce-zasilkovna-shipping-method' ); ?></p>
        </div>
<?php endif; ?>
<?php if ($this->enabled && !$this->get_option( 'api_key' )): ?>
        <div class="error">
          <p><?php _e( 'Zásilkovna is enabled, but the API key has not been set.', 'woocommerce-zasilkovna-shipping-method' ); ?></p>
        </div>
<?php endif; ?>
<?php if ($this->debug_mode == 'yes'): ?>
        <div class="updated woocommerce-message">
          <p><?php _e( 'Zásilkovna debug mode is activated, only administrators can use it.', 'woocommerce-zasilkovna-shipping-method' ); ?></p>
        </div>
<?php endif; ?>
<?php
        parent::admin_options();
    }
    
    public function calculate_shipping( $package = array() ) {
        $rate = array(
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $this->get_option( 'base_rate' ),
            'package' => $package
        );
        $this->add_rate( $rate );
    }

    public function pickup_point( $pickup_point_id ) {
        if ( sizeof( $this->pickup_points ) == 0 ) {
            $this->load_pickup_points();
        }
        if ( array_key_exists( $pickup_point_id, $this->pickup_points ) ) {
            return $this->pickup_points[ $pickup_point_id ];
        }
        return null;
    }
    
    public function pickup_points( $country ) {
        if ( sizeof( $this->pickup_points ) == 0 ) {
            $this->load_pickup_points();
        }
        
        $iterator = new ArrayIterator( $this->pickup_points );
        
        return new WC_Zasilkovna_Pickup_Points_By_Country( $iterator, $country );
    }
    
    function load_pickup_points() {
        $api_key = $this->get_option( 'api_key' );
        if ($api_key) {
            $transient_name = 'woocommerce_zasilkovna_pickup_points_' . $api_key;
            $pickup_points = get_transient( $transient_name );

            if ( empty($pickup_points) ) {
                $pickup_points = array();
                $json = $this->fetch_pickup_points( $api_key );
                foreach( $json as $id => $pickup_point ) {
                    $id = intval( $id );
                    $pickup_points[ $id ] = $pickup_point;
                }
                set_transient( $transient_name, $pickup_points, WC_ZASILKOVNA_TTL );
            }
            $this->pickup_points = $pickup_points;
        }
    }

    function fetch_pickup_points( $api_key ) {
        $url = 'https://www.zasilkovna.cz/api/v3/' . $api_key . '/branch.json';
        $result = wp_remote_get( $url );
        if ( is_wp_error( $result ) ) {
            throw new Exception( __METHOD__ . ": failed to get content from {$url}." );
        }
        $code = $result['response']['code'];
        if ( $code != 200) {
            throw new Exception( __METHOD__ . ": invalid response code from {$url}: ${code}." );
        }
        $response_body = wp_remote_retrieve_body( $result );
        if ( $response_body == '' ) {
            throw new Exception( __METHOD__ . ": empty response body." );
        }
        $json = json_decode( $response_body );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new Exception( __METHOD__ . ": JSON decode error: " . json_last_error() );
        }
        if ( sizeof( $json->data ) <= 0 ) {
            throw new Exception( __METHOD__ . ": JSON data empty." );
        }
        
        return $json->data;
    }    
}
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Zasilkovna_Shipping_Method extends WC_Shipping_Method {

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

        $this->enabled = $this->get_option( 'enabled' );
        $this->title   = $this->get_option( 'title' );

        $this->init_instance_settings();
      
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {

        $this->instance_form_field = array(
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
        <h3><?php _e( 'Zásilkovna Settings', 'woocommerce-zasilkovna-shipping-method' ); ?></h3>
        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
              <?php if($this->debug_mode == 'yes'): ?>
              <div class="updated woocommerce-message">
                <p><?php _e( 'Zásilkovna debug mode is activated, only administrators can use it.', 'woocommerce-zasilkovna-shipping-method' ); ?></p>
              </div>
              <?php endif; ?>
              <?php if(version_compare(WC()->version, '2.6.0', 'lt')): ?>
              <div class="error woocommerce-message">
                <p><?php _e( 'This version only supports WooCommerce 2.6+', 'woocommerce-zasilkovna-shipping-method' ); ?></p>
              </div>
              <?php endif; ?>
              <table class="form-table">
                <?php echo $this->get_admin_options_html(); ?>
              </table><!--/.form-table-->
            </div>
          </div>
        </div>
        <div class="clear"></div>
        <?php
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
}
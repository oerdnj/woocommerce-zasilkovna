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
            <div id="postbox-container-1" class="postbox-container">
              <div id="side-sortables" class="meta-box-sortables ui-sortable">
                <div class="postbox ">
                  <div class="handlediv" title="Click to toggle"><br></div>
                  <h3 class="hndle"><span><i class="dashicons dashicons-update"></i>&nbsp;&nbsp;Upgrade to Pro</span></h3>
                  <div class="inside">
                    <div class="support-widget">
                      <ul>
                        <li>» International Shipping</li>
                        <li>» Extra Domestic Options</li>
                        <li>» Prepaid Bags Support</li>
                        <li>» Dropshipping Support</li>
                        <li>» Handling Fees Support</li>
                        <li>» Auto Hassle-Free Updates</li>
                        <li>» High Priority Customer Support</li>
                      </ul>
                      <a href="https://wpruby.com/plugin/woocommerce-new-zealand-post-shipping-method-pro/" class="button wpruby_button" target="_blank"><span class="dashicons dashicons-star-filled"></span> Upgrade Now</a>
                    </div>
                  </div>
                </div>
                <div class="postbox ">
                  <div class="handlediv" title="Click to toggle"><br></div>
                  <h3 class="hndle"><span><i class="dashicons dashicons-editor-help"></i>&nbsp;&nbsp;Plugin Support</span></h3>
                  <div class="inside">
                    <div class="support-widget">
                      <p>
                        <img style="width: 70%;margin: 0 auto;position: relative;display: inherit;" src="https://wpruby.com/wp-content/uploads/2016/03/wpruby_logo_with_ruby_color-300x88.png">
                        <br/>
                        Got a Question, Idea, Problem or Praise?</p>
                      <ul>
                        <li>» <a target="_blank" href="https://www.nzpost.co.nz/tools/rate-finder/sending-nz">Weight and Size Guidlines </a>on Zásilkovna Post website.</li>
                        <li>» Please leave us a <a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/zasilkovna-woocommerce-shipping-method?filter=5">★★★★★</a> rating.</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="postbox rss-postbox">
                  <div class="handlediv" title="Click to toggle"><br></div>
                  <h3 class="hndle"><span><i class="fa fa-wordpress"></i>&nbsp;&nbsp;WPRuby Blog</span></h3>
                  <div class="inside">
                    <div class="rss-widget">
                    <?php
                      wp_widget_rss_output(array(
                        'url' => 'https://wpruby.com/feed/',
                        'title' => 'WPRuby Blog',
                        'items' => 3,
                        'show_summary' => 0,
                        'show_author' => 0,
                        'show_date' => 1,
                       ));
                    ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="clear"></div>
        <style type="text/css">
          .wpruby_button{
             background-color:#4CAF50 !important;
             border-color:#4CAF50 !important;
             color:#ffffff !important;
             width:100%;
             padding:5px !important;
             text-align:center;
             height:35px !important;
             font-size:12pt !important;
           }
        </style>
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
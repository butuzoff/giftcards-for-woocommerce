<?php
defined( 'ABSPATH' ) || exit;

/**
 */
add_action( 'woocommerce_shipping_init', function() {

    class WC_Shipping_Email_Delivery extends WC_Shipping_Method {

        /**
         */
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'email_delivery';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Electronic delivery', 'cgfwc' );
            $this->method_description = __( 'Email delivery for virtual gift cards.', 'cgfwc' );
            $this->supports           = [ 'shipping-zones', 'instance-settings' ];

            $this->init();
        }

        /**
         */
        public function init() {
            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option( 'enabled', 'yes' );
            $this->title   = $this->get_option( 'title', $this->method_title );
            $this->cost    = $this->get_option( 'cost', '0' );

            add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
        }

        /**
         */
        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => __( 'Enable method', 'cgfwc' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Electronic delivery', 'cgfwc' ),
                    'default' => 'yes',
                ],
                'title'   => [
                    'title'       => __( 'Title', 'cgfwc' ),
                    'type'        => 'text',
                    'description' => __( 'What the customer sees during checkout.', 'cgfwc' ),
                    'default'     => __( 'Electronic delivery', 'cgfwc' ),
                ],
                'cost'    => [
                    'title'       => __( 'Cost', 'cgfwc' ),
                    'type'        => 'text',
                    'description' => __( 'Delivery cost (0 for free).', 'cgfwc' ),
                    'default'     => '0',
                ],
            ];
        }

        /**
         */
        public function calculate_shipping( $package = [] ) {
            $rate = [
                'id'    => $this->id,
                'label' => $this->title,
                'cost'  => $this->cost,
            ];
            $this->add_rate( $rate );
        }
    }

} );

/**
 */
add_filter( 'woocommerce_shipping_methods', function( $methods ) {
    $methods['email_delivery'] = 'WC_Shipping_Email_Delivery';
    return $methods;
} );

/**
 */
add_filter( 'woocommerce_package_rates', function( $rates ) {
    if ( WC()->cart && cgfwc_cart_contains_only_giftcards() ) {
        foreach ( $rates as $key => $rate ) {
            if ( 'email_delivery' !== $rate->method_id ) {
                unset( $rates[ $key ] );
            }
        }
    }
    return $rates;
}, 20 );

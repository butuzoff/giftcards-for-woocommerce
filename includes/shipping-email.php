<?php
defined( 'ABSPATH' ) || exit;

/**
 * 1) Регистрируем «Email delivery» на этапе инициализации методов доставки
 */
add_action( 'woocommerce_shipping_init', function() {

    class WC_Shipping_Email_Delivery extends WC_Shipping_Method {

        /**
         * @param int $instance_id ID конкретной настройки в зоне
         */
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'email_delivery';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Electronic delivery', 'cgfwc' );
            $this->method_description = __( 'Email delivery for virtual gift cards.', 'cgfwc' );
            $this->supports           = [ 'shipping-zones', 'instance-settings' ];

            // Подгружаем настройки (API Settings)
            $this->init();
        }

        /**
         * Подключение API настроек
         */
        public function init() {
            // Определяем поля настройки
            $this->init_form_fields();
            // Загружаем сохранённые значения
            $this->init_settings();

            // Локальные переменные
            $this->enabled = $this->get_option( 'enabled', 'yes' );
            $this->title   = $this->get_option( 'title', $this->method_title );
            $this->cost    = $this->get_option( 'cost', '0' );

            // Сохраняем настройки при сохранении зоны
            add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
        }

        /**
         * Описание полей настроек в админке
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
         * Добавляем рейты в корзину
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
 * 2) Добавляем наш метод в общий список
 */
add_filter( 'woocommerce_shipping_methods', function( $methods ) {
    $methods['email_delivery'] = 'WC_Shipping_Email_Delivery';
    return $methods;
} );

/**
 * 3) Подменяем доступные методы доставки, когда в корзине только Gift Cards
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

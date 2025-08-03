<?php
defined( 'ABSPATH' ) || exit;

/**
 * Проверяет, является ли продукт (или его parent, если вариация) Gift Card
 */
function cgfwc_is_gift_product( $product_id ) {
    $product = wc_get_product( $product_id );
    if ( $product && $product->is_type( 'variation' ) ) {
        $product_id = $product->get_parent_id();
    }
    return get_post_meta( $product_id, '_cgfwc_is_gift_card', true ) === 'yes';
}

/**
 * Содержит ли корзина только Gift Cards?
 */
function cgfwc_cart_contains_only_giftcards() {
    if ( ! WC()->cart ) {
        return false;
    }
    $has = WC()->cart->get_cart();
    if ( empty( $has ) ) {
        return false;
    }
    foreach ( $has as $item ) {
        if ( ! cgfwc_is_gift_product( $item['product_id'] ) ) {
            return false;
        }
    }
    return true;
}

/**
 * 1) Блокируем «микс»
 */
add_action( 'init', function() {
    add_filter( 'woocommerce_add_to_cart_validation', function( $passed, $product_id ) {
        $is_gc      = cgfwc_is_gift_product( $product_id );
        $only_gc    = cgfwc_cart_contains_only_giftcards();
        $has_items  = WC()->cart && WC()->cart->get_cart();
        $has_gc     = $only_gc;
        $has_reg    = $has_items && ! $only_gc;

        if ( $is_gc && $has_reg ) {
            wc_add_notice( __( 'Gift cards must be purchased separately. Please clear your cart first.', 'cgfwc' ), 'error' );
            return false;
        }
        if ( ! $is_gc && $has_gc ) {
            wc_add_notice( __( 'Gift cards must be purchased separately. Please clear your cart first.', 'cgfwc' ), 'error' );
            return false;
        }
        return $passed;
    }, 10, 2 );
});

/**
 * 2) Минимальный набор полей на чекауте для GC
 */
add_filter( 'woocommerce_checkout_fields', function( $fields ) {
    if ( is_checkout() && cgfwc_cart_contains_only_giftcards() ) {
        $keep = [ 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone' ];
        foreach ( $fields['billing'] as $k => $f ) {
            if ( ! in_array( $k, $keep, true ) ) {
                unset( $fields['billing'][ $k ] );
            }
        }
        unset( $fields['shipping'] );
    }
    return $fields;
}, 20 );

/**
 * 3) Отключаем COD для GC
 */
add_filter( 'woocommerce_available_payment_gateways', function( $gateways ) {
    if ( cgfwc_cart_contains_only_giftcards() && isset( $gateways['cod'] ) ) {
        unset( $gateways['cod'] );
    }
    return $gateways;
}, 20 );

/**
 * 4) Говорим WooCommerce, что доставка нужна, даже если товары виртуальные
 */
add_filter( 'woocommerce_cart_needs_shipping', function( $needs ) {
    return cgfwc_cart_contains_only_giftcards() ? true : $needs;
}, 20 );

/**
 * 5) Принудительно выбираем email_delivery и сохраняем его в сессии
 */
add_action( 'woocommerce_before_checkout_form', function() {
    if ( ! cgfwc_cart_contains_only_giftcards() ) {
        return;
    }

    // Получаем рассчитанные тарифы
    $packages = WC()->shipping()->get_packages();
    $chosen   = [];

    foreach ( $packages as $index => $package ) {
        foreach ( $package['rates'] as $rate_id => $rate ) {
            if ( 'email_delivery' === $rate->method_id ) {
                $chosen[ $index ] = $rate_id;
                break 2;
            }
        }
    }

    if ( $chosen ) {
        WC()->session->set( 'chosen_shipping_methods', $chosen );
    }
}, 5 );

/**
 * 6) Убираем метод email_delivery, если в корзине есть хоть один обычный товар
 *    и показываем его только когда в корзине исключительно GC
 */
add_filter( 'woocommerce_package_rates', function( $rates, $package ) {

    // корзина содержит только GC?
    $only_gc = function_exists( 'cgfwc_cart_contains_only_giftcards' )
        ? cgfwc_cart_contains_only_giftcards()
        : false;

    // если только GC — оставляем email_delivery (остальные методы вырезаются выше)
    if ( $only_gc ) {
        return $rates;
    }

    // иначе — убираем email_delivery из списка тарифов
    foreach ( $rates as $rate_id => $rate ) {
        if ( isset( $rate->method_id ) && 'email_delivery' === $rate->method_id ) {
            unset( $rates[ $rate_id ] );
        }
    }

    return $rates;

}, 100, 2 );

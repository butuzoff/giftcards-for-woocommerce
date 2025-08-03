<?php
defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( ! $order_id ) {
        return;
    }
    
    if ( ! is_numeric( $order_id ) || $order_id <= 0 ) {
        return;
    }
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    if ( $order->get_status() !== 'completed' ) {
        return;
    }
    
    $user_id = $order->get_user_id();
    
    if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
        return;
    }

    $logger = wc_get_logger();
    $logger->info( "Starting gift card generation for order {$order_id}", [
        'source' => 'giftcards',
        'order_id' => $order_id,
        'user_id' => $user_id,
        'order_status' => $order->get_status()
    ] );

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        
        if ( ! is_numeric( $product_id ) || $product_id <= 0 ) {
            continue;
        }
        
        if ( get_post_meta( $product_id, '_cgfwc_is_gift_card', true ) !== 'yes' ) {
            continue;
        }
        
        $qty = $item->get_quantity();
        if ( $qty <= 0 ) {
            continue;
        }
        
        $amount = floatval( $item->get_total() ) / max(1, $qty);
        
        if ( $amount <= 0 ) {
            $logger->error( "Invalid gift card amount: {$amount} for product {$product_id}", [
                'source' => 'giftcards',
                'order_id' => $order_id,
                'product_id' => $product_id
            ] );
            continue;
        }

        for ( $i = 0; $i < $qty; $i++ ) {
            $attempts = 0;
            $max_attempts = 10;
            $code = '';
            
            do {
                $code = 'GC' . strtoupper( wp_generate_password( 8, false, false ) );
                $attempts++;
                
                $existing_card = get_posts([
                    'post_type' => 'gift_card',
                    'meta_key' => '_cgfwc_giftcard_code',
                    'meta_value' => $code,
                    'numberposts' => 1
                ]);
                
            } while ( ! empty( $existing_card ) && $attempts < $max_attempts );
            
            if ( $attempts >= $max_attempts ) {
                $logger->error( "Failed to generate unique gift card code after {$max_attempts} attempts", [
                    'source' => 'giftcards',
                    'order_id' => $order_id,
                    'product_id' => $product_id
                ] );
                continue;
            }
            
            $expiry = date( 'Y-m-d', strtotime( '+1 year' ) );

            $gc_id = wp_insert_post([
                'post_type'   => 'gift_card',
                'post_title'  => $code,
                'post_status' => 'publish',
                'post_author' => $user_id,
            ]);

            if ( ! is_wp_error( $gc_id ) ) {
                update_post_meta( $gc_id, '_cgfwc_giftcard_owner', $user_id );
                update_post_meta( $gc_id, '_cgfwc_giftcard_code',  $code );
                update_post_meta( $gc_id, '_cgfwc_balance',       $amount );
                update_post_meta( $gc_id, '_cgfwc_expiry_date',   $expiry );
                update_post_meta( $gc_id, '_cgfwc_pdf_url',       '' );
                update_post_meta( $gc_id, '_cgfwc_order_id',      $order_id );
                update_post_meta( $gc_id, '_cgfwc_created_date',  current_time( 'mysql' ) );
                update_post_meta( $gc_id, '_cgfwc_product_name', get_the_title( $product_id ) );
                
                $logger->info( "Gift card created successfully", [
                    'source' => 'giftcards',
                    'gift_card_id' => $gc_id,
                    'code' => $code,
                    'amount' => $amount,
                    'order_id' => $order_id,
                    'user_id' => $user_id
                ] );
            } else {
                $logger->error( "Failed to create gift card: " . $gc_id->get_error_message(), [
                    'source' => 'giftcards',
                    'order_id' => $order_id,
                    'code' => $code,
                    'amount' => $amount
                ] );
            }
        }
    }
    
    $logger->info( "Gift card generation completed for order {$order_id}", [
        'source' => 'giftcards',
        'order_id' => $order_id
    ] );
}, 10, 1 );
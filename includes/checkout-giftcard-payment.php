<?php
defined( 'ABSPATH' ) || exit;

/**
 */
add_action( 'woocommerce_before_order_notes', function( $checkout ) {
    return;
}, 1 );

 

/**
 */
add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $giftcards_processed = get_post_meta( $order_id, '_giftcards_processed', true );
    if ( $giftcards_processed ) {
    }

    $applied_code = WC()->session->get( 'giftcard_code', '' );
    $applied_amount = WC()->session->get( 'giftcard_amount', 0 );
    
    if ( ! $applied_code || $applied_amount <= 0 ) {
        return;
    }

    if ( ! cgfwc_cart_has_regular_product() ) {
        return;
    }

    $code   = wc_clean( $applied_code );
    $amount = floatval( $applied_amount );

    $posts = get_posts([
        'post_type'   => 'gift_card',
        'meta_key'    => '_cgfwc_giftcard_code',
        'meta_value'  => $code,
        'numberposts' => 1,
    ]);
    
    if ( $posts ) {
        $gc_id = $posts[0]->ID;
        
        global $wpdb;
        
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            $current_balance = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE post_id = %d AND meta_key = '_cgfwc_balance' 
                 FOR UPDATE",
                $gc_id
            ) );
            
            if ( $current_balance === null ) {
                throw new Exception( "Gift card balance not found" );
            }
            
            $current_balance = floatval( $current_balance );
            
            if ( $current_balance < $amount ) {
                $logger = wc_get_logger();
                $logger->error( "Insufficient balance for gift card {$code}. Required: {$amount}, Available: {$current_balance}", [
                    'source' => 'giftcards',
                    'order_id' => $order_id,
                    'user_id' => get_current_user_id()
                ] );
                
                $wpdb->query( 'ROLLBACK' );
                return;
            }
            
            $new_balance = max( 0, $current_balance - $amount );
            
            $update_result = $wpdb->update(
                $wpdb->postmeta,
                array( 'meta_value' => $new_balance ),
                array( 
                    'post_id' => $gc_id,
                    'meta_key' => '_cgfwc_balance'
                ),
                array( '%f' ),
                array( '%d', '%s' )
            );
            
            if ( $update_result === false ) {
                throw new Exception( "Failed to update gift card balance" );
            }
            
            $updated_balance = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE post_id = %d AND meta_key = '_cgfwc_balance'",
                $gc_id
            ) );
            
            if ( floatval( $updated_balance ) !== $new_balance ) {
                throw new Exception( "Balance update verification failed" );
            }
            
            $wpdb->query( 'COMMIT' );
            
        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            
            $logger = wc_get_logger();
            $logger->error( "Gift card balance update failed: " . $e->getMessage(), [
                'source' => 'giftcards',
                'order_id' => $order_id,
                'gift_card_id' => $gc_id,
                'code' => $code,
                'amount' => $amount
            ] );
            
            return;
        }

        $usage_entry = sprintf(
            '%s: -%s via order #%d (Partial use: %s remaining)',
            current_time( 'mysql' ),
            wc_price( $amount ),
            $order_id,
            wc_price( $new_balance )
        );
        add_post_meta( $gc_id, '_cgfwc_usage', $usage_entry );
        
        update_post_meta( $gc_id, '_cgfwc_last_order', $order_id );
        
        $logger = wc_get_logger();
        $logger->info( "Gift card {$code} balance updated for order {$order_id}", [
            'source' => 'giftcards',
            'amount_used' => $amount,
            'balance_before' => $current_balance,
            'balance_after' => $new_balance,
            'is_partial' => $new_balance > 0
        ] );
        
        if ( $new_balance <= 0 ) {
            update_post_meta( $gc_id, '_cgfwc_status', 'used' );
            $logger->info( "Gift card {$code} marked as fully used", [
                'source' => 'giftcards',
                'order_id' => $order_id
            ] );
        }

        update_post_meta( $order_id, '_giftcards_processed', 'yes' );
    }

    WC()->session->__unset( 'giftcard_code' );
    WC()->session->__unset( 'giftcard_amount' );
}, 10, 1 );

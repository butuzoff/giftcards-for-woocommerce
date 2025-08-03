<?php
defined( 'ABSPATH' ) || exit;

/**
 * 1) Больше не выводим форму на checkout,
 *    т.к. gift-карта применяется в корзине и хранится в сессии.
 *    Просто проверяем, что в корзине обычные товары, иначе пропускаем.
 */
add_action( 'woocommerce_before_order_notes', function( $checkout ) {
    // ничего не делаем
    return;
}, 1 );

 

/**
 * После создания заказа уменьшаем баланс карты
 * Обновлено для поддержки частичного использования
 */
add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    // Проверяем, не обрабатывались ли уже подарочные карты для этого заказа
    $giftcards_processed = get_post_meta( $order_id, '_giftcards_processed', true );
    if ( $giftcards_processed ) {
        return; // Уже обработано
    }

    // Получаем данные о примененной карте из сессии
    $applied_code = WC()->session->get( 'giftcard_code', '' );
    $applied_amount = WC()->session->get( 'giftcard_amount', 0 );
    
    if ( ! $applied_code || $applied_amount <= 0 ) {
        return;
    }

    // Только если в корзине были обычные товары
    if ( ! cgfwc_cart_has_regular_product() ) {
        return;
    }

    $code   = wc_clean( $applied_code );
    $amount = floatval( $applied_amount );

    // Получаем карту из базы данных
    $posts = get_posts([
        'post_type'   => 'gift_card',
        'meta_key'    => '_cgfwc_giftcard_code',
        'meta_value'  => $code,
        'numberposts' => 1,
    ]);
    
    if ( $posts ) {
        $gc_id = $posts[0]->ID;
        
        // Используем транзакцию для предотвращения race conditions
        global $wpdb;
        
        // Начинаем транзакцию
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            // Блокируем запись для чтения с блокировкой
            $current_balance = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE post_id = %d AND meta_key = '_cgfwc_balance' 
                 FOR UPDATE",
                $gc_id
            ) );
            
            // Валидируем баланс
            if ( $current_balance === null ) {
                throw new Exception( "Gift card balance not found" );
            }
            
            $current_balance = floatval( $current_balance );
            
            // Проверяем, что баланс достаточен для списания
            if ( $current_balance < $amount ) {
                // Логируем ошибку
                $logger = wc_get_logger();
                $logger->error( "Insufficient balance for gift card {$code}. Required: {$amount}, Available: {$current_balance}", [
                    'source' => 'giftcards',
                    'order_id' => $order_id,
                    'user_id' => get_current_user_id()
                ] );
                
                $wpdb->query( 'ROLLBACK' );
                return;
            }
            
            // Вычисляем новый баланс
            $new_balance = max( 0, $current_balance - $amount );
            
            // Обновляем баланс карты с проверкой
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
            
            // Проверяем, что обновление прошло успешно
            $updated_balance = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE post_id = %d AND meta_key = '_cgfwc_balance'",
                $gc_id
            ) );
            
            if ( floatval( $updated_balance ) !== $new_balance ) {
                throw new Exception( "Balance update verification failed" );
            }
            
            // Подтверждаем транзакцию
            $wpdb->query( 'COMMIT' );
            
        } catch ( Exception $e ) {
            // Откатываем транзакцию в случае ошибки
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

        // Добавляем запись об использовании
        $usage_entry = sprintf(
            '%s: -%s via order #%d (Partial use: %s remaining)',
            current_time( 'mysql' ),
            wc_price( $amount ),
            $order_id,
            wc_price( $new_balance )
        );
        add_post_meta( $gc_id, '_cgfwc_usage', $usage_entry );
        
        // Обновляем информацию о последнем заказе
        update_post_meta( $gc_id, '_cgfwc_last_order', $order_id );
        
        // Логируем успешное списание
        $logger = wc_get_logger();
        $logger->info( "Gift card {$code} balance updated for order {$order_id}", [
            'source' => 'giftcards',
            'amount_used' => $amount,
            'balance_before' => $current_balance,
            'balance_after' => $new_balance,
            'is_partial' => $new_balance > 0
        ] );
        
        // Если карта полностью использована, помечаем её как неактивную
        if ( $new_balance <= 0 ) {
            update_post_meta( $gc_id, '_cgfwc_status', 'used' );
            $logger->info( "Gift card {$code} marked as fully used", [
                'source' => 'giftcards',
                'order_id' => $order_id
            ] );
        }

        // Отмечаем, что подарочные карты для этого заказа были обработаны
        update_post_meta( $order_id, '_giftcards_processed', 'yes' );
    }

    // Очищаем сессию, чтобы не списалось дважды
    WC()->session->__unset( 'giftcard_code' );
    WC()->session->__unset( 'giftcard_amount' );
}, 10, 1 );

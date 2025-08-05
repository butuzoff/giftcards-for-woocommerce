<?php
defined( 'ABSPATH' ) || exit;

/**
 */
if ( ! function_exists( 'cgfwc_cart_has_regular_product' ) ) {
    function cgfwc_cart_has_regular_product() {
        if ( ! WC()->cart ) {
            return false;
        }
        foreach ( WC()->cart->get_cart() as $item ) {
            if ( get_post_meta( $item['product_id'], '_cgfwc_is_gift_card', true ) !== 'yes' ) {
                return true;
            }
        }
        return false;
    }
}

/**
 */
add_action( 'woocommerce_cart_calculate_fees', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    $applied_code = WC()->session->get( 'giftcard_code', '' );
    $applied_amount = WC()->session->get( 'giftcard_amount', 0 );

    if ( $applied_code && $applied_amount > 0 ) {
        $card_post = cgfwc_get_gift_card_by_code( $applied_code );
        if ( $card_post ) {
            $card_status = cgfwc_get_gift_card_status( $card_post );
            if ( $card_status['valid'] && $card_status['balance'] >= $applied_amount ) {
                $cart->add_fee( 
                    sprintf( __( 'Gift Card (%s)', 'cgfwc' ), $applied_code ), 
                    -$applied_amount, 
                    true, 
                    'standard' 
                );
            } else {
                WC()->session->__unset( 'giftcard_code' );
                WC()->session->__unset( 'giftcard_amount' );
                wc_add_notice( __( 'Gift card is no longer valid and has been removed.', 'cgfwc' ), 'error' );
            }
        }
    }
});

/**
 */
function cgfwc_get_gift_card_by_code( $code ) {
    $code = sanitize_text_field( strtoupper( $code ) );
    $posts = get_posts( [
        'post_type'   => 'gift_card',
        'meta_key'    => '_cgfwc_giftcard_code',
        'meta_value'  => $code,
        'numberposts' => 1,
    ] );
    return $posts ? $posts[0] : null;
}



/**
 */
function cgfwc_get_gift_card_status( $card_post ) {
    if ( ! $card_post ) {
        return [ 'valid' => false, 'message' => 'Card not found' ];
    }
    
    $balance = get_post_meta( $card_post->ID, '_cgfwc_balance', true );
    $status = get_post_meta( $card_post->ID, '_cgfwc_status', true );
    $expiry_date = get_post_meta( $card_post->ID, '_cgfwc_expiry_date', true );
    
    if ( ! is_numeric( $balance ) ) {
        $balance = 0.0;
    } else {
        $balance = floatval( $balance );
    }
    
    if ( $status === 'used' || $balance <= 0 ) {
        return [ 
            'valid' => false, 
            'message' => __( 'This gift card has been fully used.', 'cgfwc' ),
            'status' => 'used',
            'balance' => $balance
        ];
    }

    if ( $status === 'blocked' ) {
        return [ 
            'valid' => false, 
            'message' => __( 'This gift card has been blocked for security reasons.', 'cgfwc' ),
            'status' => 'blocked',
            'balance' => $balance
        ];
    }
    
    if ( ! empty( $expiry_date ) ) {
        $expiry_timestamp = strtotime( $expiry_date );
        if ( $expiry_timestamp === false ) {
            return [ 
                'valid' => false, 
                'message' => __( 'This gift card has an invalid expiry date.', 'cgfwc' ),
                'status' => 'invalid',
                'balance' => $balance
            ];
        }
        
        $current_time = time();
        if ( $expiry_timestamp < $current_time ) {
            return [ 
                'valid' => false, 
                'message' => sprintf( 
                    __( 'This gift card expired on %s.', 'cgfwc' ), 
                    date_i18n( get_option( 'date_format' ), $expiry_timestamp )
                ),
                'status' => 'expired',
                'balance' => $balance,
                'expiry_date' => $expiry_date
            ];
        }
        
        $days_until_expiry = floor( ( $expiry_timestamp - $current_time ) / DAY_IN_SECONDS );
        if ( $days_until_expiry <= 30 && $days_until_expiry > 0 ) {
            return [ 
                'valid' => true, 
                'message' => sprintf( 
                    __( 'Gift card is valid but expires in %d days.', 'cgfwc' ), 
                    $days_until_expiry 
                ),
                'status' => 'expiring_soon',
                'balance' => $balance,
                'expiry_date' => $expiry_date,
                'days_until_expiry' => $days_until_expiry
            ];
        }
    }
    
    return [ 
        'valid' => true, 
        'message' => __( 'Gift card is valid.', 'cgfwc' ),
        'status' => 'active',
        'balance' => $balance,
        'expiry_date' => $expiry_date
    ];
}

/**
 */
add_action( 'woocommerce_cart_coupon', function() {
    if ( ! cgfwc_cart_has_regular_product() ) {
        return;
    }
    
    $applied_code = WC()->session->get( 'giftcard_code', '' );
    $applied_amount = WC()->session->get( 'giftcard_amount', 0 );
    $card_info = null;
    
    if ( $applied_code ) {
        $card_post = cgfwc_get_gift_card_by_code( $applied_code );
        if ( $card_post ) {
            $balance = floatval( get_post_meta( $card_post->ID, '_cgfwc_balance', true ) );
            $card_info = [
                'balance' => $balance,
                'used' => $applied_amount,
                'remaining' => $balance - $applied_amount
            ];
        }
    }
        ?>
    <tr class="coupon giftcard-coupon">
        <td colspan="6" class="actions">
            <div class="woocommerce-giftcard">
                <h3><?php esc_html_e( 'Подарочная карта', 'cgfwc' ); ?></h3>
                <div class="coupon">
                    <input type="text" name="giftcard_code" class="input-text" id="giftcard_code"
                           value="<?php echo esc_attr( $applied_code ); ?>"
                           placeholder="<?php esc_attr_e( 'Код подарочной карты', 'cgfwc' ); ?>"
                           <?php echo $applied_code ? 'readonly' : ''; ?>>
                    
                    <?php if ( ! $applied_code ) : ?>
                        <input type="number" name="giftcard_amount" class="input-text" id="giftcard_amount"
                               value="<?php echo esc_attr( $applied_amount ); ?>"
                               placeholder="<?php esc_attr_e( 'Сумма к использованию', 'cgfwc' ); ?>"
                               step="0.01" min="0" max="<?php echo $card_info ? esc_attr( $card_info['balance'] ) : ''; ?>">
                    <?php endif; ?>
                    
                    <?php if ( $applied_code ) : ?>
                        <button type="button" class="button cgfwc-remove-btn" data-action="remove">
                            <span class="btn-text"><?php esc_html_e( 'Убрать подарочную карту', 'cgfwc' ); ?></span>
                            <span class="btn-loading" style="display: none;"><?php esc_html_e( 'Убираем...', 'cgfwc' ); ?></span>
                        </button>
                    <?php else : ?>
                        <button type="button" class="button cgfwc-apply-btn" data-action="apply">
                            <span class="btn-text"><?php esc_html_e( 'Применить подарочную карту', 'cgfwc' ); ?></span>
                            <span class="btn-loading" style="display: none;"><?php esc_html_e( 'Применяем...', 'cgfwc' ); ?></span>
                        </button>
                    <?php endif; ?>
                    
                    <?php wp_nonce_field( 'cgfwc_giftcard_action', 'cgfwc_nonce' ); ?>
                    
                    <?php if ( $card_info ) : ?>
                        <div class="giftcard-info">
                            <?php 
                            printf(
                                esc_html__( 'Использовано: %s | Остаток: %s', 'cgfwc' ),
                                wc_price( $card_info['used'] ),
                                wc_price( $card_info['remaining'] )
                            ); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="giftcard-messages" style="display: none;"></div>
            </div>
        </td>
    </tr>
    <?php
}, 9 );

/**
 * AJAX обработчики для подарочных карт
 */
add_action( 'wp_ajax_cgfwc_apply_giftcard', 'cgfwc_ajax_apply_giftcard' );
add_action( 'wp_ajax_nopriv_cgfwc_apply_giftcard', 'cgfwc_ajax_apply_giftcard' );
add_action( 'wp_ajax_cgfwc_remove_giftcard', 'cgfwc_ajax_remove_giftcard' );
add_action( 'wp_ajax_nopriv_cgfwc_remove_giftcard', 'cgfwc_ajax_remove_giftcard' );

/**
 * AJAX обработчик для применения подарочной карты
 */
function cgfwc_ajax_apply_giftcard() {
    check_ajax_referer( 'cgfwc_giftcard_action', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'Вы должны быть авторизованы для использования подарочных карт.', 'cgfwc' ) );
    }
    
    $code = isset( $_POST['code'] ) ? sanitize_text_field( trim( $_POST['code'] ) ) : '';
    $amount_raw = isset( $_POST['amount'] ) ? $_POST['amount'] : '';
    
    if ( empty( $code ) ) {
        wp_send_json_error( __( 'Пожалуйста, введите код подарочной карты.', 'cgfwc' ) );
    }
    
    if ( strlen( $code ) !== 10 ) {
        wp_send_json_error( __( 'Неверная длина кода подарочной карты.', 'cgfwc' ) );
    }
    
    if ( ! preg_match( '/^GC[A-Z0-9]{8}$/', strtoupper( $code ) ) ) {
        wp_send_json_error( __( 'Неверный формат кода подарочной карты.', 'cgfwc' ) );
    }
    
    if ( ! is_numeric( $amount_raw ) ) {
        wp_send_json_error( __( 'Пожалуйста, введите корректную сумму.', 'cgfwc' ) );
    }
    
    $amount = floatval( $amount_raw );
    
    if ( $amount <= 0 ) {
        wp_send_json_error( __( 'Пожалуйста, введите сумму больше нуля.', 'cgfwc' ) );
    }
    
    $min_amount = 1.00;
    if ( $amount < $min_amount ) {
        wp_send_json_error( sprintf( __( 'Минимальная сумма к использованию: %s.', 'cgfwc' ), wc_price( $min_amount ) ) );
    }
    
    $max_amount = 999999.99;
    if ( $amount > $max_amount ) {
        wp_send_json_error( __( 'Сумма слишком большая.', 'cgfwc' ) );
    }
    
    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        cgfwc_increment_failed_attempts( $code );
        wp_send_json_error( __( 'Подарочная карта не найдена.', 'cgfwc' ) );
    }
    
    if ( ! cgfwc_detect_suspicious_activity( $code ) ) {
        wp_send_json_error( __( 'Эта подарочная карта заблокирована по соображениям безопасности.', 'cgfwc' ) );
    }
    
    $card_status = cgfwc_get_gift_card_status( $card_post );
    if ( ! $card_status['valid'] ) {
        cgfwc_increment_failed_attempts( $code );
        wp_send_json_error( $card_status['message'] );
    }
    
    $balance = $card_status['balance'];
    
    if ( $amount > $balance ) {
        wp_send_json_error( sprintf( __( 'Сумма превышает доступный баланс (%s).', 'cgfwc' ), wc_price( $balance ) ) );
    }
    
    $cart_total = WC()->cart->get_total( 'edit' );
    if ( $amount > $cart_total ) {
        wp_send_json_error( sprintf( __( 'Сумма не может превышать общую стоимость корзины (%s).', 'cgfwc' ), wc_price( $cart_total ) ) );
    }
    
    if ( ! cgfwc_cart_has_regular_product() ) {
        wp_send_json_error( __( 'Подарочные карты можно использовать только с обычными товарами.', 'cgfwc' ) );
    }
    
    WC()->session->set( 'giftcard_code', strtoupper( $code ) );
    WC()->session->set( 'giftcard_amount', $amount );
    
    cgfwc_reset_failed_attempts( $code );
    
    $logger = wc_get_logger();
    $logger->info( "Gift card {$code} applied for amount {$amount}", [
        'source' => 'giftcards',
        'user_id' => get_current_user_id(),
        'user_ip' => cgfwc_get_user_ip(),
        'balance_before' => $balance,
        'balance_after' => $balance - $amount,
        'cart_total' => $cart_total,
        'timestamp' => current_time( 'mysql' )
    ] );
    
    WC()->cart->calculate_fees();
    
    wp_send_json_success( [
        'message' => sprintf( __( 'Подарочная карта успешно применена. Сумма: %s', 'cgfwc' ), wc_price( $amount ) ),
        'cart_total' => WC()->cart->get_total(),
        'giftcard_amount' => wc_price( $amount )
    ] );
}

/**
 * AJAX обработчик для удаления подарочной карты
 */
function cgfwc_ajax_remove_giftcard() {
    check_ajax_referer( 'cgfwc_giftcard_action', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'Вы должны быть авторизованы для управления подарочными картами.', 'cgfwc' ) );
    }
    
    $applied_code = WC()->session->get( 'giftcard_code', '' );
    $applied_amount = WC()->session->get( 'giftcard_amount', 0 );
    
    if ( $applied_code ) {
        $logger = wc_get_logger();
        $logger->info( "Gift card {$applied_code} removed from cart", [
            'source' => 'giftcards',
            'user_id' => get_current_user_id(),
            'user_ip' => cgfwc_get_user_ip(),
            'amount_removed' => $applied_amount,
            'timestamp' => current_time( 'mysql' )
        ] );
        
        WC()->session->__unset( 'giftcard_code' );
        WC()->session->__unset( 'giftcard_amount' );
        
        WC()->cart->calculate_fees();
        
        wp_send_json_success( [
            'message' => __( 'Подарочная карта убрана из корзины.', 'cgfwc' ),
            'cart_total' => WC()->cart->get_total()
        ] );
    } else {
        wp_send_json_error( __( 'Подарочная карта не была применена.', 'cgfwc' ) );
    }
}

/**
 * Legacy обработчик для совместимости
 */
add_action( 'woocommerce_cart_updated', function() {
    if ( ! empty( $_POST['apply_giftcard'] ) ) {
        cgfwc_apply_gift_card();
    }
    
    if ( ! empty( $_POST['remove_giftcard'] ) ) {
        cgfwc_remove_gift_card();
    }
});

/**
 */
function cgfwc_apply_gift_card() {
    if ( ! isset( $_POST['cgfwc_nonce'] ) || ! wp_verify_nonce( $_POST['cgfwc_nonce'], 'cgfwc_giftcard_action' ) ) {
        wc_add_notice( __( 'Security check failed. Please try again.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! is_user_logged_in() ) {
        wc_add_notice( __( 'You must be logged in to use gift cards.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! cgfwc_check_rate_limit() ) {
        wc_add_notice( __( 'Too many attempts. Please try again later.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! cgfwc_check_temporary_block() ) {
        wc_add_notice( __( 'Your IP is temporarily blocked due to suspicious activity.', 'cgfwc' ), 'error' );
        return;
    }

    $code = isset( $_POST['giftcard_code'] ) ? sanitize_text_field( trim( $_POST['giftcard_code'] ) ) : '';
    $amount_raw = isset( $_POST['giftcard_amount'] ) ? $_POST['giftcard_amount'] : '';

    if ( empty( $code ) ) {
        wc_add_notice( __( 'Please enter a gift card code.', 'cgfwc' ), 'error' );
        return;
    }

    if ( strlen( $code ) !== 10 ) {
        wc_add_notice( __( 'Invalid gift card code length.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! preg_match( '/^GC[A-Z0-9]{8}$/', strtoupper( $code ) ) ) {
        wc_add_notice( __( 'Invalid gift card code format.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! is_numeric( $amount_raw ) ) {
        wc_add_notice( __( 'Please enter a valid amount.', 'cgfwc' ), 'error' );
        return;
    }

    $amount = floatval( $amount_raw );
    
    if ( $amount <= 0 ) {
        wc_add_notice( __( 'Please enter an amount greater than zero.', 'cgfwc' ), 'error' );
        return;
    }

    $min_amount = 1.00;
    if ( $amount < $min_amount ) {
        wc_add_notice( sprintf( __( 'Minimum amount to use is %s.', 'cgfwc' ), wc_price( $min_amount ) ), 'error' );
        return;
    }

    $max_amount = 999999.99;
    if ( $amount > $max_amount ) {
        wc_add_notice( __( 'Amount is too large.', 'cgfwc' ), 'error' );
        return;
    }

    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        cgfwc_increment_failed_attempts( $code );
        wc_add_notice( __( 'Gift card not found.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! cgfwc_detect_suspicious_activity( $code ) ) {
        wc_add_notice( __( 'This gift card has been blocked for security reasons.', 'cgfwc' ), 'error' );
        return;
    }

    $card_status = cgfwc_get_gift_card_status( $card_post );
    if ( ! $card_status['valid'] ) {
        cgfwc_increment_failed_attempts( $code );
        wc_add_notice( $card_status['message'], 'error' );
        return;
    }

    $balance = $card_status['balance'];

    if ( $amount > $balance ) {
        wc_add_notice( sprintf( __( 'Amount exceeds available balance (%s).', 'cgfwc' ), wc_price( $balance ) ), 'error' );
        return;
    }

    $cart_total = WC()->cart->get_total( 'edit' );
    if ( $amount > $cart_total ) {
        wc_add_notice( sprintf( __( 'Amount cannot exceed cart total (%s).', 'cgfwc' ), wc_price( $cart_total ) ), 'error' );
        return;
    }

    if ( ! cgfwc_cart_has_regular_product() ) {
        wc_add_notice( __( 'Gift cards can only be used with regular products.', 'cgfwc' ), 'error' );
        return;
    }

    WC()->session->set( 'giftcard_code', strtoupper( $code ) );
    WC()->session->set( 'giftcard_amount', $amount );
    
    cgfwc_reset_failed_attempts( $code );
    
    $logger = wc_get_logger();
    $logger->info( "Gift card {$code} applied for amount {$amount}", [
        'source' => 'giftcards',
        'user_id' => get_current_user_id(),
        'user_ip' => cgfwc_get_user_ip(),
        'balance_before' => $balance,
        'balance_after' => $balance - $amount,
        'cart_total' => $cart_total,
        'timestamp' => current_time( 'mysql' )
    ] );

    wc_add_notice( sprintf( __( 'Gift card applied successfully. Amount: %s', 'cgfwc' ), wc_price( $amount ) ), 'success' );
    WC()->cart->calculate_fees();
}

/**
 */
function cgfwc_remove_gift_card() {
    if ( ! isset( $_POST['cgfwc_nonce'] ) || ! wp_verify_nonce( $_POST['cgfwc_nonce'], 'cgfwc_giftcard_action' ) ) {
        wc_add_notice( __( 'Security check failed. Please try again.', 'cgfwc' ), 'error' );
        return;
    }

    if ( ! is_user_logged_in() ) {
        wc_add_notice( __( 'You must be logged in to manage gift cards.', 'cgfwc' ), 'error' );
        return;
    }

    $applied_code = WC()->session->get( 'giftcard_code', '' );
    $applied_amount = WC()->session->get( 'giftcard_amount', 0 );
    
    if ( $applied_code ) {
        $logger = wc_get_logger();
        $logger->info( "Gift card {$applied_code} removed from cart", [
            'source' => 'giftcards',
            'user_id' => get_current_user_id(),
            'user_ip' => cgfwc_get_user_ip(),
            'amount_removed' => $applied_amount,
            'timestamp' => current_time( 'mysql' )
        ] );
        
        WC()->session->__unset( 'giftcard_code' );
        WC()->session->__unset( 'giftcard_amount' );
        
        wc_add_notice( __( 'Gift card removed from cart.', 'cgfwc' ), 'success' );
        WC()->cart->calculate_fees();
    }
}

/**
 */
add_action( 'wp_head', function() {
    if ( is_cart() ) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Обработчик для применения подарочной карты
            $(document).on('click', '.cgfwc-apply-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $form = $btn.closest('.woocommerce-giftcard');
                var $messages = $form.find('.giftcard-messages');
                
                // Получаем данные
                var code = $form.find('#giftcard_code').val().trim();
                var amount = $form.find('#giftcard_amount').val();
                var nonce = $form.find('#cgfwc_nonce').val();
                
                // Валидация
                if (!code) {
                    showMessage($messages, 'Пожалуйста, введите код подарочной карты.', 'error');
                    return;
                }
                
                if (!amount || amount <= 0) {
                    showMessage($messages, 'Пожалуйста, введите корректную сумму.', 'error');
                    return;
                }
                
                // Показываем загрузку
                setButtonLoading($btn, true);
                hideMessage($messages);
                
                // Отправляем AJAX запрос
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'cgfwc_apply_giftcard',
                        code: code,
                        amount: amount,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage($messages, response.data.message, 'success');
                            // Обновляем страницу для отображения изменений
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showMessage($messages, response.data, 'error');
                            setButtonLoading($btn, false);
                        }
                    },
                    error: function() {
                        showMessage($messages, 'Произошла ошибка. Попробуйте еще раз.', 'error');
                        setButtonLoading($btn, false);
                    }
                });
            });
            
            // Обработчик для удаления подарочной карты
            $(document).on('click', '.cgfwc-remove-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $form = $btn.closest('.woocommerce-giftcard');
                var $messages = $form.find('.giftcard-messages');
                var nonce = $form.find('#cgfwc_nonce').val();
                
                // Показываем загрузку
                setButtonLoading($btn, true);
                hideMessage($messages);
                
                // Отправляем AJAX запрос
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'cgfwc_remove_giftcard',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage($messages, response.data.message, 'success');
                            // Обновляем страницу для отображения изменений
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showMessage($messages, response.data, 'error');
                            setButtonLoading($btn, false);
                        }
                    },
                    error: function() {
                        showMessage($messages, 'Произошла ошибка. Попробуйте еще раз.', 'error');
                        setButtonLoading($btn, false);
                    }
                });
            });
            
            // Функция для показа сообщений
            function showMessage($container, message, type) {
                $container.html('<div class="giftcard-message giftcard-' + type + '">' + message + '</div>');
                $container.show();
            }
            
            // Функция для скрытия сообщений
            function hideMessage($container) {
                $container.hide();
            }
            
            // Функция для управления состоянием кнопки
            function setButtonLoading($btn, loading) {
                if (loading) {
                    $btn.prop('disabled', true);
                    $btn.find('.btn-text').hide();
                    $btn.find('.btn-loading').show();
                } else {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').show();
                    $btn.find('.btn-loading').hide();
                }
            }
        });
        </script>
        
        <style>
        .giftcard-coupon h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .giftcard-coupon .coupon {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 2;
        }
        
        .giftcard-coupon .coupon input[type="text"],
        .giftcard-coupon .coupon input[type="number"] {
            flex: 0 0 auto;
            width: 150px;
            margin-right: 10px;
        }
        
        .giftcard-coupon .coupon .button {
            margin-left: 5px;
        }
        
        .woocommerce-giftcard {
            position: relative;
            padding-bottom: 10px;
        }
        
        .giftcard-info {
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        /* Стили для сообщений */
        .giftcard-messages {
            margin-top: 15px;
            position: relative;
            z-index: 1;
        }
        
        .giftcard-message {
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .giftcard-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .giftcard-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Стили для кнопок с загрузкой */
        .cgfwc-apply-btn:disabled,
        .cgfwc-remove-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-loading {
            font-style: italic;
        }
        
        /* Анимация для кнопок */
        .cgfwc-apply-btn,
        .cgfwc-remove-btn {
            transition: all 0.3s ease;
        }
        
        .cgfwc-apply-btn:hover:not(:disabled),
        .cgfwc-remove-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .giftcard-coupon .coupon {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .giftcard-coupon .coupon input[type="text"],
            .giftcard-coupon .coupon input[type="number"] {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .giftcard-message {
                font-size: 13px;
                padding: 8px 12px;
            }
            
            .giftcard-messages {
                margin-top: 12px;
            }
        }
        </style>
        <?php
    }
});

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
add_action( 'woocommerce_before_cart_table', function() {
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
    <div class="razzi-giftcard-form-wrapper">
        <div class="razzi-giftcard-form">
            <h4 class="giftcard-title"><?php esc_html_e( 'Gift Card', 'cgfwc' ); ?></h4>
            <form method="post" class="giftcard-apply-form">
                <div class="giftcard-fields">
                    <div class="giftcard-field">
                        <label for="giftcard_code"><?php esc_html_e( 'Gift Card Code:', 'cgfwc' ); ?></label>
                        <input type="text" name="giftcard_code" class="input-text" id="giftcard_code"
                               value="<?php echo esc_attr( $applied_code ); ?>"
                               placeholder="<?php esc_attr_e( 'Enter gift card code', 'cgfwc' ); ?>"
                               <?php echo $applied_code ? 'readonly' : ''; ?>>
                    </div>
                    
                    <div class="giftcard-field">
                        <label for="giftcard_amount"><?php esc_html_e( 'Amount to use:', 'cgfwc' ); ?></label>
                        <input type="number" name="giftcard_amount" class="input-text" id="giftcard_amount"
                               value="<?php echo esc_attr( $applied_amount ); ?>"
                               placeholder="<?php esc_attr_e( 'Amount to use', 'cgfwc' ); ?>"
                               step="0.01" min="0" max="<?php echo $card_info ? esc_attr( $card_info['balance'] ) : ''; ?>">
                    </div>
                    
                    <div class="giftcard-actions">
                        <?php if ( $applied_code ) : ?>
                            <button type="submit" class="button btn razzi-btn-primary" name="remove_giftcard" value="1">
                                <?php esc_html_e( 'Remove', 'cgfwc' ); ?>
                            </button>
                        <?php else : ?>
                            <button type="submit" class="button btn razzi-btn-primary" name="apply_giftcard" value="1">
                                <?php esc_html_e( 'Apply gift card', 'cgfwc' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php wp_nonce_field( 'cgfwc_giftcard_action', 'cgfwc_nonce' ); ?>
                </div>
                
                <?php if ( $card_info ) : ?>
                    <div class="giftcard-info razzi-card-info">
                        <div class="info-content">
                            <?php 
                            printf(
                                esc_html__( 'Balance: %s | Used: %s | Remaining: %s', 'cgfwc' ),
                                wc_price( $card_info['balance'] ),
                                wc_price( $card_info['used'] ),
                                wc_price( $card_info['remaining'] )
                            ); 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php
}, 9 );

/**
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
        <style>
        .razzi-giftcard-form-wrapper {
            margin-bottom: 30px;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .razzi-giftcard-form .giftcard-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .giftcard-fields {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .giftcard-field {
            display: flex;
            flex-direction: column;
        }
        
        .giftcard-field label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .giftcard-field input.input-text {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: #fff;
        }
        
        .giftcard-field input.input-text:focus {
            border-color: #007cba;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,124,186,0.1);
        }
        
        .giftcard-actions button.razzi-btn-primary {
            background: #007cba;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: fit-content;
        }
        
        .giftcard-actions button.razzi-btn-primary:hover {
            background: #005a87;
            transform: translateY(-1px);
        }
        
        .razzi-card-info {
            margin-top: 20px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 6px;
            border-left: 4px solid #28a745;
        }
        
        .razzi-card-info .info-content {
            color: #495057;
            font-size: 14px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .razzi-giftcard-form-wrapper {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .giftcard-fields {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .giftcard-actions button.razzi-btn-primary {
                width: 100%;
                padding: 14px 24px;
            }
            
            .razzi-card-info {
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .razzi-giftcard-form-wrapper {
                padding: 15px;
            }
            
            .razzi-giftcard-form .giftcard-title {
                font-size: 16px;
                margin-bottom: 15px;
            }
        }
        </style>
        <?php
    }
});

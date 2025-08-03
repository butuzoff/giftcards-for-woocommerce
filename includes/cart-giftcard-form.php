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
add_action( 'woocommerce_before_cart_contents', function() {
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
    <tr class="giftcard-form">
        <td colspan="6" class="actions">
            <div class="coupon">
                <label for="giftcard_code"><?php esc_html_e( 'Gift Card:', 'cgfwc' ); ?></label>
                <div class="coupon-row">
                    <input type="text" name="giftcard_code" class="input-text" id="giftcard_code"
                           value="<?php echo esc_attr( $applied_code ); ?>"
                           placeholder="<?php esc_attr_e( 'Enter gift card code', 'cgfwc' ); ?>"
                           <?php echo $applied_code ? 'readonly' : ''; ?>>
                    
                    <input type="number" name="giftcard_amount" class="input-text" id="giftcard_amount"
                           value="<?php echo esc_attr( $applied_amount ); ?>"
                           placeholder="<?php esc_attr_e( 'Amount to use', 'cgfwc' ); ?>"
                           step="0.01" min="0" max="<?php echo $card_info ? esc_attr( $card_info['balance'] ) : ''; ?>">
                    
                    <?php if ( $applied_code ) : ?>
                        <button type="submit" class="button" name="remove_giftcard" value="1">
                            <?php esc_html_e( 'Remove', 'cgfwc' ); ?>
                        </button>
                    <?php else : ?>
                        <button type="submit" class="button" name="apply_giftcard" value="1">
                            <?php esc_html_e( 'Apply gift card', 'cgfwc' ); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php wp_nonce_field( 'cgfwc_giftcard_action', 'cgfwc_nonce' ); ?>
                </div>
                
                <?php if ( $card_info ) : ?>
                    <div class="giftcard-info">
                        <small>
                            <?php 
                            printf(
                                esc_html__( 'Balance: %s | Used: %s | Remaining: %s', 'cgfwc' ),
                                wc_price( $card_info['balance'] ),
                                wc_price( $card_info['used'] ),
                                wc_price( $card_info['remaining'] )
                            ); 
                            ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </td>
    </tr>
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
        .giftcard-form .coupon-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .giftcard-form .coupon-row input {
            flex: 1;
            min-width: 120px;
        }
        .giftcard-form .coupon-row button {
            white-space: nowrap;
        }
        .giftcard-info {
            margin-top: 8px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007cba;
        }
        .giftcard-info small {
            color: #6c757d;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .giftcard-form .coupon-row {
                flex-direction: column;
                align-items: stretch;
            }
            .giftcard-form .coupon-row input,
            .giftcard-form .coupon-row button {
                width: 100%;
            }
        }
        </style>
        <?php
    }
});

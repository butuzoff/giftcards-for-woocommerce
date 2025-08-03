<?php
defined( 'ABSPATH' ) || exit;

/**
 * Prevent brute force attacks by limiting gift card redemption attempts per IP
 * This helps protect against people trying to guess gift card codes
 */
function cgfwc_check_rate_limit( $ip = null ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    // Check how many attempts this IP has made in the last hour
    $attempts_key = "giftcard_attempts_{$ip}";
    $attempts = get_transient( $attempts_key );
    
    // Allow max 10 attempts per hour (can be filtered by other plugins)
    $max_attempts = apply_filters( 'cgfwc_max_attempts_per_hour', 10 );
    
    if ( $attempts && $attempts >= $max_attempts ) {
        return false;
    }
    
    // Increment the attempt counter
    $new_attempts = ( $attempts ?: 0 ) + 1;
    set_transient( $attempts_key, $new_attempts, HOUR_IN_SECONDS );
    
    return true;
}

/**
 * Check if an IP address is temporarily blocked from making gift card attempts
 */
function cgfwc_check_temporary_block( $ip = null ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    $blocked_until = get_transient( "giftcard_blocked_{$ip}" );
    if ( $blocked_until && $blocked_until > time() ) {
        return false; // This IP is currently blocked
    }
    
    return true;
}

/**
 * Temporarily block an IP address from making gift card attempts
 */
function cgfwc_block_ip_temporarily( $ip = null, $duration = 3600 ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    $block_until = time() + $duration;
    set_transient( "giftcard_blocked_{$ip}", $block_until, $duration );
    
    // Log the blocking action for admin review
    $logger = wc_get_logger();
    $logger->warning( "IP {$ip} temporarily blocked for gift card abuse", [
        'source' => 'giftcards',
        'ip' => $ip,
        'block_until' => date( 'Y-m-d H:i:s', $block_until ),
        'duration' => $duration
    ] );
}

/**
 * Get the user's real IP address, even behind proxies and load balancers
 */
function cgfwc_get_user_ip() {
    $ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
    foreach ( $ip_keys as $key ) {
        if ( array_key_exists( $key, $_SERVER ) === true ) {
            foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                $ip = trim( $ip );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                    return $ip;
                }
            }
        }
    }
    return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
}

/**
 * Detect suspicious activity for a specific gift card and block it if needed
 */
function cgfwc_detect_suspicious_activity( $code, $ip = null ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    // Find the gift card by its code
    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        return false;
    }
    
    $gc_id = $card_post->ID;
    
    // Check how many times someone has failed to use this card
    $failed_attempts = get_post_meta( $gc_id, '_failed_attempts', true ) ?: 0;
    
    // If there are too many failed attempts, block the card to prevent abuse
    if ( $failed_attempts > 5 ) {
        update_post_meta( $gc_id, '_cgfwc_status', 'blocked' );
        
        $logger = wc_get_logger();
        $logger->warning( "Gift card {$code} blocked due to suspicious activity", [
            'source' => 'giftcards',
            'code' => $code,
            'failed_attempts' => $failed_attempts,
            'ip' => $ip
        ] );
        
        return false;
    }
    
    return true;
}

/**
 * Keep track of failed attempts to use a gift card (helps detect fraud)
 */
function cgfwc_increment_failed_attempts( $code ) {
    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        return;
    }
    
    $gc_id = $card_post->ID;
    $failed_attempts = get_post_meta( $gc_id, '_failed_attempts', true ) ?: 0;
    $failed_attempts++;
    
    update_post_meta( $gc_id, '_failed_attempts', $failed_attempts );
    
    // Log the failed attempt so admins can review suspicious activity
    $logger = wc_get_logger();
    $logger->info( "Failed gift card attempt for code {$code}", [
        'source' => 'giftcards',
        'code' => $code,
        'failed_attempts' => $failed_attempts,
        'ip' => cgfwc_get_user_ip()
    ] );
}

/**
 * Reset failed attempts counter when a gift card is successfully used
 */
function cgfwc_reset_failed_attempts( $code ) {
    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        return;
    }
    
    $gc_id = $card_post->ID;
    delete_post_meta( $gc_id, '_failed_attempts' );
} 
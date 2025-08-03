<?php
defined( 'ABSPATH' ) || exit;

/**
 * Проверяет rate limiting для подарочных карт
 * Ограничивает количество попыток применения карт с одного IP
 */
function cgfwc_check_rate_limit( $ip = null ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    // Проверяем количество попыток за последний час
    $attempts_key = "giftcard_attempts_{$ip}";
    $attempts = get_transient( $attempts_key );
    
    // Максимум 10 попыток в час
    $max_attempts = apply_filters( 'cgfwc_max_attempts_per_hour', 10 );
    
    if ( $attempts && $attempts >= $max_attempts ) {
        return false;
    }
    
    // Увеличиваем счетчик попыток
    $new_attempts = ( $attempts ?: 0 ) + 1;
    set_transient( $attempts_key, $new_attempts, HOUR_IN_SECONDS );
    
    return true;
}

/**
 * Проверяет временную блокировку IP
 */
function cgfwc_check_temporary_block( $ip = null ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    $blocked_until = get_transient( "giftcard_blocked_{$ip}" );
    if ( $blocked_until && $blocked_until > time() ) {
        return false; // IP заблокирован
    }
    
    return true;
}

/**
 * Блокирует IP на определенное время
 */
function cgfwc_block_ip_temporarily( $ip = null, $duration = 3600 ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    $block_until = time() + $duration;
    set_transient( "giftcard_blocked_{$ip}", $block_until, $duration );
    
    // Логируем блокировку
    $logger = wc_get_logger();
    $logger->warning( "IP {$ip} temporarily blocked for gift card abuse", [
        'source' => 'giftcards',
        'ip' => $ip,
        'block_until' => date( 'Y-m-d H:i:s', $block_until ),
        'duration' => $duration
    ] );
}

/**
 * Получает IP адрес пользователя для логирования
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
 * Проверяет подозрительную активность для конкретной карты
 */
function cgfwc_detect_suspicious_activity( $code, $ip = null ) {
    if ( ! $ip ) {
        $ip = cgfwc_get_user_ip();
    }
    
    // Получаем карту по коду
    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        return false;
    }
    
    $gc_id = $card_post->ID;
    
    // Проверяем количество неудачных попыток
    $failed_attempts = get_post_meta( $gc_id, '_failed_attempts', true ) ?: 0;
    
    // Если больше 5 неудачных попыток - блокируем карту
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
 * Увеличивает счетчик неудачных попыток для карты
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
    
    // Логируем неудачную попытку
    $logger = wc_get_logger();
    $logger->info( "Failed gift card attempt for code {$code}", [
        'source' => 'giftcards',
        'code' => $code,
        'failed_attempts' => $failed_attempts,
        'ip' => cgfwc_get_user_ip()
    ] );
}

/**
 * Сбрасывает счетчик неудачных попыток при успешном использовании
 */
function cgfwc_reset_failed_attempts( $code ) {
    $card_post = cgfwc_get_gift_card_by_code( $code );
    if ( ! $card_post ) {
        return;
    }
    
    $gc_id = $card_post->ID;
    delete_post_meta( $gc_id, '_failed_attempts' );
} 
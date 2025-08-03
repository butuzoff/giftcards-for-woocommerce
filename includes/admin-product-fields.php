<?php
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_checkbox([
        'id'          => '_cgfwc_is_gift_card',
        'label'       => 'Gift Card Product?',
        'desc_tip'    => true,
        'description' => 'Отметьте, если это подарочная карта.'
    ]);
});

add_action('woocommerce_process_product_meta', function( $post_id ) {
    // Проверяем права доступа
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Проверяем nonce для безопасности
    if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
        return;
    }
    
    // Валидируем post_id
    if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
        return;
    }
    
    // Проверяем, что это действительно товар
    $post_type = get_post_type( $post_id );
    if ( $post_type !== 'product' ) {
        return;
    }
    
    // Сохраняем флаг у текущего продукта с валидацией
    $is_gift = isset( $_POST['_cgfwc_is_gift_card'] ) ? 'yes' : 'no';
    
    // Валидируем значение
    if ( ! in_array( $is_gift, array( 'yes', 'no' ), true ) ) {
        $is_gift = 'no';
    }
    
    update_post_meta( $post_id, '_cgfwc_is_gift_card', $is_gift );

    // Если это gift-карта, делаем её виртуальной и скрытой из каталога
    if ( 'yes' === $is_gift ) {
        update_post_meta( $post_id, '_virtual', 'yes' );
        update_post_meta( $post_id, '_visibility', 'hidden' );
        wp_set_object_terms( $post_id, 'exclude-from-catalog', 'product_visibility' );
        
        // Логируем создание gift card продукта
        $logger = wc_get_logger();
        $logger->info( "Gift card product created/updated", [
            'source' => 'giftcards',
            'product_id' => $post_id,
            'user_id' => get_current_user_id(),
            'action' => 'product_meta_update'
        ] );
    }

    // WPML: распространяем изменения на все языковые версии товара
    if ( function_exists( 'icl_get_languages' ) ) {
        // Получаем все активные языки (включая дефолтный)
        $langs = icl_get_languages( 'skip_missing=0' );
        foreach ( $langs as $lang_code => $lang ) {
            // Получаем ID перевода в этом языке
            $trans_id = apply_filters( 'wpml_object_id', $post_id, 'product', false, $lang_code );
            if ( $trans_id && $trans_id !== $post_id && is_numeric( $trans_id ) ) {
                // Копируем мета-флаг
                update_post_meta( $trans_id, '_cgfwc_is_gift_card', $is_gift );

                if ( 'yes' === $is_gift ) {
                    update_post_meta( $trans_id, '_virtual', 'yes' );
                    update_post_meta( $trans_id, '_visibility', 'hidden' );
                    wp_set_object_terms( $trans_id, 'exclude-from-catalog', 'product_visibility' );
                }
            }
        }
    }
});

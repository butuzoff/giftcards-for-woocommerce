<?php
// Add a simple checkbox to mark products as gift cards in the admin
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_checkbox([
        'id'          => '_cgfwc_is_gift_card',
        'label'       => 'Gift Card Product?',
        'desc_tip'    => true,
        'description' => 'Check this box if this product is a gift card.'
    ]);
});

// Save the gift card checkbox when products are updated
add_action('woocommerce_process_product_meta', function( $post_id ) {
    // Security first - make sure user has permission to edit this product
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Verify WordPress nonce for security
    if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
        return;
    }
    
    // Make sure we have a valid product ID
    if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
        return;
    }
    
    // Double-check this is actually a product
    $post_type = get_post_type( $post_id );
    if ( $post_type !== 'product' ) {
        return;
    }
    
    // Get the gift card flag from the form (defaults to 'no' if unchecked)
    $is_gift = isset( $_POST['_cgfwc_is_gift_card'] ) ? 'yes' : 'no';
    
    // Just to be extra safe, validate the value
    if ( ! in_array( $is_gift, array( 'yes', 'no' ), true ) ) {
        $is_gift = 'no';
    }
    
    update_post_meta( $post_id, '_cgfwc_is_gift_card', $is_gift );

    // If this is a gift card, set it up as virtual and hide from catalog
    if ( 'yes' === $is_gift ) {
        update_post_meta( $post_id, '_virtual', 'yes' );
        update_post_meta( $post_id, '_visibility', 'hidden' );
        wp_set_object_terms( $post_id, 'exclude-from-catalog', 'product_visibility' );
        
        // Keep a log of gift card products being created/updated
        $logger = wc_get_logger();
        $logger->info( "Gift card product created/updated", [
            'source' => 'giftcards',
            'product_id' => $post_id,
            'user_id' => get_current_user_id(),
            'action' => 'product_meta_update'
        ] );
    }

    // WPML support: sync gift card settings across all language versions
    if ( function_exists( 'icl_get_languages' ) ) {
        // Get all active languages (including default)
        $langs = icl_get_languages( 'skip_missing=0' );
        foreach ( $langs as $lang_code => $lang ) {
            // Find the translated product ID for this language
            $trans_id = apply_filters( 'wpml_object_id', $post_id, 'product', false, $lang_code );
            if ( $trans_id && $trans_id !== $post_id && is_numeric( $trans_id ) ) {
                // Copy the gift card flag to the translation
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

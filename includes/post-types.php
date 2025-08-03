<?php
defined( 'ABSPATH' ) || exit;

// Register a hidden post type to store gift card data
// We keep this private since gift cards are managed through the plugin interface
add_action( 'init', function() {
    register_post_type( 'gift_card', [
        'label'        => __( 'Gift Cards', 'cgfwc' ),
        'public'       => false,
        'show_ui'      => false,
        'supports'     => [],
    ] );
} );

<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function() {
    register_post_type( 'gift_card', [
        'label'        => __( 'Gift Cards', 'cgfwc' ),
        'public'       => false,
        'show_ui'      => false,
        'supports'     => [],
    ] );
} );

<?php
defined( 'ABSPATH' ) || exit;


add_action( 'init', function() {
    add_rewrite_endpoint( 'my-giftcards', EP_ROOT | EP_PAGES );
});

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'my-giftcards';
    return $vars;
});

add_filter( 'woocommerce_account_menu_items', function( $items ) {
    $items['my-giftcards'] = __( 'My Gift Cards', 'cgfwc' );
    return $items;
});

add_action( 'woocommerce_account_my-giftcards_endpoint', function() {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        echo '<p>' . esc_html__( 'You must be logged in to view your gift cards.', 'cgfwc' ) . '</p>';
        return;
    }


    if ( ! current_user_can( 'read' ) ) {
        echo '<p>' . esc_html__( 'You do not have permission to view gift cards.', 'cgfwc' ) . '</p>';
        return;
    }

    global $wpdb;
    $meta_table = $wpdb->postmeta;


    $giftcards = $wpdb->get_results( $wpdb->prepare(
        "SELECT post_id FROM {$meta_table}
         WHERE meta_key = '_cgfwc_giftcard_owner' 
         AND meta_value = %d 
         AND post_id IN (
             SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'gift_card' 
             AND post_status = 'publish'
         )",
        $user_id
    ) );

    if ( ! $giftcards ) {
        echo '<p>' . esc_html__( 'You have no gift cards.', 'cgfwc' ) . '</p>';
        return;
    }

    echo '<h2>' . esc_html__( 'Your Gift Cards', 'cgfwc' ) . '</h2>';
    echo '<table class="shop_table shop_table_responsive my_account_giftcards">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Name',     'cgfwc' ) . '</th>';
    echo '<th>' . esc_html__( 'Code',     'cgfwc' ) . '</th>';
    echo '<th>' . esc_html__( 'Balance',  'cgfwc' ) . '</th>';
    echo '<th>' . esc_html__( 'Status',   'cgfwc' ) . '</th>';
    echo '<th>' . esc_html__( 'Expiry',   'cgfwc' ) . '</th>';
    echo '<th>' . esc_html__( 'Download','cgfwc' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $giftcards as $row ) {
        $gc_id   = $row->post_id;
        $code    = get_post_meta( $gc_id, '_cgfwc_giftcard_code',    true );
        $balance = get_post_meta( $gc_id, '_cgfwc_balance',          true );
        $status  = get_post_meta( $gc_id, '_cgfwc_status',           true );
        $expiry  = get_post_meta( $gc_id, '_cgfwc_expiry_date',      true );
        $pdf     = get_post_meta( $gc_id, '_cgfwc_pdf_url',          true );
        

        $name    = get_post_meta( $gc_id, '_cgfwc_product_name',     true );
        if ( ! $name ) {
            $name = get_the_title( $gc_id );
        }


        $balance_float = floatval( $balance );
        $is_expired = $expiry && strtotime( $expiry ) < time();
        
        if ( $status === 'used' || $balance_float <= 0 ) {
            $status_text = '<span class="giftcard-status used">' . esc_html__( 'Used', 'cgfwc' ) . '</span>';
        } elseif ( $is_expired ) {
            $status_text = '<span class="giftcard-status expired">' . esc_html__( 'Expired', 'cgfwc' ) . '</span>';
        } elseif ( $balance_float > 0 ) {
            $status_text = '<span class="giftcard-status active">' . esc_html__( 'Active', 'cgfwc' ) . '</span>';
        } else {
            $status_text = '<span class="giftcard-status inactive">' . esc_html__( 'Inactive', 'cgfwc' ) . '</span>';
        }

        echo '<tr>';
        echo '<td data-title="' . esc_attr__( 'Name', 'cgfwc' ) . '">' . esc_html( $name ) . '</td>';
        echo '<td data-title="' . esc_attr__( 'Code', 'cgfwc' ) . '">' . esc_html( $code ) . '</td>';
        echo '<td data-title="' . esc_attr__( 'Balance', 'cgfwc' ) . '">' . wc_price( $balance_float ) . '</td>';
        echo '<td data-title="' . esc_attr__( 'Status', 'cgfwc' ) . '">' . $status_text . '</td>';
        echo '<td data-title="' . esc_attr__( 'Expiry', 'cgfwc' ) . '">' . esc_html( $expiry ) . '</td>';
        echo '<td data-title="' . esc_attr__( 'Download', 'cgfwc' ) . '">';
        if ( $pdf ) {
            echo '<a href="' . esc_url( $pdf ) . '" target="_blank" class="button">'
                 . esc_html__( 'Download PDF', 'cgfwc' ) . '</a>';
        } else {
            echo '&mdash;';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    

    echo '<style>
    .giftcard-status {
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    .giftcard-status.active {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .giftcard-status.used {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .giftcard-status.expired {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    .giftcard-status.inactive {
        background-color: #e2e3e5;
        color: #383d41;
        border: 1px solid #d6d8db;
    }
    </style>';
});

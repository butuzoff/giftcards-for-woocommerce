<?php
/* ------------------------------------------------------------------
 *  Шорткод [gift_card_products] 
 * -----------------------------------------------------------------*/
add_shortcode( 'gift_card_products', function () {

	$args  = [
		'post_type'      => 'product',
		'posts_per_page' => - 1,
		'meta_key'       => '_cgfwc_is_gift_card',
		'meta_value'     => 'yes',
	];
	$query = new WP_Query( $args );
	ob_start();

	if ( $query->have_posts() ) {
		echo '<div class="cgfwc-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			/** @var WC_Product $product */
			$product = wc_get_product( get_the_ID() );

			$pid    = $product->get_id();
			$thumb  = $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'cgfwc-thumb' ] );
			$price  = $product->get_price_html();
			$title  = $product->get_name();
			$link   = esc_url( add_query_arg( 'add-to-cart', $pid, wc_get_checkout_url() ) );

		$button_text = esc_html( $product->add_to_cart_text() );

        echo "<div class='cgfwc-card'>
        <div class='cgfwc-image'>{$thumb}</div>
        <h2 class='cgfwc-title'>{$title}</h2>
        <span class='cgfwc-price'>{$price}</span>
        <a href='{$link}' class='button alt cgfwc-buy'>{$button_text}</a>
        </div>";
		}
		echo '</div>';
		wp_reset_postdata();

		/* -- простые стили и адаптивная сетка -- */
		echo <<<CSS
		
<style>
.cgfwc-grid {
    display: grid;
    gap: 20px;
    /* По умолчанию — 4 колонки */
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

/* Большие планшеты и маленькие десктопы */
@media (max-width: 1024px) {
    .cgfwc-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

/* Мобильные — 2 колонки */
@media (max-width: 768px) {
    .cgfwc-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Экстра-маленькие экраны — 1 колонка */
@media (max-width: 480px) {
    .cgfwc-grid {
        grid-template-columns: 1fr;
    }
}

.cgfwc-card {
    padding: 15px;
 
    border: 1px solid transparent;
    transition: border .2s;
}
.cgfwc-card:hover {
    border-color: #ddd;
}
.cgfwc-thumb {
    pointer-events: none;
    max-width: 100%;
    height: auto;
}
.cgfwc-title {
    margin: 10px 0;
   
}
.cgfwc-price {
    display: block;
    margin-bottom: 10px;
}
</style>		
		
 
CSS;
	} else {
		echo '<p>'. esc_html__( 'No gift cards available at the moment.', 'cgfwc' ) .'</p>';
	}

	return ob_get_clean();
} );


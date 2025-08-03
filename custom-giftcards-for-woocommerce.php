<?php
/**
 * Plugin Name: Custom Giftcards for WooCommerce
 * Description: Gift card checkout, PDF download, balance tracking.
 * Version: 1.0.8
 * Author: FLANCER.EU
 */

defined( 'ABSPATH' ) || exit;


define( 'CGFWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CGFWC_VERSION', '1.0.8' );

/**
 * 1) Класс shipping-email (priority 0)
 */
add_action( 'plugins_loaded', function() {
    require_once CGFWC_PLUGIN_DIR . 'includes/shipping-email.php';
}, 0 );

/**
 * 2) Основные фильтры и функции для корзины и чекаута
 */
require_once CGFWC_PLUGIN_DIR . 'includes/checkout-filters.php';

/**
 * 3) Шорткоды, поля товаров, личный кабинет и т.д.
 */
require_once CGFWC_PLUGIN_DIR . 'includes/shortcodes.php';
require_once CGFWC_PLUGIN_DIR . 'includes/admin-product-fields.php';
require_once CGFWC_PLUGIN_DIR . 'includes/account-giftcards.php';
require_once CGFWC_PLUGIN_DIR . 'includes/checkout-giftcard-payment.php';
require_once CGFWC_PLUGIN_DIR . 'includes/post-types.php';
require_once CGFWC_PLUGIN_DIR . 'includes/generate-giftcards.php';

/**
 * 4) Форма в корзине – после того, как объявлены функции из checkout-filters.php
 */
require_once CGFWC_PLUGIN_DIR . 'includes/cart-giftcard-form.php';

/**
 * 5) GitHub обновления
 */
require_once CGFWC_PLUGIN_DIR . 'includes/github-updater.php';




add_action('admin_notices', function() {
    if (get_user_meta(get_current_user_id(), '_cgfwc_notice_dismissed', true)) {
        return;
    }
    echo '<div class="notice notice-success is-dismissible cgfwc-notice">
        <p><strong>Плагин Подарочных карт установлен и работает.</strong> Исключительные права принадлежат <a href="https://flancer.eu" target="_blank">Flancer.eu</a>. Разработан специально для <a href="https://lecharmie.com" target="_blank">lecharmie.com</a></p>
    </div>';
});

add_action('admin_footer', function () {
    ?>
    <script>
        jQuery(document).on('click', '.cgfwc-notice .notice-dismiss', function () {
            jQuery.post(ajaxurl, {
                action: 'cgfwc_dismiss_notice'
            });
        });
    </script>
    <?php
});

add_action('wp_ajax_cgfwc_dismiss_notice', function () {
    update_user_meta(get_current_user_id(), '_cgfwc_notice_dismissed', 1);
    wp_die();
});

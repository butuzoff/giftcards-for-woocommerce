<?php
/**
 * Plugin Name: Custom Giftcards for WooCommerce
 * Description: Gift card checkout, PDF download, balance tracking.
 * Version: 1.0.10
 * Author: FLANCER.EU
 */

defined( 'ABSPATH' ) || exit;


define( 'CGFWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CGFWC_VERSION', '1.0.10' );

/**
 * Load email shipping method early to ensure proper initialization
 */
add_action( 'plugins_loaded', function() {
    require_once CGFWC_PLUGIN_DIR . 'includes/shipping-email.php';
}, 0 );

/**
 * Core functionality: Cart and checkout filters and functions
 */
require_once CGFWC_PLUGIN_DIR . 'includes/checkout-filters.php';

/**
 * User interface components: Shortcodes, admin fields, and user account features
 */
require_once CGFWC_PLUGIN_DIR . 'includes/shortcodes.php';
require_once CGFWC_PLUGIN_DIR . 'includes/admin-product-fields.php';
require_once CGFWC_PLUGIN_DIR . 'includes/account-giftcards.php';
require_once CGFWC_PLUGIN_DIR . 'includes/checkout-giftcard-payment.php';
require_once CGFWC_PLUGIN_DIR . 'includes/post-types.php';
require_once CGFWC_PLUGIN_DIR . 'includes/generate-giftcards.php';

/**
 * Cart form functionality - loaded after checkout filters are defined
 */
require_once CGFWC_PLUGIN_DIR . 'includes/cart-giftcard-form.php';

/**
 * Security features: Rate limiting, fraud detection, and access control
 */
require_once CGFWC_PLUGIN_DIR . 'includes/security-functions.php';

/**
 * Auto-update system via GitHub releases
 */
require_once CGFWC_PLUGIN_DIR . 'includes/github-updater.php';




// Show a friendly welcome notice when the plugin is first activated
add_action('admin_notices', function() {
    if (get_user_meta(get_current_user_id(), '_cgfwc_notice_dismissed', true)) {
        return;
    }
    echo '<div class="notice notice-success is-dismissible cgfwc-notice">
        <p><strong>Gift Cards plugin is up and running!</strong> Developed by <a href="https://flancer.eu" target="_blank">Flancer.eu</a></p>
    </div>';
});

// Handle dismissal of admin notices with a simple AJAX call
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

// Save the user's preference to hide the welcome notice
add_action('wp_ajax_cgfwc_dismiss_notice', function () {
    update_user_meta(get_current_user_id(), '_cgfwc_notice_dismissed', 1);
    wp_die();
});

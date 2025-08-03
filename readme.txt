=== Custom Gift Cards for WooCommerce ===
Contributors: flancer
Tags: woocommerce, gift cards, payments, certificates
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.11-test
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive gift card solution for WooCommerce with balance tracking and secure payments.

== Description ==

**Custom Gift Cards for WooCommerce** is a comprehensive plugin for creating, managing, and tracking gift cards in WooCommerce. Perfect for businesses looking to offer digital gift certificates with complete balance management.

= Key Features =

* **Gift Card Products** - Create customizable gift card products with various denominations
* **Balance Tracking** - Complete balance management in user accounts
* **Partial Usage** - Allow customers to use gift cards across multiple purchases
* **Security First** - Built with nonce protection, data validation, and expiration checks
* **Mobile Responsive** - Works perfectly on all devices
* **WooCommerce Integration** - Seamless integration with cart and checkout processes
* **Email Notifications** - Automatic notifications
* **GitHub Updates** - Built-in update system for latest features

= How It Works =

1. **Admin Setup**: Create gift card products with custom denominations
2. **Customer Purchase**: Customers buy gift cards like any other product
3. **Easy Redemption**: Customers apply gift cards during checkout
4. **Balance Management**: Track remaining balances in user accounts

= Perfect For =

* E-commerce stores wanting to offer gift certificates
* Businesses looking to increase customer retention
* Stores needing professional-looking gift certificates

= Technical Requirements =

* WordPress 5.0 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

== Installation ==

= Automatic Installation =

1. Download the plugin ZIP file from the releases page
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Configure your gift card products

= Manual Installation =

1. Upload the plugin files to `/wp-content/plugins/giftcards-for-woocommerce/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your gift card settings

== Frequently Asked Questions ==

= How do I create a gift card product? =

1. Go to Products → Add New
2. Set the product type to "Gift Card" (checkbox option)
3. Configure the denomination, expiration, and other settings
4. Publish the product

= Can customers use gift cards partially? =

Yes! The plugin supports partial usage, allowing customers to use gift cards across multiple purchases until the balance is exhausted.

= Are the gift cards secure? =

Absolutely! All gift cards are protected with unique codes, expiration dates, and nonce validation to prevent unauthorized usage.

== Changelog ==

= 1.0.11-test =
* **Testing**: Reduced update check cache from 12 hours to 2 hours for faster update detection
* **Testing**: This is a test release to verify the update notification system

= 1.0.10 =
* **Critical Bug Fix**: Fixed Fatal Error in gift card generation caused by misplaced code execution
* **Code Improvement**: Removed all Russian language comments for better international compatibility
* **Code Quality**: Improved code organization and documentation

= 1.0.9 =
* **Security Enhancement**: Added comprehensive rate limiting system to prevent brute force attacks
* **Security Enhancement**: Implemented suspicious activity detection and automatic card blocking
* **Bug Fix**: Fixed double balance deduction issue in checkout process
* **Security Enhancement**: Added IP-based temporary blocking for abusive behavior
* **Security Enhancement**: Enhanced logging for all gift card operations
* **Security Enhancement**: Added failed attempts tracking per gift card
* **Code Improvement**: Centralized security functions in dedicated file
* **Code Improvement**: Removed duplicate functions and improved code organization

= 1.0.8 =
* Enhanced security with comprehensive nonce validation
* Improved gift card expiration management
* Added race condition protection for balance deductions
* Mobile-responsive design improvements
* Detailed operation logging
* WooCommerce 5.0+ compatibility improvements

== Support ==

* **Documentation**: [GitHub Wiki](https://github.com/butuzoff/giftcards-for-woocommerce/wiki)
* **Support**: [Flancer.eu](https://flancer.eu)
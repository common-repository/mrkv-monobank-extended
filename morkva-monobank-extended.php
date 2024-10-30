<?php
/**
 * Plugin Name: Morkva Plata by Mono Extended
 * Description: Краще ніж офіційний: інтернет-еквайринг, Checkout by mono.
 * Version: 1.0.3
 * Tested up to: 6.6
 * Requires at least: 5.2
 * Requires PHP: 7.1
 * WC tested up to: 8.8
 * Author: MORKVA
 * Author URI: https://morkva.co.ua
 * Text Domain: morkva-monobank-extended
 * Domain Path: /languages
 */

# This prevents a public user from directly accessing your .php files
if (! defined('ABSPATH')) 
{
    # Exit if accessed directly
    exit;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

# Include monopay to menu Wordpress
require_once plugin_dir_path(__FILE__) . 'includes/class-morkva-monobank-menu.php';

# Create page and show in menu
new MorkvaMonopayMenu();

# Define constant of plugin direction and path
define('MORKVAMONOGATEWAY_DIR', plugin_dir_path(__FILE__));
define('MORKVAMONOGATEWAY_PATH', plugin_dir_url(__FILE__));

# Add payment method to site
add_action( 'plugins_loaded', 'mrkv_mono_init_mono_gateway_class', 11 );
add_action( 'plugins_loaded', 'mrkv_mono_true_load_plugin_textdomain', 11 );
add_filter( 'woocommerce_payment_gateways', 'mrkv_mono_add_mono_gateway_class' );

/**
 * Load translate 
 * */
function mrkv_mono_true_load_plugin_textdomain() 
{
    # Get languages path
    $plugin_path = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
    # Load languages
    load_plugin_textdomain( 'morkva-monobank-extended', false, $plugin_path );
}

/**
 * Include gateway morkva monopay class
 * 
 * */
function mrkv_mono_init_mono_gateway_class() 
{
    # Require monopay class
    require_once MORKVAMONOGATEWAY_DIR . 'includes/class-wc-morkva-mono-gateway.php';

    # Require monopay payparts class
    require_once MORKVAMONOGATEWAY_DIR . 'includes/class-wc-morkva-mono-payparts.php'; 
}

/**
 * Add Morkva monopay Gateway to Woocommerce
 * @param array All exist methods
 * @return array All exist methods
 * 
 * */
function mrkv_mono_add_mono_gateway_class( $methods ) 
{
    # Include Morkva Monopay
    $methods[] = 'WC_Gateway_Morkva_Mono';

    # Include Morkva Monopay Payparts
    $methods[] = 'WC_Gateway_Morkva_Mono_Payparts';

    # Return all methods
    return $methods;
}

/**
 * Load all classes monopay connection
 * 
 * */
function mrkv_mono_loadMonoLibrary() 
{
    require_once MORKVAMONOGATEWAY_DIR . 'includes/classes/Morkva_Mono_Payment.php';
    require_once MORKVAMONOGATEWAY_DIR . 'includes/classes/Morkva_Mono_Order.php';
    require_once MORKVAMONOGATEWAY_DIR . 'includes/classes/Morkva_Mono_Response.php';
}

# Add filter block supports
add_action( 'woocommerce_blocks_loaded', 'morkva_mono_gateway_block_support' );

/**
 * Check woo blocks support
 * */
function morkva_mono_gateway_block_support()
{
    if ( !class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) 
    {
        return;
    }

    # Including Mono gateway blocks
    require_once MORKVAMONOGATEWAY_DIR . 'includes/blocks/class-wc-gateway-mono-blocks.php';

    # Registering the PHP class we have just included
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) 
        {
            # Register an instance of WC_Gateway_Morkva_Mono_Blocks
            $payment_method_registry->register( new WC_Gateway_Morkva_Mono_Blocks );
        }
    );
}

/**
 * Hook include checkout
 * */
add_action( 'init', 'mrkv_mono_session_start' );

function mrkv_mono_session_start(){
    if( !session_id()  && !headers_sent())
    {
        session_start(['read_and_close' => true]);
    }
}

/**
 * Hook include checkout
 * */
add_action( 'wp_loaded', 'mrkv_mono_include_checkout' );
/**
 * Function include checkout
 * */
function mrkv_mono_include_checkout()
{
    # Include monopay widget to woocommerce
    require_once plugin_dir_path(__FILE__) . 'includes/class-morkva-monobank-widget.php';

    # Create widget monopay checkout
    new MorkvaMonopayWidget();
    

    # Include monopay checkout callback to woocommerce
    require_once plugin_dir_path(__FILE__) . 'includes/class-morkva-mono-checkout-callback.php';

    # Create monopay checkout callback
    new MrkvMonoCheckoutCallback();

    require_once MORKVAMONOGATEWAY_DIR . 'includes/class-morkva-mono-orders.php';

    # Create widget monopay orders
    new MorkvaMonopayOrders();

     require_once MORKVAMONOGATEWAY_DIR . 'includes/class-morkva-monobank-shortcodes.php';

    # Create widget monopay shortcodes
    new MorkvaMonopayShortcodes();
}

# Include styles
add_action('admin_head', 'mrkv_mono_style_settings_checkout');
/**
 * Add styles to settings
 * */
function mrkv_mono_style_settings_checkout()
{
    # Check if payment settings
    if(isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'checkout' && isset($_GET['section']) 
        && ($_GET['section'] == 'morkva-monopay' || $_GET['section'] == 'morkva-monopay-payparts')){
        # Add styles
        wp_enqueue_style( 'monopay-setting-checkout-style', MORKVAMONOGATEWAY_PATH . 'assets/css/monopay-setting-style.css' );
    }
}

# Add all styles and scripts for widget
add_action('wp_enqueue_scripts', 'mrkv_mono_register_styles_func');

function mrkv_mono_register_styles_func()
{
    if(is_checkout())
    {
        # Add styles
        wp_enqueue_style( 'monopay-checkout', MORKVAMONOGATEWAY_PATH . 'assets/css/monopay-checkout.css' );
    }
}
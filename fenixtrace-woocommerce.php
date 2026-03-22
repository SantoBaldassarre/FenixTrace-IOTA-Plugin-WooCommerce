<?php
/**
 * Plugin Name: FenixTrace for WooCommerce
 * Plugin URI:  https://trace.fenixsoftwarelabs.com
 * Description: Register WooCommerce products on the IOTA L1 blockchain via the FenixTrace Integration Kit.
 * Version:     1.0.0
 * Author:      Fenix Software Labs
 * Author URI:  https://www.fenixsoftwarelabs.com
 * License:     GPL-2.0-or-later
 * Text Domain: fenixtrace-woocommerce
 * Requires Plugins: woocommerce
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FENIXTRACE_WC_VERSION', '1.0.0' );
define( 'FENIXTRACE_WC_FILE', __FILE__ );
define( 'FENIXTRACE_WC_DIR', plugin_dir_path( __FILE__ ) );
define( 'FENIXTRACE_WC_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active before loading.
 */
function fenixtrace_wc_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="error"><p><strong>FenixTrace for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Initialize the plugin.
 */
function fenixtrace_wc_init() {
    if ( ! fenixtrace_wc_check_woocommerce() ) {
        return;
    }

    require_once FENIXTRACE_WC_DIR . 'includes/class-fenixtrace-api.php';
    require_once FENIXTRACE_WC_DIR . 'includes/class-fenixtrace-settings.php';
    require_once FENIXTRACE_WC_DIR . 'includes/class-fenixtrace-product.php';

    FenixTrace_Settings::init();
    FenixTrace_Product::init();
}
add_action( 'plugins_loaded', 'fenixtrace_wc_init' );

/**
 * Enqueue admin styles.
 */
function fenixtrace_wc_admin_styles( $hook ) {
    $screen = get_current_screen();
    if ( $screen && ( $screen->id === 'product' || strpos( $hook, 'fenixtrace' ) !== false || $screen->id === 'edit-product' ) ) {
        wp_enqueue_style( 'fenixtrace-admin', FENIXTRACE_WC_URL . 'assets/css/fenixtrace-admin.css', array(), FENIXTRACE_WC_VERSION );
    }
}
add_action( 'admin_enqueue_scripts', 'fenixtrace_wc_admin_styles' );

/**
 * Add settings link on plugin page.
 */
function fenixtrace_wc_settings_link( $links ) {
    $url = admin_url( 'admin.php?page=fenixtrace-settings' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'fenixtrace_wc_settings_link' );

/**
 * Activation hook — set defaults.
 */
function fenixtrace_wc_activate() {
    if ( ! get_option( 'fenixtrace_kit_url' ) ) {
        update_option( 'fenixtrace_kit_url', 'http://localhost:3005' );
    }
    if ( ! get_option( 'fenixtrace_template' ) ) {
        update_option( 'fenixtrace_template', 'generic' );
    }
}
register_activation_hook( __FILE__, 'fenixtrace_wc_activate' );

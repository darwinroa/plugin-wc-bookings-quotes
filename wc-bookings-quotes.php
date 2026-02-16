<?php
/**
 * Plugin Name: WooCommerce Bookings – Quotes
 * Description: Sistema de cotización con pre-reservas para WooCommerce Bookings.
 * Version:     1.1.0
 * Author:      Darwin Roa
 * Text Domain: wc-bookings-quotes
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WCBQ_VERSION', '1.0.0' );
define( 'WCBQ_PLUGIN_FILE', __FILE__ );
define( 'WCBQ_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCBQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


function wcbq_check_dependencies() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return false;
    }

    if ( ! class_exists( 'WC_Bookings' ) ) {
        return false;
    }

    return true;
}


function wcbq_admin_notice_missing_dependencies() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>WooCommerce Bookings – Quotes</strong> requiere que
            <strong>WooCommerce</strong> y <strong>WooCommerce Bookings</strong>
            estén activos.
        </p>
    </div>
    <?php
}

function wcbq_init_plugin() {

    if ( ! wcbq_check_dependencies() ) {
        add_action( 'admin_notices', 'wcbq_admin_notice_missing_dependencies' );
        return;
    }

    // Includes principales
    require_once WCBQ_PLUGIN_PATH . 'includes/post-types/quote-cpt.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/frontend/quote-form.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/frontend/quote-handler.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/admin/quote-admin.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/orders/order-handler.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/emails/email-handler.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/meta/quote-meta.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/meta/quote-meta.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/statuses/quote-statuses.php';
    require_once WCBQ_PLUGIN_PATH . 'includes/helpers/quote-helpers.php';
}
add_action( 'plugins_loaded', 'wcbq_init_plugin' );

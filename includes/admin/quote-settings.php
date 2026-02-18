<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Add settings submenu
 */
add_action( 'admin_menu', 'wcbq_add_settings_page' );

function wcbq_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=quote_request', // Parent: Quotes
        __( 'Email Configuration', 'wc-bookings-quotes' ), // Page Title
        __( 'Email Settings', 'wc-bookings-quotes' ),      // Menu Title
        'manage_options',                   // Capability
        'wcbq-email-settings',              // Slug
        'wcbq_render_settings_page'         // Callback Function
    );
}

/**
 * 2. Register options (Settings API)
 */
add_action( 'admin_init', 'wcbq_register_settings' );

function wcbq_register_settings() {
    // Group: Admin Notifications
    register_setting( 'wcbq_email_options', 'wcbq_email_admin_recipients' );
    register_setting( 'wcbq_email_options', 'wcbq_email_admin_subject' );
    register_setting( 'wcbq_email_options', 'wcbq_email_admin_heading' );

    // Group: Customer Confirmation
    register_setting( 'wcbq_email_options', 'wcbq_email_customer_received_subject' );
    register_setting( 'wcbq_email_options', 'wcbq_email_customer_received_heading' );
    register_setting( 'wcbq_email_options', 'wcbq_email_customer_received_intro' );

    // Group: Statuses (Approved, Rejected, etc)
    $statuses = array('approved', 'review', 'rejected', 'pending');
    foreach($statuses as $st) {
        register_setting( 'wcbq_email_options', 'wcbq_email_' . $st . '_subject' );
        register_setting( 'wcbq_email_options', 'wcbq_email_' . $st . '_intro' );
    }
}

/**
 * 3. Render HTML page
 */
function wcbq_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( '📧 Email Template Settings', 'wc-bookings-quotes' ); ?></h1>
        <p><?php esc_html_e( 'Edit the automatic email texts. Leave fields empty to use default values.', 'wc-bookings-quotes' ); ?></p>
        
        <form method="post" action="options.php">
            <?php settings_fields( 'wcbq_email_options' ); ?>
            <?php do_settings_sections( 'wcbq_email_options' ); ?>

            <hr>
            <h2><?php esc_html_e( '1. Admin Notification (New Request)', 'wc-bookings-quotes' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Recipient Emails', 'wc-bookings-quotes' ); ?></th>
                    <td>
                        <input type="text" name="wcbq_email_admin_recipients" value="<?php echo esc_attr( get_option('wcbq_email_admin_recipients') ); ?>" class="large-text" placeholder="admin@example.com, sales@example.com">
                        <p class="description"><?php esc_html_e( 'Enter email addresses separated by commas. If empty, the WordPress admin email will be used.', 'wc-bookings-quotes' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Email Subject', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_admin_subject" value="<?php echo esc_attr( get_option('wcbq_email_admin_subject') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Default: ✦ NEW QUOTE REQUEST', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Main Title (H1)', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_admin_heading" value="<?php echo esc_attr( get_option('wcbq_email_admin_heading') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Default: NEW REQUEST', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
            </table>

            <hr>
            <h2><?php esc_html_e( '2. Customer Confirmation (Received)', 'wc-bookings-quotes' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Email Subject', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_customer_received_subject" value="<?php echo esc_attr( get_option('wcbq_email_customer_received_subject') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Default: ✦ REQUEST RECEIVED', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Main Title (H1)', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_customer_received_heading" value="<?php echo esc_attr( get_option('wcbq_email_customer_received_heading') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Default: REQUEST RECEIVED', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Introductory Text', 'wc-bookings-quotes' ); ?></th>
                    <td><textarea name="wcbq_email_customer_received_intro" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Thank you for contacting us. We have received your request...', 'wc-bookings-quotes' ); ?>"><?php echo esc_textarea( get_option('wcbq_email_customer_received_intro') ); ?></textarea></td>
                </tr>
            </table>

            <hr>
            <h2><?php esc_html_e( '3. Status Updates (Customer)', 'wc-bookings-quotes' ); ?></h2>
            
            <h3 style="background:#e8f5e9; padding:10px;"><?php esc_html_e( '✅ Quote Approved', 'wc-bookings-quotes' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Subject', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_approved_subject" value="<?php echo esc_attr( get_option('wcbq_email_approved_subject') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'QUOTE APPROVED', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Intro Message', 'wc-bookings-quotes' ); ?></th>
                    <td><textarea name="wcbq_email_approved_intro" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'We are pleased to inform you that your quote has been approved...', 'wc-bookings-quotes' ); ?>"><?php echo esc_textarea( get_option('wcbq_email_approved_intro') ); ?></textarea></td>
                </tr>
            </table>

            <h3 style="background:#e3f2fd; padding:10px;"><?php esc_html_e( 'Under Review', 'wc-bookings-quotes' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Subject', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_review_subject" value="<?php echo esc_attr( get_option('wcbq_email_review_subject') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'UNDER REVIEW', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Intro Message', 'wc-bookings-quotes' ); ?></th>
                    <td><textarea name="wcbq_email_review_intro" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Your request is being reviewed...', 'wc-bookings-quotes' ); ?>"><?php echo esc_textarea( get_option('wcbq_email_review_intro') ); ?></textarea></td>
                </tr>
            </table>

            <h3 style="background:#ffebee; padding:10px;"><?php esc_html_e( 'Rejected', 'wc-bookings-quotes' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Subject', 'wc-bookings-quotes' ); ?></th>
                    <td><input type="text" name="wcbq_email_rejected_subject" value="<?php echo esc_attr( get_option('wcbq_email_rejected_subject') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'REQUEST REJECTED', 'wc-bookings-quotes' ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Intro Message', 'wc-bookings-quotes' ); ?></th>
                    <td><textarea name="wcbq_email_rejected_intro" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'We regret to inform you that we cannot proceed...', 'wc-bookings-quotes' ); ?>"><?php echo esc_textarea( get_option('wcbq_email_rejected_intro') ); ?></textarea></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
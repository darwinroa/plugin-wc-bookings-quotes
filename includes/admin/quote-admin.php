<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Add Meta Box
 */
add_action( 'add_meta_boxes', 'wcbq_add_quote_meta_boxes' );

function wcbq_add_quote_meta_boxes() {
    add_meta_box(
        'wcbq_quote_details',
        __( 'Quote Management (Editable)', 'wc-bookings-quotes' ),
        'wcbq_render_quote_details_meta_box',
        'quote_request',
        'normal',
        'high'
    );
}

/**
 * 2. Render Meta Box (EDITABLE FORM)
 */
function wcbq_render_quote_details_meta_box( $post ) {

    // Retrieve existing data
    $product_id  = get_post_meta( $post->ID, '_quote_product_id', true );
    $start       = get_post_meta( $post->ID, '_quote_booking_start', true );
    $people      = get_post_meta( $post->ID, '_quote_people', true );
    $price       = get_post_meta( $post->ID, '_quote_price', true );
    $notes       = get_post_meta( $post->ID, '_quote_customer_notes', true );
    
    // Contact details
    $name        = get_post_meta( $post->ID, '_wcbq_customer_name', true );
    $email       = get_post_meta( $post->ID, '_wcbq_customer_email', true );
    $phone       = get_post_meta( $post->ID, '_wcbq_customer_phone', true );
    
    // Admin Notes (Agreements)
    $admin_notes = get_post_meta( $post->ID, '_quote_admin_notes', true );
    
    $order_id    = get_post_meta( $post->ID, '_quote_order_id', true );
    $product     = $product_id ? wc_get_product( $product_id ) : false;

    // Security Token
    wp_nonce_field( 'wcbq_save_quote_details', 'wcbq_quote_nonce' );

    echo '<table class="widefat striped" style="margin-top:10px;">';
    echo '<tbody>';

    // CLIENT SECTION
    echo '<tr><th colspan="2" style="background:#f0f0f1; padding:10px;">' . esc_html__( '👤 Client Details', 'wc-bookings-quotes' ) . '</th></tr>';
    
    echo '<tr><th>' . esc_html__( 'Name', 'wc-bookings-quotes' ) . '</th><td>' . esc_html( $name ) . '</td></tr>';
    echo '<tr><th>' . esc_html__( 'Email', 'wc-bookings-quotes' ) . '</th><td><a href="mailto:' . esc_attr($email) . '">' . esc_html( $email ) . '</a></td></tr>';
    echo '<tr><th>' . esc_html__( '📞 Phone', 'wc-bookings-quotes' ) . '</th><td><strong>' . ( $phone ? esc_html( $phone ) : esc_html__( 'Not registered', 'wc-bookings-quotes' ) ) . '</strong></td></tr>';

    // QUOTE SECTION
    echo '<tr><th colspan="2" style="background:#f0f0f1; padding:10px;">' . esc_html__( '📋 Event Details', 'wc-bookings-quotes' ) . '</th></tr>';

    echo '<tr><th>' . esc_html__( 'Product', 'wc-bookings-quotes' ) . '</th><td>' . ( $product ? esc_html( $product->get_name() ) : '-' ) . '</td></tr>';

    // Client Notes
    echo '<tr style="background:#fff8e1;">';
    echo '<th>' . esc_html__( '📝 Client Request', 'wc-bookings-quotes' ) . '</th>';
    echo '<td>' . ( $notes ? nl2br( esc_html( $notes ) ) : '<em>' . esc_html__( 'No notes.', 'wc-bookings-quotes' ) . '</em>' ) . '</td>';
    echo '</tr>';
    
    echo '<tr><th>' . esc_html__( 'Date', 'wc-bookings-quotes' ) . '</th><td>' . ( $start ? date( 'Y-m-d H:i', $start ) : '-' ) . '</td></tr>';

    // === EDITABLE ZONE ===
    echo '<tr><th colspan="2" style="background:#e1f0ff; padding:10px;">' . esc_html__( '🛠️ Agreement Zone (Admin)', 'wc-bookings-quotes' ) . '</th></tr>';

    // Editable People
    echo '<tr>';
    echo '<th><label for="_quote_people">' . esc_html__( 'Agreed No. People', 'wc-bookings-quotes' ) . '</label></th>';
    echo '<td><input type="number" name="_quote_people" id="_quote_people" value="' . esc_attr( $people ) . '" class="regular-text" style="max-width:100px;"></td>';
    echo '</tr>';

    // Editable Price
    echo '<tr>';
    echo '<th><label for="_quote_price">' . esc_html__( 'Final Price', 'wc-bookings-quotes' ) . ' (' . get_woocommerce_currency_symbol() . ')</label></th>';
    echo '<td><input type="number" step="0.01" name="_quote_price" id="_quote_price" value="' . esc_attr( $price ) . '" class="regular-text" style="max-width:150px;"></td>';
    echo '</tr>';

    // Admin Notes
    echo '<tr>';
    echo '<th><label for="_quote_admin_notes">' . esc_html__( '📒 Final Notes / Agreement', 'wc-bookings-quotes' ) . '</label></th>';
    echo '<td>';
    echo '<textarea name="_quote_admin_notes" id="_quote_admin_notes" rows="5" style="width:100%;" placeholder="' . esc_attr__( 'Write the final details agreed with the client here (e.g., Removed onions, added dessert, etc)...', 'wc-bookings-quotes' ) . '">' . esc_textarea( $admin_notes ) . '</textarea>';
    echo '<p class="description">' . esc_html__( 'These notes will be copied to the Order when you approve the quote.', 'wc-bookings-quotes' ) . '</p>';
    echo '</td>';
    echo '</tr>';

    if ( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $edit_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
            echo '<tr style="background:#e7f6e7;">';
            echo '<th><strong>' . esc_html__( '✅ Order Generated', 'wc-bookings-quotes' ) . '</strong></th>';
            echo '<td><a href="' . esc_url( $edit_url ) . '" class="button button-primary">' . sprintf( esc_html__( 'View Order #%s', 'wc-bookings-quotes' ), $order_id ) . '</a></td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
}

/**
 * 3. Save Admin Changes
 */
add_action( 'save_post', 'wcbq_save_quote_details_admin' );

function wcbq_save_quote_details_admin( $post_id ) {

    // Security checks
    if ( ! isset( $_POST['wcbq_quote_nonce'] ) || ! wp_verify_nonce( $_POST['wcbq_quote_nonce'], 'wcbq_save_quote_details' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save Edited Price
    if ( isset( $_POST['_quote_price'] ) ) {
        update_post_meta( $post_id, '_quote_price', wc_format_decimal( $_POST['_quote_price'] ) );
    }

    // Save Edited People
    if ( isset( $_POST['_quote_people'] ) ) {
        update_post_meta( $post_id, '_quote_people', absint( $_POST['_quote_people'] ) );
    }

    // Save Admin Notes
    if ( isset( $_POST['_quote_admin_notes'] ) ) {
        update_post_meta( $post_id, '_quote_admin_notes', sanitize_textarea_field( $_POST['_quote_admin_notes'] ) );
    }
}

/**
 * 4. Add Notification Counter in Admin Menu
 */
add_action( 'admin_menu', 'wcbq_add_pending_quote_bubble', 99 );

function wcbq_add_pending_quote_bubble() {
    global $menu;

    // 1. Count posts with 'quote-pending' status
    $count_posts = wp_count_posts( 'quote_request' );
    
    // Access dynamic property
    $pending_count = 0;
    if ( isset( $count_posts->{'quote-pending'} ) ) {
        $pending_count = $count_posts->{'quote-pending'};
    }

    // 2. If there are pending quotes, modify the menu
    if ( $pending_count > 0 ) {
        foreach ( $menu as $key => $value ) {
            // Find the menu entry corresponding to our CPT
            if ( 'edit.php?post_type=quote_request' === $value[2] ) {
                
                // 3. Inject WordPress standard notification HTML
                $menu[$key][0] .= sprintf(
                    ' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span></span>',
                    absint( $pending_count )
                );
                
                break; // Found it, stop searching
            }
        }
    }
}
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Intercept before Woo adds to cart
 */
add_filter( 'woocommerce_add_to_cart_validation', 'wcbq_intercept_quote_request', 10, 5 );

function wcbq_intercept_quote_request( $passed, $product_id, $quantity, $variation_id = 0, $variations = null ) {

    // 1. Verify if it is a quote request
    if ( ! isset( $_POST['wcbq_request_quote'] ) ) {
        return $passed;
    }

    $product = wc_get_product( $product_id );

    if ( ! $product || ! $product->is_type( 'booking' ) ) {
        return $passed;
    }

    // 2. Get processed form data
    $booking_data = wc_bookings_get_posted_data( $_POST, $product );
    
    // If Woo helper detected an error, show it and stop.
    if ( is_wp_error( $booking_data ) ) {
        wc_add_notice( $booking_data->get_error_message(), 'error' );
        return false;
    }

    // =======================================================
    // 🔴 STRICT VALIDATION (SECURITY BLOCK)
    // =======================================================
    
    // Validate Start Date
    if ( empty( $booking_data['_start_date'] ) ) {
        wc_add_notice( __( 'Please select a date and time to request a quote.', 'wc-bookings-quotes' ), 'error' );
        return false;
    }

    // Validate Persons (Only if product has persons)
    if ( $product->has_persons() ) {
        $persons = isset( $booking_data['_persons'] ) ? $booking_data['_persons'] : 0;
        
        // Sum total persons
        $total_persons = is_array( $persons ) ? array_sum( $persons ) : intval( $persons );

        if ( $total_persons <= 0 ) {
            wc_add_notice( __( 'Please specify the number of persons.', 'wc-bookings-quotes' ), 'error' );
            return false;
        }
    }

    // =======================================================
    // 3. PRICE CALCULATION
    // =======================================================
    
    $booking_cost = 0;

    if ( class_exists( 'WC_Bookings_Cost_Calculation' ) ) {
        try {
            $booking_cost = WC_Bookings_Cost_Calculation::calculate_booking_cost( $booking_data, $product );

            if ( '' === $booking_cost || null === $booking_cost ) {
                $booking_cost = 0;
            }
        } catch ( Exception $e ) {
            error_log( 'WCBQ Error: ' . $e->getMessage() );
            $booking_cost = 0;
        }
    } else {
        error_log( 'WCBQ Error: The WC_Bookings_Cost_Calculation class does not exist.' );
    }

    // ==========================
    // 4. CREATE QUOTE CPT
    // ==========================

    // Translated Title: Quote #12345 - Product Name
    $quote_title = sprintf( __( 'Quote #%s – %s', 'wc-bookings-quotes' ), uniqid(), $product->get_name() );
    
    $quote_id = wp_insert_post( array(
        'post_type'   => 'quote_request',
        'post_status' => 'quote-pending',
        'post_title'  => $quote_title,
        'post_author' => get_current_user_id(),
    ) );

    if ( ! is_wp_error( $quote_id ) && $quote_id > 0 ) {

        update_post_meta( $quote_id, '_quote_product_id', $product_id );
        update_post_meta( $quote_id, '_quote_booking_start', isset($booking_data['_start_date']) ? $booking_data['_start_date'] : '' );
        update_post_meta( $quote_id, '_quote_booking_end', isset($booking_data['_end_date']) ? $booking_data['_end_date'] : '' );
        
        $persons = isset( $booking_data['_persons'] ) ? $booking_data['_persons'] : 0;
        if( is_array($persons) ){
             update_post_meta( $quote_id, '_quote_people_breakdown', $persons );
             update_post_meta( $quote_id, '_quote_people', array_sum($persons) );
        } else {
             update_post_meta( $quote_id, '_quote_people', $persons );
        }

        update_post_meta( $quote_id, '_quote_price', $booking_cost );
        update_post_meta( $quote_id, '_quote_raw_data', $booking_data );

        // === CAPTURE MODAL DATA ===
        
        // 1. Notes
        if ( isset( $_POST['wcbq_final_notes'] ) ) {
            update_post_meta( $quote_id, '_quote_customer_notes', sanitize_textarea_field( $_POST['wcbq_final_notes'] ) );
        }

        // 2. Phone
        if ( isset( $_POST['wcbq_final_phone'] ) ) {
            update_post_meta( $quote_id, '_wcbq_customer_phone', sanitize_text_field( $_POST['wcbq_final_phone'] ) );
        }

        // 3. Name and Email
        $user = wp_get_current_user();
        if ( $user->exists() ) {
            update_post_meta( $quote_id, '_wcbq_customer_email', $user->user_email );
            update_post_meta( $quote_id, '_wcbq_customer_name', $user->display_name );
        } else {
            if ( isset( $_POST['wcbq_final_email'] ) ) {
                update_post_meta( $quote_id, '_wcbq_customer_email', sanitize_email( $_POST['wcbq_final_email'] ) );
            }
            if ( isset( $_POST['wcbq_final_name'] ) ) {
                update_post_meta( $quote_id, '_wcbq_customer_name', sanitize_text_field( $_POST['wcbq_final_name'] ) );
            }
        }

        // =======================================================
        // 📧 1. SEND EMAIL TO ADMIN
        // =======================================================
        if ( function_exists( 'wcbq_notify_admin_new_quote' ) ) {
            wcbq_notify_admin_new_quote( $quote_id );
        }

        // =======================================================
        // 📧 2. SEND EMAIL TO CUSTOMER
        // =======================================================
        if ( function_exists( 'wcbq_notify_customer_new_quote' ) ) {
            wcbq_notify_customer_new_quote( $quote_id );
        }
    }

    wc_clear_notices();
    
    // Show success message with price
    $precio_formateado = wc_price( $booking_cost );
    wc_add_notice( sprintf( __( 'Quote request sent successfully. Estimated price: %s', 'wc-bookings-quotes' ), $precio_formateado ), 'success' );

    // Return false to stop adding to cart
    return false;
}
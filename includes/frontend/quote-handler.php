<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interceptar antes de que Woo agregue al carrito
 */
add_filter( 'woocommerce_add_to_cart_validation', 'wcbq_intercept_quote_request', 10, 5 );

function wcbq_intercept_quote_request( $passed, $product_id, $quantity, $variation_id = 0, $variations = null ) {

    // 1. Verificar si es una solicitud de cotización
    if ( ! isset( $_POST['wcbq_request_quote'] ) ) {
        return $passed;
    }

    $product = wc_get_product( $product_id );

    if ( ! $product || ! $product->is_type( 'booking' ) ) {
        return $passed;
    }

    // 2. Obtener datos procesados del formulario
    $booking_data = wc_bookings_get_posted_data( $_POST, $product );
    
    // Si el helper de Woo ya detectó un error, lo mostramos y paramos.
    if ( is_wp_error( $booking_data ) ) {
        wc_add_notice( $booking_data->get_error_message(), 'error' );
        return false;
    }

    // =======================================================
    // 🔴 VALIDACIÓN ESTRICTA (NUEVO BLOQUE DE SEGURIDAD)
    // =======================================================
    
    // Validar Fecha de Inicio
    if ( empty( $booking_data['_start_date'] ) ) {
        wc_add_notice( 'Por favor, selecciona una fecha y hora para realizar la cotización.', 'error' );
        return false;
    }

    // Validar Personas (Solo si el producto maneja personas)
    if ( $product->has_persons() ) {
        $persons = isset( $booking_data['_persons'] ) ? $booking_data['_persons'] : 0;
        
        // Sumamos el total de personas (por si hay adultos + niños)
        $total_persons = is_array( $persons ) ? array_sum( $persons ) : intval( $persons );

        if ( $total_persons <= 0 ) {
            wc_add_notice( 'Por favor, indica el número de personas.', 'error' );
            return false;
        }
    }

    // =======================================================
    // 3. CÁLCULO DE PRECIO
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
        error_log( 'WCBQ Error: La clase WC_Bookings_Cost_Calculation no existe.' );
    }

    // ==========================
    // 4. CREAR EL CPT COTIZACIÓN
    // ==========================

    $quote_title = 'Cotización #' . uniqid() . ' – ' . $product->get_name();
    
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

        // === NUEVO: CAPTURAR DATOS DEL MODAL ===
        
        // 1. Notas
        if ( isset( $_POST['wcbq_final_notes'] ) ) {
            update_post_meta( $quote_id, '_quote_customer_notes', sanitize_textarea_field( $_POST['wcbq_final_notes'] ) );
        }

        // 2. Teléfono (Nuevo Campo)
        if ( isset( $_POST['wcbq_final_phone'] ) ) {
            update_post_meta( $quote_id, '_wcbq_customer_phone', sanitize_text_field( $_POST['wcbq_final_phone'] ) );
        }

        // 3. Nombre y Email (Prioridad: Usuario Logueado > Formulario Modal)
        $user = wp_get_current_user();
        if ( $user->exists() ) {
            // Si está logueado, confiamos en sus datos de WP, aunque también podríamos guardar los del form
            update_post_meta( $quote_id, '_wcbq_customer_email', $user->user_email );
            update_post_meta( $quote_id, '_wcbq_customer_name', $user->display_name );
        } else {
            // Si es invitado, usamos lo que llenó en el modal
            if ( isset( $_POST['wcbq_final_email'] ) ) {
                update_post_meta( $quote_id, '_wcbq_customer_email', sanitize_email( $_POST['wcbq_final_email'] ) );
            }
            if ( isset( $_POST['wcbq_final_name'] ) ) {
                update_post_meta( $quote_id, '_wcbq_customer_name', sanitize_text_field( $_POST['wcbq_final_name'] ) );
            }
        }

        // =======================================================
        // 📧 1. ENVIAR CORREO AL ADMINISTRADOR
        // =======================================================
        if ( function_exists( 'wcbq_notify_admin_new_quote' ) ) {
            wcbq_notify_admin_new_quote( $quote_id );
        }

        // =======================================================
        // 📧 2. ENVIAR CORREO AL CLIENTE (NUEVO)
        // =======================================================
        if ( function_exists( 'wcbq_notify_customer_new_quote' ) ) {
            wcbq_notify_customer_new_quote( $quote_id );
        }
    }

    wc_clear_notices();
    
    // Mostramos mensaje de éxito con el precio
    $precio_formateado = wc_price( $booking_cost );
    wc_add_notice( sprintf( 'Solicitud de cotización enviada correctamente. Precio estimado: %s', $precio_formateado ), 'success' );

    // Retornamos false para cancelar el agregado al carrito
    return false;
}
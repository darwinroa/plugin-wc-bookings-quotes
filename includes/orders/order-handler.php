<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Escuchar el cambio de estado de la cotización y crear Orden + Reserva
 * IMPORTANTE: Prioridad 50 para asegurar que los datos del Admin ya se guardaron.
 */
add_action( 'transition_post_status', 'wcbq_create_order_and_booking_on_approval', 50, 3 );

function wcbq_create_order_and_booking_on_approval( $new_status, $old_status, $post ) {

    // 1. Validaciones
    if ( 'quote_request' !== $post->post_type ) { return; }
    if ( 'quote-approved' !== $new_status ) { return; }

    $existing_order_id = get_post_meta( $post->ID, '_quote_order_id', true );
    if ( $existing_order_id ) { return; }

    // 2. Recuperar Datos
    $product_id = get_post_meta( $post->ID, '_quote_product_id', true );
    $price      = get_post_meta( $post->ID, '_quote_price', true );
    $start      = intval( get_post_meta( $post->ID, '_quote_booking_start', true ) );
    $end        = intval( get_post_meta( $post->ID, '_quote_booking_end', true ) );
    $persons    = intval( get_post_meta( $post->ID, '_quote_people', true ) );
    
    // Recuperar Notas Admin
    $admin_notes = get_post_meta( $post->ID, '_quote_admin_notes', true );
    if ( empty( $admin_notes ) && isset( $_POST['_quote_admin_notes'] ) ) {
        $admin_notes = sanitize_textarea_field( $_POST['_quote_admin_notes'] );
        update_post_meta( $post->ID, '_quote_admin_notes', $admin_notes );
    }

    $customer_notes = get_post_meta( $post->ID, '_quote_customer_notes', true );
    
    // Datos del Cliente
    $email      = get_post_meta( $post->ID, '_wcbq_customer_email', true );
    $name       = get_post_meta( $post->ID, '_wcbq_customer_name', true );
    $phone      = get_post_meta( $post->ID, '_wcbq_customer_phone', true ); // 🟢 NUEVO: Recuperamos el teléfono
    
    $persons_breakdown = get_post_meta( $post->ID, '_quote_people_breakdown', true );
    $raw_data   = get_post_meta( $post->ID, '_quote_raw_data', true );

    if ( empty( $start ) || empty( $end ) ) { return; }

    $product = wc_get_product( $product_id );
    if ( ! $product ) { return; }

    // 3. Crear la Orden
    $order_args = array();
    $customer_id = 0;

    if ( $email ) {
        $user = get_user_by( 'email', $email );
        if ( $user ) {
            $customer_id = $user->ID;
            $order_args['customer_id'] = $customer_id;
        }
    }

    $order = wc_create_order( $order_args );

    if ( is_wp_error( $order ) ) {
        error_log( 'Error creando orden para cotización ' . $post->ID );
        return;
    }

    // 4. Agregar Producto
    $item_id = $order->add_product( $product, 1, array(
        'subtotal' => $price,
        'total'    => $price,
    ) );

    // 5. Inyectar Datos Visuales (Item Metadata)
    $item = $order->get_item( $item_id );
    if ( $item ) {
        if ( $persons > 0 ) {
            $item->add_meta_data( 'Personas', $persons, true );
        }
        $item->add_meta_data( 'Fecha', date_i18n( get_option( 'date_format' ), $start ), true );

        if ( ! empty( $customer_notes ) ) {
            $item->add_meta_data( '📝 Nota Cliente', substr($customer_notes, 0, 200) . (strlen($customer_notes)>200?'...':''), true );
        }

        if ( ! empty( $admin_notes ) ) {
            $item->add_meta_data( '📒 Acuerdo Final', $admin_notes, true );
        }
        
        $item->save();
    }

    // 6. Crear Reserva
    try {
        $new_booking = new WC_Booking();

        $booking_props = array(
            'start'       => $start,
            'end'         => $end,
            'all_day'     => isset($raw_data['_all_day']) ? (bool) $raw_data['_all_day'] : false,
            'resource_id' => isset($raw_data['_resource_id']) ? intval($raw_data['_resource_id']) : 0,
            'parent_id'   => 0,
            'product_id'  => $product_id,
            'order_item_id' => $item_id,
            'order_id'    => $order->get_id(),
            'user_id'     => $customer_id,
            'status'      => 'unpaid',
            'cost'        => $price,
        );

        $product_person_types = $product->get_person_types();
        if ( ! empty( $product_person_types ) ) {
            if ( is_array( $persons_breakdown ) && array_sum( $persons_breakdown ) == $persons ) {
                $booking_props['persons'] = $persons_breakdown;
            } else {
                $first_type = current( $product_person_types );
                $booking_props['persons'] = array( $first_type->get_id() => $persons );
            }
        } else {
            $booking_props['persons'] = $persons;
        }

        $new_booking->set_props( $booking_props );
        $new_booking->save();

        // FIX Personas 0
        if ( empty( $product_person_types ) && $persons > 0 ) {
            update_post_meta( $new_booking->get_id(), '_booking_persons', $persons );
        }
        
        if ( ! empty( $admin_notes ) ) {
            update_post_meta( $new_booking->get_id(), '_booking_admin_note', $admin_notes );
        }

        wc_add_order_item_meta( $item_id, '_booking_id', $new_booking->get_id() );

    } catch ( Exception $e ) {
        error_log( 'Error creando reserva real: ' . $e->getMessage() );
        $order->add_order_note( 'Error creando reserva: ' . $e->getMessage() );
    }

    // 7. Datos Facturación (CON TELÉFONO INCLUIDO)
    if ( $name ) {
        $parts = explode( ' ', $name, 2 );
        $first_name = $parts[0];
        $last_name  = isset( $parts[1] ) ? $parts[1] : '';
        $base_country = WC()->countries->get_base_country();
        $base_state   = WC()->countries->get_base_state();

        $address = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone'      => $phone, // 🟢 AQUI LO AGREGAMOS
            'country'    => $base_country,
            'state'      => $base_state,
        );
        $order->set_address( $address, 'billing' );
        $order->set_address( $address, 'shipping' );
    }

    // Agregar notas laterales
    if ( ! empty( $admin_notes ) ) {
        $order->add_order_note( '📒 ACUERDO FINAL: ' . $admin_notes );
    }

    $order->calculate_totals();
    $order->update_status( 'pending', 'Orden generada desde Cotización.' );
    
    update_post_meta( $post->ID, '_quote_order_id', $order->get_id() );
    if ( isset( $new_booking ) && $new_booking->get_id() ) {
        update_post_meta( $post->ID, '_quote_generated_booking_id', $new_booking->get_id() );
    }
}
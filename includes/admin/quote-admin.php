<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Agregar metabox
 */
add_action( 'add_meta_boxes', 'wcbq_add_quote_meta_boxes' );

function wcbq_add_quote_meta_boxes() {
    add_meta_box(
        'wcbq_quote_details',
        'Gestión de Cotización (Editable)',
        'wcbq_render_quote_details_meta_box',
        'quote_request',
        'normal',
        'high'
    );
}

/**
 * 2. Render del metabox (FORMULARIO EDITABLE)
 */
function wcbq_render_quote_details_meta_box( $post ) {

    // Recuperar datos existentes
    $product_id  = get_post_meta( $post->ID, '_quote_product_id', true );
    $start       = get_post_meta( $post->ID, '_quote_booking_start', true );
    $people      = get_post_meta( $post->ID, '_quote_people', true );
    $price       = get_post_meta( $post->ID, '_quote_price', true );
    $notes       = get_post_meta( $post->ID, '_quote_customer_notes', true );
    
    // Datos de contacto
    $name        = get_post_meta( $post->ID, '_wcbq_customer_name', true );
    $email       = get_post_meta( $post->ID, '_wcbq_customer_email', true );
    $phone       = get_post_meta( $post->ID, '_wcbq_customer_phone', true );
    
    // NUEVO: Notas del Admin (Acuerdos)
    $admin_notes = get_post_meta( $post->ID, '_quote_admin_notes', true );
    
    $order_id    = get_post_meta( $post->ID, '_quote_order_id', true );
    $product     = $product_id ? wc_get_product( $product_id ) : false;

    // Token de seguridad
    wp_nonce_field( 'wcbq_save_quote_details', 'wcbq_quote_nonce' );

    echo '<table class="widefat striped" style="margin-top:10px;">';
    echo '<tbody>';

    // SECCIÓN CLIENTE
    echo '<tr><th colspan="2" style="background:#f0f0f1; padding:10px;">👤 Datos del Cliente</th></tr>';
    
    echo '<tr><th>Nombre</th><td>' . esc_html( $name ) . '</td></tr>';
    echo '<tr><th>Email</th><td><a href="mailto:' . esc_attr($email) . '">' . esc_html( $email ) . '</a></td></tr>';
    echo '<tr><th>📞 Teléfono</th><td><strong>' . ( $phone ? esc_html( $phone ) : 'No registrado' ) . '</strong></td></tr>';

    // SECCIÓN COTIZACIÓN
    echo '<tr><th colspan="2" style="background:#f0f0f1; padding:10px;">📋 Detalle del Evento</th></tr>';

    echo '<tr><th>Producto</th><td>' . ( $product ? esc_html( $product->get_name() ) : '-' ) . '</td></tr>';

    // Notas del Cliente
    echo '<tr style="background:#fff8e1;">';
    echo '<th>📝 Solicitud del Cliente</th>';
    echo '<td>' . ( $notes ? nl2br( esc_html( $notes ) ) : '<em>Sin notas.</em>' ) . '</td>';
    echo '</tr>';
    
    echo '<tr><th>Fecha</th><td>' . ( $start ? date( 'Y-m-d H:i', $start ) : '-' ) . '</td></tr>';

    // === ZONA EDITABLE ===
    echo '<tr><th colspan="2" style="background:#e1f0ff; padding:10px;">🛠️ Zona de Acuerdo (Admin)</th></tr>';

    // Personas Editable
    echo '<tr>';
    echo '<th><label for="_quote_people">Nº Personas Acordado</label></th>';
    echo '<td><input type="number" name="_quote_people" id="_quote_people" value="' . esc_attr( $people ) . '" class="regular-text" style="max-width:100px;"></td>';
    echo '</tr>';

    // Precio Editable
    echo '<tr>';
    echo '<th><label for="_quote_price">Precio Final ($)</label></th>';
    echo '<td><input type="number" step="0.01" name="_quote_price" id="_quote_price" value="' . esc_attr( $price ) . '" class="regular-text" style="max-width:150px;"></td>';
    echo '</tr>';

    // NUEVO: Notas del Admin
    echo '<tr>';
    echo '<th><label for="_quote_admin_notes">📒 Notas / Acuerdo Final</label></th>';
    echo '<td>';
    echo '<textarea name="_quote_admin_notes" id="_quote_admin_notes" rows="5" style="width:100%;" placeholder="Escribe aquí los detalles finales acordados con el cliente (Ej: Se quitó la cebolla, se agregó postre, etc)...">' . esc_textarea( $admin_notes ) . '</textarea>';
    echo '<p class="description">Estas notas se copiarán a la Orden cuando apruebes la cotización.</p>';
    echo '</td>';
    echo '</tr>';

    if ( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $edit_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
            echo '<tr style="background:#e7f6e7;">';
            echo '<th><strong>✅ Orden Generada</strong></th>';
            echo '<td><a href="' . esc_url( $edit_url ) . '" class="button button-primary">Ver Pedido #' . $order_id . '</a></td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
}

/**
 * 3. Guardar los cambios del Admin
 */
add_action( 'save_post', 'wcbq_save_quote_details_admin' );

function wcbq_save_quote_details_admin( $post_id ) {

    // Verificaciones de seguridad
    if ( ! isset( $_POST['wcbq_quote_nonce'] ) || ! wp_verify_nonce( $_POST['wcbq_quote_nonce'], 'wcbq_save_quote_details' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Guardar Precio Editado
    if ( isset( $_POST['_quote_price'] ) ) {
        update_post_meta( $post_id, '_quote_price', wc_format_decimal( $_POST['_quote_price'] ) );
    }

    // Guardar Personas Editadas
    if ( isset( $_POST['_quote_people'] ) ) {
        update_post_meta( $post_id, '_quote_people', absint( $_POST['_quote_people'] ) );
    }

    // NUEVO: Guardar Notas del Admin
    if ( isset( $_POST['_quote_admin_notes'] ) ) {
        update_post_meta( $post_id, '_quote_admin_notes', sanitize_textarea_field( $_POST['_quote_admin_notes'] ) );
    }
}

/**
 * 4. Agregar Contador de Notificaciones en el Menú Admin
 */
add_action( 'admin_menu', 'wcbq_add_pending_quote_bubble', 99 );

function wcbq_add_pending_quote_bubble() {
    global $menu;

    // 1. Contar cuántos posts hay con el estado 'quote-pending'
    $count_posts = wp_count_posts( 'quote_request' );
    
    // Accedemos a la propiedad dinámica del objeto (quote-pending)
    $pending_count = 0;
    if ( isset( $count_posts->{'quote-pending'} ) ) {
        $pending_count = $count_posts->{'quote-pending'};
    }

    // 2. Si hay pendientes, modificamos el menú
    if ( $pending_count > 0 ) {
        foreach ( $menu as $key => $value ) {
            // Buscamos la entrada del menú que corresponde a nuestro CPT
            if ( 'edit.php?post_type=quote_request' === $value[2] ) {
                
                // 3. Inyectamos el HTML estándar de WordPress para notificaciones
                // "awaiting-mod" es la clase nativa que le da el fondo rojo/naranja
                $menu[$key][0] .= sprintf(
                    ' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span></span>',
                    absint( $pending_count )
                );
                
                break; // Ya lo encontramos, dejamos de buscar
            }
        }
    }
}
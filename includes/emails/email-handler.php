<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ==============================================================================
 * 1. GATILLO AUTOMÁTICO (HOOK)
 * Escucha cuando cambia el estado de una cotización y dispara el correo
 * ==============================================================================
 */
add_action( 'transition_post_status', 'wcbq_trigger_email_on_status_change', 100, 3 );

function wcbq_trigger_email_on_status_change( $new_status, $old_status, $post ) {
    
    // 1. Validar que sea una cotización
    if ( 'quote_request' !== $post->post_type ) {
        return;
    }

    // 2. Evitar enviar correo si el estado no cambió
    if ( $new_status === $old_status ) {
        return;
    }

    // 3. Definir los estados que disparan correo al cliente
    // NOTA: Excluimos 'new' -> 'quote-pending' porque ese ya se envía al crear la cotización (Confirmación inicial)
    // Si quieres que también se envíe en ese caso, quita la validación de $old_status !== 'new'
    $valid_statuses = array(
        'quote-pending',
        'quote-review',
        'quote-approved',
        'quote-rejected',
        'quote-expired'
    );

    if ( in_array( $new_status, $valid_statuses ) ) {
        
        // Evitar duplicado en la creación inicial (ya que tenemos wcbq_notify_customer_new_quote en el handler)
        if ( $old_status === 'new' && $new_status === 'quote-pending' ) {
            return; 
        }

        // ENVIAR EL CORREO DE ACTUALIZACIÓN
        wcbq_send_status_update_email( $post->ID, $new_status );
    }
}

/**
 * ==============================================================================
 * 2. FUNCIÓN DE ENVÍO DE CORREO DE ACTUALIZACIÓN
 * ==============================================================================
 */
function wcbq_send_status_update_email( $quote_id, $new_status ) {

    // Recuperar Email del Cliente
    $customer_email = get_post_meta( $quote_id, '_wcbq_customer_email', true );
    if ( ! is_email( $customer_email ) ) { return; }

    // Configurar Asunto y Título según el estado
    $status_labels = array(
        'quote-pending'  => 'Pendiente',
        'quote-review'   => 'En Revisión',
        'quote-approved' => '¡Aprobada!',
        'quote-rejected' => 'Rechazada',
        'quote-expired'  => 'Expirada',
    );

    $status_messages = array(
        'quote-pending'  => 'Tu cotización ha vuelto al estado Pendiente.',
        'quote-review'   => 'Tu cotización está siendo revisada por nuestro equipo.',
        'quote-approved' => '¡Buenas noticias! Tu cotización ha sido aprobada. Hemos generado una orden para ti.',
        'quote-rejected' => 'Lo sentimos, no podemos proceder con tu cotización en este momento.',
        'quote-expired'  => 'El tiempo de validez de tu cotización ha expirado.',
    );

    $label = isset( $status_labels[ $new_status ] ) ? $status_labels[ $new_status ] : 'Actualizada';
    $intro = isset( $status_messages[ $new_status ] ) ? $status_messages[ $new_status ] : 'El estado de tu cotización ha cambiado.';

    $to = $customer_email;
    $subject = '📢 Actualización de Cotización #' . $quote_id . ' - ' . $label;

    // Recuperar Datos
    $product_id = get_post_meta( $quote_id, '_quote_product_id', true );
    $start      = get_post_meta( $quote_id, '_quote_booking_start', true );
    $people     = get_post_meta( $quote_id, '_quote_people', true );
    $price      = get_post_meta( $quote_id, '_quote_price', true );
    $notes      = get_post_meta( $quote_id, '_quote_customer_notes', true );
    $admin_notes= get_post_meta( $quote_id, '_quote_admin_notes', true ); // Acuerdo final
    $name       = get_post_meta( $quote_id, '_wcbq_customer_name', true );
    $phone      = get_post_meta( $quote_id, '_wcbq_customer_phone', true );
    $order_id   = get_post_meta( $quote_id, '_quote_order_id', true );

    $product = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : 'Evento';
    $fecha_fmt = $start ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start ) : '-';

    // === GENERAR LINK DE PAGO (Botón Principal) ===
    $payment_section_html = '';
    
    if ( $new_status === 'quote-approved' && $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( $order && $order->needs_payment() ) {
            $pay_url = $order->get_checkout_payment_url();
            
            $payment_section_html = '
            <div style="background-color:#e8f5e9; padding: 20px; border-radius: 8px; text-align: center; margin: 25px 0; border: 1px solid #c8e6c9;">
                <h3 style="margin-top:0; color:#2e7d32;">✅ Cotización Aprobada</h3>
                <p style="margin-bottom:20px;">Todo está listo. Para confirmar tu fecha, por favor completa el pago:</p>
                
                <a href="' . esc_url( $pay_url ) . '" style="background-color:#28a745; color:#fff; padding:18px 35px; text-decoration:none; border-radius:6px; font-weight:bold; font-size:18px; display:inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    💳 PAGAR AHORA $' . $order->get_total() . '
                </a>
                
                <p style="font-size:12px; color:#666; margin-top:15px;">Serás redirigido a la pasarela de pago segura.</p>
            </div>';
        }
    }
    // =============================================

    // Construir HTML
    $message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; border: 1px solid #e1e1e1; background: #fff; }
            .header { background-color: #f7f7f7; padding: 20px; text-align: center; border-bottom: 1px solid #ddd; }
            .content { padding: 20px; }
            h2 { color: #333; margin: 0 0 10px; }
            .status-badge { display: inline-block; padding: 5px 10px; background: #333; color: #fff; border-radius: 4px; font-size: 12px; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
            td { padding: 10px; border-bottom: 1px solid #f1f1f1; }
            td:first-child { font-weight: bold; width: 40%; color: #555; }
            .note-box { background: #fff8e1; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px; }
            .admin-note-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin-top: 20px; }
            .footer { background-color: #f7f7f7; padding: 15px; text-align: center; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Actualización de Estado</h2>
                <span class="status-badge">' . esc_html( $label ) . '</span>
            </div>
            
            <div class="content">
                <p>Hola <strong>' . esc_html( $name ) . '</strong>,</p>
                <p>' . esc_html( $intro ) . '</p>

                ' . $payment_section_html . '

                <h3>📋 Resumen de la Cotización</h3>
                <table>
                    <tr><td>Producto:</td><td>' . esc_html( $product_name ) . '</td></tr>
                    <tr><td>Fecha:</td><td>' . esc_html( $fecha_fmt ) . '</td></tr>
                    <tr><td>Personas:</td><td>' . esc_html( $people ) . '</td></tr>
                    <tr><td>Precio Final:</td><td><strong>' . wc_price( $price ) . '</strong></td></tr>
                </table>

                <h3>📝 Tu solicitud original</h3>
                <div class="note-box">
                    ' . ( $notes ? nl2br( esc_html( $notes ) ) : '<em>Sin notas.</em>' ) . '
                </div>

                <h3>📒 Notas / Acuerdo Final</h3>
                <div class="admin-note-box">
                    ' . ( $admin_notes ? nl2br( esc_html( $admin_notes ) ) : '<em>No hay notas adicionales del administrador.</em>' ) . '
                </div>
            </div>

            <div class="footer">
                <p>Gracias por confiar en nosotros.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    wp_mail( $to, $subject, $message, $headers );
}


/**
 * ==============================================================================
 * 3. Función principal para enviar el correo al administrador
 * ==============================================================================
 */
function wcbq_notify_admin_new_quote( $quote_id ) {

    // 1. Configuración del destinatario
    $to = 'darwinmichaelroamora@gmail.com'; // 📧 TU CORREO
    $subject = '🔔 Nueva Solicitud de Cotización #' . $quote_id;

    // 2. Recuperar todos los datos
    $product_id = get_post_meta( $quote_id, '_quote_product_id', true );
    $start      = get_post_meta( $quote_id, '_quote_booking_start', true );
    $people     = get_post_meta( $quote_id, '_quote_people', true );
    $price      = get_post_meta( $quote_id, '_quote_price', true );
    $notes      = get_post_meta( $quote_id, '_quote_customer_notes', true );
    
    // Datos del Cliente
    $name       = get_post_meta( $quote_id, '_wcbq_customer_name', true );
    $email      = get_post_meta( $quote_id, '_wcbq_customer_email', true );
    $phone      = get_post_meta( $quote_id, '_wcbq_customer_phone', true );

    $product = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : 'Producto desconocido';
    $fecha_fmt = $start ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start ) : '-';
    $edit_link = admin_url( 'post.php?post=' . $quote_id . '&action=edit' );

    // 3. Construir el mensaje HTML
    $message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; border: 1px solid #e5e5e5; padding: 20px; border-radius: 5px; background: #f9f9f9; }
            h2 { color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f1f1f1; width: 40%; font-weight: bold; }
            .btn { display: inline-block; padding: 10px 20px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 20px; }
            .note { background-color: #fff8e1; padding: 10px; border-left: 4px solid #ffc107; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>¡Nueva Solicitud Recibida!</h2>
            <p>Hola Admin, has recibido una nueva solicitud de cotización desde la web.</p>

            <h3>👤 Datos del Cliente</h3>
            <table>
                <tr><th>Nombre:</th><td>' . esc_html( $name ) . '</td></tr>
                <tr><th>Email:</th><td><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td></tr>
                <tr><th>Teléfono:</th><td>' . esc_html( $phone ) . '</td></tr>
            </table>

            <h3>📋 Detalles del Evento</h3>
            <table>
                <tr><th>Producto:</th><td>' . esc_html( $product_name ) . '</td></tr>
                <tr><th>Fecha del Evento:</th><td>' . esc_html( $fecha_fmt ) . '</td></tr>
                <tr><th>Personas:</th><td>' . esc_html( $people ) . '</td></tr>
                <tr><th>Precio Estimado:</th><td>' . wc_price( $price ) . '</td></tr>
            </table>

            <h3>📝 Mensaje / Requerimientos</h3>
            <div class="note">
                ' . ( $notes ? nl2br( esc_html( $notes ) ) : '<em>Sin notas adicionales.</em>' ) . '
            </div>

            <p style="text-align: center;">
                <a href="' . esc_url( $edit_link ) . '" class="btn" style="color:#fff;">Ver y Gestionar Cotización</a>
            </p>
        </div>
    </body>
    </html>
    ';

    // 4. Encabezados para HTML
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // 5. Enviar
    wp_mail( $to, $subject, $message, $headers );
}


/**
 * ==============================================================================
 * 3. Correo para el CLIENTE (Confirmación)
 * ==============================================================================
 */
function wcbq_notify_customer_new_quote( $quote_id ) {

    // Recuperar Email del Cliente
    $customer_email = get_post_meta( $quote_id, '_wcbq_customer_email', true );
    
    // Si no hay email, no podemos enviar nada
    if ( ! is_email( $customer_email ) ) {
        return;
    }

    $to = $customer_email;
    $subject = '✅ Hemos recibido tu solicitud de cotización #' . $quote_id;

    // Recuperar datos
    $product_id = get_post_meta( $quote_id, '_quote_product_id', true );
    $start      = get_post_meta( $quote_id, '_quote_booking_start', true );
    $people     = get_post_meta( $quote_id, '_quote_people', true );
    $price      = get_post_meta( $quote_id, '_quote_price', true );
    $notes      = get_post_meta( $quote_id, '_quote_customer_notes', true );
    $name       = get_post_meta( $quote_id, '_wcbq_customer_name', true );
    $phone      = get_post_meta( $quote_id, '_wcbq_customer_phone', true );

    $product = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : 'Evento';
    $fecha_fmt = $start ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start ) : '-';

    // Construir mensaje HTML (Tono amable para el cliente)
    $message = '
    <html>
    <head>
        <style>
            body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 0; border-radius: 8px; overflow: hidden; }
            .header { background-color: #222; color: #fff; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #fff; }
            h2 { margin: 0; font-size: 20px; }
            h3 { color: #555; border-bottom: 2px solid #eee; padding-bottom: 5px; margin-top: 20px; font-size: 16px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
            td { padding: 10px; border-bottom: 1px solid #f5f5f5; }
            td:first-child { font-weight: bold; width: 40%; color: #777; }
            .note { background-color: #f9f9f9; padding: 15px; border-radius: 5px; font-style: italic; color: #555; margin-top: 5px; }
            .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>¡Solicitud Recibida!</h2>
            </div>
            <div class="content">
                <p>Hola <strong>' . esc_html( $name ) . '</strong>,</p>
                <p>Gracias por contactarnos. Hemos recibido correctamente tu solicitud de cotización y nuestro equipo la está revisando.</p>
                <p>Pronto nos pondremos en contacto contigo (vía email o WhatsApp) para confirmar disponibilidad y detalles.</p>

                <h3>📋 Resumen de tu solicitud</h3>
                <table>
                    <tr><td>Evento/Producto:</td><td>' . esc_html( $product_name ) . '</td></tr>
                    <tr><td>Fecha deseada:</td><td>' . esc_html( $fecha_fmt ) . '</td></tr>
                    <tr><td>Personas:</td><td>' . esc_html( $people ) . '</td></tr>
                    <tr><td>Tu Teléfono:</td><td>' . esc_html( $phone ) . '</td></tr>
                </table>

                <h3>📝 Tus comentarios</h3>
                <div class="note">
                    "' . ( $notes ? nl2br( esc_html( $notes ) ) : 'Sin comentarios adicionales' ) . '"
                </div>
            </div>
            <div class="footer">
                <p>Este es un correo automático, por favor espera nuestra confirmación.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    wp_mail( $to, $subject, $message, $headers );
}
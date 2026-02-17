<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ==============================================================================
 * GATILLO AUTOMÁTICO (HOOK) - Prioridad 100
 * ==============================================================================
 */
add_action( 'transition_post_status', 'wcbq_trigger_email_on_status_change', 100, 3 );

function wcbq_trigger_email_on_status_change( $new_status, $old_status, $post ) {
    if ( 'quote_request' !== $post->post_type ) { return; }
    if ( $new_status === $old_status ) { return; }

    $valid_statuses = array( 'quote-pending', 'quote-review', 'quote-approved', 'quote-rejected', 'quote-expired' );

    if ( in_array( $new_status, $valid_statuses ) ) {
        if ( $old_status === 'new' && $new_status === 'quote-pending' ) { return; }
        wcbq_send_status_update_email( $post->ID, $new_status );
    }
}

/**
 * ==============================================================================
 * GENERADOR DE ESTILOS (DISEÑO PREMIUM / GEORGIA)
 * ==============================================================================
 */
function wcbq_get_email_styles() {
    // Colores basados en la imagen de referencia (Negro elegante y Dorado/Beige)
    $color_bg       = '#f4f4f4';
    $color_container= '#ffffff';
    $color_text     = '#333333';
    $color_accent   = '#c5a065'; // Dorado suave estilo "Cake"
    $color_header   = '#1a1a1a'; // Negro casi puro
    $font_family    = 'Georgia, "Times New Roman", Times, serif';
    $font_sans      = '"Helvetica Neue", Helvetica, Arial, sans-serif';

    return "
        body { margin: 0; padding: 0; background-color: $color_bg; font-family: $font_sans; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: $color_bg; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: $color_container; border-top: 5px solid $color_header; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        .header { background-color: $color_header; color: #ffffff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-family: $font_family; font-size: 24px; letter-spacing: 1px; text-transform: uppercase; color: $color_accent; }
        .header .date { font-size: 12px; color: #bbbbbb; margin-top: 5px; font-family: $font_sans; letter-spacing: 1px;}

        .content { padding: 40px 30px; color: $color_text; line-height: 1.6; }
        
        h2 { font-family: $font_family; font-size: 18px; color: $color_header; text-transform: uppercase; border-bottom: 1px solid $color_accent; padding-bottom: 10px; margin-top: 30px; margin-bottom: 20px; letter-spacing: 0.5px; }
        h2:first-child { margin-top: 0; }
        
        p { margin-bottom: 15px; font-size: 14px; color: #555; }
        strong { color: #000; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        td { padding: 12px 5px; border-bottom: 1px solid #eeeeee; vertical-align: top; }
        td.label { font-weight: bold; color: #1a1a1a; width: 35%; font-family: $font_family; }
        td.value { color: #555; font-family: $font_sans; }
        
        .price-box { background-color: #fcfcfc; border: 1px solid #eee; padding: 20px; text-align: center; margin: 20px 0; }
        .price-label { font-family: $font_family; font-size: 12px; text-transform: uppercase; color: #888; letter-spacing: 1px; }
        .price-value { font-family: $font_family; font-size: 28px; color: $color_accent; font-weight: bold; margin-top: 5px; }

        .note-box { background-color: #fafafa; border-left: 3px solid $color_accent; padding: 15px; font-style: italic; color: #555; margin-bottom: 20px; font-size: 13px; }
        
        .btn-container { text-align: center; margin: 35px 0; }
        .btn { background-color: $color_accent; color: #ffffff !important; padding: 15px 30px; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; display: inline-block; font-family: $font_sans; }
        .btn:hover { background-color: #000000; }

        .footer { background-color: $color_header; color: #666; padding: 20px; text-align: center; font-size: 11px; font-family: $font_sans; }
        .footer a { color: #888; text-decoration: none; }
    ";
}

/**
 * ==============================================================================
 * FUNCIÓN DE ENVÍO DE ACTUALIZACIONES (CLIENTE)
 * ==============================================================================
 */
function wcbq_send_status_update_email( $quote_id, $new_status ) {
    $customer_email = get_post_meta( $quote_id, '_wcbq_customer_email', true );
    if ( ! is_email( $customer_email ) ) return;

    // 1. Textos por defecto
    $default_titles = array(
        'quote-pending'  => 'COTIZACIÓN PENDIENTE',
        'quote-review'   => 'EN REVISIÓN',
        'quote-approved' => 'COTIZACIÓN APROBADA',
        'quote-rejected' => 'SOLICITUD RECHAZADA',
        'quote-expired'  => 'SOLICITUD EXPIRADA',
    );
    $default_intros = array(
        'quote-pending'  => 'El estado de tu solicitud ha cambiado a Pendiente.',
        'quote-review'   => 'Tu solicitud está siendo revisada por nuestro equipo.',
        'quote-approved' => 'Nos complace informarte que tu cotización ha sido aprobada. A continuación encontrarás los detalles y el enlace para confirmar tu reserva.',
        'quote-rejected' => 'Lamentamos informarte que no podemos proceder con tu solicitud en esta fecha.',
        'quote-expired'  => 'La validez de esta propuesta ha expirado.',
    );

    // 2. Mapeo
    $status_key_map = array(
        'quote-pending'  => 'pending',
        'quote-review'   => 'review',
        'quote-approved' => 'approved',
        'quote-rejected' => 'rejected',
        'quote-expired'  => 'expired',
    );
    
    $clean_status = isset($status_key_map[$new_status]) ? $status_key_map[$new_status] : 'pending';

    // 3. Recuperar opción (Dinámico)
    $saved_title = get_option( 'wcbq_email_' . $clean_status . '_subject' );
    $title_text  = !empty($saved_title) ? $saved_title : (isset($default_titles[$new_status]) ? $default_titles[$new_status] : 'ACTUALIZACIÓN');
    
    $saved_intro = get_option( 'wcbq_email_' . $clean_status . '_intro' );
    $intro_text  = !empty($saved_intro) ? $saved_intro : (isset($default_intros[$new_status]) ? $default_intros[$new_status] : '');

    // Recuperar Datos
    $product_id  = get_post_meta( $quote_id, '_quote_product_id', true );
    $start       = get_post_meta( $quote_id, '_quote_booking_start', true );
    $people      = get_post_meta( $quote_id, '_quote_people', true );
    $price       = get_post_meta( $quote_id, '_quote_price', true );
    $notes       = get_post_meta( $quote_id, '_quote_customer_notes', true );
    $admin_notes = get_post_meta( $quote_id, '_quote_admin_notes', true );
    $name        = get_post_meta( $quote_id, '_wcbq_customer_name', true );
    $order_id    = get_post_meta( $quote_id, '_quote_order_id', true );

    $product = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : 'Evento';
    $date_formatted = $start ? date_i18n( 'l, F j, Y', $start ) : '-';

    $cta_html = '';
    if ( $new_status === 'quote-approved' && $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order && $order->needs_payment() ) {
            $pay_url = $order->get_checkout_payment_url();
            $cta_html = '
            <div class="btn-container">
                <a href="' . esc_url( $pay_url ) . '" class="btn">CONFIRMAR Y PAGAR</a>
                <p style="font-size:11px; margin-top:10px; color:#999;">Serás redirigido a nuestra pasarela segura</p>
            </div>';
        }
    }

    $styles = wcbq_get_email_styles();
    $current_date = date_i18n( 'F j, Y, g:i a' );

    $message = '
    <!DOCTYPE html>
    <html>
    <head><title>' . esc_html( $title_text ) . '</title><style>' . $styles . '</style></head>
    <body>
        <div class="wrapper">
            <div class="container">
                <div class="header">
                    <h1>' . esc_html( $title_text ) . '</h1>
                    <div class="date">' . $current_date . '</div>
                </div>

                <div class="content">
                    <p>Hello <strong>' . esc_html( $name ) . '</strong>,</p>
                    <p>' . nl2br( esc_html( $intro_text ) ) . '</p>

                    <h2>INFORMACIÓN DEL CLIENTE</h2>
                    <table>
                        <tr><td class="label">Nombre</td><td class="value">' . esc_html( $name ) . '</td></tr>
                        <tr><td class="label">Email</td><td class="value">' . esc_html( $customer_email ) . '</td></tr>
                    </table>

                    <h2>RESUMEN DEL EVENTO</h2>
                    <div class="price-box">
                        <div class="price-label">COTIZACIÓN ESTIMADA</div>
                        <div class="price-value">' . strip_tags( wc_price( $price ) ) . '</div>
                    </div>

                    <table>
                        <tr><td class="label">Servicio</td><td class="value"><strong>' . esc_html( $product_name ) . '</strong></td></tr>
                        <tr><td class="label">Fecha</td><td class="value">' . esc_html( $date_formatted ) . '</td></tr>
                        <tr><td class="label">Invitados</td><td class="value">' . esc_html( $people ) . ' personas</td></tr>
                    </table>

                    ' . ( $notes ? '<h2>SOLICITUDES ADICIONALES</h2><div class="note-box">' . nl2br( esc_html( $notes ) ) . '</div>' : '' ) . '
                    
                    ' . ( $admin_notes ? '<h2>NOTAS DEL ACUERDO</h2><div class="note-box" style="border-left-color:#333;">' . nl2br( esc_html( $admin_notes ) ) . '</div>' : '' ) . '

                    ' . $cta_html . '
                </div>
                <div class="footer"><p>&copy; ' . date('Y') . ' ' . get_bloginfo( 'name' ) . '.</p></div>
            </div>
        </div>
    </body>
    </html>';

    $subject = $title_text . ' - #' . $quote_id;
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    wp_mail( $customer_email, $subject, $message, $headers );
}

/**
 * ==============================================================================
 * CORREO 1: NOTIFICACIÓN AL ADMINISTRADOR (NUEVA SOLICITUD)
 * ==============================================================================
 */
function wcbq_notify_admin_new_quote( $quote_id ) {
    $to = 'darwinmichaelroamora@gmail.com'; 
    
    // 🟢 AQUÍ ESTÁ LA CORRECCIÓN: Conectando con los Ajustes
    $saved_subject = get_option( 'wcbq_email_admin_subject' );
    $subject_base  = !empty($saved_subject) ? $saved_subject : 'NUEVA SOLICITUD DE COTIZACIÓN';
    $subject       = $subject_base . ' - #' . $quote_id;

    $saved_heading = get_option( 'wcbq_email_admin_heading' );
    $heading       = !empty($saved_heading) ? $saved_heading : 'NUEVA SOLICITUD';
    // ------------------------------------------------------

    // Recuperar Datos
    $name       = get_post_meta( $quote_id, '_wcbq_customer_name', true );
    $email      = get_post_meta( $quote_id, '_wcbq_customer_email', true );
    $phone      = get_post_meta( $quote_id, '_wcbq_customer_phone', true );
    $product_id = get_post_meta( $quote_id, '_quote_product_id', true );
    $start      = get_post_meta( $quote_id, '_quote_booking_start', true );
    $people     = get_post_meta( $quote_id, '_quote_people', true );
    $price      = get_post_meta( $quote_id, '_quote_price', true );
    $notes      = get_post_meta( $quote_id, '_quote_customer_notes', true );
    
    $product = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : 'Evento';
    $date_formatted = $start ? date_i18n( 'l, F j, Y', $start ) : '-';
    $edit_link = admin_url( 'post.php?post=' . $quote_id . '&action=edit' );
    
    $styles = wcbq_get_email_styles();
    $current_date = date_i18n( 'F j, Y, g:i a' );

    $message = '
    <!DOCTYPE html>
    <html>
    <head><style>' . $styles . '</style></head>
    <body>
        <div class="wrapper">
            <div class="container">
                <div class="header">
                    <h1>' . esc_html( $heading ) . '</h1>
                    <div class="date">' . $current_date . '</div>
                </div>
                <div class="content">
                    <p>Hello Admin,</p>
                    <p>A new inquiry has been submitted through the website. Please find the details for your review below.</p>

                    <h2>INFORMACIÓN DEL CLIENTE</h2>
                    <table>
                        <tr><td class="label">Nombre</td><td class="value">' . esc_html( $name ) . '</td></tr>
                        <tr><td class="label">Email</td><td class="value"><a href="mailto:' . esc_attr($email) . '">' . esc_html( $email ) . '</a></td></tr>
                        <tr><td class="label">Teléfono</td><td class="value">' . esc_html( $phone ) . '</td></tr>
                    </table>

                    <h2>DETALLES DEL EVENTO</h2>
                     <div class="price-box">
                        <div class="price-label">PRESUPUESTO INICIAL</div>
                        <div class="price-value">' . strip_tags( wc_price( $price ) ) . '</div>
                    </div>
                    <table>
                        <tr><td class="label">Servicio</td><td class="value"><strong>' . esc_html( $product_name ) . '</strong></td></tr>
                        <tr><td class="label">Fecha</td><td class="value">' . esc_html( $date_formatted ) . '</td></tr>
                        <tr><td class="label">Invitados</td><td class="value">' . esc_html( $people ) . ' personas</td></tr>
                    </table>

                    ' . ( $notes ? '<h2>MENSAJE DEL CLIENTE</h2><div class="note-box">' . nl2br( esc_html( $notes ) ) . '</div>' : '' ) . '
                    
                    <div class="btn-container">
                        <a href="' . esc_url( $edit_link ) . '" class="btn">GESTIONAR EN WORDPRESS</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    wp_mail( $to, $subject, $message, $headers );
}

/**
 * ==============================================================================
 * CORREO 2: CONFIRMACIÓN INICIAL AL CLIENTE (DINÁMICO)
 * ==============================================================================
 */
function wcbq_notify_customer_new_quote( $quote_id ) {
    $customer_email = get_post_meta( $quote_id, '_wcbq_customer_email', true );
    if ( ! is_email( $customer_email ) ) return;

    // --- SETTINGS ---
    $saved_subject = get_option( 'wcbq_email_customer_received_subject' );
    $subject_base  = !empty($saved_subject) ? $saved_subject : 'SOLICITUD RECIBIDA';
    $subject       = $subject_base . ' - #' . $quote_id;
    
    $saved_heading = get_option( 'wcbq_email_customer_received_heading' );
    $heading       = !empty($saved_heading) ? $saved_heading : 'SOLICITUD RECIBIDA';

    $saved_intro = get_option( 'wcbq_email_customer_received_intro' );
    $intro       = !empty($saved_intro) ? $saved_intro : 'Gracias por contactarnos. Hemos recibido tu solicitud correctamente y nuestro equipo la revisará a la brevedad.';
    // ----------------

    $name       = get_post_meta( $quote_id, '_wcbq_customer_name', true );
    $product_id = get_post_meta( $quote_id, '_quote_product_id', true );
    $start      = get_post_meta( $quote_id, '_quote_booking_start', true );
    $people     = get_post_meta( $quote_id, '_quote_people', true );
    $notes      = get_post_meta( $quote_id, '_quote_customer_notes', true );
    
    $product = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : 'Evento';
    $date_formatted = $start ? date_i18n( 'l, F j, Y', $start ) : '-';

    $styles = wcbq_get_email_styles();
    $current_date = date_i18n( 'F j, Y, g:i a' );

    $message = '
    <!DOCTYPE html>
    <html>
    <head><style>' . $styles . '</style></head>
    <body>
        <div class="wrapper">
            <div class="container">
                <div class="header">
                    <h1>' . esc_html( $heading ) . '</h1>
                    <div class="date">' . $current_date . '</div>
                </div>
                <div class="content">
                    <p>Hello <strong>' . esc_html( $name ) . '</strong>,</p>
                    <p>' . nl2br( esc_html( $intro ) ) . '</p>

                    <h2>RESUMEN DE TU SOLICITUD</h2>
                    <table>
                        <tr><td class="label">Servicio</td><td class="value"><strong>' . esc_html( $product_name ) . '</strong></td></tr>
                        <tr><td class="label">Fecha</td><td class="value">' . esc_html( $date_formatted ) . '</td></tr>
                        <tr><td class="label">Invitados</td><td class="value">' . esc_html( $people ) . ' personas</td></tr>
                    </table>

                    ' . ( $notes ? '<h2>TUS COMENTARIOS</h2><div class="note-box">' . nl2br( esc_html( $notes ) ) . '</div>' : '' ) . '
                    
                    <p style="margin-top:30px;">Pronto recibirás una notificación con la respuesta a tu cotización.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . get_bloginfo( 'name' ) . '.</p>
                </div>
            </div>
        </div>
    </body>
    </html>';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    wp_mail( $customer_email, $subject, $message, $headers );
}
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Agregar submenú de ajustes
 */
add_action( 'admin_menu', 'wcbq_add_settings_page' );

function wcbq_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=quote_request', // Padre: Cotizaciones
        'Configuración de Correos',         // Título Página
        'Ajustes de Correo',                // Título Menú
        'manage_options',                   // Capacidad
        'wcbq-email-settings',              // Slug
        'wcbq_render_settings_page'         // Función Callback
    );
}

/**
 * 2. Registrar las opciones (Settings API)
 */
add_action( 'admin_init', 'wcbq_register_settings' );

function wcbq_register_settings() {
    // Grupo: Notificaciones Admin
    register_setting( 'wcbq_email_options', 'wcbq_email_admin_subject' );
    register_setting( 'wcbq_email_options', 'wcbq_email_admin_heading' );

    // Grupo: Confirmación Cliente
    register_setting( 'wcbq_email_options', 'wcbq_email_customer_received_subject' );
    register_setting( 'wcbq_email_options', 'wcbq_email_customer_received_heading' );
    register_setting( 'wcbq_email_options', 'wcbq_email_customer_received_intro' );

    // Grupo: Estados (Aprobado, Rechazado, etc)
    $statuses = array('approved', 'review', 'rejected', 'pending');
    foreach($statuses as $st) {
        register_setting( 'wcbq_email_options', 'wcbq_email_' . $st . '_subject' );
        register_setting( 'wcbq_email_options', 'wcbq_email_' . $st . '_intro' );
    }
}

/**
 * 3. Renderizar la página HTML
 */
function wcbq_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>📧 Configuración de Plantillas de Correo</h1>
        <p>Edita los textos de los correos automáticos. Deja los campos vacíos para usar los valores por defecto.</p>
        
        <form method="post" action="options.php">
            <?php settings_fields( 'wcbq_email_options' ); ?>
            <?php do_settings_sections( 'wcbq_email_options' ); ?>

            <hr>
            <h2>1. Notificación al Administrador (Nueva Solicitud)</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Asunto del Correo</th>
                    <td><input type="text" name="wcbq_email_admin_subject" value="<?php echo esc_attr( get_option('wcbq_email_admin_subject') ); ?>" class="regular-text" placeholder="Por defecto: ✦ NUEVA SOLICITUD DE COTIZACIÓN"></td>
                </tr>
                <tr>
                    <th scope="row">Título Principal (H1)</th>
                    <td><input type="text" name="wcbq_email_admin_heading" value="<?php echo esc_attr( get_option('wcbq_email_admin_heading') ); ?>" class="regular-text" placeholder="Por defecto: NUEVA SOLICITUD"></td>
                </tr>
            </table>

            <hr>
            <h2>2. Confirmación al Cliente (Recibido)</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Asunto del Correo</th>
                    <td><input type="text" name="wcbq_email_customer_received_subject" value="<?php echo esc_attr( get_option('wcbq_email_customer_received_subject') ); ?>" class="regular-text" placeholder="Por defecto: ✦ SOLICITUD RECIBIDA"></td>
                </tr>
                <tr>
                    <th scope="row">Título Principal (H1)</th>
                    <td><input type="text" name="wcbq_email_customer_received_heading" value="<?php echo esc_attr( get_option('wcbq_email_customer_received_heading') ); ?>" class="regular-text" placeholder="Por defecto: SOLICITUD RECIBIDA"></td>
                </tr>
                <tr>
                    <th scope="row">Texto Introductorio</th>
                    <td><textarea name="wcbq_email_customer_received_intro" rows="3" class="large-text" placeholder="Gracias por contactarnos. Hemos recibido tu solicitud..."><?php echo esc_textarea( get_option('wcbq_email_customer_received_intro') ); ?></textarea></td>
                </tr>
            </table>

            <hr>
            <h2>3. Actualizaciones de Estado (Cliente)</h2>
            
            <h3 style="background:#e8f5e9; padding:10px;">✅ Cotización Aprobada</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Asunto</th>
                    <td><input type="text" name="wcbq_email_approved_subject" value="<?php echo esc_attr( get_option('wcbq_email_approved_subject') ); ?>" class="regular-text" placeholder="COTIZACIÓN APROBADA"></td>
                </tr>
                <tr>
                    <th scope="row">Mensaje Intro</th>
                    <td><textarea name="wcbq_email_approved_intro" rows="3" class="large-text" placeholder="Nos complace informarte que tu cotización ha sido aprobada..."><?php echo esc_textarea( get_option('wcbq_email_approved_intro') ); ?></textarea></td>
                </tr>
            </table>

            <h3 style="background:#e3f2fd; padding:10px;">En Revisión</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Asunto</th>
                    <td><input type="text" name="wcbq_email_review_subject" value="<?php echo esc_attr( get_option('wcbq_email_review_subject') ); ?>" class="regular-text" placeholder="EN REVISIÓN"></td>
                </tr>
                <tr>
                    <th scope="row">Mensaje Intro</th>
                    <td><textarea name="wcbq_email_review_intro" rows="3" class="large-text" placeholder="Tu solicitud está siendo revisada..."><?php echo esc_textarea( get_option('wcbq_email_review_intro') ); ?></textarea></td>
                </tr>
            </table>

            <h3 style="background:#ffebee; padding:10px;">Rechazada</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Asunto</th>
                    <td><input type="text" name="wcbq_email_rejected_subject" value="<?php echo esc_attr( get_option('wcbq_email_rejected_subject') ); ?>" class="regular-text" placeholder="SOLICITUD RECHAZADA"></td>
                </tr>
                <tr>
                    <th scope="row">Mensaje Intro</th>
                    <td><textarea name="wcbq_email_rejected_intro" rows="3" class="large-text" placeholder="Lamentamos informarte que no podemos proceder..."><?php echo esc_textarea( get_option('wcbq_email_rejected_intro') ); ?></textarea></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
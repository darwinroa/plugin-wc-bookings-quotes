<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enganchamos nuestro botón y modal
add_action( 'woocommerce_after_add_to_cart_button', 'wcbq_render_quote_button_and_modal', 20 );

function wcbq_render_quote_button_and_modal() {
    global $product;
    if ( ! $product || ! $product->is_type( 'booking' ) ) {
        return;
    }

    $user = wp_get_current_user();
    $is_logged_in = $user->exists();
    
    // Pre-llenado de datos si existe usuario
    $default_name  = $is_logged_in ? $user->display_name : '';
    $default_email = $is_logged_in ? $user->user_email : '';
    $default_phone = $is_logged_in ? get_user_meta( $user->ID, 'billing_phone', true ) : ''; // Intento de obtener teléfono del perfil
    ?>

    <button type="button" 
            id="wcbq-trigger-btn" 
            class="button alt wcbq-btn-disabled" 
            style="margin-top:10px; width:100%; background-color:#ccc; cursor:not-allowed;"
            disabled>
        Selecciona fecha para Cotizar
    </button>

    <input type="hidden" name="wcbq_request_quote" value="1">
    <input type="hidden" name="wcbq_final_name" id="wcbq_final_name">
    <input type="hidden" name="wcbq_final_email" id="wcbq_final_email">
    <input type="hidden" name="wcbq_final_phone" id="wcbq_final_phone">
    <input type="hidden" name="wcbq_final_notes" id="wcbq_final_notes">

    <div id="wcbq-modal-overlay" class="wcbq-modal-overlay" style="display:none;">
        <div class="wcbq-modal-content">
            <span id="wcbq-close-modal" class="wcbq-close">&times;</span>
            
            <h3>📋 Solicitar Cotización</h3>
            <p style="font-size:0.9em; color:#666;">Completa tus datos para que el administrador te contacte.</p>
            
            <div class="wcbq-summary-box">
                <strong>Resumen de tu selección:</strong><br>
                <span id="wcbq-display-date">Fecha: ...</span><br>
                <span id="wcbq-display-persons">Personas: ...</span>
            </div>

            <div class="wcbq-form-group">
                <label>Nombre Completo *</label>
                <input type="text" id="modal_name" value="<?php echo esc_attr($default_name); ?>" <?php echo $is_logged_in ? 'readonly style="background:#f0f0f0;"' : ''; ?>>
            </div>

            <div class="wcbq-form-group">
                <label>Correo Electrónico *</label>
                <input type="email" id="modal_email" value="<?php echo esc_attr($default_email); ?>" <?php echo $is_logged_in ? 'readonly style="background:#f0f0f0;"' : ''; ?>>
            </div>

            <div class="wcbq-form-group">
                <label>Teléfono / WhatsApp *</label>
                <input type="tel" id="modal_phone" placeholder="+51 999 999 999" value="<?php echo esc_attr($default_phone); ?>">
            </div>

            <div class="wcbq-form-group">
                <label>Mensaje / Requerimientos Especiales *</label>
                <textarea id="modal_notes" rows="3" placeholder="Ej: Alergias, tipo de evento..."></textarea>
            </div>

            <button type="button" id="wcbq-submit-final" class="button alt" style="width:100%; margin-top:10px;">
                Confirmar y Enviar Solicitud
            </button>
        </div>
    </div>

    <style>
        .wcbq-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 99999;
            display: flex; justify-content: center; align-items: center;
        }
        .wcbq-modal-content {
            background: #fff; padding: 25px; border-radius: 8px;
            width: 90%; max-width: 500px; position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            max-height: 90vh; overflow-y: auto;
        }
        .wcbq-close {
            position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #999;
        }
        .wcbq-summary-box {
            background: #eef2f7; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #3c85f5;
        }
        .wcbq-form-group { margin-bottom: 12px; text-align: left; }
        .wcbq-form-group label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 0.9em; }
        .wcbq-form-group input, .wcbq-form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        
        /* Estilos botón deshabilitado vs habilitado */
        .wcbq-btn-disabled { background-color: #ccc !important; color: #666 !important; pointer-events: none; }
        .wcbq-btn-ready { background-color: #333 !important; color: #fff !important; cursor: pointer !important; animation: pulse 2s infinite; }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(0, 0, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0); }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        
        var $triggerBtn = $('#wcbq-trigger-btn');
        var $modal = $('#wcbq-modal-overlay');
        var $bookingForm = $('form.cart');
        
        // A. ESCUCHAR SI BOOKINGS HA CALCULADO EL COSTO (Validación de fechas)
        // WC Bookings dispara eventos o muestra la caja .wc-bookings-booking-cost
        
        // Método 1: Escuchar cambios en el DOM o eventos de bookings
        $('body').on('wc_bookings_calculated_cost', function() {
            enableQuoteButton();
        });
        
        // Método 2: Intervalo de seguridad (por si el evento falla)
        setInterval(function() {
            // Si la caja de costo es visible y no está vacía, habilitamos
            if ( $('.wc-bookings-booking-cost').is(':visible') && $('.wc-bookings-booking-cost').text().trim() !== '' ) {
                enableQuoteButton();
            } else {
                disableQuoteButton();
            }
        }, 1000);

        function enableQuoteButton() {
            $triggerBtn.removeClass('wcbq-btn-disabled').addClass('wcbq-btn-ready')
                       .prop('disabled', false).text('Solicitar Cotización');
        }

        function disableQuoteButton() {
            $triggerBtn.addClass('wcbq-btn-disabled').removeClass('wcbq-btn-ready')
                       .prop('disabled', true).text('Selecciona fecha para Cotizar');
        }

        // B. ABRIR MODAL Y CAPTURAR DATOS
        $triggerBtn.on('click', function() {
            // Capturar Fecha (Intentamos leer inputs ocultos de WC Bookings)
            var year = $('[name="wc_bookings_field_start_date_year"]').val();
            var month = $('[name="wc_bookings_field_start_date_month"]').val();
            var day = $('[name="wc_bookings_field_start_date_day"]').val();
            var dateStr = day + '/' + month + '/' + year;
            
            // Capturar Personas
            var persons = $('[name="wc_bookings_field_persons"]').val();
            if(!persons) persons = 0; 

            // Mostrar en el resumen del modal
            $('#wcbq-display-date').text('📅 Fecha: ' + dateStr);
            $('#wcbq-display-persons').text('👥 Personas: ' + persons);

            $modal.fadeIn(200);
        });

        // C. CERRAR MODAL
        $('#wcbq-close-modal').on('click', function() {
            $modal.fadeOut(200);
        });

        // D. ENVIAR FORMULARIO FINAL
        $('#wcbq-submit-final').on('click', function() {
            
            // 1. Obtener valores del modal
            var name = $('#modal_name').val().trim();
            var email = $('#modal_email').val().trim();
            var phone = $('#modal_phone').val().trim();
            var notes = $('#modal_notes').val().trim();

            // 2. Validación JS simple
            if( name === '' || email === '' || phone === '' || notes === '' ) {
                alert('Por favor, completa todos los campos obligatorios (*).');
                return;
            }

            // 3. Pasar valores a inputs ocultos del formulario principal
            $('#wcbq_final_name').val(name);
            $('#wcbq_final_email').val(email);
            $('#wcbq_final_phone').val(phone);
            $('#wcbq_final_notes').val(notes);

            // 4. Cambiar texto del botón para feedback
            $(this).text('Enviando...').prop('disabled', true);

            // 5. Enviar el formulario real de WooCommerce
            // Eliminamos el target del botón nativo para que no choque
            $bookingForm.trigger('submit');
        });
    });
    </script>
    <?php
}
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook our button and modal
add_action( 'woocommerce_after_add_to_cart_button', 'wcbq_render_quote_button_and_modal', 20 );

function wcbq_render_quote_button_and_modal() {
    global $product;
    if ( ! $product || ! $product->is_type( 'booking' ) ) {
        return;
    }

    $user = wp_get_current_user();
    $is_logged_in = $user->exists();
    
    // Pre-fill data if user exists
    $default_name  = $is_logged_in ? $user->display_name : '';
    $default_email = $is_logged_in ? $user->user_email : '';
    $default_phone = $is_logged_in ? get_user_meta( $user->ID, 'billing_phone', true ) : ''; // Try to get phone from profile
    ?>

    <button type="button" 
            id="wcbq-trigger-btn" 
            class="button alt wcbq-btn-disabled" 
            style="margin-top:10px; width:100%; background-color:#ccc; cursor:not-allowed;"
            disabled>
        <?php esc_html_e( 'Select date to Request Quote', 'wc-bookings-quotes' ); ?>
    </button>

    <input type="hidden" name="wcbq_request_quote" value="1">
    <input type="hidden" name="wcbq_final_name" id="wcbq_final_name">
    <input type="hidden" name="wcbq_final_email" id="wcbq_final_email">
    <input type="hidden" name="wcbq_final_phone" id="wcbq_final_phone">
    <input type="hidden" name="wcbq_final_notes" id="wcbq_final_notes">

    <div id="wcbq-modal-overlay" class="wcbq-modal-overlay" style="display:none;">
        <div class="wcbq-modal-content">
            <span id="wcbq-close-modal" class="wcbq-close">&times;</span>
            
            <h3><?php esc_html_e( '📋 Request Quote', 'wc-bookings-quotes' ); ?></h3>
            <p style="font-size:0.9em; color:#666;"><?php esc_html_e( 'Complete your details so the administrator can contact you.', 'wc-bookings-quotes' ); ?></p>
            
            <div class="wcbq-summary-box">
                <strong><?php esc_html_e( 'Selection Summary:', 'wc-bookings-quotes' ); ?></strong><br>
                <span id="wcbq-display-date"><?php esc_html_e( 'Date:', 'wc-bookings-quotes' ); ?> ...</span><br>
                <span id="wcbq-display-persons"><?php esc_html_e( 'People:', 'wc-bookings-quotes' ); ?> ...</span>
            </div>

            <div class="wcbq-form-group">
                <label><?php esc_html_e( 'Full Name *', 'wc-bookings-quotes' ); ?></label>
                <input type="text" id="modal_name" value="<?php echo esc_attr($default_name); ?>" <?php echo $is_logged_in ? 'readonly style="background:#f0f0f0;"' : ''; ?>>
            </div>

            <div class="wcbq-form-group">
                <label><?php esc_html_e( 'Email Address *', 'wc-bookings-quotes' ); ?></label>
                <input type="email" id="modal_email" value="<?php echo esc_attr($default_email); ?>" <?php echo $is_logged_in ? 'readonly style="background:#f0f0f0;"' : ''; ?>>
            </div>

            <div class="wcbq-form-group">
                <label><?php esc_html_e( 'Phone / WhatsApp *', 'wc-bookings-quotes' ); ?></label>
                <input type="tel" id="modal_phone" placeholder="+1 555 123 4567" value="<?php echo esc_attr($default_phone); ?>">
            </div>

            <div class="wcbq-form-group">
                <label><?php esc_html_e( 'Message / Special Requirements *', 'wc-bookings-quotes' ); ?></label>
                <textarea id="modal_notes" rows="3" placeholder="<?php esc_attr_e( 'Ex: Allergies, event type...', 'wc-bookings-quotes' ); ?>"></textarea>
            </div>

            <button type="button" id="wcbq-submit-final" class="button alt" style="width:100%; margin-top:10px;">
                <?php esc_html_e( 'Confirm and Send Request', 'wc-bookings-quotes' ); ?>
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
        
        /* Disabled vs Enabled button styles */
        .wcbq-btn-disabled { background-color: #ccc !important; color: #666 !important; pointer-events: none; }
        .wcbq-btn-ready { background-color: #333 !important; color: #fff !important; cursor: pointer !important; animation: pulse 2s infinite; }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(0, 0, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0); }
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        var $triggerBtn = $('#wcbq-trigger-btn');
        var $modal = $('#wcbq-modal-overlay');
        var $bookingForm = $('form.cart');
        
        // Localization strings for JS
        var txt_request_quote = "<?php echo esc_js( __( 'Request Quote', 'wc-bookings-quotes' ) ); ?>";
        var txt_select_date = "<?php echo esc_js( __( 'Select date to Request Quote', 'wc-bookings-quotes' ) ); ?>";
        var txt_sending = "<?php echo esc_js( __( 'Sending...', 'wc-bookings-quotes' ) ); ?>";
        var txt_date_label = "<?php echo esc_js( __( 'Date:', 'wc-bookings-quotes' ) ); ?>";
        var txt_people_label = "<?php echo esc_js( __( 'People:', 'wc-bookings-quotes' ) ); ?>";
        var txt_error_fields = "<?php echo esc_js( __( 'Please complete all required fields (*).', 'wc-bookings-quotes' ) ); ?>";

        // A. LISTEN IF BOOKINGS HAS CALCULATED THE COST (Date validation)
        // WC Bookings triggers events or shows the .wc-bookings-booking-cost box
        
        // Method 1: Listen to DOM changes or booking events
        $('body').on('wc_bookings_calculated_cost', function() {
            enableQuoteButton();
        });
        
        // Method 2: Safety interval (in case the event fails)
        setInterval(function() {
            // If the cost box is visible and not empty, enable
            if ( $('.wc-bookings-booking-cost').is(':visible') && $('.wc-bookings-booking-cost').text().trim() !== '' ) {
                enableQuoteButton();
            } else {
                disableQuoteButton();
            }
        }, 1000);

        function enableQuoteButton() {
            $triggerBtn.removeClass('wcbq-btn-disabled').addClass('wcbq-btn-ready')
                       .prop('disabled', false).text(txt_request_quote);
        }

        function disableQuoteButton() {
            $triggerBtn.addClass('wcbq-btn-disabled').removeClass('wcbq-btn-ready')
                       .prop('disabled', true).text(txt_select_date);
        }

        // B. OPEN MODAL AND CAPTURE DATA
        $triggerBtn.on('click', function() {
            // Capture Date (Try to read hidden inputs from WC Bookings)
            var year = $('[name="wc_bookings_field_start_date_year"]').val();
            var month = $('[name="wc_bookings_field_start_date_month"]').val();
            var day = $('[name="wc_bookings_field_start_date_day"]').val();
            var dateStr = day + '/' + month + '/' + year;
            
            // Capture People
            var persons = $('[name="wc_bookings_field_persons"]').val();
            if(!persons) persons = 0; 

            // Show in modal summary
            $('#wcbq-display-date').text('📅 ' + txt_date_label + ' ' + dateStr);
            $('#wcbq-display-persons').text('👥 ' + txt_people_label + ' ' + persons);

            $modal.fadeIn(200);
        });

        // C. CLOSE MODAL
        $('#wcbq-close-modal').on('click', function() {
            $modal.fadeOut(200);
        });

        // D. SUBMIT FINAL FORM
        $('#wcbq-submit-final').on('click', function() {
            
            // 1. Get values from modal
            var name = $('#modal_name').val().trim();
            var email = $('#modal_email').val().trim();
            var phone = $('#modal_phone').val().trim();
            var notes = $('#modal_notes').val().trim();

            // 2. Simple JS validation
            if( name === '' || email === '' || phone === '' || notes === '' ) {
                alert(txt_error_fields);
                return;
            }

            // 3. Pass values to hidden inputs in the main form
            $('#wcbq_final_name').val(name);
            $('#wcbq_final_email').val(email);
            $('#wcbq_final_phone').val(phone);
            $('#wcbq_final_notes').val(notes);

            // 4. Change button text for feedback
            $(this).text(txt_sending).prop('disabled', true);

            // 5. Submit the real WooCommerce form
            // Remove target from native button to avoid conflicts
            $bookingForm.trigger('submit');
        });
    });
    </script>
    <?php
}
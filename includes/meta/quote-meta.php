<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wcbq_register_quote_meta() {

    $meta_fields = array(
        '_quote_product_id' => 'integer',
        '_quote_booking_start' => 'integer',
        '_quote_booking_end' => 'integer',
        '_quote_people' => 'integer',
        '_quote_price' => 'number',
        '_quote_booking_id' => 'integer',
        '_quote_order_id' => 'integer',
    );

    foreach ( $meta_fields as $key => $type ) {
        register_post_meta( 'quote_request', $key, array(
            'type'              => $type,
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
            'show_in_rest'      => false,
        ));
    }

    register_post_meta( 'quote_request', '_quote_notes', array(
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_textarea_field',
        'show_in_rest'      => false,
    ));
}
add_action( 'init', 'wcbq_register_quote_meta' );

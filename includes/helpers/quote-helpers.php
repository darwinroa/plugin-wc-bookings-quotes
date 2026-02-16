<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wcbq_get_quote( $quote_id ) {
    $post = get_post( $quote_id );
    if ( ! $post || $post->post_type !== 'quote_request' ) {
        return false;
    }
    return $post;
}

function wcbq_update_quote_status( $quote_id, $new_status ) {

    $allowed = array(
        'quote-pending',
        'quote-review',
        'quote-approved',
        'quote-rejected',
        'quote-expired',
    );

    if ( ! in_array( $new_status, $allowed, true ) ) {
        return false;
    }

    return wp_update_post( array(
        'ID'          => $quote_id,
        'post_status' => $new_status,
    ) );
}

function wcbq_get_quote_status_label( $quote_id ) {
    $post = wcbq_get_quote( $quote_id );
    if ( ! $post ) {
        return '';
    }

    $map = array(
        'quote-pending' => 'Pendiente',
        'quote-review'  => 'En revisión',
        'quote-approved'=> 'Aprobada',
        'quote-rejected'=> 'Rechazada',
        'quote-expired' => 'Expirada',
    );

    return $map[ $post->post_status ] ?? $post->post_status;
}


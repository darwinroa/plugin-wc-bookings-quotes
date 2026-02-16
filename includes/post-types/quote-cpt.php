<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wcbq_register_quote_cpt() {

    $labels = array(
        'name'               => 'Cotizaciones',
        'singular_name'      => 'Cotización',
        'menu_name'          => 'Cotizaciones',
        'name_admin_bar'     => 'Cotización',
        'add_new'            => 'Nueva cotización',
        'add_new_item'       => 'Agregar cotización',
        'edit_item'          => 'Editar cotización',
        'new_item'           => 'Nueva cotización',
        'view_item'          => 'Ver cotización',
        'search_items'       => 'Buscar cotizaciones',
        'not_found'          => 'No se encontraron cotizaciones',
        'not_found_in_trash' => 'No hay cotizaciones en la papelera'
    );

    $args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-clipboard',
			'supports'           => array( 'title' ),
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
		);


    register_post_type( 'quote_request', $args );
}
add_action( 'init', 'wcbq_register_quote_cpt' );


add_filter( 'wp_insert_post_data', 'wcbq_force_initial_status', 10, 2 );

function wcbq_force_initial_status( $data, $postarr ) {

    if ( $data['post_type'] === 'quote_request' ) {

        if ( $postarr['ID'] === 0 ) {
            $data['post_status'] = 'quote-pending';
        }
    }

    return $data;
}

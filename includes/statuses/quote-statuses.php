<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wcbq_register_quote_statuses() {

    $statuses = array(
        'quote-pending' => 'Pendiente',
        'quote-review'  => 'En revisión',
        'quote-approved'=> 'Aprobada',
        'quote-rejected'=> 'Rechazada',
        'quote-expired' => 'Expirada',
    );

    foreach ( $statuses as $status => $label ) {
        register_post_status( $status, array(
            'label'                     => $label,
            'public'                    => true,
            'internal'                  => false,
            'protected'                 => false,
            'private'                   => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                "$label <span class='count'>(%s)</span>",
                "$label <span class='count'>(%s)</span>"
            ),
        ));

    }
}
add_action( 'init', 'wcbq_register_quote_statuses' );


function wcbq_add_statuses_to_dropdown() {
    global $post;
    if ( ! $post || $post->post_type !== 'quote_request' ) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const select = document.querySelector('#post_status');
            if (!select) return;

            const statuses = {
                'quote-pending': 'Pendiente',
                'quote-review': 'En revisión',
                'quote-approved': 'Aprobada',
                'quote-rejected': 'Rechazada',
                'quote-expired': 'Expirada'
            };

            Object.entries(statuses).forEach(([value, label]) => {
                if (!select.querySelector(`option[value="${value}"]`)) {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = label;
                    select.appendChild(opt);
                }
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer-post.php', 'wcbq_add_statuses_to_dropdown' );
add_action( 'admin_footer-post-new.php', 'wcbq_add_statuses_to_dropdown' );


add_action( 'admin_footer-edit.php', 'wcbq_quick_edit_statuses' );

function wcbq_quick_edit_statuses() {
    global $post_type;

    if ( $post_type !== 'quote_request' ) {
        return;
    }
    ?>
    <script>
        (function () {
            const statuses = {
                'quote-pending': 'Pendiente',
                'quote-review': 'En revisión',
                'quote-approved': 'Aprobada',
                'quote-rejected': 'Rechazada',
                'quote-expired': 'Expirada'
            };

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.editinline')) return;

                setTimeout(() => {
                    const select = document.querySelector('select[name="_status"]');
                    if (!select) return;

                    Object.entries(statuses).forEach(([value, label]) => {
                        if (!select.querySelector(`option[value="${value}"]`)) {
                            const opt = document.createElement('option');
                            opt.value = value;
                            opt.textContent = label;
                            select.appendChild(opt);
                        }
                    });
                }, 50);
            });
        })();
    </script>
    <?php
}


add_filter( 'display_post_states', 'wcbq_show_custom_status_label', 10, 2 );

function wcbq_show_custom_status_label( $states, $post ) {

    if ( $post->post_type !== 'quote_request' ) {
        return $states;
    }

    $labels = array(
        'quote-pending' => 'Pendiente',
        'quote-review'  => 'En revisión',
        'quote-approved'=> 'Aprobada',
        'quote-rejected'=> 'Rechazada',
        'quote-expired' => 'Expirada',
    );

    if ( isset( $labels[ $post->post_status ] ) ) {
        $states[] = $labels[ $post->post_status ];
    }

    return $states;
}

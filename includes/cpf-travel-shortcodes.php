<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cpf_travel_user_trips_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<p>Faça login para ver suas viagens.</p>';
    }
    $atts = shortcode_atts([ 'per_page' => 10, 'page' => 1 ], $atts, 'user_trips');
    $user_id = get_current_user_id();
    $trips = cpf_travel_get_bookings( $user_id, [ 'per_page' => intval($atts['per_page']), 'page' => intval($atts['page']) ] );

    if ( empty( $trips ) ) {
        return '<p>Você ainda não possui viagens cadastradas.</p>';
    }

    ob_start();
    echo '<div class="cpf-trips row">';
    foreach( $trips as $t ) {
        echo '<div class="col-md-6 mb-3">';
        echo '<div class="card p-3">';
        echo '<h4>Booking ID: ' . $t->id . '</h4>';

        if ( ! empty($t->segments) ) {
            echo '<hr>';
            foreach ($t->segments as $segment) {
                echo '<p><strong>Flight:</strong> ' . esc_html($segment->flight_code) . ' <small class="text-muted">' . esc_html($segment->airline) . '</small></p>';
                echo '<p><strong>Origin:</strong> ' . esc_html($segment->origin) . ' <strong>Destination:</strong> ' . esc_html($segment->destination) . '</p>';
                if ( $segment->departure ) echo '<p><strong>Departure:</strong> ' . esc_html( date_i18n('d/m/Y H:i', strtotime($segment->departure)) ) . '</p>';
                if ( $segment->arrival ) echo '<p><strong>Arrival:</strong> ' . esc_html( date_i18n('d/m/Y H:i', strtotime($segment->arrival)) ) . '</p>';
                echo '<hr>';
            }
        }

        echo '<p><strong>Status:</strong> ' . esc_html( $t->status ) . '</p>';
        echo '</div></div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('user_trips', 'cpf_travel_user_trips_shortcode');

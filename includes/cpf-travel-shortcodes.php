<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cpf_travel_user_trips_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<p>Faça login para ver suas viagens.</p>';
    }
    $atts = shortcode_atts([ 'per_page' => 10, 'page' => 1 ], $atts, 'user_trips');
    $cpf = get_user_meta(get_current_user_id(), 'cpf', true);
    $trips = cpf_travel_get_bookings([ 'cpf' => $cpf, 'per_page' => intval($atts['per_page']), 'page' => intval($atts['page']) ]);

    if ( empty( $trips ) ) {
        return '<p>Você ainda não possui viagens cadastradas.</p>';
    }

    $airports = cpf_travel_get_airports();

    ob_start();
    echo '<div class="cpf-trips row">';
    foreach( $trips as $t ) {
        echo '<div class="col-md-6 mb-3">';
        echo '<div class="card p-3">';
        echo '<h4>Booking ID: ' . $t->id . '</h4>';

        if ( ! empty($t->segments) ) {
            echo '<hr>';
            foreach ($t->segments as $segment) {
                $origin_name = isset($airports[$segment->origin]) ? $airports[$segment->origin] : $segment->origin;
                $destination_name = isset($airports[$segment->destination]) ? $airports[$segment->destination] : $segment->destination;

                echo '<div class="flight-segment-block">';
                echo '<div class="flight-info-group">';
                echo '<div class="flight-code-information"><strong>Flight:</strong> ' . esc_html($segment->flight_code) . '</div>';
                echo '<div class="airline-information"><small class="text-muted">' . esc_html($segment->airline) . '</small></div>';
                echo '</div>';
                echo '<div class="flight-info-group">';
                echo '<div class="origin-information"><strong>Origin:</strong> ' . esc_html($origin_name) . '</div>';
                if ( $segment->departure ) echo '<div class="departure-information"><strong>Departure:</strong> ' . esc_html( date_i18n('d/m/Y H:i', strtotime($segment->departure)) ) . '</div>';
                echo '</div>';
                echo '<div class="flight-info-group">';
                echo '<div class="destination-information"><strong>Destination:</strong> ' . esc_html($destination_name) . '</div>';
                if ( $segment->arrival ) echo '<div class="arrival-information"><strong>Arrival:</strong> ' . esc_html( date_i18n('d/m/Y H:i', strtotime($segment->arrival)) ) . '</div>';
                echo '</div>';
                echo '</div>';

            }
        }

        echo '<p><strong>Status:</strong> ' . esc_html( $t->status ) . '</p>';
        echo '</div></div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('user_trips', 'cpf_travel_user_trips_shortcode');

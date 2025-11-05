<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_cpf_travel_add_booking', 'cpf_travel_add_booking_ajax');
function cpf_travel_add_booking_ajax() {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Acesso negado.');
    }
    check_admin_referer('cpf_travel_add_nonce', 'nonce');

    $data = [
        'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : null,
        'cpf' => isset($_POST['cpf']) ? preg_replace('/\\D/','', $_POST['cpf']) : null,
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed',
    ];

    $booking_id = cpf_travel_add_booking($data);
    if ( is_wp_error($booking_id) ) {
        wp_send_json_error($booking_id->get_error_message());
    }

    if (isset($_POST['segments']) && is_array($_POST['segments'])) {
        foreach ($_POST['segments'] as $segment_data) {
            if (!empty($segment_data['flight_code'])) {
                cpf_travel_add_segment($booking_id, $segment_data);
            }
        }
    }

    wp_send_json_success(['id' => $booking_id]);
}

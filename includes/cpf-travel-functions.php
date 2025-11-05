<?

if ( ! defined( 'ABSPATH' ) ) exit;

function cpf_travel_add_booking( $data ) {
    global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';

    $cpf = isset($data['cpf']) && !empty($data['cpf']) ? preg_replace('/\D/','', $data['cpf']) : null;
    $user_id = isset($data['user_id']) && !empty($data['user_id']) ? intval($data['user_id']) : null;

    if ( $cpf && ! $user_id ) {
        $found_user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'cpf' AND meta_value = %s LIMIT 1", $cpf) );
        $user_id = $found_user_id ? (int) $found_user_id : null;
    }

    $fields = [
        'user_id' => $user_id,
        'cpf' => $cpf,
        'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'confirmed',
    ];

    $inserted = $wpdb->insert( $table, $fields );
    if ( $inserted === false ) {
        return new WP_Error('db_insert', $wpdb->last_error);
    }

    return $wpdb->insert_id;
}

function cpf_travel_add_segment($booking_id, $segment_data) {
    global $wpdb;
    $table = $wpdb->prefix . 'flight_segments';

    $fields = [
        'booking_id' => $booking_id,
        'flight_code' => isset($segment_data['flight_code']) ? sanitize_text_field($segment_data['flight_code']) : '',
        'airline' => isset($segment_data['airline']) ? sanitize_text_field($segment_data['airline']) : null,
        'origin' => isset($segment_data['origin']) ? sanitize_text_field($segment_data['origin']) : null,
        'destination' => isset($segment_data['destination']) ? sanitize_text_field($segment_data['destination']) : null,
        'departure' => isset($segment_data['departure']) ? sanitize_text_field($segment_data['departure']) : null,
        'arrival' => isset($segment_data['arrival']) ? sanitize_text_field($segment_data['arrival']) : null,
    ];

    if ( empty( $fields['flight_code'] ) ) {
        return new WP_Error('no_flight_code', 'flight_code is required for each segment.');
    }

    return $wpdb->insert( $table, $fields );
}

function cpf_travel_get_bookings( $user_id = null, $args = [] ) {
   global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';

    $defaults = [
        'per_page' => 20,
        'page' => 1,
        'order' => 'DESC'
    ];
    $args = wp_parse_args($args, $defaults);
    $offset = max(0, intval($args['page'] - 1)) * intval($args['per_page']);

    $order = in_array(strtoupper($args['order']), ['ASC','DESC']) ? strtoupper($args['order']) : 'DESC';

    if ( $user_id ) {
        $query = $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d ORDER BY departure $order LIMIT %d OFFSET %d", $user_id, intval($args['per_page']), $offset );
    }else if ( isset($args['cpf']) && ! empty($args['cpf']) ) {
        $cpf = preg_replace('/\\D/','', $args['cpf']);
        $query = $wpdb->prepare( "SELECT * FROM $table WHERE cpf = %s ORDER BY departure $order LIMIT %d OFFSET %d", $cpf, intval($args['per_page']), $offset );
    }else {
        $query = $wpdb->prepare( "SELECT * FROM $table ORDER BY departure $order LIMIT %d OFFSET %d", intval($args['per_page']), $offset );
    }

    $rows = $wpdb->get_results( $query );

    foreach ($rows as $row) {
        $row->segments = cpf_travel_get_segments($row->id);
    }

    return $rows;
}

function cpf_travel_get_segments($booking_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'flight_segments';
    $query = $wpdb->prepare("SELECT * FROM $table WHERE booking_id = %d ORDER BY departure ASC", $booking_id);
    return $wpdb->get_results($query);
}

function travel_exists($user_id = null, $args = []){
    return ! empty(cpf_travel_get_bookings($user_id, $args));
}

function cpf_travel_sync_users() {
    global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';

    $rows = $wpdb->get_results( "SELECT id, cpf FROM $table WHERE user_id IS NULL AND cpf IS NOT NULL" );
    if ( empty($rows) ) return;

    foreach ( $rows as $r ) {
        $user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'cpf' AND meta_value = %s LIMIT 1", $r->cpf) );
        if ( $user_id ) {
            $wpdb->update( $table, [ 'user_id' => $user_id ], [ 'id' => $r->id ] );
        }
    }
}

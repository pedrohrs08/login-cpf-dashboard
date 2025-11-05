<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cpf_travel_add_booking( $data ) {
    global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';

    $cpf = isset($data['cpf']) && !empty($data['cpf']) ? preg_replace('/\\D/','', $data['cpf']) : null;
    $user_id = isset($data['user_id']) && !empty($data['user_id']) ? intval($data['user_id']) : null;

    if ( $cpf && ! $user_id ) {
        $found_user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'cpf' AND meta_value = %s LIMIT 1", $cpf) );
        $user_id = $found_user_id ? (int) $found_user_id : null;
    }

    $fields = [
        'user_id' => $user_id,
        'cpf' => $cpf,
        'flight_code' => isset($data['flight_code']) ? sanitize_text_field($data['flight_code']) : '',
        'airline' => isset($data['airline']) ? sanitize_text_field($data['airline']) : null,
        'origin' => isset($data['origin']) ? sanitize_text_field($data['origin']) : null,
        'destination' => isset($data['destination']) ? sanitize_text_field($data['destination']) : null,
        'departure' => isset($data['departure']) ? sanitize_text_field($data['departure']) : null,
        'arrival' => isset($data['arrival']) ? sanitize_text_field($data['arrival']) : null,
        'return_flight_code' => isset($data['return_flight_code']) ? sanitize_text_field($data['return_flight_code']) : null,
        'return_origin' => isset($data['return_origin']) ? sanitize_text_field($data['return_origin']) : null,
        'return_destination' => isset($data['return_destination']) ? sanitize_text_field($data['return_destination']) : null,
        'return_departure' => isset($data['return_departure']) ? sanitize_text_field($data['return_departure']) : null,
        'return_arrival' => isset($data['return_arrival']) ? sanitize_text_field($data['return_arrival']) : null,
        'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'confirmed',
    ];

    if ( isset($data['stops']) ) {
        if ( is_array($data['stops']) ) {
            $fields['stops'] = wp_json_encode($data['stops']);
        } else {
            $decoded = json_decode($data['stops']);
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $fields['stops'] = wp_json_encode($decoded);
            } else {
                $raw = sanitize_text_field($data['stops']);
                $parts = preg_split('/[;\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
                $arr = [];
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (strpos($p, ':') !== false) {
                        list($local,$tempo) = array_map('trim', explode(':', $p, 2));
                        $arr[] = ['local' => $local, 'tempo' => $tempo];
                    } else {
                        $arr[] = ['local' => $p, 'tempo' => ''];
                    }
                }
                if (!empty($arr)) $fields['stops'] = wp_json_encode($arr);
                else $fields['stops'] = null;
            }
        }
    } else {
        $fields['stops'] = null;
    }

    if ( empty( $fields['flight_code'] ) ) {
        return new WP_Error('no_flight_code', 'flight_code é obrigatório.');
    }

    $inserted = $wpdb->insert( $table, $fields );
    if ( $inserted === false ) {
        return new WP_Error('db_insert', $wpdb->last_error);
    }

    return $wpdb->insert_id;
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
    return $rows;
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

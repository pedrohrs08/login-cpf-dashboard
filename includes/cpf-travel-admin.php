<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', function(){
    add_menu_page('Travel Bookings', 'Travel Bookings', 'manage_options', 'cpf-travel-bookings', 'cpf_travel_admin_page', 'dashicons-airplane', 27);
});

function cpf_travel_admin_page() {
    if ( ! current_user_can('manage_options') ) wp_die('Acesso negado.');
    $msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
    ?>
    <div class="wrap">
        <h1>Adicionar Booking</h1>
        <?php if ($msg === 'ok'): ?><div class="notice notice-success"><p>Booking adicionado com sucesso.</p></div><?php endif; ?>
        <?php if ($msg === 'error'): ?><div class="notice notice-error"><p>Erro ao adicionar booking.</p></div><?php endif; ?>
        <?php if ($msg === 'user_not_found'): ?><div class="notice notice-error"><p>Usuário não encontrado para o CPF informado.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('cpf_travel_add_nonce'); ?>
            <input type="hidden" name="action" value="cpf_travel_add_booking_form" />
            <table class="form-table">
                <tr><th><label for="cpf">CPF (ou User ID):
                </label></th>
                    <td><input type="text" name="cpf" id="cpf" /> ou <input type="text" name="user_id" id="user_id" /></td></tr>
                <tr><th><label for="flight_code">Flight code</label></th><td><input type="text" name="flight_code" id="flight_code" required /></td></tr>
                <tr><th><label for="airline">Airline</label></th><td><input type="text" name="airline" id="airline" /></td></tr>
                <tr><th><label for="origin">Origin</label></th><td><input type="text" name="origin" id="origin" /></td></tr>
                <tr><th><label for="destination">Destination</label></th><td><input type="text" name="destination" id="destination" /></td></tr>                
                <tr><th><label for="departure">Departure</label></th><td><input type="datetime-local" name="departure" id="departure" /></td></tr>
                <tr><th><label for="arrival">Arrival</label></th><td><input type="datetime-local" name="arrival" id="arrival" /></td></tr>
                <tr><td><hr></td></tr>
                <tr><th><label for="return_flight_code">Return Flight code</label></th><td><input type="text" name="return_flight_code" id="return_flight_code"></td></tr>
                <tr><th><label for="return_origin">Return Origin</label></th><td><input type="text" name="return_origin" id="return_origin"></td></tr>
                <tr><th><label for="return_destination">Return Destination</label></th><td><input type="text" name="return_destination" id="return_destination"></td></tr>
                <tr><th><label for="return_departure">Return Departure</label></th><td><input type="datetime-local" name="return_departure" id="return_departure"></td></tr>
                <tr><th><label for="return_arrival">Return Arrival</label></th><td><input type="datetime-local" name="return_arrival" id="return_arrival"></td></tr>
                <tr><td><hr></td></tr>
                <tr><th><label for="stops">Stops</label></th>
                <td><textarea name="stops" id="stops" rows="4" cols="50" placeholder='Ex: [{"local":"LIS","tempo":"1h30"},{"local":"MAD","tempo":"2h"}] OR LIS:1h30;MAD:2h'></textarea></td></tr>
                <tr><td><hr></td></tr>
                <tr><th><label for="status">Status</label></th><td><input type="text" name="status" id="status" value="confirmed" /></td></tr>
            </table>
            <?php submit_button('Adicionar Booking'); ?>
        </form>
    </div>
    <?php
}

add_action('admin_post_cpf_travel_add_booking_form', 'cpf_travel_admin_handle_form');
function cpf_travel_admin_handle_form() {
    if ( ! current_user_can('manage_options') ) wp_die('Acesso negado.');
    check_admin_referer('cpf_travel_add_nonce');

    $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $cpf = isset($_POST['cpf']) && !empty($_POST['cpf']) ? preg_replace('/\D/', '', $_POST['cpf']) : '';

    if ( empty($user_id) && empty($cpf) ) {
        wp_redirect(add_query_arg('msg','missing', admin_url('admin.php?page=cpf-travel-bookings')));
        exit;
    }

    if ( empty($user_id) && ! empty($cpf) ) {
        $uq = new WP_User_Query([ 'meta_key' => 'cpf', 'meta_value' => $cpf, 'number' => 1 ]);
        $users = $uq->get_results();
        if ( empty($users) ) {
            $user_id = null;
        }else{
            $user_id = $users[0]->ID;
        }
    }

    $data = [
        'user_id' => $user_id,
        'cpf' => $cpf ? $cpf : null,
        'flight_code' => isset($_POST['flight_code']) ? sanitize_text_field($_POST['flight_code']) : '',
        'airline' => isset($_POST['airline']) ? sanitize_text_field($_POST['airline']) : '',
        'origin' => isset($_POST['origin']) ? sanitize_text_field($_POST['origin']) : '',
        'destination' => isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '',
        'departure' => isset($_POST['departure']) ? sanitize_text_field($_POST['departure']) : null,
        'arrival' => isset($_POST['arrival']) ? sanitize_text_field($_POST['arrival']) : null,
        'return_flight_code' => isset($_POST['return_flight_code']) ? sanitize_text_field($_POST['return_flight_code']) : null,
        'return_origin' => isset($_POST['return_origin']) ? sanitize_text_field($_POST['return_origin']) : null,
        'return_destination' => isset($_POST['return_destination']) ? sanitize_text_field($_POST['return_destination']) : null,
        'return_departure' => isset($_POST['return_departure']) ? sanitize_text_field($_POST['return_departure']) : null,
        'return_arrival' => isset($_POST['return_arrival']) ? sanitize_text_field($_POST['return_arrival']) : null,
        'stops' => isset($_POST['stops']) ? sanitize_textarea_field($_POST['stops']) : null,
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed',
    ];

    $insert = cpf_travel_add_booking($user_id, $data);
    if ( is_wp_error($insert) ) {
        $url = admin_url('admin.php?page=cpf-travel-bookings');
        $url = add_query_arg('msg', 'error', $url);
        $url = add_query_arg('code', $insert->get_error_code(), $url);
        $url = add_query_arg('message', $insert->get_error_message(), $url);    
        wp_redirect($url);
        exit;
    }

    wp_redirect(add_query_arg('msg','ok', admin_url('admin.php?page=cpf-travel-bookings')));
    exit;
}

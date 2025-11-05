<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', function(){
    add_menu_page('Travel Bookings', 'Travel Bookings', 'manage_options', 'cpf-travel-bookings', 'cpf_travel_admin_page', 'dashicons-airplane', 27);
});


function cpf_travel_admin_page() {
    if ( ! current_user_can('manage_options') ) wp_die('Acesso negado.');

    $msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    $action = $booking_id ? 'edit' : 'add';

    $booking = null;
    $segments = [];
    if ($booking_id) {
        $booking = cpf_travel_get_booking_by_id($booking_id);
        $segments = cpf_travel_get_segments($booking_id);
    }

    ?>
    <div class="wrap">
        <h1><?php echo $booking_id ? 'Editar Booking' : 'Adicionar Booking'; ?></h1>

        <?php if ($msg === 'ok'): ?><div class="notice notice-success"><p>Booking salvo com sucesso.</p></div><?php endif; ?>
        <?php if ($msg === 'error'): ?><div class="notice notice-error"><p>Erro ao salvar booking.</p></div><?php endif; ?>
        <?php if ($msg === 'deleted'): ?><div class="notice notice-success"><p>Booking deletado com sucesso.</p></div><?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('cpf_travel_add_nonce'); ?>
            <input type="hidden" name="action" value="cpf_travel_add_booking_form" />
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>" />

            <table class="form-table">
                <tr>
                    <th><label for="cpf">CPF (ou User ID):</label></th>
                    <td>
                        <input type="text" name="cpf" id="cpf" value="<?php echo $booking ? esc_attr($booking->cpf) : ''; ?>" /> ou 
                        <input type="text" name="user_id" id="user_id" value="<?php echo $booking ? esc_attr($booking->user_id) : ''; ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td><input type="text" name="status" id="status" value="<?php echo $booking ? esc_attr($booking->status) : 'confirmed'; ?>" /></td>
                </tr>
            </table>

            <h2>Segmentos de Voo</h2>
            <div id="flight-segments-wrapper">
                <?php if (empty($segments)) : ?>
                <div class="flight-segment">
                    <table class="form-table">
                        <tr><th><label>Flight code</label></th><td><input type="text" name="segments[0][flight_code]" required /></td></tr>
                        <tr><th><label>Airline</label></th><td><input type="text" name="segments[0][airline]" /></td></tr>
                        <tr><th><label>Origin</label></th><td><input type="text" name="segments[0][origin]" /></td></tr>
                        <tr><th><label>Destination</label></th><td><input type="text" name="segments[0][destination]" /></td></tr>
                        <tr><th><label>Departure</label></th><td><input type="datetime-local" name="segments[0][departure]" /></td></tr>
                        <tr><th><label>Arrival</label></th><td><input type="datetime-local" name="segments[0][arrival]" /></td></tr>
                    </table>
                    <button type="button" class="button remove-segment">Remover Segmento</button>
                    <hr>
                </div>
                <?php else : ?>
                    <?php foreach ($segments as $i => $segment) : ?>
                    <div class="flight-segment">
                        <table class="form-table">
                            <tr><th><label>Flight code</label></th><td><input type="text" name="segments[<?php echo $i; ?>][flight_code]" value="<?php echo esc_attr($segment->flight_code); ?>" required /></td></tr>
                            <tr><th><label>Airline</label></th><td><input type="text" name="segments[<?php echo $i; ?>][airline]" value="<?php echo esc_attr($segment->airline); ?>" /></td></tr>
                            <tr><th><label>Origin</label></th><td><input type="text" name="segments[<?php echo $i; ?>][origin]" value="<?php echo esc_attr($segment->origin); ?>" /></td></tr>
                            <tr><th><label>Destination</label></th><td><input type="text" name="segments[<?php echo $i; ?>][destination]" value="<?php echo esc_attr($segment->destination); ?>" /></td></tr>
                            <tr><th><label>Departure</label></th><td><input type="datetime-local" name="segments[<?php echo $i; ?>][departure]" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($segment->departure))); ?>" /></td></tr>
                            <tr><th><label>Arrival</label></th><td><input type="datetime-local" name="segments[<?php echo $i; ?>][arrival]" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($segment->arrival))); ?>" /></td></tr>
                        </table>
                        <button type="button" class="button remove-segment">Remover Segmento</button>
                        <hr>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add-segment" class="button">Adicionar Segmento</button>
            
            <?php submit_button($booking_id ? 'Salvar Alterações' : 'Adicionar Booking'); ?>
        </form>

        <h2>Bookings</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CPF</th>
                    <th>User ID</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $all_bookings = cpf_travel_get_bookings();
                foreach ($all_bookings as $b) {
                    echo '<tr>';
                    echo '<td>' . $b->id . '</td>';
                    echo '<td>' . esc_html($b->cpf) . '</td>';
                    echo '<td>' . $b->user_id . '</td>';
                    echo '<td>' . esc_html($b->status) . '</td>';
                    echo '<td>';
                    echo '<a href="' . esc_url(add_query_arg(['page' => 'cpf-travel-bookings', 'booking_id' => $b->id], admin_url('admin.php'))) . '">Editar</a> | ';
                    echo '<a href="' . esc_url(wp_nonce_url(add_query_arg(['action' => 'cpf_travel_delete_booking', 'booking_id' => $b->id], admin_url('admin-post.php')), 'cpf_travel_delete_nonce')) . '" onclick="return confirm(\'Tem certeza que deseja deletar este booking?\')">Deletar</a>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

add_action('admin_post_cpf_travel_add_booking_form', 'cpf_travel_admin_handle_form');

function cpf_travel_get_booking_by_id($booking_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $booking_id));
}

function cpf_travel_admin_handle_form() {
    if ( ! current_user_can('manage_options') ) wp_die('Acesso negado.');
    check_admin_referer('cpf_travel_add_nonce');

    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

    $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $cpf = isset($_POST['cpf']) && !empty($_POST['cpf']) ? preg_replace('/\D/', '', $_POST['cpf']) : '';

    if ( empty($user_id) && empty($cpf) ) {
        wp_redirect(add_query_arg('msg','missing', admin_url('admin.php?page=cpf-travel-bookings')));
        exit;
    }

    if ( empty($user_id) && ! empty($cpf) ) {
        $uq = new WP_User_Query([ 'meta_key' => 'cpf', 'meta_value' => $cpf, 'number' => 1 ]);
        $users = $uq->get_results();
        $user_id = !empty($users) ? $users[0]->ID : null;
    }

    $data = [
        'user_id' => $user_id,
        'cpf' => $cpf ? $cpf : null,
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed',
    ];

    global $wpdb;
    $booking_table = $wpdb->prefix . 'travel_bookings';

    if ($booking_id) { // Update
        $wpdb->update($booking_table, $data, ['id' => $booking_id]);
    } else { // Insert
        $wpdb->insert($booking_table, $data);
        $booking_id = $wpdb->insert_id;
    }

    if (!$booking_id) {
        wp_redirect(add_query_arg('msg', 'error', admin_url('admin.php?page=cpf-travel-bookings')));
        exit;
    }

    // Handle segments
    $segments_table = $wpdb->prefix . 'flight_segments';
    $wpdb->delete($segments_table, ['booking_id' => $booking_id]);

    if (isset($_POST['segments']) && is_array($_POST['segments'])) {
        foreach ($_POST['segments'] as $segment_data) {
            if (!empty($segment_data['flight_code'])) {
                cpf_travel_add_segment($booking_id, $segment_data);
            }
        }
    }

    wp_redirect(add_query_arg('msg','ok', admin_url('admin.php?page=cpf-travel-bookings')));
    exit;
}

add_action('admin_post_cpf_travel_delete_booking', 'cpf_travel_delete_booking_handler');
function cpf_travel_delete_booking_handler() {
    if ( ! current_user_can('manage_options') ) wp_die('Acesso negado.');
    check_admin_referer('cpf_travel_delete_nonce');

    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

    if ($booking_id) {
        cpf_travel_delete_booking($booking_id);
        wp_redirect(add_query_arg('msg', 'deleted', admin_url('admin.php?page=cpf-travel-bookings')));
    } else {
        wp_redirect(add_query_arg('msg', 'error', admin_url('admin.php?page=cpf-travel-bookings')));
    }
    exit;
}

function cpf_travel_delete_booking($booking_id) {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'travel_bookings';
    $segments_table = $wpdb->prefix . 'flight_segments';

    $wpdb->delete($segments_table, ['booking_id' => $booking_id]);
    $wpdb->delete($bookings_table, ['id' => $booking_id]);
}

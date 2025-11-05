<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CPF_Login {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_shortcode( 'cpf_login_form', [ $this, 'render_form' ] );
        add_action( 'wp_ajax_nopriv_cpf_login', [ $this, 'cpf_login_handler' ] );
        add_action( 'wp_ajax_cpf_login', [ $this, 'cpf_login_handler' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'cpf-login-css', LOGIN_URL . 'assets/css/cpf-login.css' );
        wp_enqueue_script( 'cpf-login-js', LOGIN_URL . 'assets/js/cpf-login.js', [ 'jquery' ], null, true );
        wp_localize_script( 'cpf-login-js', 'cpfLoginAjax', [
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'cpf_login_nonce' )
        ] );
    }

    public function render_form() {
        ob_start(); ?>
        <form id="cpf-login-form" class="cpf-login-form" autocomplete="off" method="post">
            <label for="cpf">Digite seu CPF</label>
            <input type="text" id="cpf" name="cpf" required maxlength="14" inputmode="numeric" placeholder="000.000.000-00" />
            <button type="submit">Entrar</button>
            <div id="cpf-login-msg" aria-label="polite"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function cpf_login_handler() {
        check_ajax_referer('cpf_login_nonce', 'nonce');
        global $wpdb;

        $cpf_raw = isset($_POST['cpf']) ? sanitize_text_field($_POST['cpf']) : '';
        $cpf = preg_replace('/\D/', '', $cpf_raw);

        if (empty($cpf)) {
            wp_send_json_error(['msg' => 'CPF é obrigatório.']);
        }

        if (strlen($cpf) !== 11) {
            wp_send_json_error(['msg' => 'CPF inválido.']);
        }

        // 🔍 1️⃣ Primeiro, tenta localizar o usuário pelo CPF em wp_usermeta
        $uq = new WP_User_Query([
            'meta_key'   => 'cpf',
            'meta_value' => $cpf,
            'number'     => 1,
        ]);
        $users = $uq->get_results();

        if (!empty($users)) {
            $user = $users[0];
        } else { // User with this CPF does not exist
            // We check the bookings table to provide a more specific message, but we do not create a user.
            $booking_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}travel_bookings WHERE cpf = %s LIMIT 1", $cpf));
            $message = $booking_exists ? 'Seu CPF foi encontrado, mas seu usuário ainda não foi criado. Contate o administrador.' : 'CPF não cadastrado em nossa base.';
            wp_send_json_error(['msg' => $message]);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        update_user_meta($user->ID, 'cpf', $cpf);

        wp_send_json_success([
            'msg'      => 'Login realizado com sucesso!',
            'redirect' => home_url('/dashboard/'),
        ]);
    }
}

new CPF_Login();

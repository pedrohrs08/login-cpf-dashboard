<?

if ( ! defined( 'ABSPATH' ) ) exit;

function cpf_travel_add_booking( $data ) {
    global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';

    $cpf = isset($data['cpf']) && !empty($data['cpf']) ? preg_replace('/\D/','', $data['cpf']) : null;

    $fields = [
        'cpf' => $cpf,
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

function cpf_travel_get_bookings( $args = [] ) {
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

    if ( isset($args['cpf']) && ! empty($args['cpf']) ) {
        $cpf = preg_replace('/\D/','', $args['cpf']);
        $query = $wpdb->prepare( "SELECT * FROM $table WHERE cpf = %s ORDER BY created_at $order LIMIT %d OFFSET %d", $cpf, intval($args['per_page']), $offset );
    }else {
        $query = $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at $order LIMIT %d OFFSET %d", intval($args['per_page']), $offset );
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

function travel_exists($args = []){
    return ! empty(cpf_travel_get_bookings($args));
}

function cpf_travel_get_airports() {
    $airports = [
        'GRU' => 'São Paulo Guarulhos Airport',
        'CGH' => 'São Paulo Congonhas Airport',
        'BSB' => 'Brasília Airport',
        'GIG' => 'Rio de Janeiro Galeão Airport',
        'CNF' => 'Belo Horizonte Tancredo Neves Airport',
        'VCP' => 'Campinas Viracopos Airport',
        'SDU' => 'Rio de Janeiro Santos Dumont Airport',
        'REC' => 'Recife Airport',
        'POA' => 'Porto Alegre Airport',
        'SSA' => 'Salvador Airport',
        'FOR' => 'Fortaleza Airport',
        'CWB' => 'Curitiba Afonso Pena Airport',
        'BEL' => 'Belém Val-de-Cans Airport',
        'FLN' => 'Florianópolis Hercílio Luz Airport',
        'VIX' => 'Vitória Eurico de Aguiar Salles Airport',
        'GYN' => 'Goiânia Santa Genoveva Airport',
        'MAO' => 'Manaus Eduardo Gomes Airport',
        'CGB' => 'Cuiabá Marechal Rondon Airport',
        'NAT' => 'Natal Aluízio Alves Airport',
        'IGU' => 'Foz do Iguaçu Cataratas Airport',
        'MCZ' => 'Maceió Zumbi dos Palmares Airport',
        'BPS' => 'Porto Seguro Airport',
        'NVT' => 'Navegantes Ministro Victor Konder Airport',
        'SLZ' => 'São Luís Marechal Cunha Machado Airport',
        'CGR' => 'Campo Grande International Airport',
        'JPA' => 'João Pessoa Presidente Castro Pinto Airport',
        'AJU' => 'Aracaju Santa Maria Airport',
        'THE' => 'Teresina Senador Petrônio Portella Airport',
        'UDI' => 'Uberlândia Ten. Cel. Av. César Bombonato Airport',
        'LDB' => 'Londrina Governador José Richa Airport',
        'RAO' => 'Ribeirão Preto Dr. Leite Lopes Airport',
        'PVH' => 'Porto Velho Governador Jorge Teixeira de Oliveira Airport',
        'JOI' => 'Joinville-Lauro Carneiro de Loyola Airport',
        'AFL' => 'Alta Floresta Piloto Oswaldo Marques Dias Airport',
        'ATM' => 'Altamira Airport',
        'APS' => 'Anápolis Airport',
        'APU' => 'Apucarana Airport',
        'ARU' => 'Araçatuba Dario Guarita State Airport',
        'AUX' => 'Araguaína Regional Airport',
        'APX' => 'Arapongas Alberto Bertelli Airport',
        'AQA' => 'Araraquara Airport',
        'AAX' => 'Araxá Romeu Zema Airport',
        'BGX' => 'Bagé Comandante Gustavo Kraemer Airport',
        'BSS' => 'Balsas Airport',
        'BAZ' => 'Barcelos Airport',
        'BQQ' => 'Barra Airport',
        'BDC' => 'Barra do Corda Airport',
        'BPG' => 'Barra do Garças Airport',
        'BRA' => 'Barreiras Airport',
        'BRB' => 'Barreirinhas Airport',
        'BAT' => 'Barretos Chafei Amsei Airport',
        'BAU' => 'Bauru Airport',
        'JTC' => 'Bauru - Arealva Airport',
        'BVM' => 'Belmonte Airport',
        'PLU' => 'Belo Horizonte Pampulha Airport',
        'BNU' => 'Blumenau Airport',
        'BVB' => 'Boa Vista Atlas Brasil Cantanhede Airport',
        'BCR' => 'Boca do Acre Novo Campo Airport',
        'LAZ' => 'Bom Jesus da Lapa Airport',
        'BYO' => 'Bonito Airport',
        'RBB' => 'Borba Airport',
        'BJP' => 'Bragança Paulista Estadual Arthur Siqueira Airport',
        'BVS' => 'Breves Airport',
        'BMS' => 'Brumado Sócrates Mariani Bittencourt Airport',
        'CFB' => 'Cabo Frio International Airport',
        'CAU' => 'Caruaru Airport',
        'CBW' => 'Campo Mourão Airport',
        'CCI' => 'Concórdia Airport',
        'CCM' => 'Criciúma Forquilhinha Airport',
        'CCX' => 'Cáceres Airport',
        'CFC' => 'Caçador Carlos Alberto da Costa Neves Airport',
        'QGS' => 'Alagoinhas Airport',
        'ALQ' => 'Alegrete Novo Airport',
        'ALT' => 'Alenquer Airport',
        'AMJ' => 'Almenara Cirilo Queiróz Airport',
        'APY' => 'Alto Parnaíba Airport',
        'ARS' => 'Aragarças Estância das Cascatas Airport',
        'APQ' => 'Arapiraca Airport',
        'AAG' => 'Arapoti Airport',
        'AIR' => 'Aripuanã Airport',
        'AQM' => 'Ariquemes Nova Vida Airport',
        'AAI' => 'Arraias Airport',
        'ZFU' => 'Arujá Unifly Airport',
        'AIF' => 'Assis Marcelo Pires Halzhausen Airport',
        'QVP' => 'Avaré-Arandu Airport',
        'QAK' => 'Barbacena Major Brigadeiro Doorgal Borges Airport',
        'QXC' => 'Barra De Santo Antonio Fazenda São Braz Airport',
        'GGB' => 'Água Boa Frederico Carlos Müller Airport',
        'PHB' => 'Parnaíba-Prefeito Dr. João Silva Filho International Airport',
        'PGZ' => 'Ponta Grossa Comte. Antonio Amilton Beraldo Airport',
        'PHI' => 'Pinheiro Airport',
    ];
    return $airports;
}

<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cpf_travel_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'travel_bookings';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        cpf VARCHAR(14) NULL,
        status VARCHAR(32) DEFAULT 'confirmed',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY cpf (cpf)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    $table_segments = $wpdb->prefix . 'flight_segments';
    $sql = "CREATE TABLE $table_segments (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_id BIGINT(20) UNSIGNED NOT NULL,
        flight_code VARCHAR(50) NOT NULL,
        airline VARCHAR(100) DEFAULT NULL,
        origin VARCHAR(50) DEFAULT NULL,
        destination VARCHAR(50) DEFAULT NULL,
        departure DATETIME DEFAULT NULL,
        arrival DATETIME DEFAULT NULL,
        direction VARCHAR(10) DEFAULT 'ida' NOT NULL,
        PRIMARY KEY  (id),
        KEY booking_id (booking_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

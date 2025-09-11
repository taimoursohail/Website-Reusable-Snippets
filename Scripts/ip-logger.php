<?php

/*
Plugin Name: Tiny IP Logger
Description: Minimal plugin to log visitor IPs and countries without bloat.
Version: 1.0
Author: Taimour bin Sohail
*/

if ( ! defined('ABSPATH') ) exit;

// Create table on activation
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table = $wpdb->prefix . 'tiny_ip_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45),
        country VARCHAR(100),
        time DATETIME
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Log visitor on each page load
add_action('init', function() {
    if (is_admin()) return;

    global $wpdb;
    $table = $wpdb->prefix . 'tiny_ip_log';

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
    $time = current_time('mysql');

    // Country lookup
    $country = 'Unknown';
    $response = @file_get_contents("http://ip-api.com/json/" . $ip);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['country'])) {
            $country = sanitize_text_field($data['country']);
        }
    }

    // Already logged today?
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND DATE(time) = CURDATE()", $ip)
    );

    if (!$exists) {
        $wpdb->query(
            $wpdb->prepare("INSERT INTO $table (ip, country, time) VALUES (%s, %s, %s)", $ip, $country, $time)
        );
    }

    // Purge old logs (30 days)
    $wpdb->query("DELETE FROM $table WHERE time < (NOW() - INTERVAL 30 DAY)");
});

// Admin menu page
add_action('admin_menu', function() {
    add_menu_page(
        'IP Logs',
        'IP Logs',
        // Only Visible to administrators
        'manage_options', //If you want to use for woo shop managers use this replace with this 'manage_woocommerce';
        'tiny-ip-logs',
        'tiny_ip_logs_page',
        'dashicons-admin-site',
        80
    );
});

// Admin page callback
function tiny_ip_logs_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'tiny_ip_log';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY time DESC LIMIT 100");

    echo '<div class="wrap"><h1>Visitor IP Logs</h1>';
    echo '<table class="widefat"><thead><tr><th>ID</th><th>IP</th><th>Country</th><th>Time</th></tr></thead><tbody>';

    if ($results) {
        foreach ($results as $row) {
            echo '<tr><td>' . esc_html($row->id) . '</td><td>' . esc_html($row->ip) . '</td><td>' . esc_html($row->country) . '</td><td>' . esc_html($row->time) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="4">No logs yet.</td></tr>';
    }

    echo '</tbody></table></div>';
}

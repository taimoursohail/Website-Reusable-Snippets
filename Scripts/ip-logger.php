<?php

/*
Plugin Name: Tiny IP Logger
Description: Minimal plugin to log visitor IPs and countries without bloat. Modified to track user roles and hide super admin IPs.
Version: 1.1
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
        time DATETIME,
        user_id BIGINT DEFAULT 0,
        role VARCHAR(100) DEFAULT ''
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Log visitor on each page load
add_action('init', function() {
    // Optional: if (is_admin()) return; // Remove to log admin area too

    global $wpdb;
    $table = $wpdb->prefix . 'tiny_ip_log';

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
    $time = current_time('mysql');
    $user_id = 0;
    $role = '';

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_roles = $current_user->roles;
        $role = !empty($user_roles) ? sanitize_text_field($user_roles[0]) : '';
    }

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
        $wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND user_id = %d AND DATE(time) = CURDATE()", $ip, $user_id)
    );

    if (!$exists) {
        $wpdb->query(
            $wpdb->prepare("INSERT INTO $table (ip, country, time, user_id, role) VALUES (%s, %s, %s, %d, %s)", $ip, $country, $time, $user_id, $role)
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
        'manage_options', // Or 'manage_woocommerce' for shop managers
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
    $results = $wpdb->get_results("SELECT * FROM $table WHERE role != '' ORDER BY time DESC LIMIT 100"); // Filter to logged-in only

    echo '<div class="wrap"><h1>Visitor IP Logs</h1>';
    echo '<table class="widefat"><thead><tr><th>ID</th><th>IP</th><th>Country</th><th>Time</th><th>User ID</th><th>Role</th></tr></thead><tbody>';

    if ($results) {
        foreach ($results as $row) {
            $display_ip = esc_html($row->ip);
            if ($row->user_id > 0 && is_super_admin($row->user_id)) {
                $display_ip = 'Hidden (Super Admin)';
            }
            echo '<tr><td>' . esc_html($row->id) . '</td><td>' . $display_ip . '</td><td>' . esc_html($row->country) . '</td><td>' . esc_html($row->time) . '</td><td>' . esc_html($row->user_id) . '</td><td>' . esc_html($row->role) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="6">No logs yet.</td></tr>';
    }

    echo '</tbody></table></div>';
}

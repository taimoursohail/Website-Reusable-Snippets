<?php
// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Get DB
global $wpdb;
$table = 'wp_tiny_ip_log'; //Double check your $table_prefix in your wpconfig.php file

// Create table if not exists
$wpdb->query("CREATE TABLE IF NOT EXISTS $table (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45),
    time DATETIME
) DEFAULT CHARSET=utf8mb4;");

// Capture visitor IP
$ip   = $_SERVER['REMOTE_ADDR'] ?? '';
$time = current_time('mysql');

// Check if this IP already logged today
$exists = $wpdb->get_var(
    $wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND DATE(time) = CURDATE()", $ip)
);

// Insert only if not already logged today
if (!$exists) {
    $wpdb->query(
        $wpdb->prepare("INSERT INTO $table (ip, time) VALUES (%s, %s)", $ip, $time)
    );
}

// Auto-purge logs older than 30 days
$wpdb->query("DELETE FROM $table WHERE time < (NOW() - INTERVAL 30 DAY)");

// Optional: Display message
echo "Logged IP: " . esc_html($ip) . " at " . esc_html($time);

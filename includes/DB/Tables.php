<?php

namespace Newsblenda\Editorial\DB;

use wpdb;

/**
 * Database table creator and updater.
 */
class Tables {
    /**
     * Create required database tables using dbDelta.
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $tables_sql = [];

        $table_name = $wpdb->prefix . 'nbe_articles';
        $tables_sql[] = "CREATE TABLE {$table_name} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            writer_id BIGINT(20) NOT NULL,
            title TEXT NOT NULL,
            content LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        // Login attempts table for brute-force protection
        $login_table = $wpdb->prefix . 'nbe_login_attempts';
        $tables_sql[] = "CREATE TABLE {$login_table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(191) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            blocked_until DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            INDEX identifier_idx (identifier),
            INDEX ip_idx (ip)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $tables_sql as $sql ) {
            dbDelta( $sql );
        }
    }
}

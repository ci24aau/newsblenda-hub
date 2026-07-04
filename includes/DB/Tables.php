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
        $table_name = $wpdb->prefix . 'nbe_articles';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            writer_id BIGINT(20) NOT NULL,
            title TEXT NOT NULL,
            content LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}

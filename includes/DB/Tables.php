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
            seo_title TEXT DEFAULT NULL,
            meta_description TEXT DEFAULT NULL,
            category VARCHAR(191) DEFAULT NULL,
            tags TEXT DEFAULT NULL,
            featured_image_id BIGINT(20) DEFAULT NULL,
            sources LONGTEXT DEFAULT NULL,
            content LONGTEXT NOT NULL,
            word_count INT DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        // Login attempts table for brute-force protection (keep existing schema)
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

        // Article reviews table to store editorial actions
        $reviews_table = $wpdb->prefix . 'nbe_article_reviews';
        $tables_sql[] = "CREATE TABLE {$reviews_table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            article_id BIGINT(20) NOT NULL,
            reviewer_id BIGINT(20) NOT NULL,
            action VARCHAR(50) NOT NULL,
            comment TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX article_idx (article_id),
            INDEX reviewer_idx (reviewer_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $tables_sql as $sql ) {
            dbDelta( $sql );
        }
    }
}

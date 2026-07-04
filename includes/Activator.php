<?php

namespace Newsblenda\Editorial;

use Newsblenda\Editorial\DB\Tables;

/**
 * Activation routines for the plugin.
 */
class Activator {
    /**
     * Activate the plugin: create options, roles, tables, and schedule cron.
     */
    public static function activate() {
        // Create default options
        $defaults = [
            'rpm' => 1.0,
            'min_words' => 300,
            'max_words' => 2000,
            'min_internal_links' => 1,
            'duplicate_threshold' => 0.8,
            'auto_restriction_threshold' => 0.5,
            'email_notifications' => 1,
            'cleanup_on_uninstall' => 0,
        ];

        if ( false === get_option( 'nbe_settings' ) ) {
            add_option( 'nbe_settings', $defaults );
        } else {
            $existing = (array) get_option( 'nbe_settings', [] );
            $merged = array_merge( $defaults, $existing );
            update_option( 'nbe_settings', $merged );
        }

        // Create roles
        if ( ! get_role( 'nbe_author' ) ) {
            add_role( 'nbe_author', 'NBE Author', [
                'read' => true,
            ] );
        }

        if ( ! get_role( 'nbe_editor' ) ) {
            add_role( 'nbe_editor', 'NBE Editor', [
                'read' => true,
            ] );
        }

        // Create database tables
        Tables::create_tables();

        // Schedule cron job (hourly)
        if ( ! wp_next_scheduled( 'nbe_hourly_event' ) ) {
            wp_schedule_event( time(), 'hourly', 'nbe_hourly_event' );
        }
    }
}

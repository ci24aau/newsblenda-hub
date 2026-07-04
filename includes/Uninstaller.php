<?php

namespace Newsblenda\Editorial;

use WP_Roles;

/**
 * Uninstaller logic for the plugin.
 */
class Uninstaller {
    /**
     * Run uninstall cleanup if the setting allows it.
     */
    public static function uninstall() {
        // Safety: ensure this is called during uninstall
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return;
        }

        $settings = (array) get_option( 'nbe_settings', [] );
        $cleanup = ! empty( $settings['cleanup_on_uninstall'] );

        if ( $cleanup ) {
            // Remove options
            delete_option( 'nbe_settings' );

            // Drop tables
            global $wpdb;
            $tables = [ $wpdb->prefix . 'nbe_articles' ];
            foreach ( $tables as $table ) {
                $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
            }

            // Remove roles
            if ( function_exists( 'remove_role' ) ) {
                remove_role( 'nbe_author' );
                remove_role( 'nbe_editor' );
            }
        }
    }
}

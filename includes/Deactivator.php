<?php

namespace Newsblenda\Editorial;

/**
 * Deactivation routines for the plugin.
 */
class Deactivator {
    /**
     * Deactivate plugin: clear scheduled events.
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'nbe_hourly_event' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'nbe_hourly_event' );
        }
    }
}

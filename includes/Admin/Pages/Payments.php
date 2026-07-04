<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Payments admin page.
 */
class Payments {
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Payments</h1><p>Payments history and management.</p></div>';
    }
}

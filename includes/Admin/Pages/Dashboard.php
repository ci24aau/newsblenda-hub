<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Dashboard admin page renderer.
 */
class Dashboard {
    /**
     * Render the dashboard page.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Newsblenda Dashboard</h1><p>Welcome to Newsblenda Editorial.</p></div>';
    }
}

<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Writers admin page.
 */
class Writers {
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Writers</h1><p>Writer management will appear here.</p></div>';
    }
}

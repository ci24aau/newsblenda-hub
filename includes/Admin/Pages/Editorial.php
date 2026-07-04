<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Editorial admin page.
 */
class Editorial {
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Editorial</h1><p>Editorial queue and workflow.</p></div>';
    }
}

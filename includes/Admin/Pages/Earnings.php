<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Earnings admin page.
 */
class Earnings {
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Earnings</h1><p>Earnings overview.</p></div>';
    }
}

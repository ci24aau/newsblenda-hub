<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Reports admin page.
 */
class Reports {
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Reports</h1><p>Reporting dashboard.</p></div>';
    }
}

<?php

namespace Newsblenda\Editorial\Admin\Pages;

/**
 * Articles admin page.
 */
class Articles {
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        echo '<div class="wrap"><h1>Articles</h1><p>Article listing and management.</p></div>';
    }
}

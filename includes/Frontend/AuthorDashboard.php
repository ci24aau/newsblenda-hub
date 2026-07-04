<?php

namespace Newsblenda\Editorial\Frontend;

use WP_User;

/**
 * Frontend Author Dashboard shortcode and rendering.
 */
class AuthorDashboard {
    /**
     * Initialize shortcode and assets.
     */
    public static function init() {
        add_shortcode( 'nbe_author_dashboard', [ __CLASS__, 'render' ] );
    }

    /**
     * Register assets for the dashboard.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( dirname( __DIR__ ) ) );
        wp_register_style( 'nbe-author-dashboard-style', $dir . 'assets/css/author-dashboard.css', [], '0.1.0' );
        wp_register_script( 'nbe-author-dashboard-script', $dir . 'assets/js/author-dashboard.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render the author dashboard. Access restricted to approved authors only.
     *
     * @return string
     */
    public static function render() {
        // Enqueue assets
        wp_enqueue_style( 'nbe-author-dashboard-style' );
        wp_enqueue_script( 'nbe-author-dashboard-script' );

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof WP_User ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        // Only authors with role nbe_author or author
        $roles = (array) $user->roles;
        $is_author_role = in_array( 'nbe_author', $roles, true ) || in_array( 'author', $roles, true );
        if ( ! $is_author_role ) {
            wp_safe_redirect( site_url() );
            exit;
        }

        // Only approved (active) authors may view
        $status = get_user_meta( $user->ID, 'nbe_status', true );
        if ( empty( $status ) ) {
            $status = 'active';
        }
        // Treat 'active' as approved; if there is an explicit 'approved' value accept it too
        if ( ! in_array( $status, [ 'active', 'approved' ], true ) ) {
            // Redirect to pending/restricted/suspended as appropriate
            switch ( $status ) {
                case 'pending':
                    wp_safe_redirect( site_url( '/pending' ) );
                    break;
                case 'restricted':
                    wp_safe_redirect( site_url( '/restricted' ) );
                    break;
                case 'blocked':
                    wp_safe_redirect( site_url( '/suspended' ) );
                    break;
                default:
                    wp_safe_redirect( site_url() );
                    break;
            }
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'nbe_articles';
        $writer_id = (int) $user->ID;

        // Totals
        $total_articles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE writer_id = %d", $writer_id ) );
        $pending_articles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE writer_id = %d AND status = %s", $writer_id, 'pending' ) );
        // Approved: status 'approved' or 'published'
        $approved_articles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE writer_id = %d AND (status = %s OR status = %s)", $writer_id, 'approved', 'published' ) );
        $rejected_articles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE writer_id = %d AND status = %s", $writer_id, 'rejected' ) );

        // Recent articles (latest 5)
        $recent = $wpdb->get_results( $wpdb->prepare( "SELECT id, title, status, created_at FROM {$table} WHERE writer_id = %d ORDER BY created_at DESC LIMIT 5", $writer_id ) );

        // Earnings calculation: sum words in approved articles * rpm per 1000 words
        $settings = (array) get_option( 'nbe_settings', [] );
        $rpm = isset( $settings['rpm'] ) ? floatval( $settings['rpm'] ) : 1.0;

        $approved_rows = $wpdb->get_results( $wpdb->prepare( "SELECT content FROM {$table} WHERE writer_id = %d AND (status = %s OR status = %s)", $writer_id, 'approved', 'published' ) );
        $total_words = 0;
        foreach ( $approved_rows as $row ) {
            $content = wp_strip_all_tags( $row->content );
            $words = str_word_count( $content );
            $total_words += $words;
        }
        // Earnings: rpm * (total_words / 1000)
        $earnings = round( $rpm * ( $total_words / 1000 ), 2 );

        // Restriction status (if any)
        $restriction = $status;

        // Prepare variables for template
        ob_start();
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/author-dashboard.php';
        return ob_get_clean();
    }
}

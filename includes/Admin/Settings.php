<?php

namespace Newsblenda\Editorial\Admin;

/**
 * Settings management using the WordPress Settings API.
 */
class Settings {
    /**
     * Register settings, sections, and fields.
     */
    public static function register() {
        register_setting( 'nbe_settings_group', 'nbe_settings', [ '\\Newsblenda\\Editorial\\Admin\\Settings', 'sanitize' ] );

        add_settings_section( 'nbe_main', 'General Settings', null, 'newsblenda-editorial-settings' );

        $fields = [
            'rpm' => 'RPM',
            'min_words' => 'Minimum word count',
            'max_words' => 'Maximum word count',
            'min_internal_links' => 'Minimum internal links',
            'duplicate_threshold' => 'Duplicate article threshold',
            'auto_restriction_threshold' => 'Auto restriction threshold',
            'email_notifications' => 'Email notifications',
            'cleanup_on_uninstall' => 'Cleanup on uninstall',
        ];

        foreach ( $fields as $id => $label ) {
            add_settings_field( $id, $label, [ __CLASS__, 'field_callback' ], 'newsblenda-editorial-settings', 'nbe_main', [ 'id' => $id ] );
        }
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array $input
     * @return array
     */
    public static function sanitize( $input ) {
        $output = [];
        $output['rpm'] = isset( $input['rpm'] ) ? floatval( $input['rpm'] ) : 1.0;
        $output['min_words'] = isset( $input['min_words'] ) ? intval( $input['min_words'] ) : 300;
        $output['max_words'] = isset( $input['max_words'] ) ? intval( $input['max_words'] ) : 2000;
        $output['min_internal_links'] = isset( $input['min_internal_links'] ) ? intval( $input['min_internal_links'] ) : 1;
        $output['duplicate_threshold'] = isset( $input['duplicate_threshold'] ) ? floatval( $input['duplicate_threshold'] ) : 0.8;
        $output['auto_restriction_threshold'] = isset( $input['auto_restriction_threshold'] ) ? floatval( $input['auto_restriction_threshold'] ) : 0.5;
        $output['email_notifications'] = ! empty( $input['email_notifications'] ) ? 1 : 0;
        $output['cleanup_on_uninstall'] = ! empty( $input['cleanup_on_uninstall'] ) ? 1 : 0;
        return $output;
    }

    /**
     * Render a field.
     *
     * @param array $args
     */
    public static function field_callback( $args ) {
        $id = $args['id'];
        $settings = (array) get_option( 'nbe_settings', [] );
        $value = isset( $settings[ $id ] ) ? esc_attr( $settings[ $id ] ) : '';

        switch ( $id ) {
            case 'email_notifications':
            case 'cleanup_on_uninstall':
                printf( '<input type="checkbox" id="%1$s" name="nbe_settings[%1$s]" value="1" %2$s />', esc_attr( $id ), checked( 1, $value, false ) );
                break;
            default:
                printf( '<input type="text" id="%1$s" name="nbe_settings[%1$s]" value="%2$s" class="regular-text" />', esc_attr( $id ), esc_attr( $value ) );
                break;
        }
    }

    /**
     * Render the settings page.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'newsblenda-editorial' ) );
        }

        // Show settings form
        echo '<div class="wrap">';
        echo '<h1>Newsblenda Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'nbe_settings_group' );
        do_settings_sections( 'newsblenda-editorial-settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

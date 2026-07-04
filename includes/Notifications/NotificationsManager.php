<?php
namespace Newsblenda\Editorial\Notifications;

class Manager {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function admin_menu() {
        add_submenu_page( 'nbe-earnings', 'Email Templates', 'Email Templates', 'manage_options', 'nbe-email-templates', [ __CLASS__, 'render_page' ] );
    }

    public static function register_settings() {
        register_setting( 'nbe_email_templates_group', 'nbe_email_templates', [ 'sanitize_callback' => [ __CLASS__, 'sanitize_templates' ] ] );
        register_setting( 'nbe_email_templates_group', 'nbe_from_email', [ 'sanitize_callback' => 'sanitize_email' ] );
        register_setting( 'nbe_email_templates_group', 'nbe_from_name', [ 'sanitize_callback' => 'sanitize_text_field' ] );
    }

    public static function sanitize_templates( $input ) {
        $defaults = Notifications::get_defaults();
        $out = [];
        foreach ( $defaults as $key => $tpl ) {
            $subject = isset( $input[ $key ]['subject'] ) ? sanitize_text_field( $input[ $key ]['subject'] ) : $tpl['subject'];
            // allow basic HTML in body
            $body = isset( $input[ $key ]['body'] ) ? wp_kses_post( $input[ $key ]['body'] ) : $tpl['body'];
            $out[ $key ] = [ 'subject' => $subject, 'body' => $body ];
        }
        return $out;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        $templates = Notifications::get_templates();
        ?>
        <div class="wrap">
            <h1>Email Templates</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'nbe_email_templates_group' ); ?>
                <?php do_settings_sections( 'nbe_email_templates_group' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="nbe_from_name">From Name</label></th>
                        <td><input type="text" name="nbe_from_name" value="<?php echo esc_attr( get_option( 'nbe_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="nbe_from_email">From Email</label></th>
                        <td><input type="email" name="nbe_from_email" value="<?php echo esc_attr( get_option( 'nbe_from_email', '' ) ); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2>Templates</h2>
                <?php foreach ( $templates as $key => $tpl ) : ?>
                    <h3><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label>Subject</label></th>
                            <td><input type="text" name="nbe_email_templates[<?php echo esc_attr( $key ); ?>][subject]" value="<?php echo esc_attr( $tpl['subject'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label>Body (HTML allowed)</label></th>
                            <td><textarea name="nbe_email_templates[<?php echo esc_attr( $key ); ?>][body]" rows="6" cols="80"><?php echo esc_textarea( $tpl['body'] ); ?></textarea></td>
                        </tr>
                    </table>
                <?php endforeach; ?>

                <?php submit_button(); ?>
            </form>
            <h2>Available placeholders</h2>
            <p>Use the following placeholders in templates: {site_name}, {user_display_name}, {user_login}, {verification_link}, {reset_link}, {post_title}, {post_link}, {amount}, {payout_id}, {date}, {comments}</p>
        </div>
        <?php
    }
}

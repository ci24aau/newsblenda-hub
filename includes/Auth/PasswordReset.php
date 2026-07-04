<?php

namespace Newsblenda\Editorial\Auth;

use WP_User;

/**
 * Frontend password reset implementation.
 */
class PasswordReset {
    /**
     * Token expiry in seconds (2 hours).
     */
    const EXPIRY = 7200; // 2 * 3600

    /**
     * Initialize shortcodes and handlers.
     */
    public static function init() {
        add_shortcode( 'nbe_forgot_password', [ __CLASS__, 'render_forgot' ] );
        add_shortcode( 'nbe_reset_password', [ __CLASS__, 'render_reset' ] );
    }

    /**
     * Register assets for forgot/reset forms.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( __DIR__ ) );
        wp_register_style( 'nbe-password-style', $dir . 'assets/css/password.css', [], '0.1.0' );
        wp_register_script( 'nbe-password-script', $dir . 'assets/js/password.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render forgot password form and process request.
     * Always show a generic success message to avoid user enumeration.
     *
     * @return string
     */
    public static function render_forgot() {
        wp_enqueue_style( 'nbe-password-style' );
        wp_enqueue_script( 'nbe-password-script' );

        $errors = [];
        $messages = [];

        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_forgot_action'] ) ) {
            if ( ! isset( $_POST['nbe_forgot_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_forgot_nonce'] ) ), 'nbe_forgot' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                $email = isset( $_POST['nbe_forgot_email'] ) ? sanitize_email( wp_unslash( $_POST['nbe_forgot_email'] ) ) : '';
                if ( empty( $email ) || ! is_email( $email ) ) {
                    // Still don't reveal — show generic message but keep validation for format
                    $errors[] = __( 'Please provide a valid email address.', 'newsblenda-editorial' );
                } else {
                    // Always show generic message
                    $messages[] = __( 'If an account exists with that email address, a password reset link has been sent.', 'newsblenda-editorial' );

                    // If user exists, generate token and email
                    $user = get_user_by( 'email', $email );
                    if ( $user instanceof WP_User ) {
                        self::generate_and_send( $user->ID );
                    }
                }
            }
        }

        ob_start();
        $form_action = esc_url( get_permalink() ?: site_url( '/forgot-password' ) );
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/forgot-password.php';
        return ob_get_clean();
    }

    /**
     * Render reset password form (expects uid & token via GET or POST) and process new password.
     *
     * @return string
     */
    public static function render_reset() {
        wp_enqueue_style( 'nbe-password-style' );
        wp_enqueue_script( 'nbe-password-script' );

        $errors = [];
        $messages = [];

        // Determine uid and token from GET or POST
        $uid = 0;
        $token = '';
        if ( isset( $_GET['uid'] ) ) {
            $uid = intval( wp_unslash( $_GET['uid'] ) );
        }
        if ( isset( $_GET['token'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
        }

        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_reset_action'] ) ) {
            if ( ! isset( $_POST['nbe_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_reset_nonce'] ) ), 'nbe_reset' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                $uid = isset( $_POST['nbe_reset_uid'] ) ? intval( wp_unslash( $_POST['nbe_reset_uid'] ) ) : 0;
                $token = isset( $_POST['nbe_reset_token'] ) ? sanitize_text_field( wp_unslash( $_POST['nbe_reset_token'] ) ) : '';
                $password = isset( $_POST['nbe_new_password'] ) ? $_POST['nbe_new_password'] : '';
                $password_confirm = isset( $_POST['nbe_new_password_confirm'] ) ? $_POST['nbe_new_password_confirm'] : '';

                if ( $uid <= 0 || empty( $token ) ) {
                    $errors[] = __( 'Invalid request.', 'newsblenda-editorial' );
                }

                // Validate passwords
                if ( empty( $password ) ) {
                    $errors[] = __( 'Please enter a new password.', 'newsblenda-editorial' );
                }
                if ( $password !== $password_confirm ) {
                    $errors[] = __( 'Passwords do not match.', 'newsblenda-editorial' );
                }
                if ( ! empty( $password ) ) {
                    if ( strlen( $password ) < 8 || ! preg_match( '/[A-Za-z]/', $password ) || ! preg_match( '/\d/', $password ) ) {
                        $errors[] = __( 'Password must be at least 8 characters long and include letters and numbers.', 'newsblenda-editorial' );
                    }
                }

                if ( empty( $errors ) ) {
                    $verified = self::verify_token( $uid, $token );
                    if ( $verified !== true ) {
                        $errors[] = __( 'Invalid or expired reset link. Please request a new password reset.', 'newsblenda-editorial' );
                    } else {
                        $user = get_user_by( 'id', $uid );
                        if ( ! $user instanceof WP_User ) {
                            $errors[] = __( 'Invalid user.', 'newsblenda-editorial' );
                        } else {
                            // Use WP API to reset password (fires password_reset action)
                            reset_password( $user, $password );

                            // Clear token meta
                            delete_user_meta( $uid, 'nbe_reset_token_hashed' );
                            delete_user_meta( $uid, 'nbe_reset_expires' );

                            $messages[] = __( 'Your password has been reset. You may now log in with your new password.', 'newsblenda-editorial' );
                        }
                    }
                }
            }
        }

        ob_start();
        $form_action = esc_url( get_permalink() ?: site_url( '/reset-password' ) );
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/reset-password.php';
        return ob_get_clean();
    }

    /**
     * Generate a secure token, store hashed version and expiry, and email the reset link to the user.
     *
     * @param int $user_id
     * @return bool True if mail was sent (when user exists), false otherwise.
     */
    public static function generate_and_send( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            return false;
        }

        $token = wp_generate_password( 24, false );
        $hashed = wp_hash_password( $token );
        $expires = time() + self::EXPIRY;

        update_user_meta( $user_id, 'nbe_reset_token_hashed', $hashed );
        update_user_meta( $user_id, 'nbe_reset_expires', $expires );

        return self::send_reset_email( $user_id, $token );
    }

    /**
     * Verify a reset token for a user.
     *
     * @param int $user_id
     * @param string $token
     * @return bool True if token valid, false otherwise.
     */
    public static function verify_token( $user_id, $token ) {
        $hashed = get_user_meta( $user_id, 'nbe_reset_token_hashed', true );
        $expires = intval( get_user_meta( $user_id, 'nbe_reset_expires', true ) );

        if ( empty( $hashed ) || empty( $expires ) ) {
            return false;
        }

        if ( time() > $expires ) {
            // Token expired
            delete_user_meta( $user_id, 'nbe_reset_token_hashed' );
            delete_user_meta( $user_id, 'nbe_reset_expires' );
            return false;
        }

        if ( wp_check_password( $token, $hashed ) ) {
            return true;
        }

        return false;
    }

    /**
     * Send password reset email using wp_mail().
     *
     * @param int $user_id
     * @param string $token
     * @return bool
     */
    public static function send_reset_email( $user_id, $token ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            return false;
        }

        $reset_url = add_query_arg([
            'nbe_reset' => '1',
            'uid'       => $user_id,
            'token'     => rawurlencode( $token ),
        ], site_url( '/' ) );

        $subject = sprintf( /* translators: %s: site name */ __( '[%s] Password reset request', 'newsblenda-editorial' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

        $message = '';
        $message .= sprintf( __( 'Hi %s,', 'newsblenda-editorial' ), esc_html( $user->display_name ) ) . "\n\n";
        $message .= __( 'A request to reset your password was received. If you made this request, click the link below to set a new password. This link will expire in two hours.', 'newsblenda-editorial' ) . "\n\n";
        $message .= esc_url( $reset_url ) . "\n\n";
        $message .= __( 'If you did not request a password reset, you can ignore this email.', 'newsblenda-editorial' ) . "\n";

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        return wp_mail( $user->user_email, $subject, $message, $headers );
    }
}

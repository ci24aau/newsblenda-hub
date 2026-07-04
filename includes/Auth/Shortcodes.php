<?php

namespace Newsblenda\Editorial\Auth;

use Newsblenda\Editorial\Auth\BruteForce;
use Newsblenda\Editorial\Auth\EmailVerification;
use WP_Error;

/**
 * Shortcodes and frontend login handling.
 */
class Shortcodes {
    /**
     * Initialize shortcodes.
     */
    public static function init() {
        add_shortcode( 'nbe_login', [ __CLASS__, 'render_login' ] );
        add_shortcode( 'nbe_logout', [ __CLASS__, 'render_logout' ] );
    }

    /**
     * Enqueue assets required for the login form.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( __DIR__ ) );
        wp_register_style( 'nbe-login-style', $dir . 'assets/css/login.css', [], '0.1.0' );
        wp_register_script( 'nbe-login-script', $dir . 'assets/js/login.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render the login form and handle submission.
     *
     * @return string
     */
    public static function render_login() {
        wp_enqueue_style( 'nbe-login-style' );
        wp_enqueue_script( 'nbe-login-script' );

        $errors = [];
        $messages = [];

        // Handle resend verification POST first
        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_resend_action'] ) ) {
            if ( ! isset( $_POST['nbe_resend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_resend_nonce'] ) ), 'nbe_resend' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                $email = isset( $_POST['nbe_resend_email'] ) ? sanitize_email( wp_unslash( $_POST['nbe_resend_email'] ) ) : '';
                if ( empty( $email ) || ! is_email( $email ) ) {
                    $errors[] = __( 'Please provide a valid email address.', 'newsblenda-editorial' );
                } else {
                    $user = get_user_by( 'email', $email );
                    // Always show generic message to avoid revealing account existence
                    $messages[] = __( 'If an account exists with that email address, a verification email has been sent.', 'newsblenda-editorial' );
                    if ( $user ) {
                        // Generate new token and send
                        EmailVerification::generate_and_send( $user->ID );
                    }
                }
            }
        }

        // Handle POST login
        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_login_action'] ) ) {
            if ( ! isset( $_POST['nbe_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_login_nonce'] ) ), 'nbe_login' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                $identifier = isset( $_POST['nbe_user'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nbe_user'] ) ) ) : '';
                $password = isset( $_POST['nbe_pass'] ) ? $_POST['nbe_pass'] : '';
                $remember = ! empty( $_POST['nbe_remember'] );

                // Basic validation
                if ( empty( $identifier ) || empty( $password ) ) {
                    $errors[] = __( 'Please fill in both username/email and password.', 'newsblenda-editorial' );
                }

                $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

                // Brute force protection
                if ( BruteForce::is_blocked( $identifier, $ip ) ) {
                    $errors[] = __( 'Too many failed login attempts. Please try again later.', 'newsblenda-editorial' );
                }

                if ( empty( $errors ) ) {
                    // Resolve username if email provided
                    if ( is_email( $identifier ) ) {
                        $user_obj = get_user_by( 'email', $identifier );
                        $user_login = $user_obj ? $user_obj->user_login : '';
                    } else {
                        $user_login = $identifier;
                        $user_obj = get_user_by( 'login', $user_login );
                    }

                    // If user exists, check email verification before attempting signon
                    if ( $user_obj ) {
                        $verified = (int) get_user_meta( $user_obj->ID, 'nbe_email_verified', true );
                        if ( 1 !== $verified ) {
                            // Prevent login until email verified
                            $errors[] = __( 'Please verify your email address before logging in. Check your inbox or resend the verification email.', 'newsblenda-editorial' );
                        }
                    }

                    if ( empty( $errors ) ) {
                        $creds = [
                            'user_login'    => $user_login,
                            'user_password' => $password,
                            'remember'      => $remember,
                        ];

                        $user = wp_signon( $creds, is_ssl() );

                        if ( is_wp_error( $user ) ) {
                            // Record failed attempt
                            BruteForce::record_failed_attempt( $identifier, $ip );

                            // Generic error message for security
                            $errors[] = __( 'Invalid login credentials. Please check your username/email and password.', 'newsblenda-editorial' );
                        } else {
                            // Successful login — reset attempts
                            BruteForce::clear_attempts( $identifier, $ip );

                            // Check user status
                            $status = get_user_meta( $user->ID, 'nbe_status', true );
                            if ( empty( $status ) ) {
                                $status = 'active';
                            }

                            // Determine redirect
                            $redirect = home_url();
                            if ( in_array( 'administrator', (array) $user->roles, true ) || current_user_can( 'manage_options' ) ) {
                                $redirect = admin_url();
                            } elseif ( 'pending' === $status ) {
                                $redirect = site_url( '/pending' );
                            } elseif ( 'restricted' === $status ) {
                                $redirect = site_url( '/restricted' );
                            } elseif ( 'blocked' === $status ) {
                                $redirect = site_url( '/suspended' );
                            } elseif ( in_array( 'nbe_editor', (array) $user->roles, true ) || in_array( 'editor', (array) $user->roles, true ) ) {
                                $redirect = site_url( '/editor-dashboard' );
                            } else {
                                $redirect = site_url( '/author-dashboard' );
                            }

                            wp_safe_redirect( $redirect );
                            exit;
                        }
                    }
                }
            }
        }

        // Display verification GET result messages
        if ( isset( $_GET['nbe_verification'] ) ) {
            if ( 'success' === $_GET['nbe_verification'] ) {
                $messages[] = __( 'Email verified successfully. You may now log in. Your account may still require admin approval.', 'newsblenda-editorial' );
            } else {
                $errors[] = __( 'Verification failed or token expired. Please request a new verification email.', 'newsblenda-editorial' );
            }
        }

        // Output form via template capture
        ob_start();
        $form_action = esc_url( get_permalink() ?: site_url() );
        $login_value = isset( $_POST['nbe_user'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['nbe_user'] ) ) ) : '';
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/login.php';
        return ob_get_clean();
    }

    /**
     * Render a logout link (shortcode).
     *
     * @return string
     */
    public static function render_logout() {
        $redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url();
        $url = wp_logout_url( $redirect );
        return '<a href="' . esc_url( $url ) . '" class="nbe-logout-link">' . esc_html__( 'Log out', 'newsblenda-editorial' ) . '</a>';
    }
}

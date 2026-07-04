<?php

namespace Newsblenda\Editorial\Auth;

use WP_Error;

/**
 * Frontend registration handling and shortcode.
 */
class Registration {
    /**
     * Initialize shortcode.
     */
    public static function init() {
        add_shortcode( 'nbe_register', [ __CLASS__, 'render_registration' ] );
    }

    /**
     * Register/enqueue assets for registration form.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( __DIR__ ) );
        wp_register_style( 'nbe-register-style', $dir . 'assets/css/registration.css', [], '0.1.0' );
        wp_register_script( 'nbe-register-script', $dir . 'assets/js/registration.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render registration form and process submissions.
     *
     * @return string
     */
    public static function render_registration() {
        // Enqueue assets
        wp_enqueue_style( 'nbe-register-style' );
        wp_enqueue_script( 'nbe-register-script' );

        $errors = [];
        $messages = [];

        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_register_action'] ) ) {
            if ( ! isset( $_POST['nbe_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_register_nonce'] ) ), 'nbe_register' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                // Sanitize inputs
                $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
                $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
                $user_login = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ), true ) : '';
                $user_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
                $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
                $password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';
                $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
                $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
                $preferred_category = isset( $_POST['preferred_category'] ) ? sanitize_text_field( wp_unslash( $_POST['preferred_category'] ) ) : '';
                $bio = isset( $_POST['bio'] ) ? wp_kses_post( wp_unslash( $_POST['bio'] ) ) : '';
                $terms = isset( $_POST['terms'] ) && '1' === $_POST['terms'];

                // Required fields
                if ( empty( $first_name ) ) {
                    $errors[] = __( 'First name is required.', 'newsblenda-editorial' );
                }
                if ( empty( $last_name ) ) {
                    $errors[] = __( 'Last name is required.', 'newsblenda-editorial' );
                }
                if ( empty( $user_login ) ) {
                    $errors[] = __( 'Username is required.', 'newsblenda-editorial' );
                } elseif ( ! validate_username( $user_login ) ) {
                    $errors[] = __( 'Invalid username. Only lowercase letters, numbers and underscores are allowed.', 'newsblenda-editorial' );
                }
                if ( empty( $user_email ) || ! is_email( $user_email ) ) {
                    $errors[] = __( 'A valid email address is required.', 'newsblenda-editorial' );
                }
                if ( empty( $password ) ) {
                    $errors[] = __( 'Password is required.', 'newsblenda-editorial' );
                }
                if ( $password !== $password_confirm ) {
                    $errors[] = __( 'Passwords do not match.', 'newsblenda-editorial' );
                }
                if ( ! $terms ) {
                    $errors[] = __( 'You must agree to the terms and conditions.', 'newsblenda-editorial' );
                }

                // Password strength: min 8 chars, contains letter and number
                if ( ! empty( $password ) ) {
                    if ( strlen( $password ) < 8 || ! preg_match( '/[A-Za-z]/', $password ) || ! preg_match( '/\d/', $password ) ) {
                        $errors[] = __( 'Password must be at least 8 characters long and include letters and numbers.', 'newsblenda-editorial' );
                    }
                }

                // Prevent duplicates
                if ( empty( $errors ) ) {
                    if ( username_exists( $user_login ) ) {
                        $errors[] = __( 'This username is already registered.', 'newsblenda-editorial' );
                    }
                    if ( email_exists( $user_email ) ) {
                        $errors[] = __( 'This email is already registered.', 'newsblenda-editorial' );
                    }
                }

                // Handle profile photo upload if provided
                $profile_photo_id = 0;
                if ( empty( $errors ) && ! empty( $_FILES['profile_photo'] ) && ! empty( $_FILES['profile_photo']['name'] ) ) {
                    $file = $_FILES['profile_photo'];
                    // Basic file checks
                    $allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
                    if ( ! in_array( $file['type'], $allowed_types, true ) ) {
                        $errors[] = __( 'Profile photo must be an image (jpg, png, gif, webp).', 'newsblenda-editorial' );
                    } elseif ( $file['size'] > 2 * 1024 * 1024 ) { // 2MB limit
                        $errors[] = __( 'Profile photo must be less than 2MB.', 'newsblenda-editorial' );
                    }
                }

                if ( empty( $errors ) ) {
                    // Create user with role nbe_author and status pending
                    $userdata = [
                        'user_login' => $user_login,
                        'user_email' => $user_email,
                        'user_pass'  => $password,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'role'       => 'nbe_author',
                    ];

                    $user_id = wp_insert_user( $userdata );
                    if ( is_wp_error( $user_id ) ) {
                        $errors[] = __( 'Registration failed. Please try again later.', 'newsblenda-editorial' );
                    } else {
                        // Save meta
                        update_user_meta( $user_id, 'phone', $phone );
                        update_user_meta( $user_id, 'country', $country );
                        update_user_meta( $user_id, 'preferred_category', $preferred_category );
                        update_user_meta( $user_id, 'description', $bio );
                        update_user_meta( $user_id, 'nbe_status', 'pending' );

                        // Handle profile photo saving to uploads and create attachment
                        if ( ! empty( $_FILES['profile_photo'] ) && ! empty( $_FILES['profile_photo']['name'] ) ) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                            require_once ABSPATH . 'wp-admin/includes/image.php';
                            require_once ABSPATH . 'wp-admin/includes/media.php';

                            $uploaded = wp_handle_upload( $_FILES['profile_photo'], [ 'test_form' => false ] );
                            if ( isset( $uploaded['error'] ) ) {
                                // non-fatal: record error but keep registration
                                $messages[] = __( 'Profile photo upload failed. You can add it later from your profile.', 'newsblenda-editorial' );
                            } else {
                                $filetype = wp_check_filetype( basename( $uploaded['file'] ), null );
                                $attachment = [
                                    'post_mime_type' => $filetype['type'],
                                    'post_title'     => sanitize_file_name( basename( $uploaded['file'] ) ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit',
                                ];
                                $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], 0 );
                                if ( ! is_wp_error( $attach_id ) ) {
                                    $meta = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
                                    wp_update_attachment_metadata( $attach_id, $meta );
                                    update_user_meta( $user_id, 'profile_photo', $attach_id );
                                    $profile_photo_id = $attach_id;
                                }
                            }
                        }

                        // Success message
                        $messages[] = __( 'Registration successful. Your account is pending approval. You will receive an email when approved.', 'newsblenda-editorial' );

                        // Optionally, notify administrators here (not implemented in this step)
                    }
                }
            }
        }

        // Prepare template variables and render
        ob_start();
        $form_action = esc_url( get_permalink() ?: site_url( '/register' ) );
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/registration.php';
        return ob_get_clean();
    }
}

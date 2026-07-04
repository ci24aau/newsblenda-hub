<?php

namespace Newsblenda\Editorial\Frontend;

use WP_User;

/**
 * Frontend profile management for authors and editors.
 */
class Profile {
    /**
     * Initialize shortcode and assets.
     */
    public static function init() {
        add_shortcode( 'nbe_profile', [ __CLASS__, 'render' ] );
    }

    /**
     * Register assets for the profile form.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( dirname( __DIR__ ) ) );
        wp_register_style( 'nbe-profile-style', $dir . 'assets/css/profile.css', [], '0.1.0' );
        wp_register_script( 'nbe-profile-script', $dir . 'assets/js/profile.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render and process the profile edit form.
     *
     * @return string
     */
    public static function render() {
        wp_enqueue_style( 'nbe-profile-style' );
        wp_enqueue_script( 'nbe-profile-script' );

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof WP_User ) {
            wp_safe_redirect( wp_login_url() );
            exit;
        }

        $errors = [];
        $messages = [];

        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_profile_action'] ) ) {
            if ( ! isset( $_POST['nbe_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_profile_nonce'] ) ), 'nbe_profile' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                // Sanitize fields
                $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
                $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
                $bio = isset( $_POST['bio'] ) ? wp_kses_post( wp_unslash( $_POST['bio'] ) ) : '';
                $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
                $preferred_category = isset( $_POST['preferred_category'] ) ? sanitize_text_field( wp_unslash( $_POST['preferred_category'] ) ) : '';

                // Password change (optional)
                $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
                $password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';

                if ( ! empty( $password ) || ! empty( $password_confirm ) ) {
                    if ( $password !== $password_confirm ) {
                        $errors[] = __( 'Passwords do not match.', 'newsblenda-editorial' );
                    }
                    if ( ! empty( $password ) ) {
                        if ( strlen( $password ) < 8 || ! preg_match( '/[A-Za-z]/', $password ) || ! preg_match( '/\d/', $password ) ) {
                            $errors[] = __( 'Password must be at least 8 characters long and include letters and numbers.', 'newsblenda-editorial' );
                        }
                    }
                }

                // Handle profile photo upload
                $profile_photo_id = get_user_meta( $user->ID, 'profile_photo', true );
                if ( ! empty( $_FILES['profile_photo'] ) && ! empty( $_FILES['profile_photo']['name'] ) ) {
                    $file = $_FILES['profile_photo'];
                    $allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
                    if ( ! in_array( $file['type'], $allowed_types, true ) ) {
                        $errors[] = __( 'Profile photo must be an image (jpg, png, gif, webp).', 'newsblenda-editorial' );
                    } elseif ( $file['size'] > 3 * 1024 * 1024 ) { // 3MB limit
                        $errors[] = __( 'Profile photo must be less than 3MB.', 'newsblenda-editorial' );
                    }
                }

                if ( empty( $errors ) ) {
                    // Update WP user fields
                    $userdata = [
                        'ID' => $user->ID,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                    ];
                    wp_update_user( $userdata );

                    // Update user meta
                    update_user_meta( $user->ID, 'description', $bio );
                    update_user_meta( $user->ID, 'phone', $phone );
                    update_user_meta( $user->ID, 'preferred_category', $preferred_category );

                    // Handle profile photo upload via WP media handlers
                    if ( ! empty( $_FILES['profile_photo'] ) && ! empty( $_FILES['profile_photo']['name'] ) ) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';

                        $uploaded = wp_handle_upload( $_FILES['profile_photo'], [ 'test_form' => false ] );
                        if ( isset( $uploaded['error'] ) ) {
                            $errors[] = esc_html( $uploaded['error'] );
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
                                update_user_meta( $user->ID, 'profile_photo', $attach_id );
                                $profile_photo_id = $attach_id;
                            }
                        }
                    }

                    // Update password if provided
                    if ( ! empty( $password ) ) {
                        wp_update_user( [ 'ID' => $user->ID, 'user_pass' => $password ] );
                    }

                    if ( empty( $errors ) ) {
                        $messages[] = __( 'Profile updated successfully.', 'newsblenda-editorial' );
                        // Refresh user data
                        $user = wp_get_current_user();
                    }
                }
            }
        }

        // Prepare form values
        $first_name = isset( $first_name ) ? $first_name : $user->first_name;
        $last_name = isset( $last_name ) ? $last_name : $user->last_name;
        $bio = isset( $bio ) ? $bio : get_user_meta( $user->ID, 'description', true );
        $phone = isset( $phone ) ? $phone : get_user_meta( $user->ID, 'phone', true );
        $preferred_category = isset( $preferred_category ) ? $preferred_category : get_user_meta( $user->ID, 'preferred_category', true );
        $profile_photo_id = isset( $profile_photo_id ) ? $profile_photo_id : get_user_meta( $user->ID, 'profile_photo', true );
        $profile_photo_url = $profile_photo_id ? wp_get_attachment_url( $profile_photo_id ) : '';

        ob_start();
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/profile.php';
        return ob_get_clean();
    }
}

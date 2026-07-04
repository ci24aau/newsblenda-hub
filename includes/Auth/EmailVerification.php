<?php

namespace Newsblenda\Editorial\Auth;

use WP_User;

/**
 * Email verification helper for user registration.
 */
class EmailVerification {
    /**
     * Token expiry in seconds (24 hours).
     */
    const EXPIRY = 86400; // 24 * 3600

    /**
     * Initialize handlers for verification endpoint and resend processing.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'handle_requests' ] );
    }

    /**
     * Handle incoming verification GET requests (and optional resend via POST).
     */
    public static function handle_requests() {
        // Verification via GET: ?nbe_verify=1&uid=123&token=...
        if ( isset( $_GET['nbe_verify'] ) ) {
            $uid = isset( $_GET['uid'] ) ? intval( wp_unslash( $_GET['uid'] ) ) : 0;
            $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

            if ( $uid > 0 && ! empty( $token ) ) {
                $result = self::verify_token( $uid, $token );
                if ( $result === true ) {
                    // Successful verification
                    wp_safe_redirect( add_query_arg( 'nbe_verification', 'success', site_url( '/login' ) ) );
                    exit;
                }

                // Failure (expired or invalid)
                wp_safe_redirect( add_query_arg( 'nbe_verification', 'failure', site_url( '/login' ) ) );
                exit;
            }
        }
    }

    /**
     * Generate a token, store hashed version and expiry, and send verification email.
     *
     * @param int $user_id
     * @return bool True on sending mail (token generated), false on failure.
     */
    public static function generate_and_send( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            return false;
        }

        $token = wp_generate_password( 24, false );
        $hashed = wp_hash_password( $token );
        $expires = time() + self::EXPIRY;

        update_user_meta( $user_id, 'nbe_verification_token_hashed', $hashed );
        update_user_meta( $user_id, 'nbe_verification_expires', $expires );
        update_user_meta( $user_id, 'nbe_email_verified', 0 );

        return self::send_verification_email( $user_id, $token );
    }

    /**
     * Verify a token for a user.
     *
     * @param int $user_id
     * @param string $token
     * @return bool True if verified, false otherwise.
     */
    public static function verify_token( $user_id, $token ) {
        $hashed = get_user_meta( $user_id, 'nbe_verification_token_hashed', true );
        $expires = intval( get_user_meta( $user_id, 'nbe_verification_expires', true ) );

        if ( empty( $hashed ) || empty( $expires ) ) {
            return false;
        }

        if ( time() > $expires ) {
            // Token expired
            delete_user_meta( $user_id, 'nbe_verification_token_hashed' );
            delete_user_meta( $user_id, 'nbe_verification_expires' );
            return false;
        }

        if ( wp_check_password( $token, $hashed ) ) {
            update_user_meta( $user_id, 'nbe_email_verified', 1 );
            // Remove token
            delete_user_meta( $user_id, 'nbe_verification_token_hashed' );
            delete_user_meta( $user_id, 'nbe_verification_expires' );
            return true;
        }

        return false;
    }

    /**
     * Send the verification email with a plaintext token link.
     *
     * @param int $user_id
     * @param string $token
     * @return bool
     */
    public static function send_verification_email( $user_id, $token ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            return false;
        }

        $verify_url = add_query_arg(
            [
                'nbe_verify' => '1',
                'uid'        => $user_id,
                'token'      => rawurlencode( $token ),
            ],
            site_url( '/' )
        );

        $subject = sprintf( /* translators: %s: site name */ __( '[%s] Verify your email address', 'newsblenda-editorial' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

        $message = '';
        $message .= sprintf( __( 'Hi %s,', 'newsblenda-editorial' ), esc_html( $user->display_name ) ) . "\n\n";
        $message .= __( 'Thank you for registering. Please verify your email address by clicking the link below within 24 hours:', 'newsblenda-editorial' ) . "\n\n";
        $message .= esc_url( $verify_url ) . "\n\n";
        $message .= __( 'If you did not register, please ignore this email.', 'newsblenda-editorial' ) . "\n";

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        return wp_mail( $user->user_email, $subject, $message, $headers );
    }
}

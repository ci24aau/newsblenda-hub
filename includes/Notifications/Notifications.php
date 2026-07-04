<?php
namespace Newsblenda\Editorial\Notifications;

class Notifications {
    protected static $option_name = 'nbe_email_templates';

    public static function init() {
        // default hooks
        add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 10, 1 );
        add_action( 'nbe_user_verified', [ __CLASS__, 'on_user_verified' ], 10, 1 );

        add_action( 'nbe_article_approved', [ __CLASS__, 'on_article_approved' ], 10, 3 );
        add_action( 'nbe_article_rejected', [ __CLASS__, 'on_article_rejected' ], 10, 4 );
        add_action( 'nbe_article_revision_requested', [ __CLASS__, 'on_article_revision_requested' ], 10, 4 );

        add_action( 'nbe_earnings_paid', [ __CLASS__, 'on_earnings_paid' ], 10, 3 );

        // password reset hooks (plugin's frontend should trigger nbe_password_reset_requested) 
        add_action( 'nbe_password_reset_requested', [ __CLASS__, 'on_password_reset_requested' ], 10, 2 );
        add_action( 'password_reset', [ __CLASS__, 'on_password_reset_completed' ], 10, 2 );
    }

    public static function get_defaults() {
        $site = get_bloginfo( 'name' );
        return [
            'registration' => [
                'subject' => "Welcome to {$site}! Please verify your email",
                'body' => "Hi {user_display_name},<br/><br/>Thanks for registering at {site_name}. Please verify your email by clicking the link below:<br/><br/>{verification_link}<br/><br/>If you didn't register, ignore this message.<br/><br/>{site_name}",
            ],
            'verification' => [
                'subject' => "Your email is verified",
                'body' => "Hi {user_display_name},<br/><br/>Your email address has been successfully verified. You can now log in to {site_name}.<br/><br/>{site_name}",
            ],
            'approval' => [
                'subject' => "Your article has been approved: {post_title}",
                'body' => "Hi {user_display_name},<br/><br/>Good news — your article <a href=\"{post_link}\">{post_title}</a> has been approved and published.<br/><br/>Congratulations!<br/><br/>{site_name}",
            ],
            'rejection' => [
                'subject' => "Your article was rejected: {post_title}",
                'body' => "Hi {user_display_name},<br/><br/>We're sorry — your article <a href=\"{post_link}\">{post_title}</a> was not accepted. Reason: {comments}.<br/><br/>Please review and resubmit if appropriate.<br/><br/>{site_name}",
            ],
            'revision' => [
                'subject' => "Revision requested for: {post_title}",
                'body' => "Hi {user_display_name},<br/><br/>Editors have requested revisions for your article <a href=\"{post_link}\">{post_title}</a>.<br/><br/>Comments: {comments}<br/><br/>Please update the article and resubmit.<br/><br/>{site_name}",
            ],
            'password_reset' => [
                'subject' => "Password Reset for {site_name}",
                'body' => "Hi {user_display_name},<br/><br/>We received a request to reset your password. Click the link below to reset it:<br/><br/>{reset_link}<br/><br/>If you didn't request this, ignore this message.<br/><br/>{site_name}",
            ],
            'password_reset_completed' => [
                'subject' => "Your password has been changed",
                'body' => "Hi {user_display_name},<br/><br/>This is a confirmation that the password for your account {user_login} at {site_name} has just been changed.<br/><br/>If you did not perform this action, please contact support immediately.<br/><br/>{site_name}",
            ],
            'earnings_payment' => [
                'subject' => "Payout processed: {amount}",
                'body' => "Hi {user_display_name},<br/><br/>A payout of {amount} has been processed for your account on {date}.<br/><br/>Payout ID: {payout_id}<br/><br/>Thank you,<br/>{site_name}",
            ],
        ];
    }

    public static function get_templates() {
        $defaults = self::get_defaults();
        $saved = get_option( self::$option_name, [] );
        return wp_parse_args( $saved, $defaults );
    }

    public static function get_template( $type ) {
        $templates = self::get_templates();
        return isset( $templates[ $type ] ) ? $templates[ $type ] : [ 'subject' => '', 'body' => '' ];
    }

    public static function render_template( $type, $replacements = [] ) {
        $tpl = self::get_template( $type );
        $subject = $tpl['subject'] ?? '';
        $body = $tpl['body'] ?? '';
        if ( $replacements ) {
            $search = array_map( function( $k ) { return '{' . $k . '}'; }, array_keys( $replacements ) );
            $replace = array_values( $replacements );
            $subject = str_replace( $search, $replace, $subject );
            $body = str_replace( $search, $replace, $body );
        }
        return [ 'subject' => $subject, 'body' => $body ];
    }

    public static function send( $type, $user_id, $context = [] ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return false;
        }
        $site = get_bloginfo( 'name' );
        $defaults = [
            'site_name' => $site,
            'user_display_name' => $user->display_name,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'date' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
        ];
        $replacements = wp_parse_args( $context, $defaults );

        // Prepare template
        $rendered = self::render_template( $type, $replacements );
        $subject = $rendered['subject'];
        $body = $rendered['body'];

        // Headers
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $from_email = get_option( 'nbe_from_email', '' );
        if ( $from_email ) {
            $from_name = get_option( 'nbe_from_name', $site );
            $headers[] = 'From: ' . wp_specialchars_decode( $from_name ) . ' <' . sanitize_email( $from_email ) . '>';
        }

        $to = $user->user_email;
        return wp_mail( $to, wp_strip_all_tags( $subject ), $body, $headers );
    }

    /** Hooked handlers **/
    public static function on_user_register( $user_id ) {
        // try to build verification link from user meta
        $token = get_user_meta( $user_id, 'nbe_email_verification_token', true );
        $link = '';
        if ( $token ) {
            $link = home_url( '/nbe-verify-email?token=' . rawurlencode( $token ) );
        }
        self::send( 'registration', $user_id, [ 'verification_link' => $link ] );
    }

    public static function on_user_verified( $user_id ) {
        self::send( 'verification', $user_id );
    }

    public static function on_article_approved( $post_id, $author_id, $reviewer_id = 0 ) {
        $post = get_post( $post_id );
        $link = $post ? get_permalink( $post ) : '';
        self::send( 'approval', $author_id, [ 'post_title' => $post ? $post->post_title : '', 'post_link' => $link ] );
    }

    public static function on_article_rejected( $post_id, $author_id, $reviewer_id = 0, $comments = '' ) {
        $post = get_post( $post_id );
        $link = $post ? get_permalink( $post ) : '';
        self::send( 'rejection', $author_id, [ 'post_title' => $post ? $post->post_title : '', 'post_link' => $link, 'comments' => $comments ] );
    }

    public static function on_article_revision_requested( $post_id, $author_id, $reviewer_id = 0, $comments = '' ) {
        $post = get_post( $post_id );
        $link = $post ? get_edit_post_link( $post ) : '';
        self::send( 'revision', $author_id, [ 'post_title' => $post ? $post->post_title : '', 'post_link' => $link, 'comments' => $comments ] );
    }

    public static function on_password_reset_requested( $user_id, $reset_link ) {
        self::send( 'password_reset', $user_id, [ 'reset_link' => $reset_link ] );
    }

    public static function on_password_reset_completed( $user, $new_pass ) {
        // $user can be WP_User object or user ID
        $user_id = is_object( $user ) ? $user->ID : intval( $user );
        self::send( 'password_reset_completed', $user_id );
    }

    public static function on_earnings_paid( $author_id, $amount, $payout_id = 0 ) {
        // Format amount using locale
        $amount_f = number_format_i18n( $amount, 2 );
        self::send( 'earnings_payment', $author_id, [ 'amount' => $amount_f, 'payout_id' => $payout_id ] );
    }
}

// Boot
Notifications::init();

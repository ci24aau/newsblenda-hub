<?php
namespace Newsblenda\Editorial\Earnings;

class ViewTracker {
    public static function record_view( int $post_id, int $author_id = 0 ) : bool {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_views';
        $ip = self::get_ip();
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_kses_post( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $now = current_time( 'mysql' );

        $data = [
            'post_id' => $post_id,
            'author_id' => $author_id,
            'ip' => $ip,
            'user_agent' => $ua,
            'created_at' => $now,
        ];
        $format = [ '%d', '%d', '%s', '%s', '%s' ];
        $inserted = $wpdb->insert( $table, $data, $format );
        return $inserted !== false;
    }

    protected static function get_ip() : string {
        // basic IP retrieval
        $ip_keys = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // if X_FORWARDED_FOR contains comma-separated list, take first
                if ( strpos( $ip, ',' ) !== false ) {
                    $parts = explode( ',', $ip );
                    $ip = trim( $parts[0] );
                }
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}

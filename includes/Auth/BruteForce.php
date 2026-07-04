<?php

namespace Newsblenda\Editorial\Auth;

use wpdb;

/**
 * Simple brute-force protection helper.
 */
class BruteForce {
    /**
     * Max attempts before blocking.
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Block duration in seconds.
     */
    const BLOCK_DURATION = 15 * 60; // 15 minutes

    /**
     * Record a failed login attempt.
     *
     * @param string $identifier Username or email
     * @param string $ip
     * @return void
     */
    public static function record_failed_attempt( $identifier, $ip ) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_login_attempts';
        $now = current_time( 'mysql' );

        // Use prepared statements
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE identifier = %s AND ip = %s", $identifier, $ip ) );

        if ( $row ) {
            $attempts = (int) $row->attempts + 1;
            $blocked_until = null;
            if ( $attempts >= self::MAX_ATTEMPTS ) {
                $blocked_until = date( 'Y-m-d H:i:s', time() + self::BLOCK_DURATION );
            }

            $wpdb->update(
                $table,
                [ 'attempts' => $attempts, 'last_attempt' => $now, 'blocked_until' => $blocked_until ],
                [ 'id' => $row->id ],
                [ '%d', '%s', '%s' ],
                [ '%d' ]
            );
        } else {
            $attempts = 1;
            $blocked_until = null;
            if ( $attempts >= self::MAX_ATTEMPTS ) {
                $blocked_until = date( 'Y-m-d H:i:s', time() + self::BLOCK_DURATION );
            }
            $wpdb->insert(
                $table,
                [ 'identifier' => $identifier, 'ip' => $ip, 'attempts' => $attempts, 'last_attempt' => $now, 'blocked_until' => $blocked_until ],
                [ '%s', '%s', '%d', '%s', '%s' ]
            );
        }
    }

    /**
     * Clear attempts after successful login.
     *
     * @param string $identifier
     * @param string $ip
     * @return void
     */
    public static function clear_attempts( $identifier, $ip ) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_login_attempts';
        $wpdb->delete( $table, [ 'identifier' => $identifier, 'ip' => $ip ], [ '%s', '%s' ] );
    }

    /**
     * Determine whether the identifier/ip is currently blocked.
     *
     * @param string $identifier
     * @param string $ip
     * @return bool
     */
    public static function is_blocked( $identifier, $ip ) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_login_attempts';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE identifier = %s AND ip = %s", $identifier, $ip ) );
        if ( ! $row ) {
            return false;
        }

        if ( ! empty( $row->blocked_until ) ) {
            $blocked_until = strtotime( $row->blocked_until );
            if ( $blocked_until > time() ) {
                return true;
            }
        }

        return false;
    }
}

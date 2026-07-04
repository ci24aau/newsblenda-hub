<?php

namespace Newsblenda\Editorial\Validation;

use WP_Post;

/**
 * Article validation engine.
 *
 * Runs a set of checks on article data and stores a validation report in post meta
 * when a corresponding WP post exists, otherwise falls back to storing the report
 * in an option keyed by article id.
 *
 * Checks performed:
 * - Minimum / maximum word count
 * - Internal links presence
 * - Featured image present
 * - Duplicate titles
 * - Missing external sources
 * - SEO fields (title & meta description)
 *
 * Usage:
 *   $report = ArticleValidator::validate_by_article_id( $article_id );
 *
 */
class ArticleValidator {
    // Default rules; these can be overridden by plugin settings in the future
    public const MIN_WORDS = 300;
    public const MAX_WORDS = 2500;
    public const MIN_SEO_TITLE = 10;
    public const MAX_SEO_TITLE = 70;
    public const MIN_META_DESC = 50;
    public const MAX_META_DESC = 160;
    public const MIN_INTERNAL_LINKS = 1;
    public const MIN_EXTERNAL_SOURCES = 1;

    /**
     * Run validation given an article array (as returned from DB)
     *
     * @param array|object $article Article record. Prefer object with properties id, title, content, featured_image_id, seo_title, meta_description, post_id
     * @return array Validation report
     */
    public static function validate( $article ) : array {
        global $wpdb;

        // Normalize to object for property access
        if ( is_array( $article ) ) {
            $article = (object) $article;
        }

        $id = isset( $article->id ) ? intval( $article->id ) : 0;
        $title = isset( $article->title ) ? (string) $article->title : '';
        $content = isset( $article->content ) ? (string) $article->content : '';
        $featured = isset( $article->featured_image_id ) ? intval( $article->featured_image_id ) : 0;
        $seo_title = isset( $article->seo_title ) ? (string) $article->seo_title : '';
        $meta_desc = isset( $article->meta_description ) ? (string) $article->meta_description : '';

        $report = [
            'article_id' => $id,
            'checked_at' => current_time( 'mysql' ),
            'results' => [],
            'summary' => [
                'passed' => true,
                'errors' => 0,
                'warnings' => 0,
            ],
        ];

        // Word count
        $word_count = self::word_count( wp_strip_all_tags( $content ) );
        $passes = true;
        $message = '';
        if ( $word_count < self::MIN_WORDS ) {
            $passes = false;
            $message = sprintf( 'Too short: %d words (minimum %d).', $word_count, self::MIN_WORDS );
        } elseif ( $word_count > self::MAX_WORDS ) {
            $passes = false;
            $message = sprintf( 'Too long: %d words (maximum %d).', $word_count, self::MAX_WORDS );
        } else {
            $message = sprintf( 'Word count OK: %d words.', $word_count );
        }
        $report['results']['word_count'] = [
            'pass' => $passes,
            'value' => $word_count,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['errors']++;
        }

        // Internal links count
        $internal_links = self::count_internal_links( $content );
        $passes = $internal_links >= self::MIN_INTERNAL_LINKS;
        $message = $passes ? sprintf( '%d internal links found.', $internal_links ) : sprintf( 'Not enough internal links: %d found (minimum %d).', $internal_links, self::MIN_INTERNAL_LINKS );
        $report['results']['internal_links'] = [
            'pass' => $passes,
            'value' => $internal_links,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['warnings']++;
        }

        // Featured image
        $has_featured = false;
        if ( $featured > 0 ) {
            $has_featured = true;
        } elseif ( isset( $article->post_id ) && $article->post_id ) {
            $thumb = get_post_thumbnail_id( intval( $article->post_id ) );
            if ( $thumb ) {
                $has_featured = true;
            }
        }
        $passes = $has_featured;
        $message = $passes ? 'Featured image present.' : 'Missing featured image.';
        $report['results']['featured_image'] = [
            'pass' => $passes,
            'value' => $has_featured,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['warnings']++;
        }

        // Duplicate title
        $duplicate = false;
        if ( ! empty( $title ) ) {
            // Check custom table first
            $table = $wpdb->prefix . 'nbe_articles';
            $sql = "SELECT COUNT(*) FROM {$table} WHERE LOWER( TRIM(title) ) = LOWER( TRIM( %s ) )";
            $params = [ $title ];
            if ( $id > 0 ) {
                $sql .= ' AND id != %d';
                $params[] = $id;
            }
            $count = intval( $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ) );
            if ( $count > 0 ) {
                $duplicate = true;
            } else {
                // Check posts table as fallback
                $post_count = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_title = %s AND post_status IN ('publish','draft','pending')", $title ) ) );
                if ( $post_count > 0 ) {
                    $duplicate = true;
                }
            }
        }
        $passes = ! $duplicate;
        $message = $passes ? 'Title is unique.' : 'Duplicate title detected.';
        $report['results']['duplicate_title'] = [
            'pass' => $passes,
            'value' => (int) $duplicate,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['warnings']++;
        }

        // External sources (links to other domains) count
        $external_links = self::count_external_links( $content );
        $passes = $external_links >= self::MIN_EXTERNAL_SOURCES;
        $message = $passes ? sprintf( '%d external sources found.', $external_links ) : sprintf( 'Missing external sources: %d found (minimum %d).', $external_links, self::MIN_EXTERNAL_SOURCES );
        $report['results']['external_sources'] = [
            'pass' => $passes,
            'value' => $external_links,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['warnings']++;
        }

        // SEO title
        $len = mb_strlen( trim( $seo_title ) );
        $passes = $len >= self::MIN_SEO_TITLE && $len <= self::MAX_SEO_TITLE;
        $message = $passes ? sprintf( 'SEO title length OK (%d chars).', $len ) : sprintf( 'SEO title length issue: %d chars (recommended %d-%d).', $len, self::MIN_SEO_TITLE, self::MAX_SEO_TITLE );
        $report['results']['seo_title'] = [
            'pass' => $passes,
            'value' => $len,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['warnings']++;
        }

        // Meta description
        $len = mb_strlen( trim( $meta_desc ) );
        $passes = $len >= self::MIN_META_DESC && $len <= self::MAX_META_DESC;
        $message = $passes ? sprintf( 'Meta description length OK (%d chars).', $len ) : sprintf( 'Meta description length issue: %d chars (recommended %d-%d).', $len, self::MIN_META_DESC, self::MAX_META_DESC );
        $report['results']['meta_description'] = [
            'pass' => $passes,
            'value' => $len,
            'message' => $message,
        ];
        if ( ! $passes ) {
            $report['summary']['passed'] = false;
            $report['summary']['warnings']++;
        }

        // Finalize
        $report['summary']['errors'] = (int) $report['summary']['errors'];
        $report['summary']['warnings'] = (int) $report['summary']['warnings'];

        // Persist
        self::save_report( $id, $report, $article );

        return $report;
    }

    /**
     * Validate an article by its custom table ID. Fetches record from nbe_articles.
     *
     * @param int $article_id
     * @return array
     */
    public static function validate_by_article_id( int $article_id ) : array {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_articles';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $article_id ) );
        if ( ! $row ) {
            return [
                'article_id' => $article_id,
                'checked_at' => current_time( 'mysql' ),
                'error' => 'Article not found',
            ];
        }
        return self::validate( $row );
    }

    /**
     * Save the report to postmeta if post_id exists, otherwise fallback to option
     *
     * @param int $article_id
     * @param array $report
     * @param object|null $article_row
     */
    protected static function save_report( int $article_id, array $report, $article_row = null ) : void {
        // Prefer post_id if present
        $post_id = 0;
        if ( $article_row && isset( $article_row->post_id ) ) {
            $post_id = intval( $article_row->post_id );
        }

        if ( $post_id > 0 && get_post( $post_id ) instanceof WP_Post ) {
            update_post_meta( $post_id, 'nbe_validation_report', $report );
        } else {
            // Fallback to option storage keyed by article id
            update_option( 'nbe_validation_report_' . $article_id, $report );
        }
    }

    /**
     * Count internal links pointing to the same site.
     *
     * @param string $content
     * @return int
     */
    protected static function count_internal_links( string $content ) : int {
        $site = parse_url( home_url(), PHP_URL_HOST );
        if ( ! $site ) {
            return 0;
        }
        $links = self::extract_links( $content );
        $count = 0;
        foreach ( $links as $href ) {
            $host = parse_url( $href, PHP_URL_HOST );
            if ( $host && $host === $site ) {
                $count++;
            } elseif ( empty( $host ) && strpos( $href, '/' ) === 0 ) {
                // relative link
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count external links (sources) — links to other domains.
     *
     * @param string $content
     * @return int
     */
    protected static function count_external_links( string $content ) : int {
        $site = parse_url( home_url(), PHP_URL_HOST );
        $links = self::extract_links( $content );
        $count = 0;
        foreach ( $links as $href ) {
            $host = parse_url( $href, PHP_URL_HOST );
            if ( $host && $host !== $site ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Extract hrefs from HTML content.
     *
     * @param string $content
     * @return array
     */
    protected static function extract_links( string $content ) : array {
        $links = [];
        if ( empty( $content ) ) {
            return $links;
        }
        // Use DOMDocument to parse
        libxml_use_internal_errors( true );
        $dom = new \DOMDocument();
        // Ensure we have a utf-8 wrapper
        $html = '<?xml encoding="utf-8" ?>' . $content;
        if ( @$dom->loadHTML( $html, LIBXML_NOWARNING | LIBXML_NOERROR ) === false ) {
            libxml_clear_errors();
            return $links;
        }
        libxml_clear_errors();
        $anchors = $dom->getElementsByTagName( 'a' );
        foreach ( $anchors as $a ) {
            $href = $a->getAttribute( 'href' );
            if ( ! empty( $href ) ) {
                $links[] = $href;
            }
        }
        return $links;
    }

    /**
     * Count words using a robust method.
     *
     * @param string $text
     * @return int
     */
    protected static function word_count( string $text ) : int {
        $text = trim( preg_replace( '/\s+/', ' ', $text ) );
        if ( $text === '' ) {
            return 0;
        }
        $words = str_word_count( html_entity_decode( strip_tags( $text ) ), 0 );
        return (int) $words;
    }
}

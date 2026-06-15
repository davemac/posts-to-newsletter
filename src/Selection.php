<?php
/**
 * Shared helpers for the newsletter post selection: the stored IDs, the ordered
 * post query, and the byline. Centralises logic previously duplicated across
 * Curation, Renderer and Push.
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Selection accessors.
 */
class Selection {

	/**
	 * Option storing the ordered list of selected post IDs.
	 *
	 * @var string
	 */
	public const OPTION = 'ptn_newsletter_post_ids';

	/**
	 * Option storing the edition's subject line (per-edition content, not config).
	 *
	 * @var string
	 */
	public const SUBJECT_OPTION = 'ptn_subject';

	/**
	 * Option storing the edition's intro line (per-edition override of the
	 * Settings default; may contain the {firstname} token).
	 *
	 * @var string
	 */
	public const INTRO_OPTION = 'ptn_intro';

	/**
	 * Upper bound on how many posts a newsletter can contain. Caps the stored
	 * selection and the size of the ordered-posts query.
	 *
	 * @var int
	 */
	public const MAX_SELECTION = 30;

	/**
	 * Clean a raw value into a list of positive integer IDs.
	 *
	 * @param mixed $raw Raw IDs (array-ish).
	 * @return array<int, int>
	 */
	public static function sanitize( $raw ): array {
		return array_values( array_filter( array_map( 'absint', (array) $raw ) ) );
	}

	/**
	 * The stored, cleaned selection IDs.
	 *
	 * @return array<int, int>
	 */
	public static function ids(): array {
		return self::sanitize( get_option( self::OPTION, array() ) );
	}

	/**
	 * Published post IDs for the given ID list, preserving the given order.
	 *
	 * @param array<int, int> $ids Post IDs.
	 * @return array<int, int>
	 */
	public static function posts( array $ids ): array {
		$ids = array_slice( self::sanitize( $ids ), 0, self::MAX_SELECTION );
		if ( empty( $ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => count( $ids ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);

		return $query->posts;
	}

	/**
	 * The ordered, published, currently-selected post IDs.
	 *
	 * @return array<int, int>
	 */
	public static function ordered(): array {
		return self::posts( self::ids() );
	}

	/**
	 * The edition subject line.
	 *
	 * Resolves the stored subject, falling back to the lead article's title so a
	 * never-touched edition still has a sensible subject. Returns an empty string
	 * when neither is available, leaving the site-name fallback to the caller (the
	 * email template and the Pro push share this chain so they cannot diverge).
	 *
	 * @return string
	 */
	public static function subject(): string {
		$stored = sanitize_text_field( (string) get_option( self::SUBJECT_OPTION, '' ) );
		if ( '' !== $stored ) {
			return $stored;
		}

		$ids = self::ids();
		if ( ! empty( $ids ) ) {
			$title = get_the_title( $ids[0] );
			if ( '' !== $title ) {
				// The subject is a plain-text field; get_the_title() returns HTML
				// entities (e.g. &#8217; for a curly apostrophe), so decode them.
				return html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
			}
		}

		return '';
	}

	/**
	 * The edition's per-edition intro override, or an empty string when unset
	 * (the caller then falls back to the Settings default intro). May contain the
	 * {firstname} token, which the caller resolves.
	 *
	 * @return string
	 */
	public static function intro(): string {
		return sanitize_text_field( (string) get_option( self::INTRO_OPTION, '' ) );
	}

	/**
	 * Byline for a post, honouring Co-Authors Plus when active.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function byline( int $post_id ): string {
		if ( function_exists( 'get_coauthors' ) ) {
			$authors = get_coauthors( $post_id );
			if ( ! empty( $authors ) ) {
				$names = array_map( static fn( $a ) => $a->display_name, $authors );
				return implode( ', ', array_filter( $names ) );
			}
		}

		$name = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
		return ! empty( $name ) ? $name : get_bloginfo( 'name' );
	}
}

<?php
/**
 * Shared helpers for the curated post selection: the stored IDs, the ordered
 * post query, and the byline. Centralises logic previously duplicated across
 * Curation, Renderer and Push.
 *
 * @package Cnl
 */

namespace Cnl;

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
	public const OPTION = 'cnl_newsletter_post_ids';

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
		$ids = self::sanitize( $ids );
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

<?php
/**
 * WordPress Abilities API registration: exposes the newsletter's curation and
 * preview surface to agents (and the wp-abilities/v1 REST namespace).
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability category and the read/curation abilities. Only the
 * registration callbacks touch the Abilities API, and they only run on the
 * API's init hooks (present from WordPress 6.9), so older installs are a no-op.
 */
class Abilities {

	/**
	 * Ability category id and shared write capability.
	 */
	public const CATEGORY = 'posts-to-newsletter';

	/**
	 * Capability required for every ability (matches the curation screen/REST).
	 *
	 * @var string
	 */
	private const CAPABILITY = 'edit_others_posts';

	/**
	 * Upper bound on search results.
	 *
	 * @var int
	 */
	private const SEARCH_MAX = 50;

	/**
	 * Renderer, for the preview ability.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Register the ability category. Runs on wp_abilities_api_categories_init.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Posts to Newsletter', 'posts-to-newsletter' ),
				'description' => __( 'Curate, inspect and preview the newsletter.', 'posts-to-newsletter' ),
			)
		);
	}

	/**
	 * Register the abilities. Runs on wp_abilities_api_init.
	 *
	 * @return void
	 */
	public function register(): void {
		$post_list_schema = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array( 'type' => 'integer' ),
					'title'  => array( 'type' => 'string' ),
					'author' => array( 'type' => 'string' ),
					'date'   => array( 'type' => 'string' ),
					'url'    => array( 'type' => 'string' ),
				),
			),
		);

		wp_register_ability(
			'posts-to-newsletter/search-posts',
			array(
				'label'               => __( 'Search posts for the newsletter', 'posts-to-newsletter' ),
				'description'         => __( 'Search published posts (or list the most recent) as candidates for the newsletter.', 'posts-to-newsletter' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'query' => array(
							'type'        => 'string',
							'description' => __( 'Optional search term. Omit to list the most recent posts.', 'posts-to-newsletter' ),
						),
						'limit' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => self::SEARCH_MAX,
							'default'     => 20,
							'description' => __( 'Maximum number of posts to return.', 'posts-to-newsletter' ),
						),
					),
				),
				'output_schema'       => $post_list_schema,
				'permission_callback' => array( $this, 'can_manage' ),
				'execute_callback'    => array( $this, 'search_posts' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);

		wp_register_ability(
			'posts-to-newsletter/get-selection',
			array(
				'label'               => __( 'Get the current newsletter selection', 'posts-to-newsletter' ),
				'description'         => __( 'Return the posts currently selected for the newsletter, in order.', 'posts-to-newsletter' ),
				'category'            => self::CATEGORY,
				'output_schema'       => $post_list_schema,
				'permission_callback' => array( $this, 'can_manage' ),
				'execute_callback'    => array( $this, 'get_selection' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);

		wp_register_ability(
			'posts-to-newsletter/render-preview',
			array(
				'label'               => __( 'Render a newsletter preview', 'posts-to-newsletter' ),
				'description'         => __( 'Return the rendered newsletter email HTML for a platform.', 'posts-to-newsletter' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'platform' => array(
							'type'        => 'string',
							'enum'        => array( 'campaignmonitor', 'mailchimp' ),
							'default'     => 'campaignmonitor',
							'description' => __( 'Target platform whose merge tags should be used.', 'posts-to-newsletter' ),
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'string',
					'description' => __( 'The rendered email HTML.', 'posts-to-newsletter' ),
				),
				'permission_callback' => array( $this, 'can_manage' ),
				'execute_callback'    => array( $this, 'render_preview' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);

		wp_register_ability(
			'posts-to-newsletter/set-selection',
			array(
				'label'               => __( 'Set the newsletter selection', 'posts-to-newsletter' ),
				'description'         => __( 'Replace the ordered list of posts selected for the newsletter.', 'posts-to-newsletter' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'ids' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'maxItems'    => Selection::MAX_SELECTION,
							'description' => __( 'Ordered post IDs to select for the newsletter.', 'posts-to-newsletter' ),
						),
					),
					'required'   => array( 'ids' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'saved' => array( 'type' => 'boolean' ),
						'count' => array( 'type' => 'integer' ),
						'ids'   => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					),
				),
				'permission_callback' => array( $this, 'can_manage' ),
				'execute_callback'    => array( $this, 'set_selection' ),
				'meta'                => array( 'show_in_rest' => true ),
			)
		);
	}

	/**
	 * Permission check shared by every ability.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return bool
	 */
	public function can_manage( $input = null ): bool {
		return current_user_can( self::CAPABILITY );
	}

	/**
	 * Search published posts (or list recent), returning a compact post list.
	 *
	 * @param mixed $input Validated input.
	 * @return array<int, array<string, mixed>>
	 */
	public function search_posts( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$query = isset( $input['query'] ) ? sanitize_text_field( (string) $input['query'] ) : '';
		$limit = isset( $input['limit'] ) ? (int) $input['limit'] : 20;
		$limit = max( 1, min( self::SEARCH_MAX, $limit ) );

		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
		);

		if ( '' !== $query ) {
			$args['s'] = $query;
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		$result = new WP_Query( $args );
		if ( ! empty( $result->posts ) ) {
			_prime_post_caches( $result->posts, true, true );
		}

		return array_map( array( $this, 'format_post' ), array_map( 'intval', $result->posts ) );
	}

	/**
	 * The current ordered newsletter selection as a compact post list.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return array<int, array<string, mixed>>
	 */
	public function get_selection( $input = null ): array {
		return array_map( array( $this, 'format_post' ), Selection::ordered() );
	}

	/**
	 * Render the newsletter email HTML for a platform.
	 *
	 * @param mixed $input Validated input.
	 * @return string
	 */
	public function render_preview( $input ): string {
		$input    = is_array( $input ) ? $input : array();
		$platform = $this->renderer->resolve_platform( isset( $input['platform'] ) ? (string) $input['platform'] : 'campaignmonitor' );

		return $this->renderer->render( $platform );
	}

	/**
	 * Replace the stored, ordered newsletter selection.
	 *
	 * @param mixed $input Validated input.
	 * @return array<string, mixed>
	 */
	public function set_selection( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$ids   = Selection::sanitize( $input['ids'] ?? array() );
		$ids   = array_slice( $ids, 0, Selection::MAX_SELECTION );

		update_option( Selection::OPTION, $ids );

		return array(
			'saved' => true,
			'count' => count( $ids ),
			'ids'   => $ids,
		);
	}

	/**
	 * Shape a post ID into the compact list item used by the abilities.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	private function format_post( int $post_id ): array {
		return array(
			'id'     => $post_id,
			'title'  => (string) get_the_title( $post_id ),
			'author' => Selection::byline( $post_id ),
			'date'   => (string) get_the_date( 'Y-m-d', $post_id ),
			'url'    => (string) get_permalink( $post_id ),
		);
	}
}

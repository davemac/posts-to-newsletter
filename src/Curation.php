<?php
/**
 * Curation admin screen: pick, order and search posts for the newsletter.
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the drag-and-drop picker and stores the selected, ordered post IDs.
 */
class Curation {

	public const PAGE      = 'posts-to-newsletter';
	public const REST_NS   = 'posts-to-newsletter/v1';

	private const PER_PAGE   = 50;
	private const CAPABILITY = 'edit_others_posts';

	/**
	 * Register the top-level Newsletter admin menu.
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_menu_page(
			__( 'Newsletter', 'posts-to-newsletter' ),
			__( 'Newsletter', 'posts-to-newsletter' ),
			self::CAPABILITY,
			self::PAGE,
			array( $this, 'render_admin_page' ),
			'dashicons-email-alt',
			26
		);
	}

	/**
	 * Enqueue the curation assets on its page only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ): void {
		if ( 'toplevel_page_' . self::PAGE !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'ptn-admin', URL . 'assets/css/admin.css', array(), Plugin::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script(
			'ptn-admin',
			URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			Plugin::asset_version( 'assets/js/admin.js' ),
			true
		);

		wp_localize_script(
			'ptn-admin',
			'ptnNewsletter',
			array(
				'saveUrl'   => esc_url_raw( rest_url( self::REST_NS . '/selection' ) ),
				'searchUrl' => esc_url_raw( rest_url( self::REST_NS . '/search' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'i18n'      => array(
					'saving'     => __( 'Saving…', 'posts-to-newsletter' ),
					/* translators: %d: number of selected articles. */
					'savedOne'   => __( 'Saved — %d article selected', 'posts-to-newsletter' ),
					/* translators: %d: number of selected articles. */
					'savedMany'  => __( 'Saved — %d articles selected', 'posts-to-newsletter' ),
					'saveFailed' => __( 'Save failed — please try again', 'posts-to-newsletter' ),
					'add'        => __( 'Add', 'posts-to-newsletter' ),
					'remove'     => __( 'Remove', 'posts-to-newsletter' ),
					'noMatches'  => __( 'No matching articles.', 'posts-to-newsletter' ),
				),
			)
		);
	}

	/**
	 * Register the selection + search REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$can = static function () {
			return current_user_can( self::CAPABILITY );
		};

		register_rest_route(
			self::REST_NS,
			'/selection',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_selection' ),
				'permission_callback' => $can,
				'args'                => array(
					'ids' => array(
						'required' => true,
						'type'     => 'array',
						'maxItems' => Selection::MAX_SELECTION,
						'items'    => array( 'type' => 'integer' ),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NS,
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'search_articles' ),
				'permission_callback' => $can,
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);
	}

	/**
	 * Save the ordered selection.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_selection( WP_REST_Request $request ): WP_REST_Response {
		$ids = Selection::sanitize( $request->get_param( 'ids' ) );
		update_option( Selection::OPTION, $ids );

		return new WP_REST_Response( array( 'saved' => true, 'count' => count( $ids ) ), 200 );
	}

	/**
	 * Search published posts, returning rendered list items.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function search_articles( WP_REST_Request $request ): WP_REST_Response {
		$search = trim( (string) $request->get_param( 'q' ) );

		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => self::PER_PAGE,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
		);

		if ( '' !== $search ) {
			$args['s'] = $search;
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		$query = new WP_Query( $args );

		if ( ! empty( $query->posts ) ) {
			_prime_post_caches( $query->posts, true, true );
		}

		$items = array();
		foreach ( $query->posts as $post_id ) {
			ob_start();
			$this->render_item( (int) $post_id, false );
			$items[] = array( 'id' => (int) $post_id, 'html' => ob_get_clean() );
		}

		return new WP_REST_Response( $items, 200 );
	}

	/**
	 * Render the curation admin page.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		$selected_ids   = Selection::ids();
		$selected_posts = Selection::posts( $selected_ids );
		$recent_posts   = $this->query_recent();
		$preview_cm     = add_query_arg( array( Renderer::PLATFORM_VAR => 'campaignmonitor' ), home_url( '/ptn-newsletter/' ) );
		$preview_mc     = add_query_arg( array( Renderer::PLATFORM_VAR => 'mailchimp' ), home_url( '/ptn-newsletter/' ) );
		$settings_url   = admin_url( 'admin.php?page=' . Settings::PAGE );

		require DIR . 'templates/curation-page.php';
	}

	/**
	 * Render one article list item.
	 *
	 * @param int  $post_id     Post ID.
	 * @param bool $is_selected Whether it sits in the selected column.
	 * @return void
	 */
	public function render_item( int $post_id, bool $is_selected ): void {
		$thumb  = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, array( 70, 50 ) ) : '';
		$author = Selection::byline( $post_id );

		echo '<li class="ptn-item" data-id="' . esc_attr( (string) $post_id ) . '">';
		echo '<span class="ptn-handle dashicons dashicons-menu" aria-hidden="true"></span>';
		echo '<span class="ptn-thumb">' . wp_kses_post( $thumb ) . '</span>';
		echo '<span class="ptn-meta"><span class="ptn-title">' . esc_html( get_the_title( $post_id ) ) . '</span>';
		echo '<span class="ptn-author">' . esc_html( $author ) . '</span>';
		echo '<span class="ptn-date">' . esc_html( get_the_date( '', $post_id ) ) . '</span></span>';
		if ( $is_selected ) {
			echo '<button type="button" class="button-link ptn-remove" aria-label="' . esc_attr__( 'Remove', 'posts-to-newsletter' ) . '">&times;</button>';
		} else {
			echo '<button type="button" class="button ptn-add">' . esc_html__( 'Add', 'posts-to-newsletter' ) . '</button>';
		}
		echo '</li>';
	}

	/**
	 * Latest published posts.
	 *
	 * @return array<int, int>
	 */
	private function query_recent(): array {
		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => self::PER_PAGE,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);
		return $query->posts;
	}
}

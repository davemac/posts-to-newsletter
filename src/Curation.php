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
			__( 'Posts to Newsletter Builder', 'dmc-posts-to-newsletter-builder' ),
			__( 'Posts to Newsletter Builder', 'dmc-posts-to-newsletter-builder' ),
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

		wp_enqueue_style( 'dmc-ptn-admin', URL . 'assets/css/admin.css', array(), Plugin::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script(
			'dmc-ptn-admin',
			URL . 'assets/js/admin.js',
			array( 'jquery' ),
			Plugin::asset_version( 'assets/js/admin.js' ),
			true
		);

		wp_localize_script(
			'dmc-ptn-admin',
			'dmcPtnNewsletter',
			array(
				'saveUrl'   => esc_url_raw( rest_url( self::REST_NS . '/selection' ) ),
				'searchUrl' => esc_url_raw( rest_url( self::REST_NS . '/search' ) ),
				'cardUrl'   => esc_url_raw( rest_url( self::REST_NS . '/card' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'i18n'      => array(
					'saving'        => __( 'Saving…', 'dmc-posts-to-newsletter-builder' ),
					/* translators: %d: number of selected articles. */
					'saved'         => __( 'Saved · %d selected', 'dmc-posts-to-newsletter-builder' ),
					'saveFailed'    => __( 'Save failed — please try again', 'dmc-posts-to-newsletter-builder' ),
					'add'           => __( 'Add', 'dmc-posts-to-newsletter-builder' ),
					'added'         => __( 'Added', 'dmc-posts-to-newsletter-builder' ),
					'remove'        => __( 'Remove', 'dmc-posts-to-newsletter-builder' ),
					'noMatches'     => __( 'No matching articles.', 'dmc-posts-to-newsletter-builder' ),
					'noMatchesHint' => __( 'Try a different search or clear the filter.', 'dmc-posts-to-newsletter-builder' ),
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
					'ids'      => array(
						'required' => true,
						'type'     => 'array',
						'maxItems' => Selection::MAX_SELECTION,
						'items'    => array( 'type' => 'integer' ),
					),
					// The edition's content fields ride along on the same autosave.
					// All optional: omitting one leaves its stored value untouched.
					'subject'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'intro'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'template' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
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

		register_rest_route(
			self::REST_NS,
			'/card',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'preview_card' ),
				'permission_callback' => $can,
				'args'                => array(
					'id' => array(
						'required' => true,
						'type'     => 'integer',
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

		// Persist the edition's content fields when supplied (each is independent,
		// so a request that sends only ids leaves them as they were).
		$subject = $request->get_param( 'subject' );
		if ( null !== $subject ) {
			update_option( Selection::SUBJECT_OPTION, (string) $subject );
		}
		$intro = $request->get_param( 'intro' );
		if ( null !== $intro ) {
			update_option( Selection::INTRO_OPTION, (string) $intro );
		}
		$template = $request->get_param( 'template' );
		if ( null !== $template ) {
			update_option( Templates::OPTION, Templates::sanitize( (string) $template ) );
		}

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
			$this->render_item( (int) $post_id );
			$items[] = array( 'id' => (int) $post_id, 'html' => ob_get_clean() );
		}

		return new WP_REST_Response( $items, 200 );
	}

	/**
	 * Return the live-canvas card markup for one article (used when an article is
	 * added from the left pane).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function preview_card( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );

		ob_start();
		if ( 0 !== $id && 'publish' === get_post_status( $id ) ) {
			$this->render_canvas_card( $id );
		}

		return new WP_REST_Response( array( 'id' => $id, 'html' => ob_get_clean() ), 200 );
	}

	/**
	 * Render the curation admin page.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		$settings         = new Settings();
		$selected_ids     = Selection::ids();
		$selected_posts   = Selection::posts( $selected_ids );
		$recent_posts     = $this->query_recent();
		$categories       = get_categories( array( 'orderby' => 'count', 'order' => 'DESC' ) );
		$accent_color     = (string) $settings->get( 'accent_color' );
		$brand_color      = (string) $settings->get( 'brand_color' );
		$preview_cm       = add_query_arg( array( Renderer::PLATFORM_VAR => 'campaignmonitor' ), home_url( '/ptn-newsletter/' ) );
		$preview_mc       = add_query_arg( array( Renderer::PLATFORM_VAR => 'mailchimp' ), home_url( '/ptn-newsletter/' ) );
		$settings_url     = admin_url( 'admin.php?page=' . Settings::PAGE );
		$subject          = Selection::subject();
		$templates        = Templates::all();
		$current_template = Templates::current();
		$logo_url         = $settings->logo_url();
		$hero_url         = $settings->hero_url();
		$site_name        = (string) $settings->get( 'site_name' );
		$subscribe_url    = (string) $settings->get( 'subscribe_url' );
		// The intro is inline-editable in the canvas (per-edition), passed raw with
		// the {firstname} token intact — the editor sees and edits the literal token
		// and the email resolves it on render.
		$intro            = Selection::intro();

		require DIR . 'templates/curation-page.php';
	}

	/**
	 * Render one article as a live-canvas card: the email card (Renderer) wrapped
	 * with the drag/remove controls, so the canvas is the email two-up.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function render_canvas_card( int $post_id ): void {
		$settings   = new Settings();
		$renderer   = new Renderer( $settings );
		$image_size = (string) $settings->get( 'image_size' );
		$accent     = (string) $settings->get( 'accent_color' );

		require DIR . 'templates/canvas-card.php';
	}

	/**
	 * Render one available article row by including its template part.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function render_item( int $post_id ): void {
		$thumb    = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, array( 96, 69 ) ) : '';
		$byline   = Selection::byline( $post_id );
		$cats     = get_the_category( $post_id );
		$category = ! empty( $cats ) ? $cats[0] : null;
		$cat_ids  = ! empty( $cats ) ? array_map( 'intval', wp_list_pluck( $cats, 'term_id' ) ) : array();

		require DIR . 'templates/article-item.php';
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

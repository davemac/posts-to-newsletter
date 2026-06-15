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
					'saving'        => __( 'Saving…', 'posts-to-newsletter' ),
					/* translators: %d: number of selected articles. */
					'saved'         => __( 'Saved · %d selected', 'posts-to-newsletter' ),
					'saveFailed'    => __( 'Save failed — please try again', 'posts-to-newsletter' ),
					'add'           => __( 'Add', 'posts-to-newsletter' ),
					'remove'        => __( 'Remove', 'posts-to-newsletter' ),
					'noMatches'     => __( 'No matching articles.', 'posts-to-newsletter' ),
					'noMatchesHint' => __( 'Try a different search or clear the filter.', 'posts-to-newsletter' ),
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
					'ids'          => array(
						'required' => true,
						'type'     => 'array',
						'maxItems' => Selection::MAX_SELECTION,
						'items'    => array( 'type' => 'integer' ),
					),
					// The edition's content fields ride along on the same autosave.
					// All optional: omitting one leaves its stored value untouched.
					'subject'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'preview_text' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'template'     => array(
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
		if ( null !== $request->get_param( 'subject' ) ) {
			update_option( Selection::SUBJECT_OPTION, (string) $request->get_param( 'subject' ) );
		}
		if ( null !== $request->get_param( 'preview_text' ) ) {
			update_option( Selection::PREVIEW_OPTION, (string) $request->get_param( 'preview_text' ) );
		}
		if ( null !== $request->get_param( 'template' ) ) {
			update_option( Templates::OPTION, Templates::sanitize( (string) $request->get_param( 'template' ) ) );
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
		$selected_ids     = Selection::ids();
		$selected_posts   = Selection::posts( $selected_ids );
		$recent_posts     = $this->query_recent();
		$categories       = get_categories( array( 'orderby' => 'count', 'order' => 'DESC' ) );
		$accent_color     = ( new Settings() )->get( 'accent_color' );
		$preview_cm       = add_query_arg( array( Renderer::PLATFORM_VAR => 'campaignmonitor' ), home_url( '/ptn-newsletter/' ) );
		$preview_mc       = add_query_arg( array( Renderer::PLATFORM_VAR => 'mailchimp' ), home_url( '/ptn-newsletter/' ) );
		$settings_url     = admin_url( 'admin.php?page=' . Settings::PAGE );
		$subject          = Selection::subject();
		$preview_text     = Selection::preview_text();
		$templates        = Templates::all();
		$current_template = Templates::current();

		require DIR . 'templates/curation-page.php';
	}

	/**
	 * Render one article list item.
	 *
	 * The same markup serves both columns; CSS shows the drag handle and order
	 * index only inside the selected tray, so an item keeps both pieces when the
	 * JS moves it between columns without re-rendering.
	 *
	 * @param int  $post_id     Post ID.
	 * @param bool $is_selected Whether it sits in the selected column.
	 * @return void
	 */
	public function render_item( int $post_id, bool $is_selected ): void {
		$thumb    = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, array( 96, 69 ) ) : '';
		$byline   = Selection::byline( $post_id );
		$cats     = get_the_category( $post_id );
		$category = ! empty( $cats ) ? $cats[0] : null;
		$cat_ids  = ! empty( $cats ) ? array_map( 'intval', wp_list_pluck( $cats, 'term_id' ) ) : array();

		echo '<li class="ptn-item row" data-id="' . esc_attr( (string) $post_id ) . '" data-cats="' . esc_attr( implode( ',', $cat_ids ) ) . '">';

		// Order index (filled by a CSS counter inside the tray) and drag handle.
		echo '<span class="row__index" aria-hidden="true"></span>';
		echo '<span class="ptn-handle handle" aria-hidden="true">';
		echo self::icon( 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup.
		echo '</span>';

		// Thumbnail, or a striped placeholder when the post has no featured image.
		if ( '' !== $thumb ) {
			echo '<span class="thumb">' . wp_kses_post( $thumb ) . '</span>';
		} else {
			echo '<span class="thumb thumb--ph">';
			echo self::icon( 'image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup.
			echo '</span>';
		}

		// Title + meta pills: date, author (accent-tinted) and the first category.
		echo '<span class="meta">';
		echo '<span class="meta__title">' . esc_html( get_the_title( $post_id ) ) . '</span>';
		echo '<span class="meta__sub">';
		if ( '' !== $byline ) {
			echo '<span class="authorpill">' . esc_html( $byline ) . '</span>';
		}
		echo '<span class="datepill">' . esc_html( get_the_date( '', $post_id ) ) . '</span>';
		if ( null !== $category ) {
			echo '<span class="catpill">' . esc_html( $category->name ) . '</span>';
		}
		echo '</span>'; // .meta__sub
		echo '</span>'; // .meta

		// Add / Remove control.
		if ( $is_selected ) {
			echo '<button type="button" class="ptn-remove removebtn" aria-label="' . esc_attr__( 'Remove', 'posts-to-newsletter' ) . '">';
			echo self::icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup.
			echo '</button>';
		} else {
			echo '<button type="button" class="ptn-add addbtn">';
			echo self::icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup.
			echo '<span>' . esc_html__( 'Add', 'posts-to-newsletter' ) . '</span>';
			echo '</button>';
		}

		echo '</li>';
	}

	/**
	 * Return an inline stroke icon by name.
	 *
	 * Static, developer-defined SVG markup (echoed raw — never user input), so
	 * the curation list and the JS-built controls share one icon set.
	 *
	 * @param string $name Icon name.
	 * @return string SVG markup, or an empty string for an unknown name.
	 */
	public static function icon( string $name ): string {
		$icons = array(
			'search' => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>',
			'plus'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
			'x'      => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>',
			'check'  => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
			'grip'   => '<svg class="ico" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="6" r="1.4" fill="currentColor"/><circle cx="15" cy="6" r="1.4" fill="currentColor"/><circle cx="9" cy="12" r="1.4" fill="currentColor"/><circle cx="15" cy="12" r="1.4" fill="currentColor"/><circle cx="9" cy="18" r="1.4" fill="currentColor"/><circle cx="15" cy="18" r="1.4" fill="currentColor"/></svg>',
			'image'  => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18v14H3z"/><path d="M3 16l5-5 4 4 3-3 6 6"/></svg>',
			'eye'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
			'copy'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 9h10v10H9z"/><path d="M5 15V5h10"/></svg>',
			'send'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>',
			'gear'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',
		);

		return $icons[ $name ] ?? '';
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

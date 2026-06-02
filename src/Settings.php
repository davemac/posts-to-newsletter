<?php
/**
 * Plugin settings: storage, defaults, resolved getters, and the settings page.
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

defined( 'ABSPATH' ) || exit;

/**
 * Stores branding, sender and platform-credential settings, and renders the
 * settings admin page.
 */
class Settings {

	public const OPTION       = 'ptn_settings';
	public const PAGE         = 'posts-to-newsletter-settings';
	public const PARENT       = 'posts-to-newsletter';
	public const CAPABILITY   = 'manage_options';
	public const SAVE_ACTION  = 'ptn_save_settings';

	/**
	 * Default settings. Branding defaults derive from the WordPress site.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		$defaults = array(
			'site_name'             => get_bloginfo( 'name' ),
			'logo_id'               => 0,
			'hero_id'               => 0,
			'brand_color'           => '#cc3300',
			'accent_color'          => '#e32441',
			'subscribe_url'         => home_url( '/' ),
			'intro'                 => 'Hi {firstname}, here\'s a round-up of our top stories.',
			'image_size'            => 'large',
			'from_name'             => get_bloginfo( 'name' ),
			'from_email'            => get_bloginfo( 'admin_email' ),
			'reply_to'              => get_bloginfo( 'admin_email' ),
		);

		/**
		 * Filter the default settings.
		 *
		 * Add-ons can register their own default keys (for example the premium
		 * push layer's platform-credential fields), keeping them in the shared
		 * ptn_settings option.
		 *
		 * @param array<string, mixed> $defaults Default settings.
		 */
		return apply_filters( 'posts_to_newsletter_settings_defaults', $defaults );
	}

	/**
	 * Return all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $this->defaults() );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( string $key ) {
		$all = $this->all();
		return $all[ $key ] ?? null;
	}

	/**
	 * The image sizes offered for article thumbnails (used by the settings UI and
	 * by save-time validation, so the two never drift apart).
	 *
	 * @return array<int, string>
	 */
	public function allowed_image_sizes(): array {
		return array_values(
			array_unique(
				array_merge(
					array( 'medium', 'medium_large', 'large', 'full' ),
					get_intermediate_image_sizes()
				)
			)
		);
	}

	/**
	 * Sanitise a hex colour, falling back when the value is empty or malformed.
	 *
	 * sanitize_hex_color() returns null for a malformed value and '' for empty;
	 * either would persist a broken colour, so substitute the fallback.
	 *
	 * @param mixed  $value    Submitted colour.
	 * @param string $fallback Colour to use when the submitted value is unusable.
	 * @return string
	 */
	private function clean_hex( $value, string $fallback ): string {
		$hex = sanitize_hex_color( (string) $value );
		return ( null === $hex || '' === $hex ) ? $fallback : $hex;
	}

	/**
	 * Resolve the logo URL: chosen attachment, else the theme custom logo, else site icon.
	 *
	 * @return string
	 */
	public function logo_url(): string {
		$logo_id = (int) $this->get( 'logo_id' );
		if ( 0 < $logo_id ) {
			$url = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( ! empty( $url ) ) {
				return $url;
			}
		}

		$custom = (int) get_theme_mod( 'custom_logo' );
		if ( 0 < $custom ) {
			$url = wp_get_attachment_image_url( $custom, 'full' );
			if ( ! empty( $url ) ) {
				return $url;
			}
		}

		return (string) get_site_icon_url( 512 );
	}

	/**
	 * Resolve the hero image URL, or empty string when none is set.
	 *
	 * @return string
	 */
	public function hero_url(): string {
		$hero_id = (int) $this->get( 'hero_id' );
		if ( 0 < $hero_id ) {
			$url = wp_get_attachment_image_url( $hero_id, 'full' );
			if ( ! empty( $url ) ) {
				return $url;
			}
		}
		return '';
	}

	/**
	 * Register the settings page as a submenu of the Newsletter menu.
	 *
	 * @return void
	 */
	public function register_settings_page(): void {
		add_submenu_page(
			self::PARENT,
			__( 'Newsletter Settings', 'posts-to-newsletter' ),
			__( 'Settings', 'posts-to-newsletter' ),
			self::CAPABILITY,
			self::PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue the media picker on the settings page.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ): void {
		if ( false === strpos( (string) $hook_suffix, self::PAGE ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'ptn-settings',
			URL . 'assets/js/settings.js',
			array( 'jquery' ),
			Plugin::asset_version( 'assets/js/settings.js' ),
			true
		);
		wp_localize_script(
			'ptn-settings',
			'ptnSettings',
			array(
				'i18n' => array(
					'chooseImage' => __( 'Choose image', 'posts-to-newsletter' ),
					'useImage'    => __( 'Use this image', 'posts-to-newsletter' ),
					'unsaved'     => __( 'Unsaved changes', 'posts-to-newsletter' ),
				),
			)
		);
		wp_enqueue_style(
			'ptn-admin',
			URL . 'assets/css/admin.css',
			array(),
			Plugin::asset_version( 'assets/css/admin.css' )
		);
	}

	/**
	 * Persist submitted settings (admin-post handler).
	 *
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'posts-to-newsletter' ) );
		}

		check_admin_referer( self::SAVE_ACTION );

		$in       = wp_unslash( $_POST );
		$existing = $this->all();

		$clean = array(
			'site_name'             => sanitize_text_field( $in['site_name'] ?? '' ),
			'logo_id'               => absint( $in['logo_id'] ?? 0 ),
			'hero_id'               => absint( $in['hero_id'] ?? 0 ),
			'brand_color'           => $this->clean_hex( $in['brand_color'] ?? '', $existing['brand_color'] ),
			'accent_color'          => $this->clean_hex( $in['accent_color'] ?? '', $existing['accent_color'] ),
			'subscribe_url'         => esc_url_raw( $in['subscribe_url'] ?? '' ),
			'intro'                 => sanitize_text_field( $in['intro'] ?? '' ),
			'image_size'            => in_array( $in['image_size'] ?? '', $this->allowed_image_sizes(), true ) ? $in['image_size'] : 'large',
			'from_name'             => sanitize_text_field( $in['from_name'] ?? '' ),
			'from_email'            => sanitize_email( $in['from_email'] ?? '' ),
			'reply_to'              => sanitize_email( $in['reply_to'] ?? '' ),
		);

		/**
		 * Filter the sanitised settings before they are stored.
		 *
		 * Lets add-ons (for example the premium push layer) sanitise and merge
		 * their own submitted fields into the shared ptn_settings option.
		 *
		 * @param array<string, mixed> $clean    Sanitised settings to store.
		 * @param array<string, mixed> $in       Unslashed raw $_POST input.
		 * @param array<string, mixed> $existing Previously stored settings.
		 */
		$clean = apply_filters( 'posts_to_newsletter_settings_save', $clean, $in, $existing );

		update_option( self::OPTION, $clean );

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$s = $this->all();
		require DIR . 'templates/settings-page.php';
	}
}

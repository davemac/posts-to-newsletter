<?php
/**
 * Plugin settings: storage, defaults, resolved getters, and the settings page.
 *
 * @package Cnl
 */

namespace Cnl;

defined( 'ABSPATH' ) || exit;

/**
 * Stores branding, sender and platform-credential settings, and renders the
 * settings admin page.
 */
class Settings {

	public const OPTION       = 'cnl_settings';
	public const PAGE         = 'posts-to-newsletter-settings';
	public const PARENT       = 'posts-to-newsletter';
	public const CAPABILITY   = 'manage_options';
	public const SAVE_ACTION  = 'cnl_save_settings';

	/**
	 * Default settings. Branding defaults derive from the WordPress site.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return array(
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
			'mailchimp_api_key'     => '',
			'mailchimp_audience_id' => '',
			'cm_api_key'            => '',
			'cm_client_id'          => '',
			'cm_list_id'            => '',
		);
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
	 * Resolve the Mailchimp API key (wp-config constant wins over the option).
	 *
	 * @return string
	 */
	public function mailchimp_key(): string {
		if ( defined( 'CNL_MAILCHIMP_API_KEY' ) && '' !== (string) CNL_MAILCHIMP_API_KEY ) {
			return (string) CNL_MAILCHIMP_API_KEY;
		}
		return (string) $this->get( 'mailchimp_api_key' );
	}

	/**
	 * Resolve the Campaign Monitor API key (wp-config constant wins).
	 *
	 * @return string
	 */
	public function cm_key(): string {
		if ( defined( 'CNL_CM_API_KEY' ) && '' !== (string) CNL_CM_API_KEY ) {
			return (string) CNL_CM_API_KEY;
		}
		return (string) $this->get( 'cm_api_key' );
	}

	/**
	 * Resolve the logo URL: chosen attachment, else the theme custom logo, else site icon.
	 *
	 * @return string
	 */
	public function logo_url(): string {
		$logo_id = (int) $this->get( 'logo_id' );
		if ( $logo_id > 0 ) {
			$url = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( ! empty( $url ) ) {
				return $url;
			}
		}

		$custom = (int) get_theme_mod( 'custom_logo' );
		if ( $custom > 0 ) {
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
		if ( $hero_id > 0 ) {
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
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'cnl-settings',
			URL . 'assets/js/settings.js',
			array( 'jquery', 'wp-color-picker' ),
			Plugin::asset_version( 'assets/js/settings.js' ),
			true
		);
		wp_enqueue_style(
			'cnl-admin',
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
			'brand_color'           => sanitize_hex_color( $in['brand_color'] ?? '' ),
			'accent_color'          => sanitize_hex_color( $in['accent_color'] ?? '' ),
			'subscribe_url'         => esc_url_raw( $in['subscribe_url'] ?? '' ),
			'intro'                 => sanitize_text_field( $in['intro'] ?? '' ),
			'image_size'            => sanitize_text_field( $in['image_size'] ?? 'large' ),
			'from_name'             => sanitize_text_field( $in['from_name'] ?? '' ),
			'from_email'            => sanitize_email( $in['from_email'] ?? '' ),
			'reply_to'              => sanitize_email( $in['reply_to'] ?? '' ),
			'mailchimp_audience_id' => sanitize_text_field( $in['mailchimp_audience_id'] ?? '' ),
			'cm_client_id'          => sanitize_text_field( $in['cm_client_id'] ?? '' ),
			'cm_list_id'            => sanitize_text_field( $in['cm_list_id'] ?? '' ),
		);

		// Only overwrite stored API keys when a new value is submitted (fields render blank).
		$clean['mailchimp_api_key'] = '' !== trim( (string) ( $in['mailchimp_api_key'] ?? '' ) )
			? sanitize_text_field( $in['mailchimp_api_key'] )
			: $existing['mailchimp_api_key'];
		$clean['cm_api_key']        = '' !== trim( (string) ( $in['cm_api_key'] ?? '' ) )
			? sanitize_text_field( $in['cm_api_key'] )
			: $existing['cm_api_key'];

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

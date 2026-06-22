<?php
/**
 * Plugin bootstrap: wires hooks and handles activation/migration.
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin's components and registers their hooks.
 */
class Plugin {

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		self::maybe_migrate_options();

		$settings  = new Settings();
		$renderer  = new Renderer( $settings );
		$curation  = new Curation();

		// Public render endpoint.
		add_action( 'init', array( $renderer, 'register_endpoint' ) );
		add_filter( 'query_vars', array( $renderer, 'register_query_var' ) );
		add_action( 'template_redirect', array( $renderer, 'maybe_render' ) );

		// Admin pages + assets.
		add_action( 'admin_menu', array( $curation, 'register_admin_page' ) );
		add_action( 'admin_menu', array( $settings, 'register_settings_page' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $curation, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $settings, 'enqueue_assets' ) );
		add_action( 'admin_post_' . Settings::SAVE_ACTION, array( $settings, 'handle_save' ) );

		// REST routes.
		add_action( 'rest_api_init', array( $curation, 'register_routes' ) );
	}

	/**
	 * Cache-busting asset version from the file's modification time.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	public static function asset_version( string $relative ): string {
		$path  = DIR . $relative;
		$mtime = file_exists( $path ) ? filemtime( $path ) : 0;
		return $mtime > 0 ? (string) $mtime : VERSION;
	}

	/**
	 * Activation: migrate legacy data, register the endpoint, flush rewrites.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::maybe_migrate_options();
		( new Renderer( new Settings() ) )->register_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * One-time migration of the pre-1.0 ptn_ option keys to the dmc_ptn_ prefix.
	 *
	 * The internal prefix was lengthened from ptn_ to dmc_ptn_ so it meets the
	 * WordPress.org four-character minimum. Existing installs already hold data
	 * under the old keys, so each one is copied to its new key and the old key is
	 * removed. Idempotent and cheap: an autoloaded flag short-circuits it after
	 * the first run, and a fresh install simply finds nothing to move.
	 *
	 * @return void
	 */
	public static function maybe_migrate_options(): void {
		if ( '' !== (string) get_option( 'dmc_ptn_migrated', '' ) ) {
			return;
		}

		$map = array(
			'ptn_settings'            => Settings::OPTION,
			'ptn_newsletter_post_ids' => Selection::OPTION,
			'ptn_subject'             => Selection::SUBJECT_OPTION,
			'ptn_intro'               => Selection::INTRO_OPTION,
			'ptn_template'            => Templates::OPTION,
		);

		foreach ( $map as $old => $new ) {
			$legacy = get_option( $old, null );
			if ( null !== $legacy && false === get_option( $new, false ) ) {
				update_option( $new, $legacy );
			}
			delete_option( $old );
		}

		update_option( 'dmc_ptn_migrated', '1' );
	}
}

<?php
/**
 * Plugin bootstrap: wires hooks and handles activation/migration.
 *
 * @package Cnl
 */

namespace Cnl;

use Cnl\Integrations\MailchimpClient;
use Cnl\Integrations\CampaignMonitorClient;

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
		$settings = new Settings();
		$renderer = new Renderer( $settings );
		$curation = new Curation();
		$push     = new Push( $settings, $renderer );

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
		add_action( 'rest_api_init', array( $push, 'register_routes' ) );
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
		Settings::migrate_legacy();
		Curation::migrate_legacy();

		( new Renderer( new Settings() ) )->register_endpoint();
		flush_rewrite_rules();
	}
}

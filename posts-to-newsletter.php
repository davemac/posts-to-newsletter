<?php
/**
 * Plugin Name:       Posts to Newsletter
 * Plugin URI:        https://thecode.com.au/
 * Description:       Turn your posts into a branded newsletter — hand-pick and order them, then push a ready-to-send draft to Mailchimp or Campaign Monitor.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            The Code Company
 * License:           GPL-2.0-or-later
 * Text Domain:       posts-to-newsletter
 *
 * @package Cnl
 */

namespace Cnl;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';

define( 'Cnl\\FILE', __FILE__ );
define( 'Cnl\\DIR', plugin_dir_path( __FILE__ ) );
define( 'Cnl\\URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimal PSR-4 autoloader for the Cnl\ namespace (maps to src/).
 */
spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'Cnl\\' ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( 'Cnl\\' ) ) );
		$path     = DIR . 'src/' . $relative . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		( new Plugin() )->boot();
	}
);

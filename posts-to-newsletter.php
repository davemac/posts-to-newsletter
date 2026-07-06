<?php
/**
 * Plugin Name:       DMC Posts to Newsletter Builder
 * Plugin URI:        https://dmcweb.com.au/newsletter-builder-pro/
 * Description:       Hand-pick and order your posts into a branded HTML email, then preview a platform-ready newsletter to import into Mailchimp or Campaign Monitor.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            DMC Web
 * Author URI:        https://dmcweb.com.au
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dmc-posts-to-newsletter-builder
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.1';

define( 'PostsToNewsletter\\FILE', __FILE__ );
define( 'PostsToNewsletter\\DIR', plugin_dir_path( __FILE__ ) );
define( 'PostsToNewsletter\\URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimal PSR-4 autoloader for the PostsToNewsletter\ namespace (maps to src/).
 */
spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'PostsToNewsletter\\' ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( 'PostsToNewsletter\\' ) ) );
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

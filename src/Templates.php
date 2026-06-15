<?php
/**
 * The registry of selectable newsletter email templates.
 *
 * The free core ships one template; add-ons register more via the
 * posts_to_newsletter_templates filter. Resolution always falls back to the
 * bundled default, so the chosen template degrading (e.g. the add-on that
 * provided it being deactivated) never breaks rendering.
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the available email templates and resolves the chosen one to a file.
 */
class Templates {

	/**
	 * Option storing the edition's chosen template id.
	 *
	 * @var string
	 */
	public const OPTION = 'ptn_template';

	/**
	 * The id of the bundled default template.
	 *
	 * @var string
	 */
	public const DEFAULT_ID = 'newspaper';

	/**
	 * All registered templates, keyed by id.
	 *
	 * @return array<string, array{label:string, file:string}>
	 */
	public static function all(): array {
		$templates = array(
			self::DEFAULT_ID => array(
				'label' => __( 'Newspaper', 'posts-to-newsletter' ),
				'file'  => DIR . 'templates/email.php',
			),
		);

		/**
		 * Filter the registered newsletter email templates.
		 *
		 * Add-ons register extra templates keyed by a unique id, each with a
		 * 'label' and an absolute 'file' path to a partial that consumes the same
		 * variables as the bundled email template (see templates/email.php). The
		 * partial is required inside Renderer::render(), so $this is the Renderer.
		 *
		 * @param array<string, array{label:string, file:string}> $templates Templates by id.
		 */
		$templates = apply_filters( 'posts_to_newsletter_templates', $templates );

		return is_array( $templates ) ? $templates : array();
	}

	/**
	 * The chosen template id, validated against what is registered.
	 *
	 * @return string
	 */
	public static function current(): string {
		return self::sanitize( (string) get_option( self::OPTION, self::DEFAULT_ID ) );
	}

	/**
	 * Normalise an id to a registered template, falling back to the default.
	 *
	 * @param string $id Requested template id.
	 * @return string
	 */
	public static function sanitize( string $id ): string {
		$id = sanitize_key( $id );
		return array_key_exists( $id, self::all() ) ? $id : self::DEFAULT_ID;
	}

	/**
	 * The readable template file for an id, falling back to the bundled default.
	 *
	 * @param string $id Template id.
	 * @return string Absolute file path.
	 */
	public static function file( string $id ): string {
		$all  = self::all();
		$file = (string) ( $all[ $id ]['file'] ?? '' );

		if ( '' !== $file && is_readable( $file ) ) {
			return $file;
		}

		return DIR . 'templates/email.php';
	}
}

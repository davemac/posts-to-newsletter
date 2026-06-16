<?php
/**
 * Renders the newsletter as platform-aware email HTML and serves the
 * public preview/import endpoint.
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the email HTML for a given platform and exposes it at a public URL.
 */
class Renderer {

	public const QUERY_VAR    = 'ptn_newsletter';
	public const PLATFORM_VAR = 'ptn_platform';
	public const TEMPLATE_VAR = 'ptn_template';

	private const PLATFORMS = array( 'campaignmonitor', 'mailchimp' );

	/**
	 * Settings provider.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings provider.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register the pretty rewrite endpoint (/ptn-newsletter/).
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_rule( '^ptn-newsletter/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Allowlist the public query vars.
	 *
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public function register_query_var( $vars ): array {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::PLATFORM_VAR;
		$vars[] = self::TEMPLATE_VAR;
		return $vars;
	}

	/**
	 * Output the rendered email when the endpoint is requested.
	 *
	 * @return void
	 */
	public function maybe_render(): void {
		if ( empty( get_query_var( self::QUERY_VAR ) ) ) {
			return;
		}

		/**
		 * Filter whether the public newsletter render endpoint may be served.
		 *
		 * The endpoint is public by design (Campaign Monitor fetches it server-side),
		 * which exposes the selected posts' titles and excerpts before the newsletter
		 * is sent. Return false to restrict access (for example by IP, a shared secret
		 * query arg, or is_user_logged_in()).
		 *
		 * @param bool $allowed Whether to render. Default true.
		 */
		if ( ! apply_filters( 'posts_to_newsletter_render_allowed', true ) ) {
			status_header( 403 );
			exit;
		}

		$platform = $this->resolve_platform( (string) get_query_var( self::PLATFORM_VAR ) );

		// This is a raw email document for an email platform to import. Tell LiteSpeed
		// not to optimise it — its image Lazy Load injects a JavaScript loader (and it
		// varies output by user-agent), which Campaign Monitor rejects on import. No-op
		// when LiteSpeed is not active.
		do_action( 'litespeed_disable_all', 'Posts to Newsletter raw email output' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party (LiteSpeed) action, intentionally fired.

		header( 'Content-Type: text/html; charset=utf-8' );
		echo $this->render( $platform, (string) get_query_var( self::TEMPLATE_VAR ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed, escaped HTML email.
		exit;
	}

	/**
	 * Validate/normalise the platform, falling back to the default.
	 *
	 * @param string $raw Requested platform.
	 * @return string One of self::PLATFORMS.
	 */
	public function resolve_platform( string $raw ): string {
		return in_array( $raw, self::PLATFORMS, true ) ? $raw : 'campaignmonitor';
	}

	/**
	 * Render the full email HTML for a platform.
	 *
	 * @param string $platform    Target platform.
	 * @param string $template_id Template id to render; empty uses the saved choice.
	 * @return string HTML.
	 */
	public function render( string $platform, string $template_id = '' ): string {
		$template_id   = '' !== $template_id ? Templates::sanitize( $template_id ) : Templates::current();
		$template_file = Templates::file( $template_id );

		$settings   = $this->settings;
		$tokens     = $this->tokens( $platform );
		$posts      = Selection::ordered();
		$subject    = Selection::subject();
		$logo_url   = $settings->logo_url();
		$hero_url   = $settings->hero_url();
		$brand      = (string) $settings->get( 'brand_color' );
		$accent     = (string) $settings->get( 'accent_color' );
		$image_size = (string) $settings->get( 'image_size' );
		$site_name  = (string) $settings->get( 'site_name' );
		$subscribe  = (string) $settings->get( 'subscribe_url' );
		// The edition's per-edition intro; {firstname} resolves to the platform's
		// first-name merge tag.
		$intro      = str_replace( '{firstname}', $tokens['firstname'], Selection::intro() );

		ob_start();
		require $template_file;
		return (string) ob_get_clean();
	}

	/**
	 * Render one article card. Called from the email template.
	 *
	 * @param int    $card_id    Post ID (0 = empty filler cell).
	 * @param string $image_size Image size.
	 * @param string $accent     Accent colour for label/byline.
	 * @return void
	 */
	public function render_card( int $card_id, string $image_size, string $accent ): void {
		if ( 0 === $card_id ) {
			return;
		}

		$permalink  = get_permalink( $card_id );
		$byline     = Selection::byline( $card_id );
		$date       = get_the_date( 'F j, Y', $card_id );
		$excerpt    = wp_trim_words( get_the_excerpt( $card_id ), 22, '…' );
		$image      = has_post_thumbnail( $card_id ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $card_id ), $image_size ) : false;
		$categories = get_the_category( $card_id );
		$category   = ! empty( $categories ) ? $categories[0]->name : '';
		$pill_bg    = $this->tint( $accent );

		require DIR . 'templates/card.php';
	}

	/**
	 * Platform-specific merge tags and footer/preheader markup.
	 *
	 * @param string $platform Target platform.
	 * @return array{firstname:string, footer:string, preheader:string}
	 */
	private function tokens( string $platform ): array {
		$site_name = (string) $this->settings->get( 'site_name' );
		$grey      = 'color:#888888;';

		/* translators: %s: site/publication name. */
		$received    = sprintf( esc_html__( 'You are receiving this email because you subscribed to %s.', 'posts-to-newsletter' ), esc_html( $site_name ) );
		$unsubscribe = esc_html__( 'Unsubscribe', 'posts-to-newsletter' );
		$preferences = esc_html__( 'Update your preferences', 'posts-to-newsletter' );
		$web_version = esc_html__( 'View in browser', 'posts-to-newsletter' );
		$preheader   = esc_html__( 'View this email in your browser.', 'posts-to-newsletter' );

		if ( 'mailchimp' === $platform ) {
			$footer  = $received . '<br />';
			$footer .= '<a href="*|UNSUB|*" style="' . $grey . '">' . $unsubscribe . '</a> &nbsp;|&nbsp; ';
			$footer .= '<a href="*|UPDATE_PROFILE|*" style="' . $grey . '">' . $preferences . '</a> &nbsp;|&nbsp; ';
			$footer .= '<a href="*|ARCHIVE|*" style="' . $grey . '">' . $web_version . '</a><br /><br />';
			$footer .= '*|HTML:LIST_ADDRESS_HTML|*';

			$tokens = array(
				'firstname' => '*|FNAME|*',
				'footer'    => $footer,
				'preheader' => '<a href="*|ARCHIVE|*">' . $preheader . '</a>',
			);
		} else {
			// Campaign Monitor (default).
			$footer  = $received . '<br />';
			$footer .= '<unsubscribe style="' . $grey . '">' . $unsubscribe . '</unsubscribe> &nbsp;|&nbsp; ';
			$footer .= '<preferences style="' . $grey . '">' . $preferences . '</preferences> &nbsp;|&nbsp; ';
			$footer .= '<webversion style="' . $grey . '">' . $web_version . '</webversion><br /><br />';
			$footer .= esc_html( $site_name );

			$tokens = array(
				'firstname' => '[firstname,fallback=there]',
				'footer'    => $footer,
				'preheader' => '<webversion>' . $preheader . '</webversion>',
			);
		}

		/**
		 * Filter the platform merge-tag tokens (firstname/footer/preheader).
		 *
		 * Lets add-ons add or override tokens for a platform without forking this
		 * method.
		 *
		 * @param array{firstname:string, footer:string, preheader:string} $tokens   Tokens.
		 * @param string                                                    $platform Target platform.
		 */
		return apply_filters( 'posts_to_newsletter_platform_tokens', $tokens, $platform );
	}

	/**
	 * Produce a light tint (≈10% on white) of a hex colour for the author pill.
	 *
	 * @param string $hex Hex colour like #3c8504.
	 * @return string Hex tint.
	 */
	private function tint( string $hex ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '#fce9ec';
		}

		$mix = static function ( int $channel ): int {
			return (int) round( ( 0.1 * $channel ) + ( 0.9 * 255 ) );
		};

		$r = $mix( (int) hexdec( substr( $hex, 0, 2 ) ) );
		$g = $mix( (int) hexdec( substr( $hex, 2, 2 ) ) );
		$b = $mix( (int) hexdec( substr( $hex, 4, 2 ) ) );

		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}
}

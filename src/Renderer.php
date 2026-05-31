<?php
/**
 * Renders the curated newsletter as platform-aware email HTML and serves the
 * public preview/import endpoint.
 *
 * @package Cnl
 */

namespace Cnl;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the email HTML for a given platform and exposes it at a public URL.
 */
class Renderer {

	public const QUERY_VAR   = 'cnl_newsletter';
	public const PLATFORM_VAR = 'cnl_platform';

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
	 * Register the pretty rewrite endpoint (/cnl-newsletter/).
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_rule( '^cnl-newsletter/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Whitelist the public query vars.
	 *
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public function register_query_var( $vars ): array {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::PLATFORM_VAR;
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

		$platform = $this->resolve_platform( (string) get_query_var( self::PLATFORM_VAR ) );

		header( 'Content-Type: text/html; charset=utf-8' );
		echo $this->render( $platform ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed, escaped HTML email.
		exit;
	}

	/**
	 * Validate/normalise the platform.
	 *
	 * @param string $raw Requested platform.
	 * @return string One of self::PLATFORMS.
	 */
	private function resolve_platform( string $raw ): string {
		return in_array( $raw, self::PLATFORMS, true ) ? $raw : 'campaignmonitor';
	}

	/**
	 * Render the full email HTML for a platform.
	 *
	 * @param string $platform Target platform.
	 * @return string HTML.
	 */
	public function render( string $platform ): string {
		$settings   = $this->settings;
		$tokens     = $this->tokens( $platform );
		$posts      = Selection::ordered();
		$logo_url   = $settings->logo_url();
		$hero_url   = $settings->hero_url();
		$brand      = (string) $settings->get( 'brand_color' );
		$accent     = (string) $settings->get( 'accent_color' );
		$image_size = (string) $settings->get( 'image_size' );
		$site_name  = (string) $settings->get( 'site_name' );
		$subscribe  = (string) $settings->get( 'subscribe_url' );
		$intro      = str_replace( '{firstname}', $tokens['firstname'], (string) $settings->get( 'intro' ) );

		ob_start();
		require DIR . 'templates/email.php';
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
		$excerpt    = wp_trim_words( get_the_excerpt( $card_id ), 22, '&hellip;' );
		$image      = has_post_thumbnail( $card_id ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $card_id ), $image_size ) : false;
		$categories = get_the_category( $card_id );
		$category   = ! empty( $categories ) ? $categories[0]->name : '';
		$pill_bg    = $this->tint( $accent );

		echo '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">';
		if ( false !== $image ) {
			echo '<tr><td style="padding-bottom:10px;"><a href="' . esc_url( $permalink ) . '"><img src="' . esc_url( $image[0] ) . '" width="270" alt="" style="display:block; width:100%; max-width:270px; height:auto;" /></a></td></tr>';
		}
		if ( '' !== $category ) {
			echo '<tr><td style="font-family:Arial,Helvetica,sans-serif; font-size:12px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; color:' . esc_attr( $accent ) . '; padding-bottom:4px;">' . esc_html( $category ) . '</td></tr>';
		}
		echo '<tr><td style="font-family:Arial,Helvetica,sans-serif; font-size:18px; line-height:23px; font-weight:bold; padding-bottom:6px;"><a href="' . esc_url( $permalink ) . '" style="color:#111111; text-decoration:none;">' . esc_html( get_the_title( $card_id ) ) . '</a></td></tr>';
		echo '<tr><td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; line-height:21px; color:#333333; padding-bottom:12px;">' . esc_html( $excerpt ) . '</td></tr>';
		echo '<tr><td><table role="presentation" cellpadding="0" cellspacing="0"><tr>';
		echo '<td bgcolor="#f3f4f6" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#949494; padding:4px 8px; border-radius:4px;">' . esc_html( $date ) . '</td>';
		echo '<td width="6" style="font-size:0; line-height:0;">&nbsp;</td>';
		echo '<td bgcolor="' . esc_attr( $pill_bg ) . '" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; color:' . esc_attr( $accent ) . '; padding:4px 8px; border-radius:4px;">' . esc_html( $byline ) . '</td>';
		echo '</tr></table></td></tr>';
		echo '</table>';
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

			return array(
				'firstname' => '*|FNAME|*',
				'footer'    => $footer,
				'preheader' => '<a href="*|ARCHIVE|*">' . $preheader . '</a>',
			);
		}

		// Campaign Monitor (default).
		$footer  = $received . '<br />';
		$footer .= '<unsubscribe style="' . $grey . '">' . $unsubscribe . '</unsubscribe> &nbsp;|&nbsp; ';
		$footer .= '<preferences style="' . $grey . '">' . $preferences . '</preferences> &nbsp;|&nbsp; ';
		$footer .= '<webversion style="' . $grey . '">' . $web_version . '</webversion><br /><br />';
		$footer .= esc_html( $site_name );

		return array(
			'firstname' => '[firstname,fallback=there]',
			'footer'    => $footer,
			'preheader' => '<webversion>' . $preheader . '</webversion>',
		);
	}

	/**
	 * Produce a light tint (≈10% on white) of a hex colour for the author pill.
	 *
	 * @param string $hex Hex colour like #e32441.
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

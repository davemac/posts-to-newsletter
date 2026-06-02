<?php
/**
 * Settings admin page.
 *
 * @package PostsToNewsletter
 *
 * @var array<string,mixed> $s    Current settings (merged with defaults).
 * @var \PostsToNewsletter\Settings        $this Settings (for logo_url(), hero_url()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Settings::render_settings_page(), so these variables are method-scoped, not global.

$sizes        = $this->allowed_image_sizes();
$logo_url     = $this->logo_url();
$hero_url     = $this->hero_url();
$curation_url = admin_url( 'admin.php?page=' . \PostsToNewsletter\Curation::PAGE );
?>
<div class="wrap ptn-settings-page">

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'posts-to-newsletter' ); ?></p></div>
	<?php endif; ?>

	<div class="settings__head">
		<img class="ptn-pagelogo" src="<?php echo esc_url( \PostsToNewsletter\URL . 'assets/img/p2n-logo.png' ); ?>" alt="" width="40" height="40" />
		<div>
			<h1><?php esc_html_e( 'Posts to Newsletter Settings', 'posts-to-newsletter' ); ?></h1>
			<p><?php esc_html_e( 'Branding, sender details and platform connections for your newsletter.', 'posts-to-newsletter' ); ?></p>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ptn-settings-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( \PostsToNewsletter\Settings::SAVE_ACTION ); ?>" />
		<?php wp_nonce_field( \PostsToNewsletter\Settings::SAVE_ACTION ); ?>

		<section class="scard">
			<div class="scard__head">
				<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18v14H3z"/><path d="M3 16l5-5 4 4 3-3 6 6"/></svg>
				<h2><?php esc_html_e( 'Branding & content', 'posts-to-newsletter' ); ?></h2>
			</div>
			<div class="scard__body">

				<div class="media-row">
					<div class="ptn-media media">
						<span class="flabel"><?php esc_html_e( 'Logo', 'posts-to-newsletter' ); ?></span>
						<div class="media__frame media__frame--logo">
							<img id="ptn-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" alt="" />
						</div>
						<input type="hidden" id="ptn-logo-id" name="logo_id" value="<?php echo esc_attr( (string) $s['logo_id'] ); ?>" />
						<div class="media__actions">
							<button type="button" class="btn btn--primary ptn-choose" data-target="logo"><?php esc_html_e( 'Choose', 'posts-to-newsletter' ); ?></button>
							<button type="button" class="media__clear ptn-clear" data-target="logo"><?php esc_html_e( 'Clear', 'posts-to-newsletter' ); ?></button>
						</div>
					</div>
					<div class="ptn-media media">
						<span class="flabel"><?php esc_html_e( 'Hero image', 'posts-to-newsletter' ); ?></span>
						<div class="media__frame">
							<img id="ptn-hero-preview" src="<?php echo esc_url( $hero_url ); ?>" alt="" />
						</div>
						<input type="hidden" id="ptn-hero-id" name="hero_id" value="<?php echo esc_attr( (string) $s['hero_id'] ); ?>" />
						<div class="media__actions">
							<button type="button" class="btn btn--primary ptn-choose" data-target="hero"><?php esc_html_e( 'Choose', 'posts-to-newsletter' ); ?></button>
							<button type="button" class="media__clear ptn-clear" data-target="hero"><?php esc_html_e( 'Clear', 'posts-to-newsletter' ); ?></button>
						</div>
					</div>
				</div>

				<div class="grid2">
					<div class="ptn-field field">
						<label for="ptn-site-name"><?php esc_html_e( 'Publication name', 'posts-to-newsletter' ); ?></label>
						<input class="input" name="site_name" id="ptn-site-name" type="text" value="<?php echo esc_attr( $s['site_name'] ); ?>" />
					</div>
					<div class="ptn-field field">
						<label for="ptn-subscribe"><?php esc_html_e( 'Subscribe URL', 'posts-to-newsletter' ); ?></label>
						<input class="input" name="subscribe_url" id="ptn-subscribe" type="url" value="<?php echo esc_attr( $s['subscribe_url'] ); ?>" />
					</div>
					<div class="ptn-field field">
						<label for="ptn-brand"><?php esc_html_e( 'Brand colour', 'posts-to-newsletter' ); ?></label>
						<div class="colorfield">
							<div class="color-row">
								<input type="color" class="ptn-color-swatch" value="<?php echo esc_attr( strtolower( (string) $s['brand_color'] ) ); ?>" aria-label="<?php esc_attr_e( 'Brand colour picker', 'posts-to-newsletter' ); ?>" />
								<input name="brand_color" id="ptn-brand" class="input ptn-color-hex" type="text" value="<?php echo esc_attr( $s['brand_color'] ); ?>" autocomplete="off" spellcheck="false" />
							</div>
							<span class="fhint"><?php esc_html_e( 'Header accents, buttons.', 'posts-to-newsletter' ); ?></span>
						</div>
					</div>
					<div class="ptn-field field">
						<label for="ptn-accent"><?php esc_html_e( 'Accent colour', 'posts-to-newsletter' ); ?></label>
						<div class="colorfield">
							<div class="color-row">
								<input type="color" class="ptn-color-swatch" value="<?php echo esc_attr( strtolower( (string) $s['accent_color'] ) ); ?>" aria-label="<?php esc_attr_e( 'Accent colour picker', 'posts-to-newsletter' ); ?>" />
								<input name="accent_color" id="ptn-accent" class="input ptn-color-hex" type="text" value="<?php echo esc_attr( $s['accent_color'] ); ?>" autocomplete="off" spellcheck="false" />
							</div>
							<span class="fhint"><?php esc_html_e( 'Category labels, author pills.', 'posts-to-newsletter' ); ?></span>
						</div>
					</div>
					<div class="ptn-field field field--full">
						<label for="ptn-intro"><?php esc_html_e( 'Intro text', 'posts-to-newsletter' ); ?></label>
						<textarea class="textarea" name="intro" id="ptn-intro" rows="2"><?php echo esc_textarea( $s['intro'] ); ?></textarea>
						<span class="fhint"><?php esc_html_e( 'Use {firstname} for personalisation (mapped per platform).', 'posts-to-newsletter' ); ?></span>
					</div>
					<div class="ptn-field field">
						<label for="ptn-size"><?php esc_html_e( 'Article image size', 'posts-to-newsletter' ); ?></label>
						<select class="select" name="image_size" id="ptn-size">
							<?php foreach ( $sizes as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $s['image_size'], $size ); ?>><?php echo esc_html( $size ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</section>

		<section class="scard">
			<div class="scard__head">
				<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/></svg>
				<h2><?php esc_html_e( 'Sender', 'posts-to-newsletter' ); ?></h2>
			</div>
			<div class="scard__body">
				<div class="grid3">
					<div class="ptn-field field">
						<label for="ptn-from-name"><?php esc_html_e( 'From name', 'posts-to-newsletter' ); ?></label>
						<input class="input" name="from_name" id="ptn-from-name" type="text" value="<?php echo esc_attr( $s['from_name'] ); ?>" />
					</div>
					<div class="ptn-field field">
						<label for="ptn-from-email"><?php esc_html_e( 'From email', 'posts-to-newsletter' ); ?></label>
						<input class="input" name="from_email" id="ptn-from-email" type="email" value="<?php echo esc_attr( $s['from_email'] ); ?>" />
						<span class="fhint"><?php esc_html_e( 'Must be a verified sender.', 'posts-to-newsletter' ); ?></span>
					</div>
					<div class="ptn-field field">
						<label for="ptn-reply"><?php esc_html_e( 'Reply-to', 'posts-to-newsletter' ); ?></label>
						<input class="input" name="reply_to" id="ptn-reply" type="email" value="<?php echo esc_attr( $s['reply_to'] ); ?>" />
					</div>
				</div>
			</div>
		</section>

		<?php
		/**
		 * Fires inside the settings form, after the built-in cards.
		 *
		 * Add-ons render extra setting cards here (for example the premium
		 * platform-integration cards, wrapped in their own .scols grid).
		 * Submitted fields are caught by the posts_to_newsletter_settings_save
		 * filter.
		 *
		 * @param array<string, mixed> $s Current settings (merged with defaults).
		 */
		do_action( 'posts_to_newsletter_settings_cards', $s );

		// When no add-on provides the platform cards, invite an upgrade in their place.
		if ( ! has_action( 'posts_to_newsletter_settings_cards' ) ) :
			?>
			<section class="scard scard--upsell">
				<div class="scard__head">
					<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/></svg>
					<h2><?php esc_html_e( 'One-click push', 'posts-to-newsletter' ); ?></h2>
				</div>
				<div class="scard__body">
					<p><?php esc_html_e( 'Send your curated newsletter straight to Mailchimp or Campaign Monitor as a ready-to-review draft - no copy and paste.', 'posts-to-newsletter' ); ?></p>
					<p><a class="btn btn--primary" href="https://dmcweb.com.au" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade to Pro', 'posts-to-newsletter' ); ?></a></p>
					<p class="fhint"><?php esc_html_e( 'For now, use the preview / import URLs on the Newsletter screen to import into your platform manually.', 'posts-to-newsletter' ); ?></p>
				</div>
			</section>
			<?php
		endif;
		?>

		<div class="savebar">
			<span class="savebar__note" id="ptn-save-note"><?php esc_html_e( 'All changes saved', 'posts-to-newsletter' ); ?></span>
			<span class="spacer"></span>
			<a class="btn btn--ghost" href="<?php echo esc_url( $curation_url ); ?>"><?php esc_html_e( 'Back to Newsletter', 'posts-to-newsletter' ); ?></a>
			<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Save settings', 'posts-to-newsletter' ); ?></button>
		</div>
	</form>
</div>

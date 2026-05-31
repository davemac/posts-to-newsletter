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

$sizes = $this->allowed_image_sizes();
?>
<div class="wrap ptn-settings-page">
	<h1><?php esc_html_e( 'Newsletter Settings', 'posts-to-newsletter' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'posts-to-newsletter' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( \PostsToNewsletter\Settings::SAVE_ACTION ); ?>" />
		<?php wp_nonce_field( \PostsToNewsletter\Settings::SAVE_ACTION ); ?>

		<div class="ptn-grid">

			<section class="ptn-card ptn-card--full">
				<h2><?php esc_html_e( 'Branding & content', 'posts-to-newsletter' ); ?></h2>

				<div class="ptn-media-row">
					<div class="ptn-media">
						<span class="ptn-label"><?php esc_html_e( 'Logo', 'posts-to-newsletter' ); ?></span>
						<img id="ptn-logo-preview" class="ptn-media-preview" src="<?php echo esc_url( $this->logo_url() ); ?>" alt="" />
						<input type="hidden" id="ptn-logo-id" name="logo_id" value="<?php echo esc_attr( (string) $s['logo_id'] ); ?>" />
						<span><button type="button" class="button ptn-choose" data-target="logo"><?php esc_html_e( 'Choose', 'posts-to-newsletter' ); ?></button>
						<button type="button" class="button-link ptn-clear" data-target="logo"><?php esc_html_e( 'Clear', 'posts-to-newsletter' ); ?></button></span>
					</div>
					<div class="ptn-media">
						<span class="ptn-label"><?php esc_html_e( 'Hero image', 'posts-to-newsletter' ); ?></span>
						<img id="ptn-hero-preview" class="ptn-media-preview" src="<?php echo esc_url( $this->hero_url() ); ?>" alt="" />
						<input type="hidden" id="ptn-hero-id" name="hero_id" value="<?php echo esc_attr( (string) $s['hero_id'] ); ?>" />
						<span><button type="button" class="button ptn-choose" data-target="hero"><?php esc_html_e( 'Choose', 'posts-to-newsletter' ); ?></button>
						<button type="button" class="button-link ptn-clear" data-target="hero"><?php esc_html_e( 'Clear', 'posts-to-newsletter' ); ?></button></span>
					</div>
				</div>

				<div class="ptn-fields">
					<p class="ptn-field">
						<label for="ptn-site-name"><?php esc_html_e( 'Publication name', 'posts-to-newsletter' ); ?></label>
						<input name="site_name" id="ptn-site-name" type="text" value="<?php echo esc_attr( $s['site_name'] ); ?>" />
					</p>
					<p class="ptn-field">
						<label for="ptn-subscribe"><?php esc_html_e( 'Subscribe URL', 'posts-to-newsletter' ); ?></label>
						<input name="subscribe_url" id="ptn-subscribe" type="url" value="<?php echo esc_attr( $s['subscribe_url'] ); ?>" />
					</p>
					<p class="ptn-field">
						<label for="ptn-brand"><?php esc_html_e( 'Brand colour', 'posts-to-newsletter' ); ?></label>
						<input name="brand_color" id="ptn-brand" class="ptn-color" type="text" value="<?php echo esc_attr( $s['brand_color'] ); ?>" data-default-color="#cc3300" />
						<span class="ptn-hint"><?php esc_html_e( 'Header accents, buttons.', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="ptn-field">
						<label for="ptn-accent"><?php esc_html_e( 'Accent colour', 'posts-to-newsletter' ); ?></label>
						<input name="accent_color" id="ptn-accent" class="ptn-color" type="text" value="<?php echo esc_attr( $s['accent_color'] ); ?>" data-default-color="#e32441" />
						<span class="ptn-hint"><?php esc_html_e( 'Category labels, author pills.', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="ptn-field ptn-field--full">
						<label for="ptn-intro"><?php esc_html_e( 'Intro text', 'posts-to-newsletter' ); ?></label>
						<textarea name="intro" id="ptn-intro" rows="2"><?php echo esc_textarea( $s['intro'] ); ?></textarea>
						<span class="ptn-hint"><?php esc_html_e( 'Use {firstname} for personalisation (mapped per platform).', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="ptn-field">
						<label for="ptn-size"><?php esc_html_e( 'Article image size', 'posts-to-newsletter' ); ?></label>
						<select name="image_size" id="ptn-size">
							<?php foreach ( $sizes as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $s['image_size'], $size ); ?>><?php echo esc_html( $size ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>
			</section>

			<section class="ptn-card ptn-card--full">
				<h2><?php esc_html_e( 'Sender', 'posts-to-newsletter' ); ?></h2>
				<div class="ptn-fields ptn-fields--three">
					<p class="ptn-field">
						<label for="ptn-from-name"><?php esc_html_e( 'From name', 'posts-to-newsletter' ); ?></label>
						<input name="from_name" id="ptn-from-name" type="text" value="<?php echo esc_attr( $s['from_name'] ); ?>" />
					</p>
					<p class="ptn-field">
						<label for="ptn-from-email"><?php esc_html_e( 'From email', 'posts-to-newsletter' ); ?></label>
						<input name="from_email" id="ptn-from-email" type="email" value="<?php echo esc_attr( $s['from_email'] ); ?>" />
						<span class="ptn-hint"><?php esc_html_e( 'Must be a verified sender.', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="ptn-field">
						<label for="ptn-reply"><?php esc_html_e( 'Reply-to', 'posts-to-newsletter' ); ?></label>
						<input name="reply_to" id="ptn-reply" type="email" value="<?php echo esc_attr( $s['reply_to'] ); ?>" />
					</p>
				</div>
			</section>

			<?php
			/**
			 * Fires inside the settings grid, after the built-in cards.
			 *
			 * Add-ons render extra setting cards here (for example the premium
			 * platform-integration cards). Submitted fields are caught by the
			 * ptn_settings_save filter.
			 *
			 * @param array<string, mixed> $s Current settings (merged with defaults).
			 */
			do_action( 'posts_to_newsletter_settings_cards', $s );

			// When no add-on provides the platform cards, invite an upgrade in their place.
			if ( ! has_action( 'posts_to_newsletter_settings_cards' ) ) :
				?>
				<section class="ptn-card ptn-card--upsell">
					<h2><?php esc_html_e( 'One-click push', 'posts-to-newsletter' ); ?></h2>
					<p><?php esc_html_e( 'Send your curated newsletter straight to Mailchimp or Campaign Monitor as a ready-to-review draft - no copy and paste.', 'posts-to-newsletter' ); ?></p>
					<p><a class="button button-primary" href="https://thecode.com.au/" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade to Pro', 'posts-to-newsletter' ); ?></a></p>
					<p class="ptn-hint"><?php esc_html_e( 'For now, use the preview / import URLs on the Newsletter screen to import into your platform manually.', 'posts-to-newsletter' ); ?></p>
				</section>
				<?php
			endif;
			?>

		</div>

		<?php submit_button( __( 'Save settings', 'posts-to-newsletter' ) ); ?>
	</form>
</div>

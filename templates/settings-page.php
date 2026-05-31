<?php
/**
 * Settings admin page.
 *
 * @package Cnl
 *
 * @var array<string,mixed> $s    Current settings (merged with defaults).
 * @var \Cnl\Settings        $this Settings (for logo_url(), keys, etc.).
 */

defined( 'ABSPATH' ) || exit;

$mc_key      = $this->mailchimp_key();
$cm_key      = $this->cm_key();
$mc_constant = defined( 'CNL_MAILCHIMP_API_KEY' ) && '' !== (string) CNL_MAILCHIMP_API_KEY;
$cm_constant = defined( 'CNL_CM_API_KEY' ) && '' !== (string) CNL_CM_API_KEY;

// Server-side dropdown data (best-effort; errors shown inline).
$mc_audiences = array();
$mc_error     = '';
if ( '' !== $mc_key ) {
	$res = ( new \Cnl\Integrations\MailchimpClient( $mc_key ) )->get_audiences();
	if ( is_wp_error( $res ) ) {
		$mc_error = $res->get_error_message();
	} else {
		$mc_audiences = $res;
	}
}

$cm_clients = array();
$cm_lists   = array();
$cm_error   = '';
if ( '' !== $cm_key ) {
	$client = new \Cnl\Integrations\CampaignMonitorClient( $cm_key );
	$res    = $client->get_clients();
	if ( is_wp_error( $res ) ) {
		$cm_error = $res->get_error_message();
	} else {
		$cm_clients = $res;
		if ( '' !== (string) $s['cm_client_id'] ) {
			$lists = $client->get_lists( (string) $s['cm_client_id'] );
			if ( ! is_wp_error( $lists ) ) {
				$cm_lists = $lists;
			}
		}
	}
}

$sizes        = array_values( array_unique( array_merge( array( 'medium', 'medium_large', 'large', 'full' ), get_intermediate_image_sizes() ) ) );
$mc_connected = '' !== $mc_key && '' === $mc_error;
$cm_connected = '' !== $cm_key && '' === $cm_error;

/**
 * Render a connection badge.
 *
 * @param bool $connected Whether configured/connected.
 * @param bool $has_key   Whether a key is present.
 * @return string
 */
$badge = static function ( bool $connected, bool $has_key ): string {
	if ( $connected ) {
		return '<span class="cnl-badge cnl-badge--ok">' . esc_html__( 'Connected', 'posts-to-newsletter' ) . '</span>';
	}
	if ( $has_key ) {
		return '<span class="cnl-badge cnl-badge--err">' . esc_html__( 'Error', 'posts-to-newsletter' ) . '</span>';
	}
	return '<span class="cnl-badge">' . esc_html__( 'Not set up', 'posts-to-newsletter' ) . '</span>';
};
?>
<div class="wrap cnl-settings-page">
	<h1><?php esc_html_e( 'Newsletter Settings', 'posts-to-newsletter' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'posts-to-newsletter' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( \Cnl\Settings::SAVE_ACTION ); ?>" />
		<?php wp_nonce_field( \Cnl\Settings::SAVE_ACTION ); ?>

		<div class="cnl-grid">

			<section class="cnl-card cnl-card--full">
				<h2><?php esc_html_e( 'Branding & content', 'posts-to-newsletter' ); ?></h2>

				<div class="cnl-media-row">
					<div class="cnl-media">
						<span class="cnl-label"><?php esc_html_e( 'Logo', 'posts-to-newsletter' ); ?></span>
						<img id="cnl-logo-preview" class="cnl-media-preview" src="<?php echo esc_url( $this->logo_url() ); ?>" alt="" />
						<input type="hidden" id="cnl-logo-id" name="logo_id" value="<?php echo esc_attr( (string) $s['logo_id'] ); ?>" />
						<span><button type="button" class="button cnl-choose" data-target="logo"><?php esc_html_e( 'Choose', 'posts-to-newsletter' ); ?></button>
						<button type="button" class="button-link cnl-clear" data-target="logo"><?php esc_html_e( 'Clear', 'posts-to-newsletter' ); ?></button></span>
					</div>
					<div class="cnl-media">
						<span class="cnl-label"><?php esc_html_e( 'Hero image', 'posts-to-newsletter' ); ?></span>
						<img id="cnl-hero-preview" class="cnl-media-preview" src="<?php echo esc_url( $this->hero_url() ); ?>" alt="" />
						<input type="hidden" id="cnl-hero-id" name="hero_id" value="<?php echo esc_attr( (string) $s['hero_id'] ); ?>" />
						<span><button type="button" class="button cnl-choose" data-target="hero"><?php esc_html_e( 'Choose', 'posts-to-newsletter' ); ?></button>
						<button type="button" class="button-link cnl-clear" data-target="hero"><?php esc_html_e( 'Clear', 'posts-to-newsletter' ); ?></button></span>
					</div>
				</div>

				<div class="cnl-fields">
					<p class="cnl-field">
						<label for="cnl-site-name"><?php esc_html_e( 'Publication name', 'posts-to-newsletter' ); ?></label>
						<input name="site_name" id="cnl-site-name" type="text" value="<?php echo esc_attr( $s['site_name'] ); ?>" />
					</p>
					<p class="cnl-field">
						<label for="cnl-subscribe"><?php esc_html_e( 'Subscribe URL', 'posts-to-newsletter' ); ?></label>
						<input name="subscribe_url" id="cnl-subscribe" type="url" value="<?php echo esc_attr( $s['subscribe_url'] ); ?>" />
					</p>
					<p class="cnl-field">
						<label for="cnl-brand"><?php esc_html_e( 'Brand colour', 'posts-to-newsletter' ); ?></label>
						<input name="brand_color" id="cnl-brand" class="cnl-color" type="text" value="<?php echo esc_attr( $s['brand_color'] ); ?>" data-default-color="#cc3300" />
						<span class="cnl-hint"><?php esc_html_e( 'Header accents, buttons.', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="cnl-field">
						<label for="cnl-accent"><?php esc_html_e( 'Accent colour', 'posts-to-newsletter' ); ?></label>
						<input name="accent_color" id="cnl-accent" class="cnl-color" type="text" value="<?php echo esc_attr( $s['accent_color'] ); ?>" data-default-color="#e32441" />
						<span class="cnl-hint"><?php esc_html_e( 'Category labels, author pills.', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="cnl-field cnl-field--full">
						<label for="cnl-intro"><?php esc_html_e( 'Intro text', 'posts-to-newsletter' ); ?></label>
						<textarea name="intro" id="cnl-intro" rows="2"><?php echo esc_textarea( $s['intro'] ); ?></textarea>
						<span class="cnl-hint"><?php esc_html_e( 'Use {firstname} for personalisation (mapped per platform).', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="cnl-field">
						<label for="cnl-size"><?php esc_html_e( 'Article image size', 'posts-to-newsletter' ); ?></label>
						<select name="image_size" id="cnl-size">
							<?php foreach ( $sizes as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $s['image_size'], $size ); ?>><?php echo esc_html( $size ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>
			</section>

			<section class="cnl-card cnl-card--full">
				<h2><?php esc_html_e( 'Sender', 'posts-to-newsletter' ); ?></h2>
				<div class="cnl-fields cnl-fields--three">
					<p class="cnl-field">
						<label for="cnl-from-name"><?php esc_html_e( 'From name', 'posts-to-newsletter' ); ?></label>
						<input name="from_name" id="cnl-from-name" type="text" value="<?php echo esc_attr( $s['from_name'] ); ?>" />
					</p>
					<p class="cnl-field">
						<label for="cnl-from-email"><?php esc_html_e( 'From email', 'posts-to-newsletter' ); ?></label>
						<input name="from_email" id="cnl-from-email" type="email" value="<?php echo esc_attr( $s['from_email'] ); ?>" />
						<span class="cnl-hint"><?php esc_html_e( 'Must be a verified sender.', 'posts-to-newsletter' ); ?></span>
					</p>
					<p class="cnl-field">
						<label for="cnl-reply"><?php esc_html_e( 'Reply-to', 'posts-to-newsletter' ); ?></label>
						<input name="reply_to" id="cnl-reply" type="email" value="<?php echo esc_attr( $s['reply_to'] ); ?>" />
					</p>
				</div>
			</section>

			<section class="cnl-card">
				<h2><?php esc_html_e( 'Mailchimp', 'posts-to-newsletter' ); ?> <?php echo $badge( $mc_connected, '' !== $mc_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<p class="cnl-field">
					<label for="cnl-mc-key"><?php esc_html_e( 'API key', 'posts-to-newsletter' ); ?></label>
					<?php if ( $mc_constant ) : ?>
						<em><?php esc_html_e( 'Set via the CNL_MAILCHIMP_API_KEY constant.', 'posts-to-newsletter' ); ?></em>
					<?php else : ?>
						<input name="mailchimp_api_key" id="cnl-mc-key" type="password" value="" autocomplete="off" placeholder="<?php echo '' !== $mc_key ? esc_attr__( '•••••• saved — leave blank to keep', 'posts-to-newsletter' ) : ''; ?>" />
					<?php endif; ?>
					<?php if ( '' !== $mc_error ) : ?><span class="cnl-error"><?php echo esc_html( $mc_error ); ?></span><?php endif; ?>
				</p>
				<p class="cnl-field">
					<label for="cnl-mc-aud"><?php esc_html_e( 'Audience', 'posts-to-newsletter' ); ?></label>
					<select name="mailchimp_audience_id" id="cnl-mc-aud" <?php disabled( empty( $mc_audiences ) ); ?>>
						<option value=""><?php esc_html_e( '— Select —', 'posts-to-newsletter' ); ?></option>
						<?php foreach ( $mc_audiences as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['mailchimp_audience_id'], $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( empty( $mc_audiences ) ) : ?><span class="cnl-hint"><?php esc_html_e( 'Save a valid API key to load audiences.', 'posts-to-newsletter' ); ?></span><?php endif; ?>
				</p>
			</section>

			<section class="cnl-card">
				<h2><?php esc_html_e( 'Campaign Monitor', 'posts-to-newsletter' ); ?> <?php echo $badge( $cm_connected, '' !== $cm_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<p class="cnl-field">
					<label for="cnl-cm-key"><?php esc_html_e( 'API key', 'posts-to-newsletter' ); ?></label>
					<?php if ( $cm_constant ) : ?>
						<em><?php esc_html_e( 'Set via the CNL_CM_API_KEY constant.', 'posts-to-newsletter' ); ?></em>
					<?php else : ?>
						<input name="cm_api_key" id="cnl-cm-key" type="password" value="" autocomplete="off" placeholder="<?php echo '' !== $cm_key ? esc_attr__( '•••••• saved — leave blank to keep', 'posts-to-newsletter' ) : ''; ?>" />
					<?php endif; ?>
					<?php if ( '' !== $cm_error ) : ?><span class="cnl-error"><?php echo esc_html( $cm_error ); ?></span><?php endif; ?>
				</p>
				<p class="cnl-field">
					<label for="cnl-cm-client"><?php esc_html_e( 'Client', 'posts-to-newsletter' ); ?></label>
					<select name="cm_client_id" id="cnl-cm-client" <?php disabled( empty( $cm_clients ) ); ?>>
						<option value=""><?php esc_html_e( '— Select —', 'posts-to-newsletter' ); ?></option>
						<?php foreach ( $cm_clients as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['cm_client_id'], $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="cnl-hint"><?php esc_html_e( 'Save after changing client to load its lists.', 'posts-to-newsletter' ); ?></span>
				</p>
				<p class="cnl-field">
					<label for="cnl-cm-list"><?php esc_html_e( 'List', 'posts-to-newsletter' ); ?></label>
					<select name="cm_list_id" id="cnl-cm-list" <?php disabled( empty( $cm_lists ) ); ?>>
						<option value=""><?php esc_html_e( '— Select —', 'posts-to-newsletter' ); ?></option>
						<?php foreach ( $cm_lists as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['cm_list_id'], $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			</section>

		</div>

		<?php submit_button( __( 'Save settings', 'posts-to-newsletter' ) ); ?>
	</form>
</div>

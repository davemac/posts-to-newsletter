<?php
/**
 * Curation admin page.
 *
 * @package Cnl
 *
 * @var array<int,int> $selected_ids   Selected post IDs.
 * @var array<int,int> $selected_posts Selected post IDs (validated/ordered).
 * @var array<int,int> $recent_posts   Latest post IDs.
 * @var string         $preview_cm     Campaign Monitor preview URL.
 * @var string         $preview_mc     Mailchimp preview URL.
 * @var string         $settings_url   Settings page URL.
 * @var \Cnl\Curation  $this           Curation (for render_item()).
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap cnl-curation">
	<h1><?php esc_html_e( 'Newsletter', 'posts-to-newsletter' ); ?></h1>
	<p>
		<?php esc_html_e( 'Click Add to include an article, then drag items in the right-hand column to set the order. Changes save automatically.', 'posts-to-newsletter' ); ?>
		<span class="cn-status" aria-live="polite"></span>
	</p>

	<div class="cnl-actions">
		<button type="button" class="button button-primary cn-push" data-platform="mailchimp"><?php esc_html_e( 'Push to Mailchimp', 'posts-to-newsletter' ); ?></button>
		<button type="button" class="button button-primary cn-push" data-platform="cm"><?php esc_html_e( 'Push to Campaign Monitor', 'posts-to-newsletter' ); ?></button>
		<span class="cn-push-result" aria-live="polite"></span>
		<a class="cnl-settings-link" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'posts-to-newsletter' ); ?></a>
	</div>

	<p class="cn-urls">
		<strong><?php esc_html_e( 'Preview / import URLs:', 'posts-to-newsletter' ); ?></strong>
		Mailchimp <code><?php echo esc_html( $preview_mc ); ?></code> &nbsp;·&nbsp;
		Campaign Monitor <code><?php echo esc_html( $preview_cm ); ?></code>
	</p>

	<div class="cn-columns">
		<div class="cn-col">
			<h2><?php esc_html_e( 'Available articles', 'posts-to-newsletter' ); ?></h2>
			<input type="search" id="cn-search" class="cn-search" placeholder="<?php esc_attr_e( 'Search all articles…', 'posts-to-newsletter' ); ?>" autocomplete="off" />
			<ul id="cn-available" class="cn-list">
				<?php
				foreach ( $recent_posts as $post_id ) {
					if ( in_array( $post_id, $selected_ids, true ) ) {
						continue;
					}
					$this->render_item( (int) $post_id, false );
				}
				?>
			</ul>
		</div>
		<div class="cn-col">
			<h2><?php esc_html_e( 'In the newsletter', 'posts-to-newsletter' ); ?></h2>
			<ul id="cn-selected" class="cn-list cn-sortable">
				<?php
				foreach ( $selected_posts as $post_id ) {
					$this->render_item( (int) $post_id, true );
				}
				?>
			</ul>
		</div>
	</div>
</div>

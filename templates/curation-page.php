<?php
/**
 * Curation admin page.
 *
 * @package PostsToNewsletter
 *
 * @var array<int,int> $selected_ids   Selected post IDs.
 * @var array<int,int> $selected_posts Selected post IDs (validated/ordered).
 * @var array<int,int> $recent_posts   Latest post IDs.
 * @var string         $preview_cm     Campaign Monitor preview URL.
 * @var string         $preview_mc     Mailchimp preview URL.
 * @var string         $settings_url   Settings page URL.
 * @var \PostsToNewsletter\Curation  $this           Curation (for render_item()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_admin_page(), so these variables are method-scoped, not global.
?>
<div class="wrap ptn-curation">
	<h1><?php esc_html_e( 'Newsletter', 'posts-to-newsletter' ); ?></h1>
	<p>
		<?php esc_html_e( 'Click Add to include an article, then drag items in the right-hand column to set the order. Changes save automatically.', 'posts-to-newsletter' ); ?>
		<span class="ptn-status" aria-live="polite"></span>
	</p>

	<p class="ptn-actions-row">
		<?php
		/**
		 * Fires in the curation action bar, outside the platform cards.
		 *
		 * General (non-platform-specific) add-on buttons render here. Platform
		 * push buttons use posts_to_newsletter_platform_actions instead.
		 */
		do_action( 'posts_to_newsletter_curation_actions' );
		?>
		<a class="button button-small ptn-settings-link" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'posts-to-newsletter' ); ?></a>
	</p>

	<?php
	$ptn_platforms = array(
		'mailchimp'       => array(
			'label' => __( 'Mailchimp', 'posts-to-newsletter' ),
			'url'   => $preview_mc,
		),
		'campaignmonitor' => array(
			'label' => __( 'Campaign Monitor', 'posts-to-newsletter' ),
			'url'   => $preview_cm,
		),
	);

	/**
	 * Filters the platform cards shown on the curation screen.
	 *
	 * Add-ons can remove a platform that an editor cannot use (e.g. one whose
	 * API credentials are not configured) so its card does not render at all.
	 * With no add-on active this is unfiltered and every platform shows, since
	 * the core's Preview/Copy URL buttons support manual import without an API.
	 *
	 * @param array<string,array<string,string>> $ptn_platforms Platform cards, keyed by platform (mailchimp|campaignmonitor).
	 */
	$ptn_platforms = apply_filters( 'posts_to_newsletter_platforms', $ptn_platforms );
	?>
	<div class="ptn-platforms">
		<?php foreach ( $ptn_platforms as $ptn_key => $ptn_platform ) : ?>
		<section class="ptn-platform" aria-label="<?php echo esc_attr( $ptn_platform['label'] ); ?>">
			<h2 class="ptn-platform__title"><?php echo esc_html( $ptn_platform['label'] ); ?></h2>

			<div class="ptn-platform__body">
				<div class="ptn-platform__actions">
					<?php
					/**
					 * Fires inside a single platform card on the curation screen.
					 *
					 * Add-ons render that platform's push button and status here, so
					 * each platform's controls stay grouped in its own card.
					 *
					 * @param string $platform Platform key (mailchimp|campaignmonitor).
					 */
					do_action( 'posts_to_newsletter_platform_actions', $ptn_key );
					?>
				</div>

				<span class="ptn-platform__import-row">
					<a class="button button-small" href="<?php echo esc_url( $ptn_platform['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview', 'posts-to-newsletter' ); ?></a>
					<button type="button" class="button button-small ptn-copy-url" data-url="<?php echo esc_url( $ptn_platform['url'] ); ?>"><?php esc_html_e( 'Copy URL', 'posts-to-newsletter' ); ?></button>
				</span>
			</div>
		</section>
		<?php endforeach; ?>
	</div>

	<div class="ptn-columns">
		<div class="ptn-col">
			<h2><?php esc_html_e( 'Available articles', 'posts-to-newsletter' ); ?></h2>
			<input type="search" id="ptn-search" class="ptn-search" placeholder="<?php esc_attr_e( 'Search all articles…', 'posts-to-newsletter' ); ?>" autocomplete="off" />
			<ul id="ptn-available" class="ptn-list">
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
		<div class="ptn-col">
			<h2><?php esc_html_e( 'In the newsletter', 'posts-to-newsletter' ); ?></h2>
			<ul id="ptn-selected" class="ptn-list ptn-sortable">
				<?php
				foreach ( $selected_posts as $post_id ) {
					$this->render_item( (int) $post_id, true );
				}
				?>
			</ul>
		</div>
	</div>
</div>

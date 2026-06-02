<?php
/**
 * Curation admin page.
 *
 * @package PostsToNewsletter
 *
 * @var array<int,int> $selected_ids   Selected post IDs.
 * @var array<int,int> $selected_posts Selected post IDs (validated/ordered).
 * @var array<int,int> $recent_posts   Latest post IDs.
 * @var \WP_Term[]      $categories     Post categories (for the filter chips).
 * @var string         $preview_cm     Campaign Monitor preview URL.
 * @var string         $preview_mc     Mailchimp preview URL.
 * @var string         $settings_url   Settings page URL.
 * @var string         $accent_color   Brand accent colour (drives the author pills).
 * @var \PostsToNewsletter\Curation  $this           Curation (for render_item()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_admin_page(), so these variables are method-scoped, not global.

$ptn_selected_count  = count( $selected_posts );
$ptn_available_count = count( array_diff( $recent_posts, $selected_ids ) );
?>
<div class="wrap ptn-curation" style="--ptn-author-color: <?php echo esc_attr( $accent_color ); ?>">

	<header class="pagehead">
		<img class="ptn-pagelogo" src="<?php echo esc_url( \PostsToNewsletter\URL . 'assets/img/p2n-logo.png' ); ?>" alt="" width="40" height="40" />
		<div class="pagehead__main">
			<h1><?php esc_html_e( 'Posts to Newsletter', 'posts-to-newsletter' ); ?></h1>
			<p><?php esc_html_e( 'Add available articles, then drag and drop in the right-hand column to set the order. Changes save automatically.', 'posts-to-newsletter' ); ?></p>
		</div>
		<div class="pagehead__aside">
			<span class="savechip">
				<span class="dot" aria-hidden="true"></span>
				<span class="ptn-status" aria-live="polite">
					<?php
					/* translators: %d: number of selected articles. */
					printf( esc_html__( 'Saved · %d selected', 'posts-to-newsletter' ), (int) $ptn_selected_count );
					?>
				</span>
			</span>
			<?php
			/**
			 * Fires in the curation action bar, beside the Settings button.
			 *
			 * General (non-platform-specific) add-on buttons render here. Platform
			 * push buttons use posts_to_newsletter_platform_actions instead.
			 */
			do_action( 'posts_to_newsletter_curation_actions' );
			?>
			<a class="ghostbtn ptn-settings-link" href="<?php echo esc_url( $settings_url ); ?>">
				<?php echo $this->icon( 'gear' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
				<span><?php esc_html_e( 'Settings', 'posts-to-newsletter' ); ?></span>
			</a>
		</div>
	</header>

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
	 * API credentials are not configured) so its card does not render at all,
	 * and may enrich a card with optional 'meta' (a sub-line) and 'connected'
	 * (bool, drives the status badge) keys. With no add-on active this is
	 * unfiltered and every platform shows, since the core's Preview/Copy URL
	 * buttons support manual import without an API.
	 *
	 * @param array<string,array<string,mixed>> $ptn_platforms Platform cards, keyed by platform (mailchimp|campaignmonitor).
	 */
	$ptn_platforms = apply_filters( 'posts_to_newsletter_platforms', $ptn_platforms );
	?>
	<section class="dist">
		<div class="dist__head">
			<h2><?php esc_html_e( 'Distribution', 'posts-to-newsletter' ); ?></h2>
			<span class="sub"><?php esc_html_e( 'Push the current selection into your email platform as a draft.', 'posts-to-newsletter' ); ?></span>
		</div>
		<div class="dist__grid">
			<?php
			foreach ( $ptn_platforms as $ptn_key => $ptn_platform ) :
				$ptn_logo_mod = 'mailchimp' === $ptn_key ? 'platform__logo--mc' : ( 'campaignmonitor' === $ptn_key ? 'platform__logo--cm' : '' );
				$ptn_glyph    = mb_strtoupper( mb_substr( (string) $ptn_platform['label'], 0, 1 ) );
				// Sub-line: the add-on's audience/list line when it is known, otherwise a
				// plain "Email platform" descriptor so every card carries a sub-line.
				$ptn_meta  = ( isset( $ptn_platform['meta'] ) && '' !== (string) $ptn_platform['meta'] )
					? (string) $ptn_platform['meta']
					: __( 'Email platform', 'posts-to-newsletter' );
				$ptn_state = array_key_exists( 'connected', $ptn_platform ) ? (bool) $ptn_platform['connected'] : null;
				?>
			<section class="platform ptn-platform" aria-label="<?php echo esc_attr( $ptn_platform['label'] ); ?>">
				<div class="platform__top">
					<span class="platform__logo <?php echo esc_attr( $ptn_logo_mod ); ?>" aria-hidden="true"><?php echo esc_html( $ptn_glyph ); ?></span>
					<div class="platform__id">
						<div class="platform__name"><?php echo esc_html( $ptn_platform['label'] ); ?></div>
						<div class="platform__meta"><?php echo esc_html( $ptn_meta ); ?></div>
					</div>
					<?php if ( true === $ptn_state ) : ?>
						<span class="badge badge--ok"><span class="dot" aria-hidden="true"></span><?php esc_html_e( 'Connected', 'posts-to-newsletter' ); ?></span>
					<?php elseif ( false === $ptn_state ) : ?>
						<span class="badge"><span class="dot" aria-hidden="true"></span><?php esc_html_e( 'Not set up', 'posts-to-newsletter' ); ?></span>
					<?php endif; ?>
				</div>

				<div class="platform__actions">
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
					<a class="iconbtn" href="<?php echo esc_url( $ptn_platform['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Preview newsletter', 'posts-to-newsletter' ); ?>">
						<?php echo $this->icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
					</a>
					<button type="button" class="iconbtn ptn-copy-url" data-url="<?php echo esc_url( $ptn_platform['url'] ); ?>" aria-label="<?php esc_attr_e( 'Copy import URL', 'posts-to-newsletter' ); ?>">
						<?php echo $this->icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
					</button>
				</div>
			</section>
			<?php endforeach; ?>
		</div>
	</section>

	<div class="curate">
		<section class="col col--avail">
			<div class="col__head">
				<h2><?php esc_html_e( 'Available articles', 'posts-to-newsletter' ); ?></h2>
				<span class="count" id="ptn-available-count"><?php echo (int) $ptn_available_count; ?></span>
				<span class="spacer"></span>
			</div>

			<div class="search">
				<?php echo $this->icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
				<input type="search" id="ptn-search" placeholder="<?php esc_attr_e( 'Search all articles…', 'posts-to-newsletter' ); ?>" autocomplete="off" />
				<button type="button" class="search__clear" id="ptn-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'posts-to-newsletter' ); ?>" hidden>
					<?php echo $this->icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
				</button>
			</div>

			<?php if ( ! empty( $categories ) ) : ?>
				<div class="chips" id="ptn-chips" role="group" aria-label="<?php esc_attr_e( 'Filter by category', 'posts-to-newsletter' ); ?>">
					<button type="button" class="chip is-on" data-cat="all" aria-pressed="true"><?php esc_html_e( 'All categories', 'posts-to-newsletter' ); ?></button>
					<?php foreach ( $categories as $ptn_cat ) : ?>
						<button type="button" class="chip" data-cat="<?php echo (int) $ptn_cat->term_id; ?>" aria-pressed="false">
							<?php echo esc_html( $ptn_cat->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="scroller">
				<ul id="ptn-available" class="list list--avail">
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
		</section>

		<section class="col col--out">
			<div class="col__head">
				<h2><?php esc_html_e( 'Articles in the newsletter', 'posts-to-newsletter' ); ?></h2>
				<span class="count" id="ptn-selected-count"><?php echo (int) $ptn_selected_count; ?></span>
				<span class="spacer"></span>
				<button type="button" class="clearbtn" id="ptn-clear"<?php echo 0 === $ptn_selected_count ? ' hidden' : ''; ?>><?php esc_html_e( 'Clear all', 'posts-to-newsletter' ); ?></button>
			</div>

			<div class="tray">
				<ul id="ptn-selected" class="ptn-sortable list">
					<?php
					foreach ( $selected_posts as $post_id ) {
						$this->render_item( (int) $post_id, true );
					}
					?>
				</ul>
				<div class="tray__drophint" id="ptn-drophint"<?php echo $ptn_selected_count > 0 ? ' hidden' : ''; ?>>
					<?php
					printf(
						/* translators: %s: the bolded word "Add". */
						esc_html__( 'Nothing added yet — click %s on an article to build your newsletter.', 'posts-to-newsletter' ),
						'<strong>' . esc_html__( 'Add', 'posts-to-newsletter' ) . '</strong>'
					);
					?>
				</div>
			</div>
		</section>
	</div>
</div>

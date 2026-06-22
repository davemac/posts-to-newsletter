<?php
/**
 * Compose Edition admin page.
 *
 * A three-pane composer: pick articles on the left, a live editable canvas in
 * the centre (drag to reorder, remove inline), and delivery on the right. The
 * canvas is the editor — there is no separate running-order list.
 *
 * @package PostsToNewsletter
 *
 * @var array<int,int> $selected_ids     Selected post IDs.
 * @var array<int,int> $selected_posts   Selected post IDs (validated/ordered).
 * @var array<int,int> $recent_posts     Latest post IDs.
 * @var \WP_Term[]      $categories       Post categories (for the filter chips).
 * @var string         $preview_cm       Campaign Monitor preview URL.
 * @var string         $preview_mc       Mailchimp preview URL.
 * @var string         $settings_url     Settings page URL.
 * @var string         $accent_color     Brand accent colour (drives the author pills).
 * @var string         $brand_color      Brand colour (hero border, subscribe button).
 * @var string         $subject          Edition subject line.
 * @var array<string,array{label:string,file:string}> $templates Registered templates.
 * @var string         $current_template Chosen template id.
 * @var string         $logo_url         Masthead logo URL (may be empty).
 * @var string         $hero_url         Hero image URL (may be empty).
 * @var string         $site_name        Publication name.
 * @var string         $subscribe_url    Subscribe URL.
 * @var string         $intro            Intro line, raw (inline-editable; {firstname} token intact).
 * @var \PostsToNewsletter\Curation  $this           Curation (for render_item()/render_canvas_card()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_admin_page(), so these variables are method-scoped, not global.

$ptn_selected_count = count( $selected_posts );
?>
<div class="wrap ptn-curation" style="--ptn-author-color: <?php echo esc_attr( $accent_color ); ?>; --ptn-brand: <?php echo esc_attr( $brand_color ); ?>">

	<header class="pagehead">
		<img class="ptn-pagelogo" src="<?php echo esc_url( \PostsToNewsletter\URL . 'assets/img/p2n-logo.png' ); ?>" alt="" width="40" height="40" />
		<div class="pagehead__main">
			<h1><?php esc_html_e( 'Posts to Newsletter Builder', 'dmc-posts-to-newsletter-builder' ); ?></h1>
			<p><?php esc_html_e( 'Add articles from the left column to the preview in the middle column. Changes save automatically.', 'dmc-posts-to-newsletter-builder' ); ?></p>
		</div>
		<div class="pagehead__aside">
			<span class="savechip">
				<span class="dot" aria-hidden="true"></span>
				<span class="ptn-status" aria-live="polite">
					<?php
					/* translators: %d: number of selected articles. */
					printf( esc_html__( 'Saved · %d selected', 'dmc-posts-to-newsletter-builder' ), (int) $ptn_selected_count );
					?>
				</span>
			</span>
			<?php
			/**
			 * Fires in the compose action bar, beside the Settings button.
			 *
			 * General (non-platform-specific) add-on buttons render here.
			 */
			do_action( 'posts_to_newsletter_curation_actions' );
			?>
			<a class="ghostbtn ptn-settings-link" href="<?php echo esc_url( $settings_url ); ?>">
				<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
				<span><?php esc_html_e( 'Settings', 'dmc-posts-to-newsletter-builder' ); ?></span>
			</a>
		</div>
	</header>

	<div class="compose">

		<?php // ---------- Left: add to edition ---------- ?>
		<aside class="compose__add col">
			<div class="col__head">
				<h2><?php esc_html_e( 'Add to edition', 'dmc-posts-to-newsletter-builder' ); ?></h2>
				<span class="count" id="ptn-available-count"><?php echo (int) count( $recent_posts ); ?></span>
			</div>

			<div class="search">
				<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
				<input type="search" id="ptn-search" placeholder="<?php esc_attr_e( 'Search all articles…', 'dmc-posts-to-newsletter-builder' ); ?>" autocomplete="off" />
				<button type="button" class="search__clear" id="ptn-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'dmc-posts-to-newsletter-builder' ); ?>" hidden>
					<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
				</button>
			</div>

			<?php if ( ! empty( $categories ) ) : ?>
				<div class="chips" id="ptn-chips" role="group" aria-label="<?php esc_attr_e( 'Filter by category', 'dmc-posts-to-newsletter-builder' ); ?>">
					<button type="button" class="chip is-on" data-cat="all" aria-pressed="true"><?php esc_html_e( 'All categories', 'dmc-posts-to-newsletter-builder' ); ?></button>
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
						$this->render_item( (int) $post_id );
					}
					?>
				</ul>
			</div>
		</aside>

		<?php // ---------- Centre: live editable canvas ---------- ?>
		<section class="compose__preview">
			<div class="ptn-preview-bar">
				<div class="ptn-tpl">
					<label for="ptn-template"><?php esc_html_e( 'Template', 'dmc-posts-to-newsletter-builder' ); ?></label>
					<select id="ptn-template" class="ptn-tpl__select">
						<?php foreach ( $templates as $ptn_tid => $ptn_tpl ) : ?>
							<option value="<?php echo esc_attr( $ptn_tid ); ?>" <?php selected( $current_template, $ptn_tid ); ?>><?php echo esc_html( $ptn_tpl['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( count( $templates ) < 2 ) : ?>
						<a class="ptn-tpl__pro" href="<?php echo esc_url( 'https://dmcweb.com.au/p2npro' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get more templates with Pro', 'dmc-posts-to-newsletter-builder' ); ?></a>
					<?php endif; ?>
				</div>
				<div class="ptn-viewport" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'dmc-posts-to-newsletter-builder' ); ?>">
					<button type="button" class="ptn-viewport-toggle is-on" data-mode="desktop" aria-pressed="true"><?php esc_html_e( 'Desktop', 'dmc-posts-to-newsletter-builder' ); ?></button>
					<button type="button" class="ptn-viewport-toggle" data-mode="mobile" aria-pressed="false"><?php esc_html_e( 'Mobile', 'dmc-posts-to-newsletter-builder' ); ?></button>
				</div>
			</div>

			<div class="ptn-subjectbar">
				<label for="ptn-subject"><?php esc_html_e( 'Subject line', 'dmc-posts-to-newsletter-builder' ); ?></label>
				<input type="text" id="ptn-subject" class="ptn-subject-input" maxlength="150" value="<?php echo esc_attr( $subject ); ?>" placeholder="<?php esc_attr_e( 'Subject line — defaults to the lead story', 'dmc-posts-to-newsletter-builder' ); ?>" />
			</div>

			<div class="ptn-canvas-actions">
				<button type="button" class="ptn-clearbtn" id="ptn-clear"<?php echo 0 === $ptn_selected_count ? ' hidden' : ''; ?>><?php esc_html_e( 'Clear posts', 'dmc-posts-to-newsletter-builder' ); ?></button>
			</div>

			<div class="ptn-pv-stage">
				<div class="ptn-pv" data-mode="desktop">
					<?php if ( '' !== $logo_url ) : ?>
						<div class="ptn-pv__masthead"><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" /></div>
					<?php else : ?>
						<div class="ptn-pv__masthead ptn-pv__masthead--text"><?php echo esc_html( $site_name ); ?></div>
					<?php endif; ?>

					<?php if ( '' !== $hero_url ) : ?>
						<div class="ptn-pv__hero"><img src="<?php echo esc_url( $hero_url ); ?>" alt="" /></div>
					<?php endif; ?>

					<p class="ptn-pv__intro" id="ptn-intro" contenteditable="true" role="textbox" aria-label="<?php esc_attr_e( 'Intro greeting', 'dmc-posts-to-newsletter-builder' ); ?>" data-placeholder="<?php esc_attr_e( 'Add an intro line…', 'dmc-posts-to-newsletter-builder' ); ?>"><?php echo esc_html( $intro ); ?></p>

					<ul id="ptn-selected" class="ptn-pv__grid ptn-sortable">
						<?php
						foreach ( $selected_posts as $post_id ) {
							$this->render_canvas_card( (int) $post_id );
						}
						?>
					</ul>

					<div class="ptn-pv__empty" id="ptn-drophint"<?php echo $ptn_selected_count > 0 ? ' hidden' : ''; ?>>
						<?php
						printf(
							/* translators: %s: the bolded word "Add". */
							esc_html__( 'Nothing added yet — click %s on an article to build your edition.', 'dmc-posts-to-newsletter-builder' ),
							'<strong>' . esc_html__( 'Add', 'dmc-posts-to-newsletter-builder' ) . '</strong>'
						);
						?>
					</div>

					<div class="ptn-pv__subscribe">
						<a href="<?php echo esc_url( $subscribe_url ); ?>" class="ptn-pv__subbtn">
							<?php
							/* translators: %s: site/publication name. */
							printf( esc_html__( 'Subscribe to %s', 'dmc-posts-to-newsletter-builder' ), esc_html( $site_name ) );
							?>
						</a>
						<a class="ptn-pv__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
							<?php
							/* translators: %s: site domain. */
							printf( esc_html__( 'Read more at %s', 'dmc-posts-to-newsletter-builder' ), esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) );
							?> &rarr;
						</a>
					</div>

					<div class="ptn-pv__footer">
						<?php
						/* translators: %s: site/publication name. */
						printf( esc_html__( 'You are receiving this email because you subscribed to %s.', 'dmc-posts-to-newsletter-builder' ), esc_html( $site_name ) );
						?>
						<br /><span class="ptn-pv__muted"><?php esc_html_e( 'Unsubscribe · Update your preferences · View in browser', 'dmc-posts-to-newsletter-builder' ); ?></span>
					</div>
				</div>
			</div>
		</section>

		<?php // ---------- Right: delivery ---------- ?>
		<aside class="compose__delivery col">
			<h2 class="dpanel__title"><?php esc_html_e( 'Delivery', 'dmc-posts-to-newsletter-builder' ); ?></h2>

			<?php
			/**
			 * Fires at the top of the Delivery panel.
			 *
			 * Add-ons render the platform send-to controls and the push button here.
			 * When nothing is hooked, the core shows an upgrade upsell (below) and the
			 * manual-import affordances remain available for the free tier.
			 */
			do_action( 'posts_to_newsletter_delivery_actions' );
			?>

			<?php if ( ! has_action( 'posts_to_newsletter_delivery_actions' ) ) : ?>
				<div class="dupsell">
					<strong><?php esc_html_e( 'One-click push', 'dmc-posts-to-newsletter-builder' ); ?></strong>
					<p><?php esc_html_e( 'Add DMC Posts to Newsletter Builder Pro to push this edition straight to Mailchimp or Campaign Monitor as a draft.', 'dmc-posts-to-newsletter-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="dmanual">
				<h3><?php esc_html_e( 'Manual import', 'dmc-posts-to-newsletter-builder' ); ?></h3>
				<p><?php esc_html_e( 'Copy the platform-ready URL to import into your email tool.', 'dmc-posts-to-newsletter-builder' ); ?></p>
				<ul class="dmanual__list">
					<li>
						<span class="dmanual__name"><?php esc_html_e( 'Campaign Monitor', 'dmc-posts-to-newsletter-builder' ); ?></span>
						<span class="dmanual__btns">
							<a class="iconbtn" href="<?php echo esc_url( $preview_cm ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Open preview', 'dmc-posts-to-newsletter-builder' ); ?>" title="<?php esc_attr_e( 'Open preview in a new tab', 'dmc-posts-to-newsletter-builder' ); ?>">
								<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
							</a>
							<button type="button" class="iconbtn ptn-copy-url" data-url="<?php echo esc_url( $preview_cm ); ?>" aria-label="<?php esc_attr_e( 'Copy import URL', 'dmc-posts-to-newsletter-builder' ); ?>" title="<?php esc_attr_e( 'Copy import URL', 'dmc-posts-to-newsletter-builder' ); ?>">
								<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 9h10v10H9z"/><path d="M5 15V5h10"/></svg>
							</button>
						</span>
					</li>
					<li>
						<span class="dmanual__name"><?php esc_html_e( 'Mailchimp', 'dmc-posts-to-newsletter-builder' ); ?></span>
						<span class="dmanual__btns">
							<a class="iconbtn" href="<?php echo esc_url( $preview_mc ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Open preview', 'dmc-posts-to-newsletter-builder' ); ?>" title="<?php esc_attr_e( 'Open preview in a new tab', 'dmc-posts-to-newsletter-builder' ); ?>">
								<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
							</a>
							<button type="button" class="iconbtn ptn-copy-url" data-url="<?php echo esc_url( $preview_mc ); ?>" aria-label="<?php esc_attr_e( 'Copy import URL', 'dmc-posts-to-newsletter-builder' ); ?>" title="<?php esc_attr_e( 'Copy import URL', 'dmc-posts-to-newsletter-builder' ); ?>">
								<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 9h10v10H9z"/><path d="M5 15V5h10"/></svg>
							</button>
						</span>
					</li>
				</ul>
			</div>
		</aside>

	</div>
</div>

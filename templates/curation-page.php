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
 * @var string         $preview_text     Edition inbox preview text.
 * @var array<string,array{label:string,file:string}> $templates Registered templates.
 * @var string         $current_template Chosen template id.
 * @var string         $logo_url         Masthead logo URL (may be empty).
 * @var string         $hero_url         Hero image URL (may be empty).
 * @var string         $site_name        Publication name.
 * @var string         $subscribe_url    Subscribe URL.
 * @var string         $intro            Intro line ({firstname} resolved).
 * @var \PostsToNewsletter\Curation  $this           Curation (for render_item()/render_preview_card()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_admin_page(), so these variables are method-scoped, not global.

$ptn_selected_count = count( $selected_posts );
?>
<div class="wrap ptn-curation" style="--ptn-author-color: <?php echo esc_attr( $accent_color ); ?>; --ptn-brand: <?php echo esc_attr( $brand_color ); ?>">

	<header class="pagehead">
		<img class="ptn-pagelogo" src="<?php echo esc_url( \PostsToNewsletter\URL . 'assets/img/p2n-logo.png' ); ?>" alt="" width="40" height="40" />
		<div class="pagehead__main">
			<h1><?php esc_html_e( 'Compose Edition', 'posts-to-newsletter' ); ?></h1>
			<p><?php esc_html_e( 'Add articles on the left, then drag to reorder right in the preview. Changes save automatically.', 'posts-to-newsletter' ); ?></p>
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
			 * Fires in the compose action bar, beside the Settings button.
			 *
			 * General (non-platform-specific) add-on buttons render here.
			 */
			do_action( 'posts_to_newsletter_curation_actions' );
			?>
			<a class="ghostbtn ptn-settings-link" href="<?php echo esc_url( $settings_url ); ?>">
				<?php echo $this->icon( 'gear' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
				<span><?php esc_html_e( 'Settings', 'posts-to-newsletter' ); ?></span>
			</a>
		</div>
	</header>

	<div class="compose">

		<?php // ---------- Left: add to edition ---------- ?>
		<aside class="compose__add col">
			<div class="col__head">
				<h2><?php esc_html_e( 'Add to edition', 'posts-to-newsletter' ); ?></h2>
				<span class="count" id="ptn-available-count"><?php echo (int) count( $recent_posts ); ?></span>
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
						$this->render_item( (int) $post_id, false );
					}
					?>
				</ul>
			</div>
		</aside>

		<?php // ---------- Centre: live editable canvas ---------- ?>
		<section class="compose__preview">
			<div class="ptn-preview-bar">
				<div class="ptn-tpl">
					<label for="ptn-template"><?php esc_html_e( 'Template', 'posts-to-newsletter' ); ?></label>
					<select id="ptn-template" class="ptn-tpl__select">
						<?php foreach ( $templates as $ptn_tid => $ptn_tpl ) : ?>
							<option value="<?php echo esc_attr( $ptn_tid ); ?>" <?php selected( $current_template, $ptn_tid ); ?>><?php echo esc_html( $ptn_tpl['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( count( $templates ) < 2 ) : ?>
						<span class="ptn-tpl__hint"><?php esc_html_e( 'More templates with Pro', 'posts-to-newsletter' ); ?></span>
					<?php endif; ?>
				</div>
				<button type="button" class="ptn-clearlink" id="ptn-clear"<?php echo 0 === $ptn_selected_count ? ' hidden' : ''; ?>><?php esc_html_e( 'Clear all', 'posts-to-newsletter' ); ?></button>
				<div class="ptn-viewport" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'posts-to-newsletter' ); ?>">
					<button type="button" class="ptn-viewport-toggle is-on" data-mode="desktop" aria-pressed="true"><?php esc_html_e( 'Desktop', 'posts-to-newsletter' ); ?></button>
					<button type="button" class="ptn-viewport-toggle" data-mode="mobile" aria-pressed="false"><?php esc_html_e( 'Mobile', 'posts-to-newsletter' ); ?></button>
				</div>
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

					<?php if ( '' !== trim( $intro ) ) : ?>
						<p class="ptn-pv__intro"><?php echo esc_html( $intro ); ?></p>
					<?php endif; ?>

					<ul id="ptn-selected" class="ptn-pv__list ptn-sortable">
						<?php
						foreach ( $selected_posts as $post_id ) {
							$this->render_preview_card( (int) $post_id );
						}
						?>
					</ul>

					<div class="ptn-pv__empty" id="ptn-drophint"<?php echo $ptn_selected_count > 0 ? ' hidden' : ''; ?>>
						<?php
						printf(
							/* translators: %s: the bolded word "Add". */
							esc_html__( 'Nothing added yet — click %s on an article to build your edition.', 'posts-to-newsletter' ),
							'<strong>' . esc_html__( 'Add', 'posts-to-newsletter' ) . '</strong>'
						);
						?>
					</div>

					<div class="ptn-pv__subscribe">
						<a href="<?php echo esc_url( $subscribe_url ); ?>" class="ptn-pv__subbtn">
							<?php
							/* translators: %s: site/publication name. */
							printf( esc_html__( 'Subscribe to %s', 'posts-to-newsletter' ), esc_html( $site_name ) );
							?>
						</a>
						<a class="ptn-pv__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
							<?php
							/* translators: %s: site domain. */
							printf( esc_html__( 'Read more at %s', 'posts-to-newsletter' ), esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) );
							?> &rarr;
						</a>
					</div>

					<div class="ptn-pv__footer">
						<?php
						/* translators: %s: site/publication name. */
						printf( esc_html__( 'You are receiving this email because you subscribed to %s.', 'posts-to-newsletter' ), esc_html( $site_name ) );
						?>
						<br /><span class="ptn-pv__muted"><?php esc_html_e( 'Unsubscribe · Update your preferences · View in browser', 'posts-to-newsletter' ); ?></span>
					</div>
				</div>
			</div>
		</section>

		<?php // ---------- Right: delivery ---------- ?>
		<aside class="compose__delivery col">
			<h2 class="dpanel__title"><?php esc_html_e( 'Delivery', 'posts-to-newsletter' ); ?></h2>

			<div class="dfield">
				<label for="ptn-subject"><?php esc_html_e( 'Subject line', 'posts-to-newsletter' ); ?></label>
				<input type="text" id="ptn-subject" class="dinput" maxlength="150" value="<?php echo esc_attr( $subject ); ?>" placeholder="<?php esc_attr_e( 'Defaults to the lead story', 'posts-to-newsletter' ); ?>" />
			</div>

			<div class="dfield">
				<label for="ptn-preview"><?php esc_html_e( 'Preview text', 'posts-to-newsletter' ); ?></label>
				<input type="text" id="ptn-preview" class="dinput" maxlength="150" value="<?php echo esc_attr( $preview_text ); ?>" placeholder="<?php esc_attr_e( 'Short inbox preview line', 'posts-to-newsletter' ); ?>" />
			</div>

			<?php
			/**
			 * Fires inside the Delivery panel, below the subject + preview fields.
			 *
			 * Add-ons render the platform send-to controls and the push button here.
			 * When nothing is hooked, the core shows an upgrade upsell (below) and the
			 * manual-import affordances remain available for the free tier.
			 */
			do_action( 'posts_to_newsletter_delivery_actions' );
			?>

			<?php if ( ! has_action( 'posts_to_newsletter_delivery_actions' ) ) : ?>
				<div class="dupsell">
					<strong><?php esc_html_e( 'One-click push', 'posts-to-newsletter' ); ?></strong>
					<p><?php esc_html_e( 'Add Posts to Newsletter Pro to push this edition straight to Mailchimp or Campaign Monitor as a draft.', 'posts-to-newsletter' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="dmanual">
				<h3><?php esc_html_e( 'Manual import', 'posts-to-newsletter' ); ?></h3>
				<p><?php esc_html_e( 'Copy the platform-ready URL to import into your email tool.', 'posts-to-newsletter' ); ?></p>
				<ul class="dmanual__list">
					<li>
						<span class="dmanual__name"><?php esc_html_e( 'Campaign Monitor', 'posts-to-newsletter' ); ?></span>
						<span class="dmanual__btns">
							<a class="iconbtn" href="<?php echo esc_url( $preview_cm ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Open preview', 'posts-to-newsletter' ); ?>">
								<?php echo $this->icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
							</a>
							<button type="button" class="iconbtn ptn-copy-url" data-url="<?php echo esc_url( $preview_cm ); ?>" aria-label="<?php esc_attr_e( 'Copy import URL', 'posts-to-newsletter' ); ?>">
								<?php echo $this->icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
							</button>
						</span>
					</li>
					<li>
						<span class="dmanual__name"><?php esc_html_e( 'Mailchimp', 'posts-to-newsletter' ); ?></span>
						<span class="dmanual__btns">
							<a class="iconbtn" href="<?php echo esc_url( $preview_mc ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Open preview', 'posts-to-newsletter' ); ?>">
								<?php echo $this->icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
							</a>
							<button type="button" class="iconbtn ptn-copy-url" data-url="<?php echo esc_url( $preview_mc ); ?>" aria-label="<?php esc_attr_e( 'Copy import URL', 'posts-to-newsletter' ); ?>">
								<?php echo $this->icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?>
							</button>
						</span>
					</li>
				</ul>
			</div>
		</aside>

	</div>
</div>

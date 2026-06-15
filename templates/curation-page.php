<?php
/**
 * Compose Edition admin page.
 *
 * A three-pane composer: pick/order articles on the left, a live email preview
 * in the centre, and the delivery controls on the right.
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
 * @var string         $subject          Edition subject line.
 * @var string         $preview_text     Edition inbox preview text.
 * @var array<string,array{label:string,file:string}> $templates Registered templates.
 * @var string         $current_template Chosen template id.
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
			<h1><?php esc_html_e( 'Compose Edition', 'posts-to-newsletter' ); ?></h1>
			<p><?php esc_html_e( 'Curate the running order on the left — the live preview shows exactly what subscribers receive. Changes save automatically.', 'posts-to-newsletter' ); ?></p>
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

		<?php // ---------- Left: add / in-edition ---------- ?>
		<aside class="compose__add col">
			<div class="ptn-tabs" role="tablist">
				<button type="button" class="ptn-tab is-on" data-tab="add" role="tab" aria-selected="true" aria-controls="ptn-tabpanel-add">
					<?php esc_html_e( 'Add to edition', 'posts-to-newsletter' ); ?>
				</button>
				<button type="button" class="ptn-tab" data-tab="edition" role="tab" aria-selected="false" aria-controls="ptn-tabpanel-edition">
					<?php esc_html_e( 'In the edition', 'posts-to-newsletter' ); ?>
					<span class="count" id="ptn-edition-count"><?php echo (int) $ptn_selected_count; ?></span>
				</button>
			</div>

			<div class="ptn-tabpanel" id="ptn-tabpanel-add" role="tabpanel">
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
			</div>

			<div class="ptn-tabpanel" id="ptn-tabpanel-edition" role="tabpanel" hidden>
				<div class="col__head">
					<h2><?php esc_html_e( 'Running order', 'posts-to-newsletter' ); ?></h2>
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
							esc_html__( 'Nothing added yet — switch to %s and build your edition.', 'posts-to-newsletter' ),
							'<strong>' . esc_html__( 'Add to edition', 'posts-to-newsletter' ) . '</strong>'
						);
						?>
					</div>
				</div>
			</div>
		</aside>

		<?php // ---------- Centre: live preview ---------- ?>
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
				<div class="ptn-viewport" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'posts-to-newsletter' ); ?>">
					<button type="button" class="ptn-viewport-toggle is-on" data-mode="desktop" aria-pressed="true"><?php esc_html_e( 'Desktop', 'posts-to-newsletter' ); ?></button>
					<button type="button" class="ptn-viewport-toggle" data-mode="mobile" aria-pressed="false"><?php esc_html_e( 'Mobile', 'posts-to-newsletter' ); ?></button>
				</div>
			</div>
			<div class="ptn-preview-stage" data-mode="desktop">
				<div class="ptn-preview-frame-wrap">
					<iframe id="ptn-preview-frame" title="<?php esc_attr_e( 'Newsletter preview', 'posts-to-newsletter' ); ?>" src="<?php echo esc_url( $preview_cm ); ?>" data-base="<?php echo esc_url( $preview_cm ); ?>"></iframe>
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

<?php
/**
 * One article in the live canvas: the email card (via Renderer::render_card)
 * wrapped with a drag handle + remove control, so the preview is the email.
 *
 * Required from Curation::render_canvas_card(), which prepares the variables.
 *
 * @package PostsToNewsletter
 *
 * @var int                          $post_id    Post ID.
 * @var \PostsToNewsletter\Renderer  $renderer   Renderer (for render_card()).
 * @var string                       $image_size Image size for the card.
 * @var string                       $accent     Accent colour hex.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_canvas_card(), so these variables are method-scoped, not global.
?>
<li class="ptn-pv-card" data-id="<?php echo esc_attr( (string) $post_id ); ?>">
	<span class="ptn-handle ptn-pv-card__handle" aria-hidden="true"><svg class="ico" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="6" r="1.4" fill="currentColor"/><circle cx="15" cy="6" r="1.4" fill="currentColor"/><circle cx="9" cy="12" r="1.4" fill="currentColor"/><circle cx="15" cy="12" r="1.4" fill="currentColor"/><circle cx="9" cy="18" r="1.4" fill="currentColor"/><circle cx="15" cy="18" r="1.4" fill="currentColor"/></svg></span>
	<button type="button" class="ptn-pv-remove" aria-label="<?php esc_attr_e( 'Remove', 'dmc-posts-to-newsletter-builder' ); ?>"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg></button>
	<div class="ptn-pv-card__inner"><?php $renderer->render_card( $post_id, $image_size, $accent ); ?></div>
</li>

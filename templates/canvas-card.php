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
 * @var \PostsToNewsletter\Curation  $this       Curation (for icon()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_canvas_card(), so these variables are method-scoped, not global.
?>
<li class="ptn-pv-card" data-id="<?php echo esc_attr( (string) $post_id ); ?>">
	<span class="ptn-handle ptn-pv-card__handle" aria-hidden="true"><?php echo $this->icon( 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></span>
	<button type="button" class="ptn-pv-remove" aria-label="<?php esc_attr_e( 'Remove', 'dmc-posts-to-newsletter-builder' ); ?>"><?php echo $this->icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></button>
	<div class="ptn-pv-card__inner"><?php $renderer->render_card( $post_id, $image_size, $accent ); ?></div>
</li>

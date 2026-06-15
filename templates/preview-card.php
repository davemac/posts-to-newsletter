<?php
/**
 * Live-canvas article card: one selected article in the centre preview.
 *
 * The first card in the list is styled as the lead story (large image +
 * excerpt) and the rest collapse to a compact row via CSS, so reordering needs
 * no per-card bookkeeping.
 *
 * @package PostsToNewsletter
 *
 * @var int    $post_id  Post ID.
 * @var string $image    Large image URL (may be empty).
 * @var string $byline   Author byline (may be empty).
 * @var string $category First category name (may be empty).
 * @var string $excerpt  Trimmed excerpt.
 * @var \PostsToNewsletter\Curation $this Curation (for icon()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_preview_card(), so these variables are method-scoped, not global.
?>
<li class="ptn-pv-card" data-id="<?php echo esc_attr( (string) $post_id ); ?>">
	<span class="ptn-handle ptn-pv-card__handle" aria-hidden="true"><?php echo $this->icon( 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></span>
	<button type="button" class="ptn-pv-remove" aria-label="<?php esc_attr_e( 'Remove', 'posts-to-newsletter' ); ?>"><?php echo $this->icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></button>

	<?php if ( '' !== $image ) : ?>
		<span class="ptn-pv-card__img"><img src="<?php echo esc_url( $image ); ?>" alt="" /></span>
	<?php else : ?>
		<span class="ptn-pv-card__img ptn-pv-card__img--ph"><?php echo $this->icon( 'image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></span>
	<?php endif; ?>

	<div class="ptn-pv-card__body">
		<?php if ( '' !== $category ) : ?>
			<span class="ptn-pv-card__cat"><?php echo esc_html( $category ); ?></span>
		<?php endif; ?>
		<h3 class="ptn-pv-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
		<p class="ptn-pv-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
		<div class="ptn-pv-card__meta">
			<span class="datepill"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span>
			<?php if ( '' !== $byline ) : ?>
				<span class="authorpill"><?php echo esc_html( $byline ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</li>

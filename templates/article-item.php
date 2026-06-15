<?php
/**
 * One available article row in the "Add to edition" list.
 *
 * Required from Curation::render_item(), which prepares the variables.
 *
 * @package PostsToNewsletter
 *
 * @var int           $post_id  Post ID.
 * @var string        $thumb    Featured-image <img> markup (may be empty).
 * @var string        $byline   Author byline (may be empty).
 * @var \WP_Term|null $category First category term, or null.
 * @var int[]         $cat_ids  Category term IDs (for the chip filter).
 * @var \PostsToNewsletter\Curation $this Curation (for icon()).
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Curation::render_item(), so these variables are method-scoped, not global.
?>
<li class="ptn-item row" data-id="<?php echo esc_attr( (string) $post_id ); ?>" data-cats="<?php echo esc_attr( implode( ',', $cat_ids ) ); ?>">
	<?php if ( '' !== $thumb ) : ?>
		<span class="thumb"><?php echo wp_kses_post( $thumb ); ?></span>
	<?php else : ?>
		<span class="thumb thumb--ph"><?php echo $this->icon( 'image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></span>
	<?php endif; ?>

	<span class="meta">
		<span class="meta__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
		<span class="meta__sub">
			<?php if ( '' !== $byline ) : ?>
				<span class="authorpill"><?php echo esc_html( $byline ); ?></span>
			<?php endif; ?>
			<span class="datepill"><?php echo esc_html( get_the_date( 'M j', $post_id ) ); ?></span>
			<?php if ( null !== $category ) : ?>
				<span class="catpill"><?php echo esc_html( $category->name ); ?></span>
			<?php endif; ?>
		</span>
	</span>

	<button type="button" class="ptn-add addbtn" aria-label="<?php esc_attr_e( 'Add to edition', 'posts-to-newsletter' ); ?>"><?php echo $this->icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined SVG markup. ?></button>
</li>

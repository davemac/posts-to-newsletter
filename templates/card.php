<?php
/**
 * One article card — the shared email card markup.
 *
 * Required from Renderer::render_card(), which prepares the variables. Used by
 * both the email (templates/email.php, paired 2-up in table cells) and the live
 * admin canvas (templates/canvas-card.php), so the preview is the email.
 *
 * @package PostsToNewsletter
 *
 * @var int                $card_id    Post ID.
 * @var string             $permalink  Post permalink.
 * @var array|false        $image      wp_get_attachment_image_src() result, or false.
 * @var string             $category   First category name (may be empty).
 * @var string             $excerpt    Trimmed excerpt.
 * @var string             $date       Formatted date.
 * @var string             $byline     Author byline.
 * @var string             $accent     Accent colour hex.
 * @var string             $pill_bg    Author-pill background tint.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View partial: required within Renderer::render_card(), so these variables are method-scoped, not global.
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
	<?php if ( false !== $image ) : ?>
		<tr><td style="padding-bottom:10px;"><a href="<?php echo esc_url( $permalink ); ?>"><img src="<?php echo esc_url( $image[0] ); ?>" width="270" alt="" style="display:block; width:100%; max-width:270px; height:auto;" /></a></td></tr>
	<?php endif; ?>
	<?php if ( '' !== $category ) : ?>
		<tr><td style="font-family:Arial,Helvetica,sans-serif; font-size:12px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; color:<?php echo esc_attr( $accent ); ?>; padding-bottom:4px;"><?php echo esc_html( $category ); ?></td></tr>
	<?php endif; ?>
	<tr><td style="font-family:Arial,Helvetica,sans-serif; font-size:18px; line-height:23px; font-weight:bold; padding-bottom:6px;"><a href="<?php echo esc_url( $permalink ); ?>" style="color:#111111; text-decoration:none;"><?php echo esc_html( get_the_title( $card_id ) ); ?></a></td></tr>
	<tr><td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; line-height:21px; color:#333333; padding-bottom:12px;"><?php echo esc_html( $excerpt ); ?></td></tr>
	<tr><td><table role="presentation" cellpadding="0" cellspacing="0"><tr>
		<td bgcolor="#f3f4f6" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#949494; padding:4px 8px; border-radius:4px;"><?php echo esc_html( $date ); ?></td>
		<td width="6" style="font-size:0; line-height:0;">&nbsp;</td>
		<td bgcolor="<?php echo esc_attr( $pill_bg ); ?>" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; color:<?php echo esc_attr( $accent ); ?>; padding:4px 8px; border-radius:4px;"><?php echo esc_html( $byline ); ?></td>
	</tr></table></td></tr>
</table>

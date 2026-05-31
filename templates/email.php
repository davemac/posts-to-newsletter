<?php
/**
 * Curated newsletter email template (static, platform-aware HTML).
 *
 * @package Cnl
 *
 * @var \Cnl\Settings       $settings   Settings provider.
 * @var array<int,int>      $posts      Ordered, published post IDs.
 * @var array{firstname:string,footer:string,preheader:string} $tokens Platform tokens.
 * @var string              $logo_url   Logo URL.
 * @var string              $hero_url   Hero image URL (may be empty).
 * @var string              $brand      Brand colour hex.
 * @var string              $accent     Accent colour hex.
 * @var string              $image_size Image size for cards.
 * @var string              $site_name  Publication name.
 * @var string              $subscribe  Subscribe URL.
 * @var string              $intro      Intro line (personalisation already applied).
 * @var \Cnl\Renderer       $this       Renderer (for render_card()).
 */

defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title><?php echo esc_html( $site_name ); ?></title>
	<style type="text/css">
		@media only screen and (max-width: 620px) {
			.cn-col { display: block !important; width: 100% !important; padding: 0 0 24px 0 !important; }
			.cn-gutter { display: none !important; }
		}
	</style>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4;">

	<div style="display:none; max-height:0; overflow:hidden;">The latest news from <?php echo esc_html( $site_name ); ?>. <?php echo $tokens['preheader']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;">
		<tr>
			<td align="center" style="padding:24px 12px;">
				<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background-color:#ffffff;">

					<?php if ( '' !== $logo_url ) : ?>
					<tr>
						<td align="center" style="padding:22px 20px 8px 20px;"><a href="<?php echo esc_url( $subscribe ); ?>"><img src="<?php echo esc_url( $logo_url ); ?>" width="220" alt="<?php echo esc_attr( $site_name ); ?>" style="display:block; width:auto; max-width:240px; max-height:60px; height:auto; border:0;" /></a></td>
					</tr>
					<?php endif; ?>

					<?php if ( '' !== $hero_url ) : ?>
					<tr>
						<td style="padding:8px 20px 0 20px;"><img src="<?php echo esc_url( $hero_url ); ?>" width="560" alt="" style="display:block; width:100%; max-width:560px; height:auto; border-bottom:4px solid <?php echo esc_attr( $brand ); ?>;" /></td>
					</tr>
					<?php endif; ?>

					<?php if ( '' !== trim( $intro ) ) : ?>
					<tr>
						<td align="center" style="padding:16px 32px 8px 32px; font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:22px; color:#333333;"><?php echo esc_html( $intro ); ?></td>
					</tr>
					<?php endif; ?>

					<?php foreach ( array_chunk( $posts, 2 ) as $pair ) : ?>
					<tr>
						<td style="padding:16px 32px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
								<tr>
									<td class="cn-col" width="270" valign="top"><?php $this->render_card( (int) $pair[0], $image_size, $accent ); ?></td>
									<td class="cn-gutter" width="20" style="font-size:0; line-height:0;">&nbsp;</td>
									<td class="cn-col" width="270" valign="top"><?php $this->render_card( isset( $pair[1] ) ? (int) $pair[1] : 0, $image_size, $accent ); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endforeach; ?>

					<tr>
						<td align="center" style="padding:24px 32px 6px 32px;">
							<table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="<?php echo esc_attr( $brand ); ?>" style="border-radius:4px;"><a href="<?php echo esc_url( $subscribe ); ?>" style="display:inline-block; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; padding:13px 32px;"><?php
								/* translators: %s: site/publication name. */
								printf( esc_html__( 'Subscribe to %s', 'posts-to-newsletter' ), esc_html( $site_name ) );
							?></a></td></tr></table>
						</td>
					</tr>
					<tr>
						<td align="center" style="padding:0 32px 8px 32px; font-family:Arial,Helvetica,sans-serif; font-size:13px;"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:<?php echo esc_attr( $brand ); ?>; text-decoration:none;"><?php
							/* translators: %s: site domain. */
							printf( esc_html__( 'Read more at %s', 'posts-to-newsletter' ), esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) );
						?> &rarr;</a></td>
					</tr>

					<tr>
						<td align="center" style="padding:24px 32px 28px 32px; font-family:Arial,Helvetica,sans-serif; font-size:12px; line-height:18px; color:#888888;"><?php echo $tokens['footer']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- footer built from escaped parts + platform merge tags. ?></td>
					</tr>

				</table>
			</td>
		</tr>
	</table>

</body>
</html>

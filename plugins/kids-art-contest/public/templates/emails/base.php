<?php
/**
 * HTML-обёртка письма (как в макете, экран 9). Переопределяется в теме: kids-art-contest/emails/base.php.
 * Переменные: $head (html), $html (html), $btn (label,url,color).
 */
defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px 12px;background:#f6f4ef">
<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;border:1px solid #e6e1d6;overflow:hidden">
	<div style="padding:20px 22px;font-family:Nunito,'Segoe UI',Arial,sans-serif;font-size:14px;line-height:1.55;color:#333">
		<h3 style="margin:0 0 8px;font-size:19px;font-weight:900;color:#1f2430"><?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( ! empty( $btn['label'] ) && ! empty( $btn['url'] ) ) : ?>
			<p style="margin:14px 0 6px"><a href="<?php echo esc_url( $btn['url'] ); ?>" style="display:inline-block;background:<?php echo esc_attr( $btn['color'] ); ?>;color:#fff;border-radius:999px;padding:9px 18px;font-weight:800;text-decoration:none"><?php echo esc_html( $btn['label'] ); ?></a></p>
		<?php endif; ?>
	</div>
	<div style="padding:12px 22px;background:#faf7f0;color:#999;font-size:12px;font-family:Arial,sans-serif"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
</div>
</body></html>

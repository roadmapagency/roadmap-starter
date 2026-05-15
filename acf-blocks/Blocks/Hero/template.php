<?php
/**
 * @var string $overline
 * @var string $title
 * @var string $theme
 * @var string $background_theme
 * @var string $subtext
 * @var array  $image Use wp_get_attachment_image($image['ID']) to render.
 * @var string $title_style
 * @var string $primary_button_text
 * @var string $primary_button_url
 * @var string $secondary_button_text
 * @var string $secondary_button_url
 * @var string $image_caption
 * @var string $image_credit
 * @var string $id
 * @var string $block_id
 * @var string $classes
 * @var array  $block
 */
$text_col_width = $theme === 'right-image' ? 'col-md-6' : 'col-md-7';
$has_bg         = $background_theme !== 'right-image';
?>
<div id="<?php echo $id; ?>"
	data-block-id="<?php echo $block_id; ?>"
	class="<?php echo $has_bg ? 'has-bg' : ''; ?> mt-0 <?php echo self::get_block_class_name(); ?> <?php echo $theme; ?> <?php echo $background_theme; ?> <?php echo $classes; ?> <?php echo $image_caption || $image_credit ? 'has-caption' : ''; ?>"
>
	<?php if ( is_array( $image ) ) : ?>
		<?php
		roadmap_starter_wp_get_attachment_image(
			$image['ID'],
			$block['align'],
			false,
			array(
				'class'   => 'img-bg-cover',
				'loading' => 'eager',
				'sizes'   => '((min-width: 1200px) and (max-width: 1800px)) 900px',
			)
		)
		?>
	<?php endif; ?>
	<div class="container mb-0 content z-1">
		<div class="row <?php echo self::get_block_class_name( '__header' ); ?>">
			<?php if ( is_array( $image ) && $theme === 'right-image' ) : ?>
			<div class="col-12 mb-5 mb-md-0 d-block d-md-none <?php echo self::get_block_class_name( '__mobile-img' ); ?> px-0">
				<?php
				roadmap_starter_wp_get_attachment_image(
					$image['ID'],
					$block['align'],
					false,
					array(
						'class'   => 'img-fluid w-100 mobile',
						'loading' => 'eager',
						'sizes'   => '((min-width: 1200px) and (max-width: 1800px)) 900px',
					)
				)
				?>
			</div>
			<?php endif; ?>
			<div class="col-12 <?php echo $text_col_width; ?> mb-5 mb-md-0">
				<?php if ( $overline ) : ?>
					<div class="<?php echo self::get_block_class_name( '__overline' ); ?>"><?php echo $overline; ?></div>
				<?php endif; ?>

				<<?php echo $title_style; ?>>
					<?php echo $title; ?>
				</<?php echo $title_style; ?>>

				<?php if ( $subtext ) : ?>
					<?php echo $subtext; ?>
				<?php endif; ?>

			<?php if ( ( $primary_button_text && $primary_button_url ) || ( $secondary_button_text && $secondary_button_url ) ) : ?>
				<div class="d-flex flex-row my-0 gap-4">
					<?php if ( $primary_button_text && $primary_button_url ) : ?>
						<a href="<?php echo $primary_button_url; ?>" class="btn btn-primary">
							<?php echo $primary_button_text; ?>
						</a>
					<?php endif; ?>

					<?php if ( $secondary_button_text && $secondary_button_url ) : ?>
						<a href="<?php echo $secondary_button_url; ?>"
							class="btn btn-secondary"
						><?php echo $secondary_button_text; ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			</div>
		<?php if ( $image_caption || $image_credit ) : ?>
			<figcaption class="col-12 offset-md-5 col-md-7 mb-0 mt-4">
				<?php if ( $image_caption ) : ?>
					<p class="mb-0 text-end">
						<?php echo $image_caption; ?>
						<?php if ( $image_credit ) : ?>
							<br/><span><?php echo $image_credit; ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</figcaption>
		<?php endif; ?>
		</div>
	</div>
</div>

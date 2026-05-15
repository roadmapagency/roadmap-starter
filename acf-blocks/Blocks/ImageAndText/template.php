<?php
/**
 * @var array  $image
 * @var array  $image_circle
 * @var string $image_position
 * @var string $overline
 * @var string $title
 * @var string $description
 * @var string $primary_button_text
 * @var string $primary_button_url
 * @var string $secondary_button_text
 * @var string $secondary_button_url
 * @var string $remove_angles
 * @var string $theme
 * @var string $id
 * @var string $block_id
 * @var string $classes
 * @var array  $block
 */

$image_width   = $theme === 'circle' ? 'col-md-4' : 'col-md-6';
$text_width    = $theme === 'circle' ? 'col-md-5 offset-md-1' : 'col-md-5';
$angle_classes = $remove_angles ? '' : 'angles angles-bottom';
?>
<div id="<?php echo $id; ?>"
	data-block-id="<?php echo $block_id; ?>"
	class="<?php echo self::get_block_class_name(); ?> <?php echo $classes; ?> <?php echo $theme; ?> <?php echo $angle_classes; ?> <?php echo $theme !== 'white' ? 'has-bg' : 'no-bg'; ?>"
>
	<div class="container mb-0">
		<div class="row <?php echo $theme === 'circle' ? 'justify-content-center' : 'justify-content-between'; ?>">
			<div class="col-12 <?php echo $image_width; ?> order-0 <?php echo $image_position === 'right' ? 'order-md-2' : ''; ?> text-center">
				<?php if ( is_array( $image_circle ) && $theme === 'circle' ) : ?>
					<?php
					echo wp_get_attachment_image(
						$image_circle['ID'],
						array( 500, 500 ),
						false,
						array(
							'sizes' => '(min-width: 1200px) 500px',
							'class' => 'img-fluid mb-4 mb-md-0',
							'lazy'  => 'loading',
						)
					)
					?>
				<?php elseif ( is_array( $image ) && ! empty( $image['ID'] ) ) : ?>
					<div class="image-with-outline">
						<?php
						echo wp_get_attachment_image(
							$image['ID'],
							array( 500, 500 ),
							false,
							array(
								'sizes' => '(min-width: 1200px) 500px',
								'lazy'  => 'loading',
							)
						)
						?>
					</div>
				<?php else : ?>
					<div class="image-with-outline">
						<?php echo roadmap_starter_get_img( 'placeholder.jpg', 'placeholder', 'img-fluid' ); ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="col-12 <?php echo $text_width; ?> order-1 justify-content-center d-flex flex-column">
				<?php if ( $overline ) : ?>
					<h5 class="text-uppercase"><?php echo $overline; ?></h5>
				<?php endif; ?>
				<h3 style="margin-bottom: 20px;"><?php echo $title; ?></h3>
				<?php echo $description; ?>
				<?php if ( $primary_button_text || $secondary_button_text ) : ?>
					<div class="d-flex flex-column flex-sm-row gap-4">
						<?php if ( $primary_button_text && $primary_button_url ) : ?>
							<a href="<?php echo $primary_button_url; ?>" class="btn btn-primary d-inline-block">
								<?php echo $primary_button_text; ?>
								<?php echo roadmap_starter_fontawesome_icon( 'arrow-right', 'solid', '', 'span' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( $secondary_button_text && $secondary_button_url ) : ?>
							<a href="<?php echo $secondary_button_url; ?>"
								class="btn btn-secondary d-inline-block"
							><?php echo $secondary_button_text; ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

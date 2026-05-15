<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package roadmap-starter
 */
$post      = get_queried_object();
$post_type = get_post_type_object( get_post_type( $post ) );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="container">
	<?php get_template_part( 'template-parts/content-post-header' ); ?>
	</div>

	<div class="container">
		<div class="row">
			<div class="entry-content col-md-8 offset-md-2">
				<?php
				// show the featured image if available
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'roadmap_starter_lg', array( 'class' => 'img-fluid mb-4' ) );
				}

				the_content(
					sprintf(
						wp_kses(
							/* translators: %s: Name of current post. Only visible to screen readers */
							__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'roadmap-starter' ),
							array(
								'span' => array(
									'class' => array(),
								),
							)
						),
						get_the_title()
					)
				);

				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'roadmap-starter' ),
						'after'  => '</div>',
					)
				);
				?>
			</div><!-- .entry-content -->
		</div>
	</div>

	<div class="entry-footer container mb-5">
		<div class="row justify-content-between">
			<div class="col-12 col-sm-5 mb-4 mb-sm-0">
				<?php $prev = get_previous_post_link(); ?>
				<?php if ( $prev ) : ?>
					<p>← Previous <?php echo esc_html( $post_type->labels->singular_name ); ?><br>
						<strong><?php echo $prev; ?></strong>
					</p>
				<?php endif; ?>
			</div>
			<div class="col-12 col-sm-5 text-start text-sm-end">
				<?php $next = get_next_post_link(); ?>
				<?php if ( $next ) : ?>
					<p>Next <?php echo esc_html( $post_type->labels->singular_name ); ?> →<br>
						<strong><?php echo $next; ?></strong>
					</p>
				<?php endif; ?>
			</div>
			<?php roadmap_starter_entry_footer(); ?>
		</div>
	</div><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->

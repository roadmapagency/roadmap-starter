<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package roadmap-starter
 */

?>


<?php // get_template_part( 'template-parts/content-page-header' ); ?>

<?php // roadmap_starter_post_thumbnail(); ?>

<div class="entry-content">
	<?php
	the_content();

	wp_link_pages(
		array(
			'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'roadmap-starter' ),
			'after'  => '</div>',
		)
	);
	?>
</div><!-- .entry-content -->

<?php
/**
 * First post on the blog index: the child theme's ImageAndText block when it has one (the scaffold
 * ships it), otherwise a plain featured card.
 *
 * @package roadmap-starter
 */

$roadmap_starter_block = function_exists( 'acf_get_block_type' ) ? acf_get_block_type( 'acf/imageandtext' ) : null;

if ( $roadmap_starter_block ) {
	$roadmap_starter_block['data'] = array(
		'title'               => get_the_title(),
		'description'         => '<p>' . get_the_excerpt() . '</p>',
		'image'               => array( 'ID' => get_post_thumbnail_id() ),
		'theme'               => 'green',
		'remove_angles'       => 1,
		'primary_button_text' => 'Read More',
		'primary_button_url'  => get_the_permalink(),
	);
	echo acf_rendered_block( $roadmap_starter_block, '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'container my-5' ); ?>>
		<div class="row align-items-center g-5">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="col-md-6"><?php the_post_thumbnail( 'large', array( 'class' => 'img-fluid' ) ); ?></div>
			<?php endif; ?>
			<div class="col-md-6">
				<h2><?php the_title(); ?></h2>
				<?php the_excerpt(); ?>
				<a class="btn btn-primary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read More', 'roadmap-starter' ); ?></a>
			</div>
		</div>
	</article>
	<?php
}

get_search_form();

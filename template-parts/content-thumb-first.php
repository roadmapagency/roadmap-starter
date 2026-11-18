<?php
/**
 * First post on the blog index: the child theme's ImageAndText block when it has one (the scaffold
 * ships it), otherwise a plain featured card.
 *
 * Only fields the child's block actually has are passed (a select value only when it is one of the
 * field's choices), so a child that reworked or replaced ImageAndText gets the plain card instead of
 * a half-filled block. Children can change the block data with `roadmap_starter_featured_post_block_data`
 * (return an empty array to always use the plain card).
 *
 * @package roadmap-starter
 */

$roadmap_starter_block = function_exists( 'acf_get_block_type' ) ? acf_get_block_type( 'acf/imageandtext' ) : null;
$roadmap_starter_data  = array();

if ( $roadmap_starter_block ) {
	$roadmap_starter_fields = array();
	foreach ( acf_get_field_groups( array( 'block' => 'acf/imageandtext' ) ) as $roadmap_starter_group ) {
		foreach ( (array) acf_get_fields( $roadmap_starter_group ) as $roadmap_starter_field ) {
			$roadmap_starter_fields[ $roadmap_starter_field['name'] ] = $roadmap_starter_field;
		}
	}
	$roadmap_starter_wanted = array(
		'title'               => get_the_title(),
		'description'         => '<p>' . get_the_excerpt() . '</p>',
		'image'               => array( 'ID' => get_post_thumbnail_id() ),
		'theme'               => 'green',
		'remove_angles'       => 1,
		'primary_button_text' => __( 'Read More', 'roadmap-starter' ),
		'primary_button_url'  => get_the_permalink(),
	);
	foreach ( $roadmap_starter_wanted as $roadmap_starter_name => $roadmap_starter_value ) {
		$roadmap_starter_field = $roadmap_starter_fields[ $roadmap_starter_name ] ?? null;
		if ( ! $roadmap_starter_field ) {
			continue;
		}
		if ( ! empty( $roadmap_starter_field['choices'] ) && ! isset( $roadmap_starter_field['choices'][ $roadmap_starter_value ] ) ) {
			continue;
		}
		$roadmap_starter_data[ $roadmap_starter_name ] = $roadmap_starter_value;
	}
	// The block needs at least a title to be a sensible featured card.
	if ( empty( $roadmap_starter_data['title'] ) ) {
		$roadmap_starter_data = array();
	}
	$roadmap_starter_data = (array) apply_filters( 'roadmap_starter_featured_post_block_data', $roadmap_starter_data, get_post() );
}

if ( $roadmap_starter_block && $roadmap_starter_data ) {
	$roadmap_starter_block['data'] = $roadmap_starter_data;
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

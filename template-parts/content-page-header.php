<header class="page-header py-4 px-3 py-md-5 text-center bg-image bg-image-<?php echo get_post_field( 'post_name', get_post() ); ?>">
	<?php
	global $post;

	if ( function_exists( 'yoast_breadcrumb' ) && $post->post_parent ) {
		yoast_breadcrumb( '<p id="breadcrumbs" class="mb-2">', '</p>' );
	}
	the_title( '<h1 class="entry-title h2">', '</h1>' );
	the_archive_description( '<div class="archive-description">', '</div>' );
	?>
</header><!-- .page-header -->

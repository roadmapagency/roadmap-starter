<header class="row post-header py-4 py-md-5 text-center bg-image bg-image-<?php echo get_post_field( 'post_name', get_post() ); ?>">
	<?php
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( '<p id="breadcrumbs" class="mb-2">', '</p>' );
	}
	the_title( '<h1 class="entry-title h2">', '</h1>' );
	the_archive_description( '<div class="archive-description">', '</div>' );
	?>
</header><!-- .page-header -->

<?php
/* Start the Loop */
$i = 1;
while ( have_posts() ) :

	the_post();

	/*
	 * Include the Post-Type-specific template for the content.
	 * If you want to override this in a child theme, then include a file
	 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
	 */
	get_template_part( 'template-parts/content-thumb', $i === 1 ? 'first' : '' );

	if ( $i === 1 ) :
		echo "<div class='container'>";
		echo "	<div class='row'>";
	endif;

	++$i;
endwhile;

?>
	</div><!-- row -->
</div><!-- container -->

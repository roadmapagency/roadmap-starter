<?php
/* Start the Loop */
$i = 1;

get_search_form();
?>

<div class='container'>
	<div class='row'>
<?php
while ( have_posts() ) :

	the_post();

	/*
	 * Include the Post-Type-specific template for the content.
	 * If you want to override this in a child theme, then include a file
	 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
	 */
	get_template_part( 'template-parts/content-thumb' );

endwhile;

?>
	</div>
</div>

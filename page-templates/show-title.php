<?php
/**
 * Template Name: Show Title
 */

get_header();
?>

	<div id="primary" class="content-area mt-4">
		<main id="main" class="site-main">

			<?php
			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content-page-header' );

				if ( has_post_thumbnail() ) {
					echo "<div class='container mb-4'>";
					roadmap_starter_post_thumbnail();
					echo '</div>';
				}

				get_template_part( 'template-parts/content', 'page' );

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

			endwhile; // End of the loop.
			?>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();

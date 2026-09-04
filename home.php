<?php
/**
 * The template for displaying archive pages
 *
 * @link    https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package roadmap-starter
 */

get_header();
global $wp_query;

$title = '';

$in = '';
if ( is_category() && is_search() ) {
	$in = ' in ' . single_cat_title( '', false );
}

switch ( true ) {
	case is_search():
		$title = sprintf(
			esc_html__( 'Search Results for: %1$s%2$s', 'roadmap-starter' ),
			'<span>' . get_search_query() . '</span>',
			$in
		);
		break;
	case is_category():
		$title = single_cat_title( '', false );
		break;
	case is_tag():
		$title = single_tag_title( '', false );
		break;
	case is_author():
		$title = get_the_author();
		break;
	case is_post_type_archive():
		$title = post_type_archive_title( '', false );
		break;
	case is_tax():
		$title = single_term_title( '', false );
		break;
	default:
		$title = $wp_query->get_queried_object()->post_title;
		break;
}
?>

<style>
	.wp-block-<?php echo esc_html( THEME_SLUG ); ?>-imageandtext{
		margin-top: 0!important;
		margin-bottom: 0!important;
	}
</style>

	<div id="primary" class="content-area archive">
		<main id="main" class="site-main">
			<div class="container-fluid">
				<div class="row header">
					<div class="light-green pt-6 pb-5">
						<div class="container">
							<div class="row">
								<header class="page-header text-center col-md-8 offset-md-2">
									<h1 class="page-title mb-0 h2 mb-3"><?php echo $title; ?></h1>
									<div class="archive-description">
										<?php
										if ( is_archive() && ! is_search() ) {
											the_archive_description();
										} elseif ( $wp_query->get_queried_object() && ! is_search() ) {
											echo get_the_content( null, false, $wp_query->get_queried_object()->ID );
										}
										?>
									</div>

								</header><!-- .page-header -->
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ( have_posts() ) : ?>

				<?php get_sidebar( 'archive' ); ?>

				<?php
				if ( is_search() ) {
					get_template_part( 'template-parts/content-search-results' );
				} else {
					get_template_part( 'template-parts/content-archive-results' );
				}

				// the_posts_navigation();
				echo "<div class='col-md-8 offset-md-2 text-center mt-4 mb-5'>";
				bootstrap_pagination();
				echo '</div>';

			else :

				get_template_part( 'template-parts/content', 'none' );

			endif;

			?>
	</div>

	</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();

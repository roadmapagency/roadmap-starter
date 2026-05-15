<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package roadmap-starter
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="container">
				<section class="wp-roadmap-starter-block my-6 my-md-7 error-404 not-found alignwide">
					<header class="page-header no-gradient">
						<h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'roadmap-starter' ); ?></h1>
					</header><!-- .page-header -->

					<div class="page-content">
						<p><?php esc_html_e( 'It looks like nothing was found at this location.', 'roadmap-starter' ); ?></p>
					</div><!-- .page-content -->
				</section><!-- .error-404 -->
			</div>
<?php
get_footer();

<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link    https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package roadmap-starter
 */

?>
</div><!-- #content -->

<footer id="colophon" class="container-fluid site-footer g-0">
	<div class="container py-4-5">
		<div class="row justify-content-center flex-column">
			<?php dynamic_sidebar( 'footer' ); ?>
		</div>
	</div>
	<div class="container-fluid footer-bottom">
		<div class="container">
			<div class="row">
				<div class="col-12 col-lg-auto text-center text-lg-left order-1 order-lg-0">
					<small class="text-white">© <?php echo date( 'Y' ); ?> <?php echo get_bloginfo( 'name' ); ?> All Rights
						Reserved.</small>
				</div>
					<?php
					wp_nav_menu(
						array(
							'menu'            => 'footer-bottom',
							'theme_location'  => 'footer-bottom',
							'container'       => 'div',
							'container_id'    => 'footer-bottom-menu',
							'container_class' => 'ms-lg-auto align-items-center align-items-lg-end col-12 col-lg-auto mb-3 mb-lg-0 order-0 order-lg-1',
							'menu_id'         => false,
							'menu_class'      => 'd-flex list-unstyled list-inline inline justify-content-center justify-content-lg-end flex-column flex-lg-row text-center text-lg-left',
							'depth'           => 0,
							'fallback_cb'     => 'RoadmapStarter_Bootstrap_Navwalker::fallback',
							'walker'          => new RoadmapStarter_Bootstrap_Navwalker(),
							'use_collapse'    => false,
						)
					);
					?>
			</div>
		</div>
	</div>
</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>

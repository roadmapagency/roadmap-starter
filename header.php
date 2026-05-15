<?php

/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link    https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package roadmap-starter
 */

?>
<!DOCTYPE html>

<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<div id="page" class="site">
		<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'roadmap-starter' ); ?></a>

		<header id="masthead" class="site-header container-fluid g-0">
			<div class="container-fluid bg-primary">
				<div class="container">
					<div class="row alignwide">
						<div class="col justify-content-end top-nav px-0">
							<?php
							wp_nav_menu(
								array(
									'menu'            => 'top-nav',
									'theme_location'  => 'top-nav',
									'container'       => 'div',
									'container_id'    => 'top-nav-menu',
									'container_class' => '',
									'menu_id'         => false,
									'menu_class'      => 'navbar-nav col inline justify-content-end flex-row flex-wrap flex-md-nowrap d-none d-lg-flex',
									'depth'           => 0,
									'fallback_cb'     => 'RoadmapStarter_Bootstrap_Navwalker::fallback',
									'walker'          => new RoadmapStarter_Bootstrap_Navwalker(),
									'use_collapse'    => true,
								)
							);
							?>
						</div>
					</div>
				</div>
			</div>
			<div class="container" id="main-nav">
				<div class="row py-3 py-lg-0">
					<div class="site-branding px-2 d-flex align-self-center col-auto me-4" style="max-width: 50%">
						<span class="logo">
							<?php echo get_custom_logo(); ?>
						</span>
					</div><!-- .site-branding -->

					<div class="col d-lg-none ml-auto align-items-center justify-content-end d-flex flex-direction-row gap-3">
					<?php
						wp_nav_menu(
							array(
								'menu'            => 'mobile-cta',
								'theme_location'  => 'mobile-cta',
								'container'       => 'div',
								'container_id'    => 'mobile-cta-menu',
								'container_class' => '',
								'menu_id'         => false,
								'menu_class'      => '',
								'depth'           => 0,
								'fallback_cb'     => 'RoadmapStarter_Bootstrap_Navwalker::fallback',
								'walker'          => new RoadmapStarter_Bootstrap_Navwalker(),
								'use_collapse'    => true,
							)
						);
						?>

						<nav class="navbar navbar-dark" >
							<button class="navbar-toggler"
								type="button"
								data-bs-toggle="offcanvas"
								data-bs-target="#offcanvasNavbarLight"
								aria-controls="offcanvasNavbarLight"
								aria-label="Toggle navigation">
								<span class="navbar-toggler-icon"></span>
							</button>
						</nav>
					</div>



					<div class="offcanvas offcanvas-start mobile-nav"
						tabindex="-1"
						id="offcanvasNavbarLight"
						aria-labelledby="offcanvasNavbarLightLabel"
						aria-modal="true"
						role="dialog">
						<div class="offcanvas-header">
							<a href="/">
								<?php echo get_custom_logo(); ?>
							</a>
							<button type="button" class="btn-close ml-auto justify-self-end" data-bs-dismiss="offcanvas" aria-label="Close"></button>
						</div>
						<div class="offcanvas-body d-flex flex-column">
							<div class="d-flex gap-3 mb-4">
								<a href="#" class="btn btn-secondary" style="background-color: transparent">Customer Login</a>
							</div>
							<nav role="navigation">
								<!-- Brand and toggle get grouped for better mobile display -->
								<?php
								wp_nav_menu(
									array(
										'menu'            => 'primary-mobile',
										'theme_location'  => 'primary-mobile',
										'container'       => 'div',
										'container_id'    => 'primary-mobile-menu',
										'container_class' => 'navbar-nav justify-content-end flex-grow-1 pe-3 fs-4',
										'menu_id'         => false,
										'menu_class'      => 'navbar-nav',
										'depth'           => 0,
										'fallback_cb'     => 'RoadmapStarter_Bootstrap_Navwalker::fallback',
										'walker'          => new RoadmapStarter_Bootstrap_Navwalker(),
										'use_collapse'    => true,
									)
								);
								?>
							</nav>

							<div class="mt-auto">
								<span class="fw-bold fs-3">Sales & Support:</span><br>
								<span class="fw-bolder fs-5"><a href="tel:18009585698">1.800.958.5698</a></span>
							</div>
						</div>
					</div>

					<nav class="navbar navbar-expand-lg navbar-dark d-flex col py-0 d-none d-lg-flex" role="navigation">
						<!-- Brand and toggle get grouped for better mobile display -->
						<?php
						wp_nav_menu(
							array(
								'menu'            => 'primary',
								'theme_location'  => 'primary',
								'container'       => 'div',
								'container_id'    => 'primary-menu',
								'container_class' => 'collapse navbar-collapse align-self-end',
								'menu_id'         => false,
								'menu_class'      => 'navbar-nav inline d-flex w-100 flex-row flex-wrap flex-md-nowrap justify-content-center',
								'depth'           => 0,
								'fallback_cb'     => 'RoadmapStarter_Bootstrap_Navwalker::fallback',
								'walker'          => new RoadmapStarter_Bootstrap_Navwalker(),
								'use_collapse'    => true,
							)
						);
						?>
					</nav>

					<nav class="navbar navbar-expand-lg navbar-dark d-flex col-auto py-0 px-1 d-none d-lg-flex"
						role="navigation">
						<?php
						wp_nav_menu(
							array(
								'menu'            => 'primary-right',
								'theme_location'  => 'primary-right',
								'container'       => 'div',
								'container_id'    => 'primary-right-menu',
								'container_class' => 'collapse navbar-collapse align-self-end',
								'menu_id'         => false,
								'menu_class'      => 'navbar-nav inline d-flex w-100 flex-row flex-wrap flex-md-nowrap align-items-end',
								'depth'           => 0,
								'fallback_cb'     => 'RoadmapStarter_Bootstrap_Navwalker::fallback',
								'walker'          => new RoadmapStarter_Bootstrap_Navwalker(),
								'use_collapse'    => true,
							)
						);
						?>
					</nav>
				</div>
			</div>
		</header><!-- #masthead -->

		<div id="content" class="site-content">
			<?php if ( $text = get_field( 'banner_message_text' ) ) : ?>
			<div class="container-fluid bg-bright-green-light py-1" style="border-top: 1px solid #D6D7D9;border-bottom: 1px solid #D6D7D9;">
				<div class="container">
					<div class="row">
						<div class="col ml-auto">
							<div class="d-flex align-items-center flex-direction-row justify-content-center gap-1">
								<?php
								if ( $icon = get_field( 'banner_message_icon' ) ) {
									echo roadmap_starter_fontawesome_icon( $icon->id, $icon->style, 'i-12 bg-primary align-self-start mt-1' );
								}
								?>
								<div class="banner-text">
									<?php echo $text; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
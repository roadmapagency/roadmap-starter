<?php
/**
 * Update this parent theme from its GitHub Releases (the `roadmap-starter.zip` asset built by
 * .github/workflows/release.yml), the same way ai-by-roadmap updates itself.
 *
 * Uses the Plugin Update Checker library bundled with the required ai-by-roadmap plugin, so the
 * theme carries no copy of its own. Off when this directory is a git checkout (an update would
 * replace the folder and wipe the working copy); filter `roadmap_starter_enable_updater`.
 *
 * While the repository is private every site needs a read-only token:
 *     define( 'ROADMAP_STARTER_GITHUB_TOKEN', 'github_pat_…' );
 *
 * @package roadmap-starter
 */

add_action(
	'after_setup_theme',
	static function () {
		$is_vcs = is_dir( get_template_directory() . '/.git' );
		if ( ! apply_filters( 'roadmap_starter_enable_updater', ! $is_vcs ) ) {
			return;
		}
		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/roadmapagency/roadmap-starter/',
			get_template_directory() . '/functions.php',
			'roadmap-starter'
		);
		$checker->setBranch( 'main' );
		if ( defined( 'ROADMAP_STARTER_GITHUB_TOKEN' ) && ROADMAP_STARTER_GITHUB_TOKEN ) {
			$checker->setAuthentication( ROADMAP_STARTER_GITHUB_TOKEN );
		}
		$checker->getVcsApi()->enableReleaseAssets( '/^roadmap-starter\.zip$/' );
	}
);

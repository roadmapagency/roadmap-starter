<?php
/**
 * Update this parent theme from its GitHub Releases (the `roadmap-starter.zip` asset built by
 * .github/workflows/release.yml), the same way ai-by-roadmap updates itself.
 *
 * Uses the Plugin Update Checker library bundled with the required ai-by-roadmap plugin, so the
 * theme carries no copy of its own. Off when this directory is a git checkout (an update would
 * replace the folder and wipe the working copy); filter `roadmap_starter_enable_updater`.
 *
 * The repository is public, so no credentials are needed. A read-only token is optional and only lifts
 * GitHub's API rate limit when many sites share one server:
 *     define( 'ROADMAP_STARTER_GITHUB_TOKEN', 'github_pat_…' );
 *
 * Minor and patch releases (2.x → 2.y) install automatically through WordPress's background updater;
 * a new major version waits for someone to click Update (it can need site changes, e.g. rebuilding the
 * child theme). Filter `roadmap_starter_auto_update` to change that.
 *
 * @package roadmap-starter
 */

/**
 * Auto-update this theme for releases within the installed major version; leave majors to the site's own
 * auto-update setting (off unless someone enabled it).
 *
 * @param bool|null $update Whether to update, from the site setting or earlier filters.
 * @param object    $item   The update offer (`theme`, `new_version`).
 * @return bool|null
 */
function roadmap_starter_auto_update( $update, $item ) {
	if ( ! is_object( $item ) || 'roadmap-starter' !== ( $item->theme ?? '' ) || empty( $item->new_version ) ) {
		return $update;
	}
	$installed = (string) wp_get_theme( 'roadmap-starter' )->get( 'Version' );
	$same_major = (int) $installed > 0 && (int) $installed === (int) $item->new_version;

	return (bool) apply_filters( 'roadmap_starter_auto_update', $same_major ? true : $update, $item, $installed );
}
add_filter( 'auto_update_theme', 'roadmap_starter_auto_update', 10, 2 );

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

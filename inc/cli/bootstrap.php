<?php
/**
 * Registers the roadmap-starter WP-CLI commands.
 *
 * functions.php loads this whenever roadmap-starter or one of its children is the active theme. It can also
 * be loaded on its own, so the commands work before any roadmap-starter theme is active (e.g. to create a
 * site's first child theme on a fresh install), and so `wp help` / `wp cli has-command` can see them:
 *
 *     wp --require=wp-content/themes/roadmap-starter/inc/cli/bootstrap.php roadmap-starter scaffold-child acme
 *
 * or once for the whole site, in its wp-cli.yml:
 *
 *     require:
 *       - wp-content/themes/roadmap-starter/inc/cli/bootstrap.php
 *
 * scaffold-child, child-audit, clone and remove work with any active theme. `fields` and `patterns` act on
 * the active theme's blocks and content, so they are only registered once the parent's functions.php has
 * run (roadmap-starter or a child is active).
 *
 * @package RoadmapStarter\CLI
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || defined( 'ROADMAP_STARTER_CLI_LOADED' ) ) {
	return;
}
define( 'ROADMAP_STARTER_CLI_LOADED', true );

require_once dirname( __DIR__ ) . '/identity.php'; // child-audit reads the active child's block prefix.
require_once __DIR__ . '/class-child-command.php';
require_once __DIR__ . '/class-clone-command.php';

$roadmap_starter_cli_when = array( 'when' => 'after_wp_load' );

// New sites are child themes of this one; `clone` (full copy) is kept for existing skills only.
\WP_CLI::add_command( 'roadmap-starter scaffold-child', array( \RoadmapStarter\CLI\Child_Command::class, 'scaffold_child' ), $roadmap_starter_cli_when );
\WP_CLI::add_command( 'roadmap-starter child-audit', array( \RoadmapStarter\CLI\Child_Command::class, 'child_audit' ), $roadmap_starter_cli_when );
\WP_CLI::add_command( 'roadmap-starter clone', array( \RoadmapStarter\CLI\Clone_Command::class, 'clone_' ), $roadmap_starter_cli_when );
\WP_CLI::add_command( 'roadmap-starter remove', array( \RoadmapStarter\CLI\Clone_Command::class, 'remove' ), $roadmap_starter_cli_when );

\WP_CLI::add_hook(
	'after_wp_load',
	static function () {
		if ( ! did_action( 'roadmap_starter_loaded' ) ) {
			return; // The parent's functions.php didn't run (no roadmap-starter theme active, or --skip-themes).
		}
		require_once __DIR__ . '/class-fields-command.php';
		require_once __DIR__ . '/class-patterns-command.php';
		\WP_CLI::add_command( 'roadmap-starter fields', \RoadmapStarter\CLI\Fields_Command::class );
		\WP_CLI::add_command( 'roadmap-starter patterns', \RoadmapStarter\CLI\Patterns_Command::class );
	}
);

<?php
/**
 * Plugins this theme cannot run without.
 *
 * WordPress 6.5+ lets a *plugin* declare `Requires Plugins`, but there is no such
 * header for themes, so the theme enforces its own list: it refuses to activate
 * with dependencies missing (reverting to the previous theme) and otherwise shows
 * a persistent admin error with install / activate links, and it blocks the
 * Deactivate link on the required plugins while the theme is active.
 *
 *  - Advanced Custom Fields PRO: every block, the options pages, the FieldsBuilder.
 *  - AI by Roadmap (ROADMAP_STARTER_MIN_PLUGIN or newer): the block schemas, the
 *    Source Content field and icon search (declared via
 *    add_theme_support('ai-by-roadmap')), plus the MCP server agents use. It in
 *    turn requires mcp-adapter.
 *
 * A child theme adds its own requirements with the roadmap_starter_required_plugins
 * filter (e.g. ACF Image Aspect Ratio Crop for the scaffold's ImageAndText block).
 *
 * Optional, with graceful fallbacks: Yoast SEO (breadcrumbs), ACF Font Awesome
 * (blocks render Font Awesome from the theme's own SVG files, not the plugin).
 *
 * @package roadmap-starter
 */

/**
 * Required plugins: main file => [label, detection callback, wp.org slug or null].
 *
 * @return array<string, array{label:string, active:callable, slug:?string}>
 */
function roadmap_starter_required_plugins(): array {
	$plugins = array(
		'advanced-custom-fields-pro/acf.php'                      => array(
			'label'  => 'Advanced Custom Fields PRO',
			'active' => static fn(): bool => function_exists( 'acf_register_block_type' ) && defined( 'ACF_PRO' ),
			'slug'   => null, // commercial, not on WordPress.org
		),
		'ai-by-roadmap/ai-by-roadmap.php'                         => array(
			/* translators: %s: minimum plugin version */
			'label'  => sprintf( 'AI by Roadmap %s or newer', ROADMAP_STARTER_MIN_PLUGIN ),
			// The plugin provides the block schemas, the Source Content field and icon search.
			'active' => static fn(): bool => defined( '\\Roadmap\\AiByRoadmap\\VERSION' )
				&& version_compare( \Roadmap\AiByRoadmap\VERSION, ROADMAP_STARTER_MIN_PLUGIN, '>=' ),
			'slug'   => null, // github.com/roadmapagency/ai-by-roadmap
		),
	);

	/**
	 * Filters the plugins the theme refuses to run without.
	 *
	 * @param array<string, array{label:string, active:callable, slug:?string}> $plugins Keyed by plugin main file.
	 */
	return (array) apply_filters( 'roadmap_starter_required_plugins', $plugins );
}

/**
 * Required plugins that are not currently active.
 *
 * @return array<string, array{label:string, installed:bool, slug:?string}>
 */
function roadmap_starter_missing_plugins(): array {
	$missing = array();
	foreach ( roadmap_starter_required_plugins() as $file => $plugin ) {
		if ( call_user_func( $plugin['active'] ) ) {
			continue;
		}
		$missing[ $file ] = array(
			'label'     => $plugin['label'],
			'installed' => file_exists( WP_PLUGIN_DIR . '/' . $file ),
			'slug'      => $plugin['slug'],
		);
	}
	return $missing;
}

/**
 * Persistent admin error while something required is missing.
 */
add_action(
	'admin_notices',
	static function (): void {
		$missing = roadmap_starter_missing_plugins();
		if ( ! $missing || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$items = array();
		foreach ( $missing as $file => $plugin ) {
			if ( $plugin['installed'] ) {
				$url     = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ), 'activate-plugin_' . $file );
				$items[] = sprintf( '<li><strong>%s</strong> — %s <a href="%s">%s</a></li>', esc_html( $plugin['label'] ), esc_html__( 'installed but inactive.', 'roadmap-starter' ), esc_url( $url ), esc_html__( 'Activate', 'roadmap-starter' ) );
			} elseif ( $plugin['slug'] ) {
				$url     = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin['slug'] ), 'install-plugin_' . $plugin['slug'] );
				$items[] = sprintf( '<li><strong>%s</strong> — %s <a href="%s">%s</a></li>', esc_html( $plugin['label'] ), esc_html__( 'not installed.', 'roadmap-starter' ), esc_url( $url ), esc_html__( 'Install', 'roadmap-starter' ) );
			} else {
				$items[] = sprintf( '<li><strong>%s</strong> — %s</li>', esc_html( $plugin['label'] ), esc_html__( 'not installed; upload it under Plugins → Add New.', 'roadmap-starter' ) );
			}
		}
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p><ul style="list-style:disc;margin-left:1.5em">%s</ul></div>',
			sprintf(
				/* translators: %s: theme name */
				esc_html__( '%s is missing required plugins.', 'roadmap-starter' ),
				esc_html( (string) wp_get_theme()->get( 'Name' ) )
			),
			esc_html__( 'Blocks, options pages and AI tooling will not work until they are active:', 'roadmap-starter' ),
			implode( '', $items ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}
);

/**
 * Refuse to switch to this theme while dependencies are missing: fall back to
 * the previous theme and explain why, instead of activating a broken site.
 */
add_action(
	'after_switch_theme',
	static function ( $old_name, $old_theme = null ): void {
		$missing = roadmap_starter_missing_plugins();
		if ( ! $missing ) {
			return;
		}
		$labels = implode( ', ', array_column( $missing, 'label' ) );
		if ( $old_theme instanceof WP_Theme && $old_theme->exists() ) {
			switch_theme( $old_theme->get_stylesheet() );
		}
		wp_die(
			sprintf(
				/* translators: 1: theme name, 2: comma-separated plugin names */
				esc_html__( '%1$s needs these plugins active before it can be used: %2$s. The previous theme has been restored. Activate the plugins, then switch themes again.', 'roadmap-starter' ),
				esc_html( (string) wp_get_theme()->get( 'Name' ) ),
				esc_html( $labels )
			),
			esc_html__( 'Required plugins missing', 'roadmap-starter' ),
			array( 'back_link' => true )
		);
	},
	10,
	2
);

/**
 * Keep the required plugins from being deactivated from the Plugins screen while
 * this theme is active (mirrors what WordPress does for plugin-to-plugin deps).
 */
add_filter(
	'plugin_action_links',
	static function ( array $actions, string $plugin_file ): array {
		if ( isset( roadmap_starter_required_plugins()[ $plugin_file ] ) && isset( $actions['deactivate'] ) ) {
			$actions['deactivate'] = '<span style="color:#646970">' . sprintf(
				/* translators: %s: theme name */
				esc_html__( 'Required by the active theme (%s)', 'roadmap-starter' ),
				esc_html( (string) wp_get_theme()->get( 'Name' ) )
			) . '</span>';
		}
		return $actions;
	},
	10,
	2
);

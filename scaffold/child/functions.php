<?php
/**
 * {{NAME}} — child theme of roadmap-starter.
 *
 * The parent provides the framework (block registry, AbstractBlock, helpers, templates, build
 * factory); this theme holds what is specific to the site: blocks (acf-blocks/Blocks, namespace
 * {{NAMESPACE}}), design tokens and styles (src/sass), templates, post types and options.
 *
 * Load order: this file runs BEFORE the parent's functions.php. Only filters the parent reads while
 * loading belong at the top level; require everything else on `roadmap_starter_loaded`, once parent
 * functions and constants (THEME_SLUG, roadmap_starter_*) exist. Prefix this theme's functions with
 * {{FN_PREFIX}}_ — never redeclare a roadmap_starter_* function.
 *
 * @package {{SLUG}}
 */

add_filter( 'roadmap_starter_block_namespace', static fn() => '{{NAMESPACE_ESC}}' );
add_filter( 'roadmap_starter_block_prefix', static fn() => '{{SLUG}}' ); // = $block-prefix in src/sass/_tokens.scss

add_action(
	'roadmap_starter_loaded',
	static function () {
		// require __DIR__ . '/inc/post-types.php';
	}
);

/**
 * Plugins this site needs on top of the parent's list.
 */
add_filter(
	'roadmap_starter_required_plugins',
	static function ( array $plugins ): array {
		// The scaffold's ImageAndText block uses the image_aspect_ratio_crop field type.
		$plugins['acf-image-aspect-ratio-crop/acf-image-aspect-ratio-crop.php'] = array(
			'label'  => 'ACF Image Aspect Ratio Crop',
			'active' => static fn(): bool => class_exists( 'npx_acf_plugin_image_aspect_ratio_crop' ),
			'slug'   => 'acf-image-aspect-ratio-crop',
		);
		return $plugins;
	}
);

// Web fonts for the front-end and the editor canvas (match $font-family-* in _tokens.scss).
// add_filter( 'roadmap_starter_google_fonts_url', static fn() => 'https://fonts.googleapis.com/css2?family=…&display=swap' );

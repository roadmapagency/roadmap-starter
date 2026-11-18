
=== roadmap-starter ===

Contributors: automattic
Tags: custom-background, custom-logo, custom-menu, featured-images, threaded-comments, translation-ready

Requires at least: 4.5
Tested up to: 6.6.1
Stable tag: 2.1.1
License: GNU General Public License v2 or later
License URI: LICENSE

A starter theme called roadmap-starter.

== Description ==

Roadmap Starter's custom base wordpress theme

== Installation ==

1. In your admin panel, go to Appearance > Themes and click the Add New button.
2. Click Upload Theme and Choose File, then select the theme's .zip file. Click Install Now.
3. Click Activate to use your new theme right away.

== Frequently Asked Questions ==

= Does this theme support any plugins? =

roadmap-starter includes support for Infinite Scroll in Jetpack.

== Changelog ==

= 2.1.1 - Nov 18 2026 =
* Fixed: `wp roadmap-starter child-audit` now fails on a child block whose class isn't in the child's namespace (it silently never registered), and checks the block prefix and namespace of an inactive child too.
* Fixed: The `wp roadmap-starter` commands can be loaded before any roadmap-starter theme is active, e.g. to create a fresh site's first child: `wp --require=wp-content/themes/roadmap-starter/inc/cli/bootstrap.php roadmap-starter scaffold-child <slug>` (or add it to the site's wp-cli.yml). `wp help` and `wp cli has-command` then see them too.
* Fixed: The blog index's featured post only passes fields the child's ImageAndText block has, and falls back to a plain card otherwise. New filter: `roadmap_starter_featured_post_block_data`.
* Fixed: Removed angle background images pointing at another site's theme (`/wp-content/themes/valleyfiber/…`), which 404'd on every page. Sites that want the motif set the image in their own styles.
* Removed: An unused client photo (`src/images/client-supplied/pic3.jpg`) that was copied into every child's `public/`.
* Upgrade: rebuild each child theme (`npm run build`) so it picks up the parent's styles.

= 2.1.0 - Oct 7 2026 =
* Added: Minor and patch releases of Roadmap Starter (2.x) now install automatically through WordPress's background updater. A new major version still waits for someone to click Update. Change it with the `roadmap_starter_auto_update` filter.
* Changed: Updates come from the now-public GitHub repository; no token is needed (`ROADMAP_STARTER_GITHUB_TOKEN` is optional, for rate limits).
* Upgrade: sites on 2.0.0 need this one update installed (click Update, or enable auto-updates for the theme); later 2.x releases then install on their own.

= 2.0.0 - Oct 7 2026 =
* Changed: Roadmap Starter is now a parent theme. Each site is a child theme holding only its own blocks, design tokens, styles, templates and post types; the block framework, base styles, templates and build are shared and update in place. New sites: `wp roadmap-starter scaffold-child <slug>`.
* Changed: The AI glue (block schemas, the Source Content field, icon search) now comes from the ai-by-roadmap plugin. Requires ai-by-roadmap 0.4.0 or newer.
* Changed: The parent ships no blocks. Hero, FAQs and ImageAndText moved to the child scaffold (`scaffold/child`).
* Changed: The build is shared through `build/webpack.factory.js`; children use a 3-line `webpack.config.js`. Site variables are `!default` and the block CSS prefix is `$block-prefix` (SCSS) / `roadmap_starter_block_prefix` (PHP).
* Added: `wp roadmap-starter child-audit` (redeclared parent functions, `get_template_directory()` in child code, prefix mismatches, files identical to the parent).
* Added: Filters for children converted from older themes: `roadmap_starter_block_namespace`, `_block_container_class`, `_block_align_fallback`, `_block_default_values`; blocks without a field-group location default to their own block; `roadmap_starter/before_block_render` also passes the block slug.
* Added: Updates from GitHub Releases (`roadmap-starter.zip`), off in git checkouts.
* Fixed: Fluid heading sizes compile to a valid `clamp()`; render falls back to a field's default value; missing Font Awesome SVGs no longer log warnings; search and archive pages use a child's `home.php`.
* Upgrade: existing forks keep working until converted. To convert one, follow "Converting an existing fork" in README.md (or the theme-upgrade plugin for older TeamGI-based themes), then run `wp option update template roadmap-starter --skip-themes` with the parent installed alongside.

= 1.0.1 - Oct 7 2026 =
* Changed: `npm run package` now names the zip `<theme>-v<version>.zip` and puts the theme in a top-level folder, so WordPress installs it to the right directory.
* Added: CLAUDE.md with the release workflow.

= 1.0 - Feb 27 2026 =
* Initial release

== Credits ==

* Based on Underscores https://underscores.me/, (C) 2012-2017 Automattic, Inc., [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html)
* normalize.css https://necolas.github.io/normalize.css/, (C) 2012-2016 Nicolas Gallagher and Jonathan Neal, [MIT](https://opensource.org/licenses/MIT)

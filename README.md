# Roadmap Starter Theme

A starter WordPress theme by [Roadmap Agency Inc.](https://www.weareroadmap.com/), built with:

- ACF Blocks (custom PHP block framework)
- Bootstrap 5
- SCSS compiled via Webpack
- Composer (ACF Builder via stoutlogic/acf-builder)

## Parent theme + child themes

Roadmap Starter is a **parent theme**. Each client site is a **child theme** (`Template: roadmap-starter`) that
holds only what is specific to the site. The parent and the AI plugin are updated in place on every site.

| Lives in the parent (updates reach every site) | Lives in the child (per site) |
|---|---|
| Block registry, `AbstractBlock`, helpers, navwalker, default templates | Blocks (`acf-blocks/Blocks/*`, own namespace) |
| `framework.scss` (Bootstrap, base modules, tokens as `!default`) | `_tokens.scss` (colours, fonts, spacing), site SCSS |
| `build/webpack.factory.js` (the whole build) | `webpack.config.js` (3 lines), `package.json` |
| Updater, plugin requirements, CLI | Templates it overrides, post types, options, header/footer |

The AI glue (block schemas, the Source Content field, icon search) is provided by the **ai-by-roadmap** plugin
(0.4.0+); the theme opts in with `add_theme_support( 'ai-by-roadmap', … )`.

### Creating a new client theme

```bash
wp roadmap-starter scaffold-child client-acme --name="Acme Industries"   # --link-node-modules for local dev
cd wp-content/themes/client-acme && npm install && npm run build
wp theme activate client-acme
```

The child gets the Hero / FAQs / ImageAndText starter blocks in its own namespace (`ClientAcme\Blocks`), a
`_tokens.scss` to fill in, and a `functions.php` that loads its own files on `roadmap_starter_loaded` (its
`functions.php` runs *before* the parent's). Run `wp roadmap-starter child-audit` after changes: it fails on
redeclared parent functions, `get_template_directory()` in child code, or a `$block-prefix` that disagrees with
PHP, and lists files identical to the parent's (delete them so parent updates apply).

**Converting an existing fork** (made with the old `clone` command): set `Template: roadmap-starter` in its
`style.css`, delete the framework copies `child-audit` reports, re-namespace its blocks, move its variables to
`_tokens.scss`, point `webpack.config.js` at the factory, build, then `wp option update template roadmap-starter
--skip-themes`. Block names and ACF field keys come from the block class name, so no content changes.

`wp roadmap-starter clone` (a full copy) is kept only for older tooling.

## Development

```bash
npm install
composer install
npm run watch   # watch for changes
npm run build   # production build
npm run package # build + zip for deployment
```

## Block Structure

Blocks live in the child theme's `acf-blocks/Blocks/` (the parent ships none; starter blocks are in
`scaffold/child/`). Each block is a folder with:

- `BlockName.php` — registers the ACF block and fields (extends `AbstractBlock`)
- `template.php` — the block's HTML template

See `scaffold/child/acf-blocks/Blocks/Hero/` for a full example. A child block with the same folder name as a
parent block replaces it.

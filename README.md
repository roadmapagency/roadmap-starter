# Roadmap Starter Theme

A starter WordPress theme by [Roadmap Agency Inc.](https://www.weareroadmap.com/), built with:

- ACF Blocks (custom PHP block framework)
- Bootstrap 5
- SCSS compiled via Webpack
- Composer (ACF Builder via stoutlogic/acf-builder)

## Creating a New Client Theme

To create a new theme from this starter, copy this folder and run a find-and-replace across all files:

| Replace | With |
|---------|------|
| `roadmap-starter` | `your-client-slug` (kebab-case) |
| `roadmap_starter` | `your_client_slug` (snake_case) |
| `RoadmapStarter` | `YourClientName` (PascalCase) |
| `ROADMAP_STARTER_` | `YOUR_CLIENT_` (SCREAMING_SNAKE_CASE) |
| `Roadmap Starter` | `Your Client Name` (Title Case) |

Then rename the theme folder to `your-client-slug`.

> **Also update manually:**
> - `webpack.config.js` — update the `proxy` URL to match your local dev environment
> - `template-parts/content-contact-callout.php` — replace the `tel:` href placeholder with the client's phone number
> - `header.php` — search for any placeholder phone numbers and replace with client details

## Development

```bash
npm install
composer install
npm run watch   # watch for changes
npm run build   # production build
npm run package # build + zip for deployment
```

## Block Structure

Blocks live in `acf-blocks/Blocks/`. Each block is a folder with:

- `BlockName.php` — registers the ACF block and fields (extends `AbstractBlock`)
- `template.php` — the block's HTML template

See `acf-blocks/Blocks/Hero/` for a full example.

# Kirby Lingohub

Kirby CMS plugin that syncs content between Kirby and the Lingohub translation service. Handles serialization and deserialization of complex field types – blocks, layouts, structures and objects, including blocks nested within custom blocks.

## Tech Stack

- Panel: Vue 2.7 with Composition API (`<script setup>`, composables)
- Build: kirbyup (Vite-based bundler for Kirby Panel plugins)
- Vue utilities: kirbyuse (provides `usePanel`, `useSection`, `useContent`, etc.)
- PHP: Kirby 4/5 compatible, PHPUnit 12

## Commands

- `composer test` – run PHPUnit
- `pnpm run lint` – ESLint
- `composer csfix` – php-cs-fixer, which lives in `tools/phpcs/vendor/bin/`, not `vendor/bin/`

## Entry Points

- Plugin ID: `johannschopplich/lingohub`
- PHP bootstrap: `index.php` (registers API routes, hooks, sections, translations)
- Panel entry: `src/panel/index.js` (registers Vue components via `window.panel.plugin()`)
- API routes: `src/extensions/api.php`
- Local dev: `playground/` (self-contained Kirby installation)

## Architecture

PHP classes in `src/classes/Lingohub/`:

- `Lingohub`: the Lingohub API client and the sole reader of the plugin options
- `Content`: serializes a model's translatable fields to a flat key-value map and merges a translation back
- `PanelContext`: builds the config envelope the Panel receives
- `Multipart`: multipart body builder for resource uploads

PHP extensions in `src/extensions/`:

- `api.php`: `__lingohub__/` routes for context, status, export and import
- `sections.php`: the `lingohub-status` section
- `translations.php`: i18n strings

## Conventions

- Every plugin option is a credential or a project identifier. The Panel receives presence flags from `PanelContext`, never values, and the browser never calls `api.lingohub.com` directly.
- Comments explain why, not what. In `src/classes/**` a wrapped comment ends with a full stop and a single-line one does not; comments in `tests/**` and `src/panel/**` never do.
- Test methods are snake_case and named after the behavior they pin; data providers are camelCase.

## Search Hints

- `window.panel.plugin("johannschopplich/lingohub"` – Panel registration
- `App::plugin(` – PHP plugin registration
- `useLingohub` – main composable
- `__lingohub__/` – API route patterns

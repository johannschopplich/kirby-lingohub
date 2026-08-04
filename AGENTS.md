# Kirby Lingohub

Kirby CMS plugin that syncs content between Kirby and the Lingohub translation service. Handles serialization and deserialization of complex field types – blocks, layouts, structures and objects, including blocks nested within custom blocks.

## Commands

- `composer test` – PHPUnit
- `composer csfix` – php-cs-fixer, which lives in `tools/phpcs/vendor/bin/`, not `vendor/bin/`
- `pnpm run lint` – ESLint

## Conventions

- Every plugin option is a credential or a project identifier. The Panel receives presence flags from `PanelContext`, never values, and the browser never calls `api.lingohub.com` directly.
- `Lingohub` is the sole reader of the plugin options; nothing else reaches into the option tree.
- Kirby 4 is still supported, and its `Exception` constructor takes a single array argument. Exceptions are built with the array form so `httpCode` survives on both majors – named arguments only become available once Kirby 5 is the floor.
- Comments explain why, not what. In `src/classes/**` a wrapped comment ends with a full stop and a single-line one does not; comments in `tests/**` and `src/panel/**` never do.
- Test methods are snake_case and named after the behavior they pin; data providers are camelCase.

## Search Hints

- `window.panel.plugin("johannschopplich/lingohub"` – Panel registration
- `App::plugin(` – PHP plugin registration
- `useLingohub` – main composable
- `__lingohub__/` – API route patterns

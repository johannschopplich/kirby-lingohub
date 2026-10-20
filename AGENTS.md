# Kirby Lingohub

Kirby CMS Panel plugin that syncs content between Kirby and the Lingohub translation service. It flattens blocks, layouts, structures and objects – including blocks nested in custom blocks – into Lingohub's key–value format and merges translations back.

## Commands

```bash
composer test          # PHPUnit
composer csfix         # php-cs-fixer, lives in tools/phpcs/vendor/bin/, not vendor/bin/
pnpm run test:types    # typecheck with tsc, which does not reach .vue files
pnpm run lint          # ESLint
pnpm run build         # build the Panel bundle (index.js)
```

## Conventions

- Lingohub keys join field names, structure row indexes, and block IDs and types with `_`, e.g. `blocks_{id}_{type}_text`. Every segment already in Lingohub is stored under such a key, so changing the format orphans them all. Keys are matched by walking the content, never by splitting them.
- A model's Lingohub resource is `{model id}/{blueprint}_{locale}.json`, with `site` as the Site's path. A slug change, move, template change or locale change orphans it.
- Test methods are snake_case with no `test` prefix, marked `#[Test]`; data providers are camelCase.

## Search Hints

- `Content::walk` – the traversal both upload and download build keys with
- `Content::fromPath` – resolves the Panel view path the model-bound `__lingohub__/` routes receive
- `__lingohub__/` – the plugin's API routes

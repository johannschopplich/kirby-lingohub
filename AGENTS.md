# Kirby Lingohub

Kirby CMS Panel plugin that syncs content between Kirby and the Lingohub translation service. It flattens blocks, layouts, structures and objects – including blocks nested in custom blocks – into Lingohub's key–value format and merges translations back.

## Commands

```bash
composer test
composer csfix         # lives in tools/phpcs/vendor/bin/, not vendor/bin/
pnpm run test:types    # tsc, which does not reach .vue files
pnpm run lint
pnpm run build         # after a change in src/panel/; index.js is committed
```

## Conventions

- Lingohub keys join field names, structure row indexes, and block IDs and types with `_`, e.g. `blocks_{id}_{type}_text`. Every segment already in Lingohub is stored under such a key, so changing the format orphans them all. Keys are matched by walking the content with `Content::walk`, which upload and download share, never by splitting them.
- A model's Lingohub resource is `{model id}/{blueprint}_{locale}.json`, with `site` as the Site's path. A slug change, move, template change or locale change orphans it.
- Test methods are snake_case with no `test` prefix, marked `#[Test]`; data providers are camelCase.

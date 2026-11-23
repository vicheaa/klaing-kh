# Cambodia Open Data Platform

This project will publish Cambodian fuel prices and exchange rates in Khmer and
English. It uses Laravel 13, PHP 8.4, Inertia 3, React 19, TypeScript, and
Wayfinder. Verify PHP package versions with `composer show --direct` and
frontend versions in `package.json`.

## Read for the task

- Before planning or editing, open the [rules index](.ai/rules/index.md), read
  the rules that match the paths in scope, and search `.ai/rules` for the task's
  keywords.
- Read the [project map](.ai/project.md) for feature status, code entry points,
  and the market-data roadmap. Use [CONTRIBUTING.md](CONTRIBUTING.md) for setup,
  checks, and pull requests.
- Activate the relevant Laravel Boost skills. Generated framework guidance,
  when installed, is available on demand in `.ai/boost-guidelines.md`.

## Working rules

- Inspect `git status --short` and sibling files. Preserve existing work and
  reuse project code. Ask before adding dependencies or new top-level
  directories. Create documentation only when requested.
- Use Boost `search-docs` for version-sensitive Laravel ecosystem changes,
  including Inertia. Use Boost for schema inspection, read-only DB queries,
  recent browser logs, and resolving project URLs before sharing them.
- Use Artisan `make:` commands and `--no-interaction`. Use PHP parameter and
  return types, braces for control structures, and constructor promotion.
  Format changed PHP with `vendor/bin/pint --dirty --format agent`.
- Keep credentials and production data out of commits and fixtures. Keep
  production releases manual and owner-approved.

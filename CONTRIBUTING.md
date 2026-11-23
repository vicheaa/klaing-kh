# Contributing

Start with the [project map](.ai/project.md) and the rules for the paths your
change touches in the [rules index](.ai/rules/index.md). The same standards apply
to human and agent contributions.

## Local setup and checks

Use PHP 8.4, Composer 2, and Node.js 22 to match
[CI](.github/workflows/tests.yml). Run these commands from a fresh checkout.

```sh
composer setup
composer run dev
```

`composer setup` installs dependencies, generates a local app key, migrates the
local database, and builds frontend assets. The example environment uses
SQLite, and tests use an in-memory SQLite database. Verify MySQL-specific
behavior on a disposable MySQL database when a change depends on it.

| Task                                                                               | Command                                                            |
| ---------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| Run the PR checks (frontend formatting and lint, TypeScript, Pint, Larastan, Pest) | `composer ci:check`                                                |
| Run affected tests                                                                 | `php artisan test --compact` with the relevant test path or filter |
| Build frontend assets                                                              | `npm run build`                                                    |

## Pull requests

1. Create a branch from `main` in the repository or your fork. Keep the change
   focused and open the pull request against `main`.
2. Add regression coverage when behavior changes. Cover relevant failure paths
   and use fixtures or fakes for upstream HTTP calls.
3. Run the applicable checks and report the results in the PR. Describe any
   migration, config, API contract, or dataset changes and their rollout impact.
4. Request review and address feedback. Keep credentials, production data, and
   local Boost setup files such as `.ai/boost-guidelines.md` out of the PR.

Update the project map when feature status or stable entry points change. Add
new conventions with Boost's `record-rule` tool and edit changed rules in place.
If rule files or path globs change, regenerate the index with
`php artisan boost:index-rules --no-interaction`. Commit the shared rules and
their index together.

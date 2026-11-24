# Project map

Klaing KH is an independent public data portal for everyday life in Cambodia.
It publishes exchange rates and administrative areas in Khmer (`km`) and English
(`en`). Show the original publisher, attribution, effective date, and freshness
where users read market data.

## Repository today

| Area                                                                 | Entry point                                                                                                                                  |
| -------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| Klaing KH home and authenticated team dashboard                      | [Home page](../resources/js/pages/welcome.tsx), [web routes](../routes/web.php)                                                              |
| Fortify authentication, passkeys, registration, and account settings | [Fortify config](../config/fortify.php), [provider](../app/Providers/FortifyServiceProvider.php), [settings routes](../routes/settings.php)  |
| Team membership, roles, and invitations                              | [Team controllers](../app/Http/Controllers/Teams), [TeamPolicy](../app/Policies/TeamPolicy.php), [invitation cleanup](../routes/console.php) |
| API                                                                  | [Sanctum-authenticated user endpoint](../routes/api.php)                                                                                     |
| Public administrative-area directory and API                         | [Public page](../resources/js/pages/administrative-areas.tsx), [API routes](../routes/api.php)                                               |
| Frontend composition and shared UI                                   | [Inertia entry point](../resources/js/app.tsx), [UI primitives](../resources/js/components/ui), [theme](../resources/css/app.css)            |
| Local, test, and CI configuration                                    | [Environment example](../.env.example), [Pest setup](../tests/Pest.php), [CI workflow](../.github/workflows/tests.yml)                       |

The exchange-rate page converts amounts between currencies in the DataEF feed
and charts daily rates for the selected pair. Cross rates use both currencies'
published riel quotes for the same date. For USD/KHR in either direction, the
page can fill earlier dates from the NBC rates republished by the General
Department of Taxation (GDT). DataEF takes precedence on overlapping dates.
The existing `/api/v1/exchange-rates` endpoints return the DataEF USD/KHR series.

To import a downloaded official English GDT exchange-rate page, use
`php artisan exchange-rates:import-history <file> --no-interaction`. The import
validates the rates and stores their source separately.

Run `php artisan migrate --no-interaction` before using the page. After confirming
source permissions, enable both `DATAEF_SYNC_ENABLED` and
`DATAEF_NBC_EXCHANGE_RATE_ENABLED`, then run
`php artisan dataef:sync-exchange-rates --no-interaction` with a worker serving the
`ingestion` queue. The daily scheduler uses Phnom Penh time. External API access
is separately controlled by `PUBLIC_EXTERNAL_API_ENABLED`.

The administrative-area directory at `/administrative-areas` and read API at
`/api/v1/administrative-areas` use the 2025 Cambodia CSVs in `public/assets`.
After migrating, run `php artisan db:seed --class=AdministrativeAreaSeeder --no-interaction`
to load all four levels.

Registration is currently enabled in Fortify. The MVP calls for admin-only
accounts with public self-registration disabled.

## Remaining work

1. Ship the fuel-price history slice for gasoline 92 and diesel. Build the
   source registry, audited ingestion, persisted history, read API, and public
   page together.
2. Add a public dashboard and sources page, Khmer/English localization, and
   policy-protected source status, sync history, and manual sync.
3. Validate the production setup on staging. The target is a single Laravel
   application on Ubuntu with Nginx/PHP-FPM, MySQL 8.4, database-backed queue and
   cache, supervised workers, and a scheduler. Check backups, restoration,
   monitoring, and rollback before requesting release approval.

## Source references

- Confirm unit price versus change columns against source records and official
  announcements before enabling the fuel importer. Start with the [DataEF fuel dataset](https://data.mef.gov.kh/datasets/pd_681d89b4dbc953000126f5e7).
- The NBC importer uses the verified DataEF bulk real-time endpoint.
  [NBC exchange-rate dataset](https://data.mef.gov.kh/datasets/pd_66a0cd503e0bd300012638fb4)
- The historical import uses published NBC USD/KHR observations.
  [GDT rate archive](https://www.tax.gov.kh/gdtwebsiteweb/en/exchange-rate)
- Check dataset-specific permissions and record the original publisher and
  license status. Consult the [DataEF terms](https://data.mef.gov.kh/km/terms-of-use)
  and [data access guide](https://data.mef.gov.kh/user-guide/data-consumer/get-access-to-data).

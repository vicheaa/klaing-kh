# Project map

The Cambodia Open Data Platform will publish sourced fuel prices and exchange
rates in Khmer (`km`) and English (`en`). It is an independent public-information
site. Show the original publisher, attribution, effective date, and freshness
where users read market data.

## Repository today

| Area                                                                 | Entry point                                                                                                                                  |
| -------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| Public welcome page and authenticated team dashboard                 | [Web routes](../routes/web.php), [React pages](../resources/js/pages)                                                                        |
| Fortify authentication, passkeys, registration, and account settings | [Fortify config](../config/fortify.php), [provider](../app/Providers/FortifyServiceProvider.php), [settings routes](../routes/settings.php)  |
| Team membership, roles, and invitations                              | [Team controllers](../app/Http/Controllers/Teams), [TeamPolicy](../app/Policies/TeamPolicy.php), [invitation cleanup](../routes/console.php) |
| API                                                                  | [Sanctum-authenticated user endpoint](../routes/api.php)                                                                                     |
| Frontend composition and shared UI                                   | [Inertia entry point](../resources/js/app.tsx), [UI primitives](../resources/js/components/ui), [theme](../resources/css/app.css)            |
| Local, test, and CI configuration                                    | [Environment example](../.env.example), [Pest setup](../tests/Pest.php), [CI workflow](../.github/workflows/tests.yml)                       |

The committed application contains starter-kit flows. The dataset registry,
market-data pipelines and pages, `/api/v1` endpoints, and admin sync controls are
planned. Registration is currently enabled in Fortify. The MVP calls for
admin-only accounts with public self-registration disabled.

## Implementation order

1. Ship the fuel-price history slice for gasoline 92 and diesel. Build the
   source registry, audited ingestion, persisted history, read API, and public
   page together.
2. Verify the NBC exchange-rate endpoint, payload, and currency direction.
   Implement the confirmed pair using the same path from ingestion to display.
3. Add a public dashboard and sources page, Khmer/English localization, and
   policy-protected source status, sync history, and manual sync.
4. Validate the production setup on staging. The target is a single Laravel
   application on Ubuntu with Nginx/PHP-FPM, MySQL 8.4, database-backed queue and
   cache, supervised workers, and a scheduler. Check backups, restoration,
   monitoring, and rollback before requesting release approval.

## Source references

- Confirm unit price versus change columns against source records and official
  announcements before enabling the fuel importer. Start with the [DataEF fuel dataset](https://data.mef.gov.kh/datasets/pd_681d89b4dbc953000126f5e7).
- Verify the exchange-rate endpoint and payload before enabling live ingestion.
  Start with the [NBC exchange-rate dataset](https://data.mef.gov.kh/datasets/pd_66a0cd503e0bd300012638fb4).
- Check dataset-specific permissions and record the original publisher and
  license status. Consult the [DataEF terms](https://data.mef.gov.kh/km/terms-of-use)
  and [data access guide](https://data.mef.gov.kh/user-guide/data-consumer/get-access-to-data).

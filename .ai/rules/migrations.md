---
paths:
  - 'database/migrations/**'
---

# Migrations

## Preserve market-data identity and precision
Use decimal columns for prices and rates. Index effective-date lookups and histories, and enforce uniqueness by source, fuel type, and effective start for fuel prices, or source, currency pair, rate type, and effective time for exchange rates. Preserve provenance with source foreign keys. Keep migrations safe for fresh databases and upgrades, and verify MySQL-specific behavior on MySQL.

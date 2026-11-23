---
paths:
  - 'app/Integrations/**'
---

# Integrations

## Verify and bound upstream ingestion
Fetch only approved HTTPS hosts and dataset IDs. Verify permissions, payloads, field meanings, units, and pagination before enabling live fetches. Bound response size, pages, requests, and retries. Honor `Retry-After`. Validate and normalize in adapters before writing. Keep `DATAEF_SYNC_ENABLED` off by default, honor per-source permission and enablement, and read settings through `config()`.

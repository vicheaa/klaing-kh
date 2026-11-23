---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Validate HTTP input and delegate market-data queries
Validate HTTP inputs in Form Requests and use their validated values. Keep market-data controllers thin and read only normalized database or cache data through shared domain queries. Use API Resources for versioned JSON with `data`, `meta`, and pagination `links`. Serialize decimals as strings. Include publisher, source URL, effective date, and freshness, and distinguish unavailable, stale, and failed-sync states.

---
paths:
  - 'routes/*.php'
---

# Routes

## Keep public reads and admin sync separate
Use named routes. Put read-only market-data endpoints under `/api/v1`, throttle them, and keep third-party access disabled by default with `PUBLIC_EXTERNAL_API_ENABLED` until authorized. Keep first-party Inertia reads available independently. Require authentication, policy authorization, and CSRF protection for admin writes. Have scheduled commands dispatch bounded ingestion jobs.

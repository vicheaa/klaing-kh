---
paths:
  - 'tests/**'
---

# Tests

## Cover behavior with Pest and deterministic data
Add focused regression coverage for changed behavior and important failures. Use Pest feature tests for HTTP and database flows and factories for records. Run the narrowest applicable tests. Use `Http::fake()` and sanitized fixtures for ingestion. Cover idempotency, failed imports retaining last verified values, and permission boundaries when those behaviors change.

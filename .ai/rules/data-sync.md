---
paths:
  - 'app/Jobs/DataSync/**'
---

# Data Sync

## Make dataset syncs safe to retry
Run imports on the database `ingestion` queue with a per-source lock, bounded runtime, and an auditable `sync_runs` record. Use transactional upserts keyed by source identity. Preserve last verified data on unexpected empty, malformed, or partial imports. Update successful sync metadata and invalidate caches after data commits. Record sanitized failure details and recover abandoned runs.

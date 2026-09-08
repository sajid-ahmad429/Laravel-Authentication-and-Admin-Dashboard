## 2026-08-24 - Consolidating User Summary Counts with Conditional Aggregation

**Learning:** Separately executed `COUNT(*)` queries on the `users` table for active, inactive, and total stats generate redundant database round-trips. Using `COUNT(CASE WHEN ... THEN 1 END)` inside a single `selectRaw` query computes all summary statistics in 1 database pass. Caching DataTables aggregate counts also prevents redundant queries on every table redraw.
**Action:** When computing multi-status dashboard metrics, combine separate queries into single SQL conditional aggregate queries and cache table-level aggregates for short durations.

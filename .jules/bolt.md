## 2026-09-09 - Caching DataTables Aggregate Counters & Avoiding Unused Model Selections

**Learning:** DataTables endpoints that perform `selectRaw` aggregation queries (e.g. counting active/inactive/trashed records across the whole table) on every AJAX request create severe database performance bottlenecks during pagination and sorting. Furthermore, controllers returning dashboard views can contain redundant `Model::all()` calls left over from earlier implementations that load unused Eloquent collections into memory.

**Action:** Wrap full-table DataTables aggregate counts in `Cache::remember()` with 120s TTL and purge them in `clearUserCache()` upon mutation. Always check if data variables passed to Blade views (like `$users`) are actually rendered by the view.

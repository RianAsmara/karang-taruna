# Backend Bug Tracker

For findings from testing the Laravel domain/API layer (routes, models,
policies, actions, controllers — web *or* API routes, since both live
in the same Laravel app). Add a row under **Open** for each new bug —
copy the template row, fill it in, delete the placeholder text. Move a
row to **Resolved** once it's fixed (don't delete it — keep the
history).

Known gaps that aren't bugs (features that were never built, by design
or by phase sequencing) are tracked in `docs/backend-backlog.md` —
check there before logging something that's actually a missing
feature rather than a defect in something that exists.

## Open

| ID | Date | Area | Description | Severity | Steps to reproduce | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| B-001 | | | | | | |

**Severity guide**: `blocker` (endpoint/flow unusable) · `major` (wrong
data, broken authorization, crash, data corruption risk) · `minor`
(wrong error message, inconsistent response shape) · `cosmetic` (typo,
formatting).

## Resolved

| ID | Date | Area | Description | Fixed in | Notes |
| --- | --- | --- | --- | --- | --- |
| B-001 | 25 Aug 2026 | Notifications (`notifications` table) | `php artisan notifications:table`'s stock migration uses `$table->morphs('notifiable')`, creating `notifiable_id` as `bigint`. Every notifiable model in this app (User) uses ULID string primary keys, so any insert/query against the table failed on real PostgreSQL (`invalid input syntax for type bigint`). Found via a live curl check against the running dev server, not by the test suite — Pest runs against SQLite (`:memory:`), whose dynamic typing silently accepts the string into a `bigint` column, masking the bug. | `database/migrations/..._create_notifications_table.php` — changed to `$table->ulidMorphs('notifiable')`. Rolled back and re-migrated on the dev DB. | Worth remembering generally: a schema bug tied to strict column typing (int vs ULID string) can pass the full Pest suite and still break on Postgres, precisely because tests run on SQLite. A live smoke-test against the real DB is the only thing that would have caught this. |

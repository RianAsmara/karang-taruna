# Decisions (ADR log)

Short-form architecture decision records. Newest first once Phase 1+
starts adding to this; Phase 0 entries below are foundational.

## ADR-0001: One Laravel app for web + API, not two services

**Decision**: Web (Inertia) and the future mobile REST API are served by
a single Laravel 13 app. React Native is a separate application in the
same repo (`mobile/`), but it is a client only.

**Why**: A split risks web and mobile silently drifting on business
rules (double-implementing validation, authorization, or finance
calculations). Sanctum supports both session-based (web) and
token-based (mobile) auth from one stack, so there's no technical forcing
function for two services. See `architecture.md`.

**Status**: Fixed by the product brief; not revisited.

## ADR-0002: ULIDs as the primary key strategy

**Decision**: Every table uses a ULID primary key (`HasUlids`), not
auto-increment integers, and not UUIDv4.

**Why**: Report/transaction IDs appear in shareable URLs and QR codes —
sequential IDs would leak volume information (e.g., competitor or nosy
member inferring transaction counts). ULIDs stay sortable by creation
time (useful for report/transaction listings), unlike UUIDv4, and can be
generated without server coordination if ever needed for
offline-tolerant mobile writes later.

**Status**: Applies from Phase 1's first migration onward.

## ADR-0003: Integer rupiah for all money, no floats

**Decision**: `amount` columns are `bigint` integer IDR. No floating
point, no decimal/numeric minor-unit subdivision.

**Why**: IDR has no commonly-used fractional unit in this domain (unlike
USD cents). Floating point risks non-deterministic rounding in balance
calculations, which is unacceptable for a source-of-truth financial
ledger. This is a hard constraint from the product brief, not a
convenience choice.

**Status**: Non-negotiable; enforced via DB `CHECK (amount > 0)`
constraints plus application-level validation.

## ADR-0004: Starter kit dependency versions pinned to a known-good local baseline

**Decision**: Phase 1 scaffolding targets the exact dependency set
already proven working in `usaharumahan-marketplace/` (a sibling project
in this same workspace, also on the official Laravel React Starter Kit):
Laravel `^13.17`, Inertia `^3.0`, React `^19.2`, Tailwind `^4.0`, Pest
`^4.7`, Pint `^1.27`, Larastan `^3.9`.

**Why**: Confirms compatibility on this exact machine (PHP 8.4.24,
Node 24.14.1 already verified) rather than guessing at "latest" and
hitting avoidable version-mismatch issues during scaffolding.

**Status**: Baseline for Phase 1; revisited only if a specific package
needs a different version for a documented reason.

## ADR-0005: No custom repository/service/DTO layers by default

**Decision**: Standard Laravel layering (Eloquent, Form Requests,
Policies, Actions-when-justified) — no generic `BaseRepository`,
`BaseService`, `BaseController`, or blanket DTO layer.

**Why**: Explicit constraint from the product brief; this domain's
complexity doesn't currently justify the indirection, and premature
abstraction here would slow down Phase 2–4 without a corresponding
benefit. Revisit only if a specific pain point emerges (e.g. an Action
class genuinely reused across ≥2 unrelated call sites).

**Status**: Default; exceptions require a specific justification noted
inline where introduced.

## ADR-0006: RukunID (sibling project) is a related but distinct product — not reused

**Context**: This workspace already contains `RukunID/`, a SaaS platform
for Indonesian RT/RW residential community management (finance,
transparency, voting, members) — built on Bun + ElysiaJS + Drizzle (API),
React + Vite (web), Expo + Tamagui (mobile). Domain overlap with
RukunMuda is real: both manage community finance, transparency, voting,
and membership for Indonesian local-community organizations, and
RukunMuda's role list even includes `RESIDENT`.

**Decision**: RukunMuda is a separate product, built independently on
Laravel/Inertia per the product brief, not a port or extension of
RukunID. No code, schema, or infra is shared between the two.

**Why**: RukunMuda's audience is specifically youth organizations
(Karang Taruna / Pemuda), a different primary user base from RukunID's
RT/RW residents, even though the domains rhyme. The product brief locks
in Laravel + Inertia deliberately (explicit "why" note from the user:
avoiding a separate React SPA in favor of the official starter kit's
integrated Inertia approach). Treating this as a fresh build avoids
entangling two products' release cycles and data models.

**Open question for the user**: if RukunMuda and RukunID's target
audiences turn out to overlap significantly in practice (e.g. an RT/RW
that also runs a Karang Taruna), a future decision may be needed on
whether they stay separate products or converge — noted here so it isn't
silently forgotten, not because it blocks Phase 1.

**Status**: Confirmed direction for Phase 0–8. Flagged, not blocking.

## ADR-0007: Manually upgraded the starter kit from its published Laravel 12/Inertia 2 pins

**Context**: `composer create-project laravel/react-starter-kit` currently
scaffolds against `laravel/framework: ^12.0` and
`inertiajs/inertia-laravel: ^2.0` — the public starter kit package's own
`composer.json` hasn't been bumped to Laravel 13/Inertia 3 yet, even
though Laravel 13 (up to v13.26.1) and Inertia 3 are both published and
installable directly.

**Decision**: After scaffolding, manually raised
`laravel/framework` to `^13.17`, `inertiajs/inertia-laravel` to `^3.0`,
`laravel/tinker` to `^3.0` (required — `^2.x` caps at `illuminate/support
^12.0`), `@inertiajs/react` to `^3.0.0`, and `vite`/`laravel-vite-plugin`/
`@vitejs/plugin-react` to the versions already proven together in
`usaharumahan-marketplace` (`^8.0.0` / `^3.0.0` / `^5.2.0`) — Vite 6 was
still resolvable but the Inertia 3 + `laravel-vite-plugin` TypeScript
types only lined up cleanly on the newer major.

**Fallout fixed along the way**:
- `resources/js/app.tsx`'s `resolve` callback needed an explicit
  `import.meta.glob<ResolvedComponent>(...)` generic — without it,
  TypeScript inferred a self-referential union type from
  `createInertiaApp`'s contextual typing and `tsc --noEmit` failed. Typing
  the glob call directly (rather than `resolvePageComponent`'s own
  generic) resolved it.
- Swapped `phpunit/phpunit`-only testing for Pest (`pestphp/pest`,
  `pestphp/pest-plugin-laravel`), added Larastan (`phpstan.neon` mirrors
  `usaharumahan-marketplace`'s), matching the master prompt's explicit
  Pest/Larastan requirement — the stock scaffold ships PHPUnit-runnable
  Pest-syntax tests but no Pest dependency.

**Status**: Applied in Phase 1. Re-check whether the public starter kit
package has caught up to Laravel 13/Inertia 3 before the next `composer
create-project`-based scaffold (e.g. for `mobile/`'s eventual API
consumer setup) — this manual upgrade step may no longer be needed.

## ADR-0008: Local Postgres/Redis ports remapped to 5433/6380

**Context**: This workspace runs several sibling projects side by side.
`sakuplan-postgres-1` (a Docker container) already binds host `5432`, and
a native `redis-server` on this machine already binds host `6379`.
`docker compose up` for RukunMuda's own stack failed on both.

**Decision**: RukunMuda's `docker-compose.yml` maps Postgres to host
`5433` and Redis to host `6380` (container-internal ports stay standard
`5432`/`6379`). `.env.example` matches. `REDIS_CLIENT` is `predis`, not
`phpredis` — the `phpredis` PHP extension isn't installed on this
machine and predis is already a Composer dependency, so no `sudo`-level
system package is required.

**Why this matters for anyone new to this repo**: connecting a local
`psql`/`redis-cli` to the default ports will hit a *different* project's
database. Always pass `-p 5433` / `-p 6380`, or read the port from
`rukunmuda/laravel/.env`.

**Status**: Local-dev-only concern. Does not apply to staging/production,
where each environment gets its own standard-port instances.

## ADR-0009: Tenant resolution via a middleware-bound container instance, not a URL segment

**Context**: Phase 2 needed every org-scoped controller (members, events,
announcements) to know "which organization" without a `{organization}`
URL segment (Phase 1 established single-org-per-user, org-less URLs).
Resolving it ad hoc in every `index`/`store` method would duplicate the
"never trust a client-supplied organization_id" rule at every call site.

**Decision**: `App\Http\Middleware\ResolveCurrentOrganization` (route
middleware alias `current-org`) resolves `$user->currentMembership()`
once per request and binds the resulting `Organization` and
`OrganizationMembership` into the container
(`app()->instance(Organization::class, ...)`). Controllers needing the
current org simply type-hint `Organization $organization` in the method
signature — standard Laravel method injection resolves it from the
bound instance, no manual lookup, and no way to accidentally read it
from request input. If the user has no membership, the middleware
redirects to the dashboard (which already prompts them to create an
organization) rather than 404/500ing.

**Also decided**: `User::roleIn(Organization): ?OrganizationRole` and
`User::membershipIn(Organization): ?OrganizationMembership` were
extracted onto the model — every policy (`OrganizationPolicy`,
`OrganizationMembershipPolicy`, `EventPolicy`, `EventTaskPolicy`,
`EventParticipantPolicy`, `AnnouncementPolicy`) calls these instead of
each re-implementing `$user->memberships->firstWhere(...)`. `Event`
additionally exposes `isManagedBy(User): bool` (OWNER/ADMIN or the
event's PIC) since three different policies/controllers needed that
exact rule.

**Status**: Established pattern for Phase 3+ (finance, transparency)
wherever a controller needs "the current organization" without a URL
segment.

## ADR-0010: `@property` annotations required alongside `casts()` for Larastan

**Context**: Repeated across Phase 1 and Phase 2 — Larastan (level 7)
does not reliably infer attribute types from Laravel 11+'s method-based
`protected function casts(): array` for enum and date casts on new
models, reporting `Cannot call method value() on string` /
`toIso8601String() on string` even though the cast is correctly declared
and works at runtime.

**Decision**: Every model with a non-trivial cast (enum, date/Carbon)
also carries a class-level `@property` PHPDoc block spelling out the
real type (e.g. `@property EventStatus $status`, `@property
\Illuminate\Support\Carbon $start_at`). This is documentation Larastan
trusts directly, sidesteps the inference gap, and is genuinely useful
for IDEs regardless of the static-analysis quirk.

**Status**: Standing convention — add `@property` annotations for any
enum/date cast on every new model going forward, don't wait for
Larastan to complain first.

## ADR-0011: `CHECK (amount > 0)` guarded to the `pgsql` driver only

**Context**: ADR-0003 committed to integer rupiah with a DB-level
positivity constraint. Laravel's Schema `Blueprint` has no fluent
`check()` helper, so it's added via a raw `DB::statement('ALTER TABLE
... ADD CONSTRAINT ... CHECK (amount > 0)')` after `Schema::create`.
SQLite (used for the Pest suite) only accepts `CHECK` constraints
declared inline inside `CREATE TABLE` — a separate `ALTER TABLE ADD
CONSTRAINT` statement afterward is a syntax SQLite doesn't support at
all, and would fail every migration in tests.

**Decision**: The raw `ALTER TABLE ... CHECK` statement runs only when
`DB::connection()->getDriverName() === 'pgsql'`
(`financial_transactions`, `member_dues`, `member_payments`
migrations). On SQLite the constraint simply isn't present at the DB
level; application-level validation (`'amount' => ['required',
'integer', 'min:1']` in every relevant Form Request) still enforces
positivity on every driver, including tests.

**Status**: Applies to every financial `amount`-bearing table.
Production/staging run PostgreSQL, so the real safety net is always
present where it matters; the SQLite gap is test-only and covered by
validation instead.

## ADR-0012: `User::isTreasurerOf()` / `isOrganizerOf()` — and the bug they didn't catch

**Context**: Phase 3 needed a third role-group check
(OWNER/ADMIN/TREASURER, "who does day-to-day bookkeeping") alongside
Phase 2's OWNER/ADMIN check, which by then was already duplicated
inline across `EventPolicy`, `OrganizationPolicy`,
`OrganizationMembershipPolicy`, and `AnnouncementPolicy` (see ADR-0009,
which centralized `roleIn`/`membershipIn` but not the role-*group*
checks built on top of them).

**Decision**: Added `User::isOrganizerOf(Organization): bool`
(OWNER/ADMIN) and `User::isTreasurerOf(Organization): bool`
(OWNER/ADMIN/TREASURER) alongside `roleIn`/`membershipIn`, and refactored
every policy's inline `in_array($user->roleIn($org), [...], true)` to
call one of these. All Phase 3 financial policies
(`FinancialAccountPolicy`, `FinancialCategoryPolicy`,
`FinancialTransactionPolicy`, `MemberDuePolicy`) use `isTreasurerOf` for
day-to-day management and `isOrganizerOf` for approval-grade actions.

**Postscript — a real bug this refactor did not create but tests did
catch**: `ApproveFinancialTransactionAction`/`RejectFinancialTransactionAction`
call `$transaction->update(['status' => ..., 'reviewed_by' => ...,
'reviewed_at' => ...])`. `reviewed_by`/`reviewed_at` had been left out
of `FinancialTransaction::$fillable`, so Laravel's mass-assignment
protection silently dropped both fields on every approve/reject —
`status` changed correctly (it was fillable), but the reviewer was
never recorded, and the audit observer logged `transaction.auto_approved`
(no reviewer) instead of `transaction.approved`. Caught by
`test_creating_and_approving_a_transaction_writes_an_audit_trail`
asserting the exact audit action string, then confirmed via
`php artisan tinker` reproduction before fixing. Fixed by adding both
columns to `$fillable` — safe because `UpdateFinancialTransactionRequest`
never validates or exposes those two fields, so no new mass-assignment
surface was opened for user input, only for the two Actions that are
supposed to set them.

**Status**: `isTreasurerOf`/`isOrganizerOf` are the standing pattern for
any future role-group check. The fillable bug is fixed; the postscript
stays here as a reminder to check `$fillable` whenever an Action sets
fields a Form Request never validates.

## ADR-0013: One canonical report URL serves both members and public guests

**Context**: §27 of the brief specifies a single stable URL per
published report (`/reports/{report}`) that must work two ways: an org
member views it through the normal authenticated app, and a stranger
views the exact same URL after receiving it via WhatsApp or scanning its
QR code — with no login. A `PRIVATE`/`MEMBERS`/`PUBLIC` field on the
report itself decides which case applies, and that decision can only be
made by looking at the specific report being requested — not by which
route or middleware group handled the request.

**Decision**: `ReportController@show` (and `@share`, `@qr`) sit outside
the `auth` middleware entirely and branch internally:
`Auth::user() ? Gate-check via FinancialReportPolicy::view()
: (published && PUBLIC ? show : 404)`. This is the same shape used for
the organization landing page (no prior ADR needed there — it was
simple enough — but the pattern repeats here with real authorization
stakes, so it's worth naming): **a route can be intentionally
audience-mixed**, and when it is, the controller — not routing
middleware — is where "who is this actually for" gets decided, on a
per-record basis.

The Inertia page (`reports/show.tsx`) mirrors this: it reads
`auth.user` from shared props and renders either the full `AppLayout`
shell (with publish/archive/revise/delete actions, when the viewer is
authorized for them) or a bare public shell with just a "Masuk" link —
one component, two presentations, driven by the same `auth.user` check
every other page already gets for free.

**Status**: The pattern to reach for whenever a resource is meant to be
shareable outside the authenticated app — don't split it into two
routes/controllers/pages; branch once, in the one place that already
has the record and can check its actual state.

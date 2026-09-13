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

## ADR-0014: Mobile token auth is a separate flow from web session login — and JSON clients get errors, not redirects

**Context**: Phase 5 needed Sanctum-based API auth. The existing web
`LoginRequest::authenticate()` (Phase 1) is inherently session-based:
`Auth::attempt()` followed by `$request->session()->regenerate()`. A
mobile/API client needs a bearer token back, not a session cookie — a
fundamentally different response contract, not the same business rule
expressed twice.

**Decision**: `Api\V1\AuthController::login` is its own small flow,
following Sanctum's official "issuing mobile API tokens" pattern:
look up the user by email, `Hash::check()` the password directly, issue
`$user->createToken($device_name)->plainTextToken` on success. It does
not call or wrap `LoginRequest` — credential verification here is
framework plumbing (auth), not the kind of domain business logic the
brief's "don't duplicate business logic in API controllers" rule is
protecting (that rule is about things like report figure calculation or
authorization rules, which this flow still delegates to the same
`FinancialReportPolicy` etc. as the web app).

**Also decided**: `ResolveCurrentOrganization` (ADR-0009) now branches on
`$request->expectsJson()`. A user with no organization membership hitting
a web route gets redirected to `/dashboard` (which prompts them to create
one); the same condition on an API route returns a `422 {"message": "..."}`
JSON body instead — a redirect response is meaningless to a mobile HTTP
client. The container-binding mechanism itself (`app()->instance(Organization::class,
...)`) is untouched and shared by both.

**Status**: Standing pattern — any future auth-adjacent flow that needs a
genuinely different response contract per client type (web vs. API) gets
its own thin flow rather than being forced through the web one; anything
that's an actual domain rule (authorization, calculations, validation)
must still go through the one shared implementation.

## ADR-0015: Business logic promoted onto models so the API doesn't duplicate the web controllers

**Context**: Building the Phase 5 API controllers surfaced three pieces
of logic that had, until now, only lived inline inside Phase 4's web
controllers: the mixed-audience "can a guest see this report"
check (`ReportController`, three call sites), the "Transparansi" figure
computation (`TransparencyController@index`, one ~35-line block), and QR
PNG generation (`ReportController@qr`). Writing the API's equivalents by
copying that logic would have been exactly the "duplicated business
logic in API controllers" the brief explicitly forbids.

**Decision**: Moved each onto the model/domain layer, where it belongs
per the brief's own layering rule (§8 — business logic belongs on models
when it naturally belongs to the model), and had **both** the web and API
controllers call the shared version:

- `FinancialReport::isPubliclyViewable(): bool` — was a private method
  on the web `ReportController`, duplicated three times inside that same
  class even before Phase 5.
- `Organization::transparencySummary(): array` — mirrors
  `FinancialReport::calculateFigures()`'s existing pattern (ADR from
  Phase 4) of a model computing its own derived figures. Both
  `TransparencyController@index` (web) and `Api\V1\TransparencyController@index`
  now call this one method.
- `App\Support\ReportQrCode::png(string $url)` — QR generation isn't
  domain logic, but the size/margin/target-URL choice is a real decision
  worth keeping in exactly one place; a small `Support` class (not an
  Action — no multi-step business operation here) was enough.

`PublicTransparencyController` (the §30 fully-public page) was
deliberately **not** folded into `transparencySummary()` — it computes a
narrower, differently-filtered figure set (PUBLIC-visibility reports
only, no recent-transactions list) by design, not by omission; conflating
the two would have made the public page accidentally start leaking
whatever the members-only dashboard chooses to show next.

**Status**: Standing pattern for any future feature that needs a web and
an API surface: write the computation once on the model/domain layer
first, then have both controllers call it — never build the API version
by copying the web controller's inline logic.

## ADR-0016: Sanctum's default token migration doesn't fit a ULID-keyed `users` table — caught only by testing against real Postgres

**Context**: `php artisan install:api` publishes a
`personal_access_tokens` migration using `$table->morphs('tokenable')`,
which Laravel's schema builder implements as a `bigint`
`tokenable_id` column — the correct default when `tokenable` models use
auto-incrementing integer primary keys. This app's `User` (and every
other model) uses a ULID string primary key instead (ADR-0002). The
full Pest suite (30 new API tests plus the existing 111) passed cleanly
with the un-fixed migration, because SQLite — the suite's driver — does
not enforce column type strictly and happily stored a 26-character ULID
string into a column declared `INTEGER`. The very first live login
attempt against the project's real PostgreSQL instance
(`php artisan serve` + `curl`, the phase's usual final verification step)
failed immediately: `SQLSTATE[22P02]: invalid input syntax for type
bigint: "01m0v..."`.

**Decision**: Changed the published migration to
`$table->ulidMorphs('tokenable')`, matching the `foreignUlid`/`ulid()`
convention used by every other table that references a ULID-keyed model
in this schema. The token row's own primary key stays
Sanctum's standard `$table->id()` (bigint, auto-increment) — nothing in
this app references `personal_access_tokens.id` as a foreign key, so
there's no ULID requirement on that column specifically, only on the
morph target.

**Why this matters beyond the one-line fix**: this is a concrete
instance of a known Laravel gotcha (published/vendor migrations assume
auto-increment PKs by default) that is invisible to a green SQLite-backed
test suite. It's the reason the phase workflow always ends with a live
check against the project's real PostgreSQL — not just `php artisan
test` — before calling a phase done; this bug would have shipped
straight past a "111... 141 passed" test run otherwise.

**Status**: Fixed before this ever reached staging. Standing reminder:
any future `php artisan install:*`-published migration touching a
`tokenable`/`commentable`/similar morph column needs the same check
against this app's ULID convention before it's trusted.

## ADR-0017: `OrganizationRole` migrated from six roles to the mobile design's five-role model

**Context**: The mobile design docs (`mobile-ux.md` § Roles and
authorization, `mobile-design-system.md` § Roles — explicitly called
"the single authoritative permission table in the documentation set")
specify five roles: Ketua, Bendahara, Sekretaris, Panitia, Anggota.
`OrganizationRole` (ADR-0006, ADR-0012) had six: `OWNER`, `ADMIN`,
`TREASURER`, `COMMITTEE`, `MEMBER`, `RESIDENT`. Discovered while
scoping the Phase 7 mobile build order (Anggota/Peran & Izin screens) —
before that, nothing had compared the two role lists directly.

**Decision**: `OrganizationRole` is now `KETUA`, `BENDAHARA`,
`SEKRETARIS`, `ANGGOTA` (four stored values). Mapping from the old set:
`OWNER→KETUA`, `TREASURER→BENDAHARA`, `MEMBER→ANGGOTA`; `ADMIN`,
`COMMITTEE`, and `RESIDENT` all fold to `ANGGOTA` since none has a
distinct mobile-design equivalent. `Panitia` is **not** a fifth stored
value — mobile-design-system.md is explicit that it's "per event,
temporary," so it's derived entirely from the existing `EventCommittee`
model, never from `OrganizationMembership.role`.

`isOrganizerOf()` (ADR-0012) is renamed `isChairOf()` and narrowed to
`KETUA` alone (previously `OWNER`/`ADMIN`). `isTreasurerOf()` keeps its
name, now `KETUA`/`BENDAHARA`. New `isSecretaryOf()` is
`KETUA`/`SEKRETARIS`. The chair inherits every other role's abilities —
this is what makes mobile-screens.md's "if the chair is also the
treasurer" edge case (screen 31) work without a member holding two role
values at once: `isTreasurerOf()` already returns true for a `KETUA`.
`AnnouncementPolicy` moved from `isChairOf()` to `isSecretaryOf()` to
match mobile-ux.md's table, which assigns announcements to the
secretary — a real behavior change, not just a rename.
`KETUA` is excluded from both `StoreMemberRequest` and
`UpdateMemberRoleRequest`'s assignable-role validation: the chair is
unique and transfers via a dedicated action (not built yet — see
`mobile-screens.md` § 14 Peran & Izin), never a direct invite or the
general role-update endpoint.

A companion migration remaps existing `organization_memberships.role`
data by literal string (not by referencing the old enum cases, which no
longer exist). `MemberResource`'s `isOwner` field is renamed `isChair`;
the mobile `ApiMember` TS type and the web `resources/js` member-management
page were updated to match.

**Status**: Applied. `EventPolicy::create` narrowed from
`OWNER`/`ADMIN` to `KETUA`-only as a mechanical consequence of removing
`ADMIN` — mobile-screens.md § 29 describes event creation as available
to "pengurus" (any management role) more broadly, so this may need
widening to `isSecretaryOf()`-or-broader when Buat Kegiatan is actually
built; not done here to keep this change behavior-preserving modulo the
role count reduction.

## ADR-0018: Platform superadmin is a separate, read-only, cross-organization surface — not a bypass on the existing per-org controllers

**Context**: User asked whether the app supports RBAC and requested a
new superadmin role able to "control everything." Clarified via
AskUserQuestion to a narrower scope: platform-level (not tied to any
one organization), view-only across all organizations, for support/
moderation. `OrganizationRole` (ADR-0017) is deliberately org-scoped —
`KETUA`/`BENDAHARA`/`SEKRETARIS`/`ANGGOTA` only make sense in the
context of a specific organization's `OrganizationMembership`, and every
existing controller resolves its `Organization` from the *viewer's own*
membership via the `current-org` middleware (master prompt §9). A
superadmin, by definition, has no membership anywhere, so that
resolution mechanism can never apply to them — extending it would mean
either inventing a fake membership (corrupting membership data as a
side effect) or special-casing `current-org` itself (weakening the
tenant-isolation invariant for every route, not just superadmin ones).

**Decision**: `is_superadmin` is a boolean column on `users` (default
`false`, cast in `User`, deliberately **not** in `$fillable` — the only
way to set it is `User::forceFill()`, used solely by two Artisan
commands, `superadmin:grant {email}` and `superadmin:revoke {email}`,
each requiring interactive confirmation). There is no HTTP endpoint
that can grant or revoke it — granting platform-wide cross-tenant
access is judged high-stakes enough to require shell access to the
server, not just an authenticated HTTP session.

A new `EnsureSuperadmin` middleware (aliased `superadmin`) gates a new
route group under `/api/v1/superadmin/*`, placed inside `auth:sanctum`
but explicitly **outside** `current-org` — it resolves `Organization`
from the URL directly rather than from any membership. A new
`Api\V1\Superadmin\OrganizationController` (separate namespace, not a
method added to the existing `Api\V1\OrganizationController`) is
read-only: `index` lists every organization, `show` returns full detail
for one — including `DRAFT`/`PRIVATE` financial reports a normal
organization member could never see, since "view everything, for
support/moderation" was the explicit scope. Every `show` call writes an
`AuditLog` row (`superadmin.viewed_organization`) — this bypasses the
tenant-isolation invariant on purpose, so every use of it must be
traceable to who looked at what, when. No write endpoints exist under
`/superadmin/*` at all; a superadmin attempting any existing per-org
write route still 422s exactly like any other member-less user would,
since no `current-org` context exists for them.

This first pass covers Organizations, Members, and Financial Reports
(plus the transparency summary). Documents, Sponsors, Votes, and
Inventory are not yet exposed to superadmin view — the same pattern
(a new method on the same controller, or a sibling controller) extends
to them later if a real support/moderation need arises.

A web (Inertia) panel was added the same day at
`/superadmin/organizations` (list) and `/superadmin/organizations/{id}`
(detail), gated by the same `superadmin` middleware alias — the user
asked "should mobile/web support RBAC as well," and the answer for this
specific role was: web yes (an ops/support tool), mobile no (out of
scope for the 11 member-facing design screens; CLAUDE.md is explicit
that nothing beyond those exists without asking first). A new
`ViewOrganizationOverviewAction` was extracted and is now called by
*both* the web and API controllers, so the fetch-and-audit-log
invariant can't drift between the two entry points — this was a
refactor of the API controller, not just new code for the web side. The
web detail page deliberately does **not** link report titles to the
canonical `/reports/{id}` page: that page enforces the normal
`FinancialReportPolicy::view` (per-member) check, which a superadmin
(no membership anywhere) fails for exactly the DRAFT/PRIVATE reports
this panel exists to surface — extending that policy was treated as out
of scope for what was decided here (a separate read-only surface, not a
blanket policy bypass). The sidebar shows a "Superadmin" link only when
`auth.user.is_superadmin` is true (shared via `HandleInertiaRequests`,
already unhidden on the `User` model as of this feature).

**Status**: Applied. `tests/Feature/Api/SuperadminTest.php` (7 tests),
`tests/Feature/SuperadminWebTest.php` (6 tests), and `tests/Feature/
SuperadminCommandTest.php` (4 tests) cover: non-superadmin/
unauthenticated rejection (both surfaces), full-detail visibility
regardless of membership or report visibility, the audit log write,
writes still being blocked, `is_superadmin` immunity to mass
assignment, the shared-prop flag driving the sidebar link, and both
Artisan commands' confirmation flows. Verified live in a browser
(login → sidebar link → list → detail, including a DRAFT/PRIVATE report
rendering correctly) in addition to the automated suite.

## ADR-0019: Creating a second organization is KETUA-only; creating your first is open to anyone

**Status**: accepted (13 Sep 2026)

**Context**: `OrganizationPolicy::create` returned `true` unconditionally,
so any member could create organizations while already inside one. The
requested rule was "only Ketua can create a new organization", which
taken literally breaks signup: a freshly registered user holds no
membership, therefore no role, and could never create a first
organization — dead-ending mobile's Splash → Daftar → Buat Organisasi
path and looping the no-org gate in `(app)/_layout.tsx`.

**Decision**: split the two cases. A user with **no** memberships may
create their first organization (signup keeps working). A user who
already belongs to at least one may create another only if they are
KETUA somewhere. They become KETUA of whatever they create, unchanged.

**Consequences**: enforced in the policy, which both surfaces already
route through — the shared `StoreOrganizationRequest::authorize()`
covers web and API POSTs, and the web `organizations/create` page route
gained `->can('create', Organization::class)` so the form isn't
reachable by URL either. Both org-list endpoints now return `canCreate`
so neither client re-derives the rule; the web account-menu switcher and
mobile's `OrgSwitcherSheet` hide their "Buat organisasi baru" link
accordingly (hidden until the query answers, so it never flashes for
someone who can't use it).

## ADR-0020: Leaving an organization is a request the chair approves, not a unilateral act

**Status**: accepted (13 Sep 2026)

**Context**: the mobile Profil screen had shown a "Keluar dari
organisasi?" confirmation dialog since the design build, but its
`onConfirm` only closed the dialog — no endpoint existed on either
surface. So leaving was never implemented at all, and the decision of
*how* it should work was still open.

**Decision**: a member submits a `MembershipExitRequest`; the chair
approves or rejects it. The membership stays fully active until
approval — the member keeps seeing kas, kegiatan and tugas meanwhile.
Approval soft-deletes the membership, reusing the existing 30-day
"Keluar" retention window rather than inventing a second departure
mechanism alongside the chair's own "Keluarkan". The chair may not
submit a request: an organization is never left chairless, so the role
must be handed over first via the existing `TransferChairAction` — the
same rule that already blocks removing a chair.

**Consequences**: one pending request per membership, enforced by a
partial unique index (`WHERE status = 'PENDING'`) so a double-tapped
submit can't queue two decisions — a rejected member can still ask
again. Database notifications both ways: to the chair on request, to
the member on decision. Approval also clears the member's
`active_organization_id` if it pointed at the organization they just
left. Web renders the chair's pending queue on the member list and the
member's own "Keluar dari organisasi" button below it; mobile wires the
existing dialog to the real endpoint and shows "menunggu persetujuan"
while pending. **Mobile has no chair-side approval UI** — approving is
web-only for now, because a queue screen is not one of the 11 specified
mobile screens (`mobile/AGENTS.md`) and adding one needs a design
decision first.

**Not included**: auto-approval on a timeout. A member whose chair never
responds stays stuck by design for now; revisit if it happens in
practice.

## ADR-0021: Members join by a shareable, expiring, revocable link — accepted immediately, never by email

**Status**: accepted (13 Sep 2026)

**Context**: member onboarding was the last thing blocking a real
organization from using RukunMuda. `AddMemberAction` could only attach an
**already-registered** user by email, so a chair had to ask each person to
sign up first and then add them one at a time. "Undang Anggota" (mobile
screen 15) and the full 4-step Buat Organisasi flow had both been deferred
twice waiting on this decision.

**Decision**: the chair generates a join link and shares it wherever the
organization already talks — in practice a WhatsApp group. Opening the link
while signed in joins immediately as `ANGGOTA`.

Chosen over **email invitations**, which would add a hard dependency on
production email deliverability (only Mailpit exists locally) and assume
members read email — many of these users do not. Chosen over a **join code
plus chair approval**, which is more typing and adds a per-member approval
step that hurts most exactly when it matters most: onboarding twenty people
at once.

**Holding a valid link is the chair's authorization** — they chose who to
send it to, exactly as a WhatsApp group invite works. So there is no second
approval step. What makes that safe is that every link is **expiring**
(7 days by default) and **revocable**, and optionally use-limited; those are
what stop a forwarded link from being a permanent open door.

**Consequences**:
- An invite **always** grants `ANGGOTA` and never anything higher. A link
  that could confer `BENDAHARA` or `KETUA` would turn a forwarded WhatsApp
  message into privilege escalation. The chair promotes afterwards,
  deliberately, from the member list.
- Acceptance locks the invite row (`lockForUpdate`) inside the transaction, so
  two people tapping the same single-use link at once cannot both get through.
- An existing member re-opening a link is a no-op: it never re-grades their
  role down to `ANGGOTA` and never burns a use.
- Accepting sets the organization active, so the newcomer lands in the org
  they just joined.
- The accept route is a **web** route and deliberately sits outside the
  `current-org` middleware — a newcomer belongs to no organization yet, and
  the link must work in a browser for someone with no account and no app.
  Mobile therefore creates and shares links but does not accept them.
- Token is 40 random characters; it is the entire credential, so it is never
  derived from anything about the organization.
- Creating and revoking is `KETUA`-only, reusing the same gate as the member
  list rather than inventing a parallel rule.

**Not included**: per-invite role selection, single-use-by-default, and
invite analytics. Add them only if real use shows a need.

## ADR-0022: Email verification is required before an account can do anything

**Decided**: A user must verify their email address before reaching any
authenticated surface, web or API. `User` implements `MustVerifyEmail`;
every authenticated route group carries the `verified` middleware.

**Why**: An account here reaches an organization's member list, phone
numbers, and full financial history. Before this, anyone could register
with any address — including someone else's, or one that does not exist —
and immediately create an organization. The verification scaffolding was
already present and wired (routes, controllers, the `verify-email` page);
only the two lines that enforce it were missing, so this was an
unfinished feature rather than a new one.

**What it means**: The `verified` middleware is our own
(`App\Http\Middleware\EnsureEmailIsVerified`), aliased over Laravel's,
for two reasons. It redirects with `Redirect::guest()` so the requested
URL is stored as the session's intended destination — Laravel's own
version does not, which would drop a newcomer opening an invite link onto
the dashboard having never joined, the identical silent onboarding
failure as W-002 one screen later. And it answers API clients with JSON
(`403`, `code: email_unverified`) in Indonesian, because mobile has no
verification screen and that message is what the user actually reads.

**What we ruled out**: Gating the API entirely (mobile would trap a
newly registered user with no way forward) — so `auth/logout`,
`auth/sessions` and a new `auth/email/verification-notification` sit
outside the gate deliberately, since the user who needs them is exactly
the one who cannot pass it. Also ruled out: leaving mobile exempt.
A verification rule that one client can skip is not a rule.

**Still open**: mobile has no "check your email" screen. The API now
returns `user.emailVerified` on register and login so the client can show
one; building it needs a design decision (it is not among the 11
specified screens) and a native rebuild to verify.

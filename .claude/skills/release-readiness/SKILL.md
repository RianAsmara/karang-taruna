---
name: release-readiness
description: Use when judging whether RukunMuda is fit for real users — a go/no-go call, a pre-release audit, a "what's left before launch" question, or any claim that a feature is missing, unfinished, or done
---

# Release Readiness

## Overview

Judging this app's readiness means making claims about what exists. In this repo
that is exactly where readiness reviews go wrong.

**The docs are an append-only log, not a current-state snapshot.** `next-up.md`,
`backend-backlog.md` and the design notes carry dated entries. A 2026-08-26
entry saying *"Still not built: Attendance, activity points, vote creation"*
sits above a later entry that says all three shipped. Read the early one, trust
it, and you report four features as missing that a user can use today.

That is not hypothetical. It is the measured failure mode of this exact task.

## The Iron Law

```
EVERY CLAIM ABOUT WHAT EXISTS IS CHECKED AGAINST THE FILESYSTEM, NOT THE DOCS.
```

The docs tell you what to go look for. The code tells you what is true.

## Verify Before You Claim

Before writing that anything is missing, unfinished, or API-only, run the check:

| Claim | Check |
|---|---|
| "No X domain / no models" | `ls laravel/app/Models \| grep -i x`, `ls laravel/database/migrations \| grep -i x` |
| "No web page for X" | `ls laravel/resources/js/pages` |
| "No endpoint for X" | `grep -n "x" laravel/routes/api.php laravel/routes/web.php` |
| "X screen doesn't exist" | `ls mobile/src/screens mobile/src/app` |
| "No tests for X" | `ls laravel/tests/Feature laravel/tests/Feature/Api` |
| "X is a stub" | `docs/under-construction.md`, then read the file |
| "Tests/lint fail" or "pass" | Run them. Quote the output. |

A claim you did not check does not go in the report. Drop it or check it — those
are the only two options.

## What the Report Contains

In this order:

1. **Verdict** — one sentence. Ready, ready-for-pilot-with-conditions, or not
   ready. Commit to one.
2. **What is solid** — only things you verified.
3. **Blockers** — each with the evidence you found and what it would take.
4. **Known-and-deliberate** — gaps that are decisions, not oversights, with the
   ADR or doc that records them. Reporting a deliberate scope decision as a
   defect wastes the reader's time and damages the report's credibility.
5. **Not verified here** — everything you could not check in this session. Name
   it plainly rather than omitting it.

## Readiness Dimensions

Weight them for *this* product — a volunteer treasurer tracking real community
money:

- **Financial correctness** — integer IDR, immutable published reports, only
  `APPROVED` transactions in totals, `DB::transaction()` around multi-record
  operations. Highest weight. A wrong number destroys the product's only
  promise.
- **Tenant isolation** — any cross-organization leak is a launch blocker, full
  stop.
- **Authorization** — every role, plus an outsider and a chair of another
  organization.
- **Recoverability** — backups that have actually been restored, not documented
  commands. For financial records this is not optional.
- **Observability** — can you tell that something broke without a user
  reporting it?
- **Operational reality** — has it ever run anywhere but a laptop? Queue worker
  and scheduler supervised? `dues:remind-unpaid` is the only scheduled command
  and fails silently if unwired.
- **Onboarding path** — can a real organization actually get its members in?
- **Mobile device verification** — lint and types clean is not the same as
  working on a phone. Native changes need `npx expo run:android`.
- **Legal surface** — this stores members' personal and financial data.

## Distinguishing Kinds of Incomplete

- **Defect** — behaves wrongly. Goes in a bug tracker.
- **Gap** — not built, should be. Goes in `next-up.md`.
- **Deliberate scope** — not built, decided. Recorded in
  `docs/under-construction.md` or an ADR. Mobile Kas being read-only is this,
  not a bug.
- **Blocked** — waiting on a decision, not on effort. The invite architecture is
  this, and it blocks member onboarding.

Label each one. A reader who cannot tell a decision from an oversight cannot
act on your report.

## Common Mistakes

- Quoting a dated backlog line as current state. The single most likely error
  here.
- Reporting "no tests" without listing the test directory.
- Softening the verdict into "it depends". Give the call, then the conditions.
- Treating green CI as readiness. CI proves the code builds and its tests pass;
  it says nothing about whether the app has ever run in production, has
  backups, or can onboard a user.
- Omitting what you could not verify, which reads as verified.

Record the outcome per `documenting-changes` — a readiness review that changes
the picture belongs in `docs/next-up.md`.

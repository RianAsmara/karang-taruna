# Agents and skills for RukunMuda

Project-scoped configuration, checked into the repo so every session and every
contributor gets the same specialists and the same rules.

## Agents

Invoke with the Agent tool, `subagent_type: "<name>"`.

| Agent | Owns | Reach for it when |
| --- | --- | --- |
| `fullstack` | Laravel domain, Inertia web, Expo mobile | Building or changing a feature on any surface |
| `qa` | Pest coverage, tenant isolation, on-device testing | Before a release, after a feature lands, or a bug needs reproducing |
| `ux` | Design spec, Indonesian copy, accessibility | Designing or reviewing an interface; resolving a spec conflict |
| `pm` | Scope, backlog, open product decisions | Deciding what is next, or when a requirement is ambiguous |
| `devops` | CI, environments, queues, scheduler, deploy, backups | Infrastructure changes, environment debugging, releases |
| `devsecops` | Tenant isolation, authz, uploads, secrets, rate limits | Security review, hardening, before exposing anything publicly |

Each agent is grounded in this repo specifically — its real commands, real
invariants, and the traps that have already cost debugging time here. They are
not generic role descriptions.

## Skills

| Skill | Purpose |
| --- | --- |
| `documenting-changes` | Doc updates land with the code, not "next session" |
| `release-readiness` | Judging whether this app is actually fit for real users |

Both were written against observed failures, not from imagination — see below.

## How these were validated

Written TDD-style per `superpowers:writing-skills`: run the scenario against a
fresh agent **without** the skill, record what it actually does, write the skill
against those specific failures, then re-run to confirm the behavior changed.

`documenting-changes` baseline: two agents under realistic wrap-up pressure both
declined to write any docs, rationalizing with *"a rushed doc is worse than no
doc"*, *"the ADR is the first thing that feels optional under time pressure"*,
and *"I'll defer the doc debt explicitly, that's honest"*. Those exact
rationalizations are now countered by name in the skill. Re-run with the skill:
the agent writes the ADR and the `security.md` line before reporting, and names
the shortcut as a trap.

`release-readiness` baseline: an agent asked "is this production ready?"
produced a well-structured report containing four confidently false claims —
that vote creation had no endpoint, that activity points had no models, that
Attendance did not exist, and that four domains had no web pages. All four
had shipped in `47ba408`. The cause was trusting a dated `next-up.md` entry as
current state. The skill makes filesystem verification the precondition for any
existence claim, and `next-up.md` now carries a header warning at the block that
caused it.

If you edit a skill, re-run its scenario. An untested skill edit is an untested
change.

## Relationship to the other instruction files

- `CLAUDE.md` (root) — governs everything. Agents defer to it.
- `mobile/AGENTS.md`, `docs/design/README.md` — bind anyone touching mobile.
- `docs/` — the source of truth these agents read and are required to update.

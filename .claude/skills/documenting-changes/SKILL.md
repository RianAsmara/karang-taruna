---
name: documenting-changes
description: Use when finishing any change in this repo that adds a rule, endpoint, table, screen, or decision — before reporting the work complete, and especially when short on time, wrapping up a long session, or told to just ship it
---

# Documenting Changes

## Overview

In RukunMuda the docs are load-bearing. `docs/decisions.md`, `domain-model.md`,
`database.md`, `api.md`, `security.md` and `next-up.md` are how the next session
— and the next person — learns what is true. A rule that exists only in a policy
file is a rule nobody else knows about.

**The documentation is part of the change, not a step after it.** A change that
alters a rule and does not say so in docs is unfinished, not "done with docs
pending."

**Violating the letter of this rule is violating the spirit of it.**

## The Iron Law

```
THE DOC EDIT LANDS IN THE SAME CHANGE AS THE CODE. NOT THE NEXT SESSION.
```

Not a TODO. Not a note to the user. Not a backlog item saying "write the ADR."
Those are the deferral this skill exists to prevent.

## What Requires What

| You changed | You must update |
|---|---|
| A business/authorization rule | `docs/decisions.md` (new ADR) |
| A model, relationship, or field | `docs/domain-model.md` |
| A migration, constraint, or index | `docs/database.md` |
| An endpoint or its shape | `docs/api.md` |
| Anything about authn/authz, tenancy, uploads, money | `docs/security.md` |
| Closed or added a backlog item | `docs/next-up.md` |
| A stub the user can reach | `docs/under-construction.md` |
| Deploy, env, infra, backup | `docs/deployment.md` |
| A resolved bug | the matching `*-bug-tracker.md` |

Two or more may apply. Check each row against your diff.

## The Minimum Bar

"No time for a proper ADR" is not an exemption, because the bar is low.
A five-line ADR that states the decision, the reason, and what it rules out is
**complete** — length was never the requirement. Write that.

An ADR needs exactly: what was decided, why, what it means, what you chose not
to do. If you know why you wrote the code, you can write those four things in
under two minutes.

## Red Flags — STOP

- "I'll note it for the user and they can decide"
- "A rushed doc is worse than no doc"
- "This is bookkeeping, not really documentation"
- "It's small and self-explanatory"
- "I'll flag the doc debt explicitly, that's honest"
- "I'd do it if there were time"
- Reaching the end of a long session and feeling docs are the trimmable part

**All of these mean: write the doc edit now, before you report.**

## Rationalizations

| Excuse | Reality |
|--------|---------|
| "A wrong/hasty doc is worse than none" | A wrong doc gets corrected by the next reader. An undocumented rule is invisible until it breaks something. Wrong-but-present beats absent. |
| "Deferring explicitly is honest" | Honest and incomplete. Saying "I skipped it" does not transmit the decision to anyone who reads the repo next month. |
| "It's the demo that matters tonight" | The doc edit is two minutes. It is not what makes you late. |
| "Backlog edits are just bookkeeping" | `next-up.md` is the file the next session reads first. Leaving it stale is the most expensive omission here, not the cheapest. |
| "I'd write a bad ADR under this pressure" | Then write the four-line one. Decision, reason, consequence, non-goal. |
| "It's self-explanatory from the code" | It explains *what*, never *why*. ADRs exist for the why — the reader cannot recover it from a diff. |
| "I'll do it in the next session" | The next session does not know what you knew. This is how `backend-backlog.md` drifted for weeks. |

## Writing Well, Quickly

Say what changed and **why that choice over the obvious alternative**. The
reasoning is the part a reader cannot reconstruct.

Prefer: *"First org open to anyone, second is KETUA-only — a blanket chair-only
rule dead-ends signup, since a new user holds no role."*

Over: *"Updated OrganizationPolicy::create to check roles."*

When a screenshot or a spec table disagrees with what you built, say which won
and why. When you deliberately did **not** build something, record that and the
blocker — an absent feature with a documented reason is a decision; without one
it reads as an oversight.

## Verifying

Before reporting complete, state which doc files you touched. If the answer is
"none", name the row in the table above that your diff does not match. If you
cannot, you are not done.

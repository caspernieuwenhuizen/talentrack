---
title: Configuration — VCT
group: configuration
summary: Age profiles, workload ceilings and the rules the VCT planner applies.
audience: [admin]
views: [vct-config]
order: 120
---

# VCT configuration

The Configuration grid surfaces a single **VCT configuration** tile for
the Variabel Coachen-template (VCT) module, gated on the
`tt_vct_admin_library` capability so only the Head of Development (and
admins) see it. The tile opens the VCT configuration view
(`?tt_view=vct-config`); its own tab bar handles sub-navigation between
the three sections.

The tile carries a green **NEW** pill + accent border
(`.tt-cfg-tile--vct`) and a count line summarising how much is set up
("%d block templates · %d age bands").

## The three tabs

| Tab | What it does |
| --- | --- |
| **Macro-blocks** | The season periodization calendar — a list of dated blocks (build-up, in-season, taper, …) per season, optionally overridden per team. |
| **Age profiles** | Per age band (JO8 → JO19) the workload-cap, max intensity per MD-day, max training length. Drives the wizard's workload check and the engine's enforcement everywhere a VCT training is composed or saved. |
| **Team schedules** | Per-team weekly VCT training days for a season. Drives the wizard's date-default to the next configured weekday. |

Before #1546 there were two tiles (one each for macro-blocks and age
profiles) and the Team schedules tab had no tile at all. The single tile
makes all three reachable from one entry point.

## Picking a season and team

Season and team are now **dropdowns** — no raw ID typing.

- **Season** defaults to the academy's active season and **auto-loads on
 change**: pick a different season and the view reloads for it. There is
 no separate "Load" button (a no-script fallback button is shown only
 when JavaScript is off). Applies to the Macro-blocks and Team schedules
 tabs.
- **Team** (Macro-blocks tab) is a dropdown with a **Club default (all
 teams)** option at the top. The club default applies to every team;
 pick a specific team to edit an override just for them.

## Editing macro-blocks

The block set is edited with a structured editor — one row per block,
each with a **name**, a **start date** and an **end date** (native date
pickers). Rows can be added, removed and reordered (move up / down). The
editor flags overlaps, missing names and reversed dates inline as you
type.

Each block can carry an optional **weekly phase profile**. The common
case (name + dates) needs nothing more; for the advanced case an
expandable "Advanced: weekly phase profile (JSON)" section per block
accepts an array of `{ week, phase, multiplier }` objects.

Saving sends the whole block set to `PUT /vct/macro-blocks?season_id=N&team_id=M`,
which re-validates server-side: 1–12 blocks, contiguous sequence numbers
(1..N), valid `YYYY-MM-DD` dates, end on/after start, no overlapping
ranges. The shared `VctMacroBlockValidator` is the single source of
truth, used by both the REST endpoint and any other writer, so the
WordPress render and a future SaaS front end get identical answers.

The **Reference phase profiles** read-only table above the editor lists
the seeded template profiles for reference. Four ship: a three-week, a
four-week, a six-week (the default) and the five-week speelwijze cycle.

The three-week profile is introduce → load → unload. Three weeks leaves
no room for a peak week, so it is not a shortened six-week ladder — it
is a different shape, and the right one when the block it sits in is
short.

## Which age groups are modelled, and why not the youngest

Five profiles ship seeded: **U10, U11, U12, U13 and U14**. The training generator
only drafts for an age group that has one, because the profile is what supplies
the age-safe intensity ceiling and that is not something to guess at for
children.

The youngest groups are **deliberately** outside that range. Training load is not
planned in numbers at U7–U9; the coach shapes the training. The generator will
never draft for them, and it now says so as an answer rather than reporting a
missing profile — the old message sent coaches looking for a setting that does
not exist.

Everything above the range is a different story: it simply has no profile yet,
and you can add one. The line between the two is not a fixed list of age groups —
it is the youngest profile your club actually has. Add a U9 profile and U9 stops
being "below the range" from that moment on.

## Adding and removing an age profile

At the bottom of the **Age profiles** tab, **Add an age profile** offers every
age group that does not have one yet.

Nothing is pre-filled. These numbers decide how long and how hard children train,
so a plausible-looking suggestion would be worse than an empty field — it invites
agreement instead of a decision. The seeded profiles are on the same screen as
the shape to follow, and the maximum training length and intensity ceiling are
required.

Adding a profile also copies the **training shape** from the closest age group
that already has one — U15 inherits U14's blueprint, not U10's. That shape is a
starting point; the limits you just typed are what actually cap the training. Both
have to exist, which is why adding a profile alone would otherwise stop the
draft one step later with a different message.

**Removing** a profile is refused while a team is still in that age group: those
teams would quietly stop getting drafted trainings, and nobody would connect that
to a click on this screen weeks later. Move or archive the teams first. Trainings
already planned are never affected — a saved plan carries its own blocks, and the
profile is only read while drafting.

Both actions need the VCT configuration permission, which the head of development
holds. These are the ceilings that govern how hard minors are worked, so they are
not part of general administration.

## Editing age profiles and team schedules

Both tabs use polished `<details>` accordions — one per age band / team.
Each summary shows the key numbers (minutes + intensity band for an age
profile; the training days for a team). Forms inside use the shared
`tt-field` grid and stack to a single column at 360px. Each form saves
on its own (settings sub-forms; Save-only per CLAUDE.md §6 (a)).

## Per-team complement: VCT defaults panel

The central Team schedules tab edits every team in one season at once.
The **team-detail VCT panel** at the bottom of `?tt_view=teams&id=N`
edits one team in isolation (weekday chips, default start time +
duration). Both surfaces save via the same
`VctTeamSchedulesRepository::upsert()` and are read by the new-VCT
wizard's basis step. Same cap gate, same design tokens.

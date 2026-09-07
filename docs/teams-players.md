---
title: Teams & players
group: basics
summary: How to create teams, add players, and assign players to teams.
audience: [admin]
views: [players, players-import, teams, teammate, player-attributes]
module: TT\Modules\Teams\TeamsModule
capability: tt_view_players
order: 20
---

# Teams & players

## Teams

A **team** is a squad at a specific age group (e.g. "U13 Blue", "U15 Red"). Each team has:

- A name and optional age group label
- A **football form** — how many a side it plays
- A head coach (from your **People** roster)
- Assigned players

Create teams in the **Teams** admin page. The age group field matters because [category weights](eval-categories-weights.md) are defined per age group.

### Football form

Six-a-side, eight-a-side and eleven-a-side are different games, and TalentTrack needs to know which one a team plays — otherwise the [team blueprint](team-blueprint.md) offers a U9 squad a back four and a front three.

The **Football form** field on the team is set to *Follow the age group* by default, and the form it lands on comes from **Configuration → Football form**. That covers almost every team, so you normally never touch it. Set it explicitly for the exception: a club that runs its U13 at 8v8, or an U12 already at 11v11.

The form decides which formations that team's blueprint offers. A 6v6 team is shown six-player shapes and cannot be given an eleven-a-side one.

TalentTrack ships with 6v6, 8v8 and 11v11. If your federation plays 4v4, 7v7 or 9v9, add them under **Configuration → Lookups → Football forms** and they become available everywhere, including in the age-category defaults.

### The Teams list (v4.40.0 — #1614)

The Teams list (the frontend **Teams** view) shows your teams as a grid of clickable cards rather than a table. Each card carries:

- A coloured top accent and an initials crest, tinted by age band so a team always reads the same colour.
- The team name and a head-coach line ("No head coach yet" when none is assigned).
- A two-up stat strip: **Players** (current roster size) and **Upcoming** (activities scheduled in the next 14 days).

The whole card is a link — tap or click anywhere on it to open the team page. The cards stack to a single column on phones and flow into multiple columns on wider screens.

Above the grid you still have **search** and the **Age group** filter. The list shows active teams; archived ones stay reachable through the **⋯** button at the end of the filter row, which switches between Active and Archived. While you are looking at archived teams a chip beside that button says so, with a ✕ to go back. Sorting moves to a single **Sort by** dropdown (name, age group, or player count) since cards aren't columnar.

### The team page

Clicking a team — from the Teams list, a player profile, or anywhere a team name is linked — opens the team's own page. It shows:

- **Header**: team name, age-group pill, head coach.
- **Notes**, if any are set.
- **Roster**: read-only player list (jersey, foot). Each player links to their own page.
- **Staff**: the people assigned to the team via Functional Roles.
- **Edit team** button (top-right) — only visible if you have the team-edit capability. Click it to open the management form below.

### The team edit page at a glance

The Edit form is reached by the **Edit team** button on the team page (or the "Edit" row action in the Teams list for users with the cap). It shows three blocks:

1. **Team details** — name, age group, head coach, notes, custom fields.
2. **Staff Assignments** — the people working with this team (coaches, assistants, physio, etc.). Add/remove assignments here.
3. **Players on this team** — the current roster in a sortable table with jersey, positions, foot, date of birth. Each row links to the player's own page. A "Add player to this team" button is at the top.

The Edit page itself is only open to people who may edit teams. Opening its
URL without that permission answers *You do not have permission to edit
teams.* rather than showing the form.

#### Who the "Add player" dropdown offers

The dropdown lists players **on teams you coach** who are not already on
this team. Academy Admin, Head of Development and scouts — anyone who may
read every player in the academy — see the whole academy in it.

So a coach cannot pull a player across from a team they have nothing to do
with. Moving a child between age groups is an academy-admin act, and it is
still one dropdown away for the people who do it. If a player you expect is
missing, they are on someone else's team: ask an admin to move them, or use
the player's own page.

## Players

A **player** is an individual footballer. Each player has:

- First and last name
- Position(s), preferred foot, jersey number
- Height, weight, date of birth
- **Sex (for growth references)** — optional, and blank by default on every
  existing player. It is asked for one reason: age-adjusted height, weight and
  BMI are read against published growth curves, and those curves are separate
  for boys and girls, so without it the age-adjusted figures cannot be
  calculated. It is not a record of how a young person describes themselves and
  should not be used as one. Leaving it blank costs that player only the
  age-adjusted columns — height, weight and raw BMI still read normally.
- Optional link to a WordPress user account (so they can log in)
- Custom fields your academy has configured

Create players in the **Players** admin page. Use the **+ Add new** button.

## Linking player to WordPress user

When a player has a `wp_user_id` set, logging in as that user routes to their own dashboard view on the frontend shortcode. Without the link, the player exists only as a record you can evaluate.

## Archiving vs deleting

Archived players stay in the database but disappear from active lists (old evaluations still reference them). Permanent delete only works when no evaluations, goals, or sessions reference the player. Use **archive** in most cases — see [Bulk actions](bulk-actions.md).

### Archiving a team takes its activities with it (v4.x+)

When you archive a team that still has active activities, the confirmation dialog offers **"Also archive this team's N activities"**, ticked by default. Leave it ticked and the team's trainings and matches are archived in the same action, so a retired age group's sessions stop appearing on planners, dashboards and reports. Untick it to archive the team alone.

**Players are never archived by this.** A player outlives their team — they move up an age group, or transfer to another team the same week — so their record stays active and simply has no team until you assign one.

**Restoring the team brings those activities back**, but only the ones this cascade archived. If you had already archived an activity by hand before archiving the team, it stays archived: restoring a team never revives something you deliberately put away.

One-off on upgrade: teams that were archived *before* this shipped left their activities active. Upgrading sweeps those up once, so they stop cluttering live views. That sweep isn't reversed by restoring such a team — its activities were meant to be out of the way already; restore them individually if you need them back.

## Player case page

Player detail is a six-tab case page: Profile / Goals / Evaluations / Activities / PDP / Trials. Each tab shows up to 50 records (25 for activities, 10 for PDP/Trials), every record links through to its detail surface, and breadcrumbs replace the standalone back link.

## Player file UX (v3.92.6 — #0082)

The player file got a hero-card redesign and per-tab empty-state CTAs.

- **Hero card.** Photo (or initials placeholder when no photo is uploaded) sits next to a structured info block: team and age group, status pill, age-tier badge, days-in-academy + joined-on date, and up to three "latest record" chips that link straight to the most recent activity, evaluation, and goal. The chips are dropped when the corresponding record doesn't exist; the whole latest-row hides when there's nothing to show. Stacks at 360px width, side-by-side at 480px and up.
- **Empty-state CTAs.** When a tab has no records, instead of an italic "No goals recorded yet" line you now get a centred card: icon, headline, one-sentence explainer, primary action button. The button pre-fills the player and routes to the wizard variant where one exists (flat form otherwise). Read-only viewers (scout / parent / a player on their own file) see the headline + explainer but no button — the create action is suppressed because they don't have the cap. The Activities empty state explains that activities are recorded at the team level; the CTA is suppressed when the player has no team assigned and replaced with "Assign this player to a team first".
- **Tab count badges.** Each non-Profile tab shows a small badge with its record count (Goals 12, Evaluations 4, Activities 38, etc.). Tabs with zero records render in a muted colour so an operator can scan the row and pick the populated tabs without clicking through every empty one.
- **Profile tab two-column layout.** At ≥ 768px the Profile tab now splits into Identity (DOB, position, foot, jersey, height, weight, status) on the left and Academy (team, age tier, date joined) on the right. Single column on mobile.

## Player file UX redesign (v4.8.0 — #977)

The player file is rebuilt as a port of `.local-mockups/player-profile/index.html`. Backend unchanged — same `tt_players` row, same `tt_view_players` capability gate, same `?tt_view=players&id=N` URL — but the visual contract changes substantially.

- **Hero.** Paper background with a soft bottom shadow (no more blue gradient strip). The status signal moves to a 4px coloured border on the avatar — green for `active`, gold for `trial`, red for `released`, neutral grey for `inactive`. Jersey number renders as a small badge tucked into the avatar's bottom-right corner with a paper-coloured outline. Below: name + team link + status pill (carrying inline "X yrs in academy") + one pill per preferred position, shown as the abbreviation configured for the position with the full name on hover.
- **Action row.** `+ Log behaviour` (primary inverted) · `Set potential`, then on the right a pencil **Edit** icon and the `⋯` overflow holding **Archive** and (when the player has no team) **Assign to team**. Edit is icon-only and sits beside the `⋯` rather than taking a full-width button on the left — its name still reaches a screen reader and a mouse hover. Cap-gating unchanged: `tt_rate_player_behaviour`, `tt_set_player_potential`, `tt_edit_players`.
- **Key facts strip.** Three cards (DOB / Foot / Joined) each with a small hint (age, alternate position, years-in-academy). 3-up grid on mobile + tablet; reflows to a vertical 1-up stack on desktop where the strip moves into the left rail.
- **At-a-glance KPI strip.** Three KPI cards — Avg rating (with `▲`/`▼` trend arrow vs the rolling mean), Attendance % (over the last 30 days), Goals (active count with optional `N due soon` hint when any have a due date within 7 days). Each card is a link jumping to the relevant tab.
- **Tabs.** Pill chips replacing the underlined nav. Each tab carries a count badge from `PlayerFileCounts::for()` when the count is > 0. Mobile horizontally scrolls; tablet+ wraps to a single visible row. Notes tab disappears entirely for users without `tt_view_player_notes`. **Write notes as if the family will read them**: hidden from players and parents in the UI, notes remain disclosable in a GDPR subject-access request unless your DPO documents a legitimate-interest exclusion (see the privacy operator guide).
- **Profile tab.** Identity + Academy cards are preserved (same fields, now in card-with-kv-row chrome). Two new cards land: **Parents · Guardians** (surfaces linked `tt_player_parents` rows with name + primary flag + phone + email) and **Discovery** (surfaces the `tt_prospects` row promoted to this player, with scout + event + date). Empty states are friendly when no linked record exists.
- **Listing tabs.** Goals / Evaluations / Activities / PDP / Trials / Notes all switch to a unified card-row pattern: 44px date badge | title + meta | chevron or right-side chip. Date badges paint red-tinted for due-in-7-days goals and accent-blue for today's activities. Evaluations carry a colour-coded rating chip (green for ≥75% of scale, orange for <50%). Activities planned rows show a neutral "Planned" pill instead of the wizard's default-Present pre-fill. A match where the player scored or assisted carries small chips on the activity line — "2 goals scored", "1 assist" — reading the same goal log as the profile's **Goals scored** tile, so a coach scanning the season can tell the matches the player contributed in from the ones they did not. Rows with neither, and every training, show no chips.
- **Notes tab — the box keeps your draft.** A note is a post, not a field, so it is not sent until you press **Send**: nothing half-written reaches the staff. But a half-written note is still work, so the box remembers it. Close the tab, navigate away, come back later on the same device, and what you typed is still there. It is cleared the moment the note is actually sent, it never leaves your own browser, and a private window or a browser that blocks storage simply means no draft is kept — the box works exactly as before.
- **PDP tab.** Active cycle renders a 4-step progress bar (kickoff → mid-cycle → end-of-cycle → signoff). Past cycles render as a card-row list.
- **Player card tab.** The card showcase is a tab on the unified profile, so a coach, head of development or parent sees the at-a-glance standing without leaving the player's page: the skills radar, the FIFA-style player card, and the four rating KPIs (Latest, Last 5 with its momentum delta, All-time, Evaluations). Same audience as the rest of the page; no extra permission. Before the first rated evaluation the card shows a placeholder in place of the rating, the radar renders nothing rather than an empty frame, and the KPI row is hidden.
- **Three responsive shapes.** Mobile (≤719px) — single column, sticky horizontal tab scroll. Tablet (720-1023px) — single column at 720px max, tabs flow, Profile cards 2-up, 96px avatar. Desktop (≥1024px) — two-column grid: 320px left rail (Key facts + At a glance vertically) + flex right column (tabs + active pane). Hero + actions span both columns. The `.tt-player-detail__rail` and `.tt-player-detail__main` wrappers use `display: contents` below 1024px so column row heights stay independent on desktop.
- **What stays out.** There is no Analytics tab, and no inline archive or delete on an Evaluations row — destructive actions live on the evaluation detail page.

## Team detail — trial roster

The team detail page now shows current trial players under their own **Trial players** subsection. They were previously hidden behind the active-status filter on the team roster.

## Team detail — player-profile-style redesign (v4.40.0 — #1613)

The team page is rebuilt to mirror the [player profile](teams-players.md#player-file-ux-redesign-v480--977): same shapes, same card system, same responsive rail/main grid. Backend unchanged — same `tt_teams` row, same `tt_view_teams` gate, same `?tt_view=teams&id=N` URL.

- **Hero.** Paper background with the team crest (initials in an accent chip, status-coloured ring), name, a "Teams · age group" sub-line, and identity pills (status, age group, player count). The hero is **always shown** and cannot be hidden.
- **Action row.** New activity (primary) · Planner · Edit · Print seizoens-intakes · Customize · `⋯` overflow (Archive). Cap-gated: New activity → `tt_edit_activities`, Edit + Archive → `tt_edit_teams`, batch print → `tt_edit_goals`, Customize → coach-of-this-team.
- **Key facts strip.** Age group · Head coach · Players. 3-up on mobile, vertical in the left rail on desktop.
- **At-a-glance KPIs.** Upcoming (planned activities in the next 14 days, links to the planner) · Avg attendance (last 30 days) · Avg squad rating (mean across the roster's evaluations, shown on the academy's rating scale). The squad-rating tile is **only shown to users who may view evaluations** — an assistant trainer without evaluation-view rights sees just Upcoming and Attendance, not the team score. Numbers come from `TeamKpisRepository`, not the view.
- **Cards.** Roster, Staff, Team info, Trial roster (when present), Upcoming activities — each a card panel. **Every table row is now a whole-row link** (Roster → player, Staff → person, Upcoming activities → activity) — this fixes the long-standing "the upcoming activity table has no row click". The inner per-column link stays the keyboard / assistive-tech path; middle-click and cmd/ctrl-click open in a new tab.

### Customize — per-coach sections

A **Customize** button (visible only to coaches who manage the team) opens a panel of section toggles. The choice is **personal to that coach** and applies across **every team they coach** — it is not a club-wide setting and doesn't change what anyone else sees. The toggleable sections are: Key facts, At a glance, Roster, Staff, Team info, Trial roster, Upcoming activities. The hero always shows.

The preference is stored per user and read/written through `GET`/`PUT /wp-json/talenttrack/v1/me/preferences/team-detail`, so a future non-WordPress front end gets the same layout. Players, parents, admins, and coaches who haven't customised all see the default — every section on.

## Who can open a team

`tt_view_teams` says you may look at teams. It does not say **which** teams —
that is the team scope recorded against your account, and it is what the Teams
list has always used to decide which squads to show you.

Opening one now asks the same question. A coach who is not assigned to a team
gets *"You do not have access to this team"* on `?tt_view=teams&id=N`, on the
edit form behind it, and on the API — instead of the full roster, the trial
roster and the staff list. Before, the list hid a squad and the detail page
handed it over anyway to anyone who typed its id.

Unchanged for everyone who should see everything: an administrator, and any
persona with a **global** read on teams — Head of Development, Academy Admin,
Club Admin, Read-Only Observer — still opens every team. Archived teams still
open read-only for the coach who ran them; whether you coach a squad is a fact
about the assignment, not about whether the squad is still running.

If a coach reports a team page has stopped opening, check their team
assignments under **People → Functional roles**. That is where the scope comes
from.

## Edit cap path

The team-detail edit button and the Teams REST endpoints (list / get / create / delete) now consult `AuthorizationService::userCanOrMatrix` rather than `current_user_can`. This means a Head of Development granted `tt_edit_teams` via the matrix scope-row layer (functional role bridge) passes the gate too, matching the pattern already used by Tile gating and the Activities REST endpoints.

---
title: Reports
group: analytics
summary: The tile launcher — progress charts, team ratings, coach activity.
audience: [user, admin]
views: [reports, standard-report, report-wizard, attendance-leaderboard, attendance-report-player, attendance-report-team, minutes-report-team, minutes-audit, analytics, explore, scheduled-reports, podium]
module: TT\Modules\Reports\ReportsModule
capability: tt_view_reports
order: 10
---

# Reports

The **Reports** tile is a launcher for different ways of looking at your data. The reports are grouped by purpose so the right one is easy to find: **Development & performance** (ratings, progress, rate cards), **Playing time** (minutes played and squad load), **Attendance** (team and player attendance statistics and the leaderboard), **Recruitment** (scouting, prospects, trial funnel), **Staff & quality** (coach activity and evaluation quality), and **Season overview** (the annual review). Sections you don't have access to — recruitment and season-wide reports are academy-admin only — simply don't appear. All standard reports — including team attendance, player attendance, the leaderboard and minutes-per-team — live here and breadcrumb under **Reports**; they are no longer duplicated on the Analytics dashboard.

If **every** report is unavailable to you — all of them switched off for your academy, or none within your access — the launcher says so plainly instead of showing a blank grid, and points you to ask an administrator to enable a report or widen your scope.

## Player progress

Quick visual reports for coaches:

- **Player progress** — radar charts of your top players.
- **Player comparison** — pick two or more players and see their latest evaluations as overlapping radars.
- **Team averages** — per-team averages across the main categories.

For deeper per-player views, see [Rate cards](rate-cards.md) and [Player comparison](player-comparison.md).

## Team rating averages

A simple table — one row per team, one column per main category, plus an evaluation count. Shows the lifetime average across active evaluations on each team. Archived players and archived evaluations are left out.

A quick way to see which team is strongest this season.

## Coach activity

How many evaluations each coach has saved in the chosen window (last 7, 30, 90, 180 or 365 days). Useful for spotting a coach who has fallen behind, or for confirming that a planned assessment period actually happened.

Only coaches within your own club are counted — the report is scoped to the current tenant and never surfaces activity from another academy. A coach whose user account has been deleted still appears (their saved evaluations remain in the window) but is labelled **Unknown coach** rather than a raw account number.

## Coach · Evaluation quality

The head-of-development's evaluation spot-check as a report: one row per coach with their evaluation count, rating count, mean rating, standard deviation, the most-given rating (and what share of all their ratings sits at it), and the date of their last evaluation. Filterable by team and date range.

Rows where the standard deviation is below **0.5** across **10 or more ratings** are flagged *low variance* — the statistical signature of a coach rating everyone the same number. A coach with only a handful of ratings is never flagged; there's no meaningful variance to measure yet.

Restricted to academy-wide roles (head of development / admin): coaches cannot see each other's statistics. The **Export (CSV)** button downloads the same rows; integrations can read them from `GET /wp-json/talenttrack/v1/reports/coach-evaluation-quality` with the same permission gate.

## Frontend reports + Print/Save as PDF

Team rating averages and Coach activity now render natively on the public dashboard at `?tt_view=reports&type=team_ratings` and `?type=coach_activity` — no more wp-admin tab jump. Each report has a **Print / Save as PDF** button at the top: clicking it opens the browser's print dialog with a stylesheet that strips dashboard chrome, so picking "Save as PDF" produces a clean tabular PDF.

## Player · Progress & radar

The legacy wp-admin "Player Progress & Radar" report now renders natively on the dashboard as a standard report (Reports → *Player · Progress & radar*). Same three modes with the same data: **Player Progress** (each selected player's last five evaluations as stacked radar series — leave the selection empty for the top-10 active players), **Player Comparison** (each player's most recent evaluation overlaid on one radar; pick at least two), and **Team Averages** (one radar series per team, averaged per category).

Coaches see only their own teams' players and teams; academy-wide roles see everything. The old wp-admin route redirects here, so bookmarks keep working. Integrations can read the same datasets from `GET /wp-json/talenttrack/v1/reports/player-radar?mode=…&player_ids=…`.

## Only past, actually-held activities count

Both attendance reports — and the leaderboard and at-risk panel that share their query — only count activities that have **actually been held**: completed, in the past (session date today or earlier). An activity dated in the future never contributes to an attendance statistic, even if attendance was pre-filled on it. An activity dated **today** does count. This keeps each player's attendance figure truthful — a coach reviewing a profile sees only sessions the player could really have attended.

**"Completed" means the status on the activity's page.** An activity counts once its **Status** reads *Completed* — the same word the coach sees on screen and sets with **Mark completed**. A session still showing *Planned* contributes nothing, even when it is in the past and even when attendance has already been recorded on it. This is what makes the numbers checkable: if a figure looks too high, open the activity and read its status. The team and player attendance reports, the leaderboard, the at-risk panel, the daily attendance-flag notification, the team KPI tiles and the **Activities** badge on a player's file all read this one gate, so they cannot disagree.

Recording attendance is itself the statement that a session took place, so **saving attendance in the grid marks a past-dated planned activity completed** and the save bar says how many were flipped. A future-dated session is never completed this way — pre-recording a known absence for next week is not a claim that next week has happened. Marking an activity **Cancelled** is always respected and never overridden.

## Filtering the attendance reports — period pills + activity type

Both the team report and the player report carry the same filtering vocabulary as the activities list:

- **One period control** — *Last week*, *This month* (month-to-date), *This season*, and **Custom range…**. *Last week* and *This month* are retrospective (they look back to today). ***This season* spans the whole season** — from the season's start date through the season's own **end date** — so it covers the entire campaign, not just the part that has happened so far. Picking a preset sets the window for you. **Custom range…** opens the From / To dates inside the same control, and typing there wins over the preset. Because it is one control, what it says is always the window the report actually used: pick a preset and it names the preset, type dates and it names the dates.
- **Activity type** — narrow to one type (training / game / tournament, whatever your academy has configured). The type filter narrows every figure consistently: the KPI tiles, the table, the leaderboard and the at-risk panel.

**Default window.** When you open a report without picking a pill or typing a From/To range, it defaults to **the current season** — from the season's start date through today. This matches the *This season* pill and how the academy thinks about the year, rather than an arbitrary rolling window. If no season is configured, the report falls back to the last **90 days** so it always shows something. The Analytics team-minutes report follows the same default; the two standard minutes reports keep a rolling 12-month default instead — see *Filtering the minutes reports* below. Picking a pill or typing a manual From/To still overrides it. Because this default *is* the season window, both attendance reports now show the ***This season* pill highlighted** on first open — the filter bar reflects the window you're actually looking at, instead of reading "Custom range".

**Scope note.** When you only coach some teams, the attendance reports show just those teams. If your filters return nothing, the empty-state message says the report is **limited to the teams you coach**, so an empty window doesn't read as "the academy has no data".

**Expanding a team you cannot read says so.** On the team attendance report, each row expands into its per-player breakdown. If you are not allowed to read that team's players, the expansion now says **you do not have access to this team's players** — where it previously said there was no attendance in the window, which was a claim about the data rather than about your access, and could sit directly under a row reporting 92.9% present.

On a phone the filters collapse into a **Filters** button that opens a bottom sheet; from desktop width up they sit inline. Every control is keyboard-operable.

## Saving a filter set as a named view (v4.x+)

Your **saved views** sit in the filter bar itself, as chips at its right-hand end, on every standard report — the team, player and leaderboard attendance reports, and the minutes reports. Set the filters you keep coming back to (a team, an activity type, a period), click **Save current filters…**, give it a name like *"U17 league games"*, and it becomes a one-click chip you can re-apply any time. Saved views are **personal** — only you see yours — and each belongs to the one report you saved it on. A view remembers a period pill as a **relative** choice (*This season* stays "this season" next month too); a manual From/To range is remembered as the exact dates. Use the bookmark icon beside the chips to rename, replace or delete one. Saving, listing and deleting run over the REST API (`GET/POST /filter-presets`, `DELETE /filter-presets/{id}`), gated on the capability of the surface the view belongs to — `tt_view_analytics` for these reports. The retired `/reports/filter-presets` paths still answer for one release.

Saved views are no longer reports-only. They are part of the shared filter bar, and are also available on the six standard reports and on the players, teams, people, evaluations, goals, tournaments, holidays and activities lists plus the audit log. On a list a saved view also remembers the **search term and the sort order**, not just the filters — restoring a view that reset your sort would not be the view you saved. Each screen's saved views are gated on that screen's own permission, so a saved view can never surface a screen you could not otherwise open. The attendance and minutes entry grids and the custom-fields settings screen deliberately do not offer them.
Your **saved views** sit in the filter bar itself, as chips at its right-hand end, on every standard report — the team, player and leaderboard attendance reports, and the minutes reports. Set the filters you keep coming back to (a team, an activity type, a period), click **Save current filters…**, give it a name like *"U17 league games"*, and it becomes a one-click chip you can re-apply any time. Saved views are **personal** — only you see yours — and each belongs to the one report you saved it on. A view remembers a period pill as a **relative** choice (*This season* stays "this season" next month too); a manual From/To range is remembered as the exact dates. The bookmark icon beside the chips opens a menu; **Rename, replace or delete this view** opens a small dialog where you can: rename the view, tick **Also replace its filters with the ones set now** to point it at your current filters, or delete it. Renaming keeps the filters; replacing the filters keeps the name. Saving a name you have already used on the same screen is refused — the same name on a different screen, or used by someone else, is fine. Saving, listing and deleting run over the REST API (`GET/POST /filter-presets`, `DELETE /filter-presets/{id}`), gated on the capability of the surface the view belongs to — `tt_view_analytics` for these reports. The retired `/reports/filter-presets` paths still answer for one release. Saved views are no longer reports-only: they are part of the shared filter bar, so any view built on it can offer them.
Your **saved views** sit in the filter bar itself, as chips at its right-hand end, on every standard report — the team, player and leaderboard attendance reports, and the minutes reports. Set the filters you keep coming back to (a team, an activity type, a period), click **Save current filters…**, give it a name like *"U17 league games"*, and it becomes a one-click chip you can re-apply any time. Saved views are **personal** — only you see yours — and each belongs to the one report you saved it on. A view remembers a period pill as a **relative** choice (*This season* stays "this season" next month too); a manual From/To range is remembered as the exact dates. **Open one by default.** In that dialog, tick **Open this view by default on this screen** and that view is applied whenever you open the screen without filters of your own — one click becomes none. One default per screen per person; marking a new one releases the old. The default chip is marked with a star, and the address bar shows the filters that were applied, so the page stays bookmarkable and shareable. Your default never overrides a deliberate choice: a link that already carries filters, a **← Back to** pill, or a shared URL all show exactly what the address asks for. Use **Clear** in the filter bar to see everything unfiltered — that escapes the default for the visit rather than bouncing back into it. Currently available on the attendance and minutes reports.

The bookmark icon beside the chips opens a menu; **Rename, replace or delete this view** opens a small dialog where you can: rename the view, tick **Also replace its filters with the ones set now** to point it at your current filters, or delete it. Renaming keeps the filters; replacing the filters keeps the name. Saving a name you have already used on the same screen is refused — the same name on a different screen, or used by someone else, is fine. Saving, listing and deleting run over the REST API (`GET/POST /filter-presets`, `DELETE /filter-presets/{id}`), gated on the capability of the surface the view belongs to — `tt_view_analytics` for these reports. The retired `/reports/filter-presets` paths still answer for one release. Saved views are no longer reports-only: they are part of the shared filter bar, so any view built on it can offer them.

## Reading the player attendance table

**Present carries its percentage and the fraction behind it** — *33,3% (1/3)*.
Over three activities a percentage on its own is noise; the fraction says what
actually happened without any arithmetic.

**Late, Absent, Excused and Injured show the count**, not a percentage. Two
missed sessions read as **2**. The columns sort numerically, so 2 comes before
10.

## Drilling into the activities behind a number

The **Activities** count and the at-risk badge both open the activities list,
narrowed to exactly what the number was counted from: that player, that date
window, that team — **and the activity type the report is filtered to**. Filter
the report to *Training* and the drill-down shows trainings, not the team's
whole calendar.

The list says so when you arrive. Above the filter bar a **Showing only:** strip
names the player and the date window, each with an **×** that clears that one
constraint and leaves the rest standing. Team and type appear in the filter bar
itself, where you can already see and change them.

## Drilling into a team's players (team report)

On the team report each team row is **tap-to-expand**: tapping the team name opens an inline sub-table of that team's players (player · present %, with at-risk players marked), loaded on demand for the active window and filters. Tapping again collapses it; one team is open at a time. Without JavaScript, a **View players** link beside each team opens the player report pre-filtered to that team instead — the drill-down is always reachable.

## Minutes played — totals and per-match trace

The minutes reports read only **recorded** match minutes: actual, non-guest
attendance. Planned (expected) roster rows and guest appearances never count,
and a match with no recorded minutes contributes nothing — the reports never
estimate, calculate, or construct minutes, so a zero is an honest "no data
recorded" rather than a guess. Minutes are worked out once, when a played
match is recorded (the match execution is finalised, or minutes are entered by
hand on the attendance screen), and stored on the attendance row. Every report
simply reads that stored value; a match that was played but never finalised
therefore shows nothing until its minutes are recorded, rather than being
reconstructed from the planned line-up.

**Only matches that have been played appear.** A match counts once any one of
three things is true: its activity is marked **completed**, its **date has
passed**, or it already carries **recorded minutes**. So a fixture kicking off
this evening is absent from the per-match table and from every KPI beside it —
it is not played until you complete it, record its minutes, or the day ends —
while a match you played this morning and completed on the touchline counts
straight away.

The date on its own still counts because completing an activity is a separate,
deliberate act: a match played last season says *planned* unless somebody
pressed the button, and it was still played.

**Matches, games and tournaments all count.** The minutes reports treat
matches, games and tournaments the same way — each is a minutes-bearing
activity. A single-game tournament records minutes exactly like a match (plan a
line-up, run the live match surface, finalise); a multi-game-day tournament
records minutes with the by-hand per-player entry on the attendance screen (how
many minutes each player actually played across the day). Either way the minutes
land on the attendance row and every report reads them. A tournament with no
recorded minutes still shows nothing — the same honest zero as a match.

### Goals and assists beside the minutes

**Team · Minutes distribution** carries **Goals scored** and **Assists**
columns next to the minutes. Minutes say how much of the game a player got;
goals and assists say what they did with it, and reading the two off
different screens is what stops anyone comparing them.

Both are counted from the goals logged on the match-execution screen, over
the same window as the rest of the table. A player who contributed nothing
shows a zero, not a blank — a blank would read as "not measured".

Three rules are worth knowing, because they decide whose number moves:

- A goal nobody attributed counts toward the **score** but toward **no
  player**. It belongs to the match until someone fills in the scorer.
- An **own goal** never adds to the scorer's goals. Putting one into your
  own net is not an attacking contribution.
- An **undone** goal counts for nobody.

The same figures appear on the player's own profile — the **Goals scored**
tile in the at-a-glance strip, with assists on the line beneath. Note the
wording: the tile beside it, plain **Goals**, counts a player's development
goals. The two senses are kept apart everywhere they meet.

### Filtering the minutes reports

**Player · Minutes played** and **Team · Minutes distribution** carry the same
filter bar as the attendance reports — period quick-pills (*Last week*, *This
month*, *This season*) plus a manual **From / To** range that overrides the pill.
Every figure on the page reads the window you pick: the KPI tiles, the per-match
table or player bars, each player's drill-down, and the **Explorer →** link.

**Default window: the last 12 months.** Unlike the attendance reports, the two
minutes reports default to a rolling **12 months** rather than the current
season — that is the window they have always used, and keeping it means no
number changes just because the filter bar arrived. The no-pill option is
labelled *Last 12 months* to say so. Pick a pill or type a range to narrow it;
**Reset** returns to the 12-month default. The report's sub-line always names
the dates the numbers actually cover.

### What "matches" means on the Team · Minutes distribution report

The *Matches recorded* tile counts the matches that **produced recorded
minutes** — the same matches the player bars below it are built from, so the
tile can never contradict them. Underneath it sits the honest denominator:
how many matches were **played** in the window. When the two differ the tile
is flagged: *"3 played matches have no minutes"* tells you the gap is a
recording gap, not an empty season.

**A fixture kicking off tonight is not played yet.** It belongs to neither
number until you complete it, record its minutes, or the day ends — otherwise
the report warns you that tonight's match is missing its minutes. Any one of
the three is enough, which is what lets a match played this morning count
before the day is out, and what keeps a season of matches nobody ever pressed
*Complete* on in the denominator where they belong.

Fixtures that are **archived, moved to the recycle bin, cancelled, or not yet
played** count towards neither number. A season's fixture list imported in
advance therefore reads as *"0 of 0 played matches recorded"* until the first
match is played, and *"0 of 19"* once nineteen have been played but none
recorded — instead of the misleading "19 matches" beside an empty squad that
earlier versions showed.

**Players who have left count in neither number either.** Minutes recorded
against a player who has since been archived are excluded from the *Matches
recorded* tile, exactly as that player is absent from the squad below it. The
tile and the rows describe one squad; a count the rows cannot explain is worse
than a smaller one.

Use the **Minutes audit** report (below) to see exactly which players are
missing minutes on which game.

### The relative figure: Minutes share

These two reports answer in absolutes, and one number they cannot give you is
what share of what was actually on offer each player got. **Team · Minutes
share** does: every played match's own length summed into a denominator, each
player's recorded minutes over it, and a configurable target — 30% by default —
that flags anyone below it. It is documented in full in
[Minutes share](minutes-share.md).

**Starts (basisplaatsen) count only recorded matches.** A player's *starts* and
the *% available* figure count only matches that were actually recorded (that
produced stored minutes) — a match that was planned, with a line-up, but never
played or recorded contributes nothing to either. Starts can therefore never
exceed matches. For a multi-game-day tournament the line-up-derived "starts" are
approximate (one line-up covers several games), so the recorded *minutes* are
the meaningful figure there, not the start count.

Every player's minutes total is a **drill-down**: open it to see the per-match
rows that sum to it — date, match, type, source (`actual` recorded minutes) and
minutes. The breakdown reconciles
exactly with the total, so you can always trace a reported number back to its
source rows. On the Team · Minutes distribution report each player bar expands;
on the Analytics minutes report each Total opens the per-match table beneath the
row. Both work on a phone and by keyboard; without JavaScript the per-match rows
stay visible inline.

Integrations can read the same trace — gated on `tt_view_reports` with the same
team-scope narrowing as the report:

- `GET /wp-json/talenttrack/v1/teams/{team_id}/players/{player_id}/minutes?from=…&to=…` — the per-match minutes rows for one player and the reconciling `total_minutes`.

To verify a total against the raw stored rows, the `tt_attendance` minutes rows
(`minutes_played`, `record_type`, `is_guest`, `activity_id`) are browsable in
the **Data Browser**.

The per-match breakdown table is now a **single shared component** across the
Team · Minutes distribution report and the Analytics minutes-played report, so
the two never drift and both reconcile to the player's total the same way.

## Minutes audit — games × players auditability matrix

The **Minutes audit** report (reachable from the Reports launcher under *Playing
time*, or directly at `?tt_view=minutes-audit`) is the auditability companion to
the minutes report. It answers a different question: *for each game, which squad
players have recorded minutes and which do not?* — so an admin or head coach can
spot and chase the gaps before a season's minutes data goes stale.

The overview is read-only. Each row carries an *Edit* / *Record* link — shown
only to users who can edit recorded minutes — that opens the **per-match minutes
editor** (see below). Users without that permission still see the full matrix,
just without the edit link.

The surface is a spreadsheet-style matrix:

- **Rows** are the team's game, match and tournament activities in the window
 (the same set the minutes report counts).
- **Columns** are the squad — every player who appears on the **attendance** of
 those games. The squad is resolved from attendance, not from a player's team
 assignment, so a player who was borrowed for one game still shows up, and a
 player who left the team but played earlier in the window is not silently
 dropped.
- **Cells** show the minutes recorded for that player in that game. A green cell
 is minutes recorded; a red **0** is a player who was in the squad but has no
 minutes recorded (a gap to chase); a hatched dash is a player who was not in
 that game's squad.
- Each row carries a **row total**, a completeness **status chip** — *Complete*
 (every squad player has minutes), *Incomplete* (some do, some don't), or *Not
 recorded* (nothing recorded for the game) — and the bottom **column-total** row
 sums each player's minutes across the visible games.

Above the matrix, four **gap KPIs** — *Games*, *Fully recorded*, *Incomplete*,
*Not recorded* — summarise the window. Each KPI is clickable and filters the
matrix to that completeness bucket, so *Not recorded* jumps straight to the games
still missing minutes.

Because the audit reads the **same** recorded, actual, non-guest minutes as the
minutes report, its numbers reconcile with that report exactly. The honest-zero
rules apply here too: a team with games but no recorded minutes shows every game,
honest *Not recorded* chips, and a clear next-action note — never a misleading
"0 players" empty state. An empty window (no games at all) says so distinctly.

Coaches see only the teams they coach; academy-wide roles see the whole club. The
filter bar carries the shared team / period / match-type / date-range controls
and defaults to the current-season window.

### Per-match minutes editor

The *Edit* / *Record* link on a matrix row opens the **per-match minutes editor**
(`?tt_view=minutes-audit&match_id=N`). It lists the game's squad and lets an
admin or head coach correct the **minutes recorded per player**, writing the
authoritative recorded value — the same source the reports and the match-execution
screen read. A match opened in match-execution *after* an audit edit reflects the
changed numbers.

The editor writes through the right path automatically:

- For a match that was prepped and run on the sideline (it has a match-execution),
 a saved figure sets an **explicit per-player override**. The override lives
 alongside the value the sub-log derives, so it survives every later recompute
 and stays correctable even once the match is finalized.
- For a paper match (no match-execution), a saved figure writes the player's
 recorded minutes directly.

Editing is conservative with match data: an emptied field is an *explicit* clear
(the override or recorded value is cleared, falling back to any derived figure),
never a silent zero, and a row you do not touch is never written. Each row shows
the derived figure next to the editable value, so an override is always visible.

The editor is gated on the same edit permission the write endpoints enforce, so
the link never appears for a user who could not save. Save and Cancel are both
present; Cancel returns to the overview.

**Scope of this first version:** the editor edits the **total minutes per player**
— the value the recorded data actually stores. Editing the starting line-up per
half and the substitution log (with the minute each happened) is a planned
follow-up; those live on the match-execution screen today.

## Standard reports — honest numbers

Every standard report now names the window and the source it drew from, so a
figure is never a silent guess:

- **Honest empty states.** When a report has nothing to show it says *why* in
 plain terms — "No matches recorded in this period", "No evaluations recorded
 for this team in this window", "No prospects logged in this window" — instead
 of the old generic "adjust a filter" copy (most of these reports have no
 filter to adjust). The Season summary no longer renders a blank page below
 its headline tiles when no teams exist.
- **Player · Minutes played** covers the **last 12 months** (stated in the
 page sub-line, matching the Explorer drill), and when a player has more than
 50 matches in that window it says *"Showing the 50 most recent matches"* so a
 longer history is never dropped without notice.
- **Team · Squad evaluation summary** shows a **Last evaluated** date per
 player, so a stale row is visible at a glance.
- **Team · Match analysis trends** counts how often each phase of play was
 rated *Went well*, *Mixed* or *Needs work* across the period. It counts and
 never averages — three ordered words are not a score — a phase a coach left
 unrated is excluded rather than treated as neutral, and below three rated
 matches it says *"not enough matches yet"* instead of drawing a trend through
 one data point. See [Match analysis](match-analysis.md) for the per-player
 half of the same data.
- **Season summary** per-team match counts ignore soft-archived activities on
 the join itself (not just in the count), removing a source of inflated joins.

### Trial funnel reconciliation

The Season · Trial funnel now **reconciles**. The Per-decision table lists the
outcomes of cases *opened in the window*, plus a **Pending (not yet decided)**
row and a **Total** row that sums to *Trial cases opened*. The **Decision rate**
tile carries a one-line note that its numerator (cases decided, by decision
date) and denominator (cases opened, by open date) use different windows, so
the percentage isn't misread as a same-cohort rate. Each scout name in the Per
scout table links to that scout's **Scout report card** (gated on
`tt_view_reports`, the same capability the card enforces).

### Minutes-played (team) — shared filter + KPI chrome

The Minutes-played (team) report now uses the **shared filter bar** (team,
retrospective period pills — Last week / This month / This season — a match-type
select and a manual From/To range) and the **shared KPI strip**, matching the
attendance reports. The default window is the current season. On a phone the
filters collapse into the standard bottom-sheet; every control keeps a 48px
touch target.

### Standard reports — shared filter bar, squad fix, drill-downs

Four of the standard reports now carry the **same shared filter bar** the
attendance and minutes reports use: retrospective **period pills** (Last week /
This month / This season) plus a manual **From / To** range. The default window
is the **current season** (season start → today; a 90-day rolling window when no
season is configured). Picking a period pill drops any manual range; typing a
From / To overrides the pill. The reports affected are **Team · Squad
evaluation summary**, **Season summary**, **Season · Trial funnel** and
**Scout report card**. On a phone the filters collapse into the standard
bottom-sheet; every control keeps a 48px touch target. Each report's page
sub-line and its Explorer drill now name the same window, so the figure and the
drill agree.

The **Team · Minutes distribution** report had a squad-resolution bug: it counted
matches from the team's activities but built the player list from
`tt_players.team_id`, so a team whose players had no `team_id` set showed
"18 matches, 0 players". The squad is now derived the **same way the rest of
analytics resolves a team** — players with recorded attendance on the team's
match / game / tournament activities — so the player list and the match count
share one definition, and a player appears even with **0 recorded minutes**.
Minutes still come only from persisted `record_type='actual'` attendance rows
(they are never estimated), so a match with no recorded minutes contributes 0.

Standard-report **KPIs are now drill-downs** where a filtered list reconciles to
the count: the Team · Minutes distribution *Players* tile opens the team roster
and its *Matches* tile opens the activities list filtered to that team's matches;
the Season summary *Active players / Active teams / Matches* tiles open their
lists; the Trial funnel *Prospects logged* tile opens the prospects list. Every
drill carries a **← Back to …** hint and is hidden when the viewer lacks the
destination's capability (§7 hide-don't-tease).

## Player attendance — ranking + at-risk flags

The player attendance report defaults to **worst attendance first** (lowest present %), so the players who need attention surface at the top. It lists **every player** with recorded attendance in the window — no top-N cap — and every column stays sortable (click a header to re-sort).

Players who have **missed** a configurable number of activities in the window (absent / excused / injured) are **flagged**: an inline ⚠ badge with the missed count, a tinted row, and an **At-risk players** panel above the table listing them worst-first. The threshold (default **3**) is the *single source of truth* shared with the daily attendance-flag notification, so the report and the nudge email always agree.

The ⚠ badge (and each name in the **At-risk players** panel) is a **link** — tap it to trace the flag to the sessions behind it. It opens the same player-scoped activities list the *Activities* count uses (this player, the report's team, the report's window), so you can see the dated sessions the player attended and reconcile the missed count. A **← Back** link returns to the report.

### Tracing the activity count (drill-down)

Each player's **Activities** count is a link. Open it to see the actual sessions behind the number: the activities list opens filtered to that player, the report's team, and the report's date window, showing only activities the player has a recorded attendance row for. From there each activity opens its detail with the recorded attendance status, so a coach can reconcile the count with the source rows — the same tracing the minutes report offers. A **← Back** link returns to the report.

### Setting the at-risk threshold

The threshold lives in **Configuration → General → Attendance at-risk threshold** (an academy-admin setting). One number, between 1 and 50, drives every at-risk flag: the player attendance report, the attendance leaderboard, and the daily attendance-flag notification all read it. Lower it to catch slips earlier; raise it if your academy only wants to act on persistent absence.

## Attendance leaderboard

A dedicated league table reachable from the Reports launcher (*Attendance leaderboard*). It ranks players over the chosen window into two side-by-side tables: **Needs attention** (the lowest attendance %, where at-risk players keep their ⚠ badge) and **Most reliable** (the highest attendance %). By default it shows **all** players in the window; type a number in *How many* to narrow each table to that many rows. Optionally narrow to a single team. Coaches see only their own teams; academy-wide roles see the club.

It shares the same filter bar and chrome as the player attendance report: a **team** picker, retrospective **period** pills (last week / month / season and so on), an **activity type** filter, and a manual **date range** that overrides the active period, plus the leaderboard-only *How many* cap. Opening it with no filters defaults to the **current season** window. Above the tables a KPI strip summarises the ranked players — total players, average attendance across them, and how many are at-risk — computed from the same data, so it never triggers an extra query.

On a phone the two tables stack into one column with no horizontal scroll; from tablet width up they sit side-by-side. Every column is sortable on top of the default ranking.

Integrations can read the same data — with the same `tt_view_analytics` gate and team-scope narrowing — from:

- `GET /wp-json/talenttrack/v1/reports/attendance-leaderboard?from=…&to=…&n=…&team_id=…&activity_type_key=…` — `{ top, bottom, total }`.
- `GET /wp-json/talenttrack/v1/reports/attendance-at-risk?from=…&to=…&team_id=…&activity_type_key=…` — flagged players worst-first, each with a `declining` trend marker, plus the active `threshold`.
- `GET /wp-json/talenttrack/v1/reports/attendance?from=…&to=…&team_id=…&activity_type_key=…` — the per-player attendance rows for one window (powers the team report's inline drill-down): `{ players, threshold }`.

The optional `activity_type_key` on every attendance endpoint narrows to one activity type, matching the report UI's Type filter.

## Dimension explorer — row cap and filter validation

The dimension explorer (any KPI's *Explore* affordance) lets you filter a metric's underlying fact rows and drill into them. Three safeguards keep the drill-down honest:

- **5000-row cap, now visible.** The explorer reads at most **5000** fact rows for a drill-down. When a filtered set hits that ceiling the table shows a **"Capped at 5000 rows — use grouping to aggregate larger sets."** notice under the pager, so the visible page count is never mistaken for the whole dataset. Group by a dimension to aggregate larger sets instead of paging through raw rows.
- **Filters validated against the KPI's dimensions.** Only the dimensions a KPI actually offers for exploration are accepted as filters. A `filter_<key>` for a dimension the KPI doesn't declare is silently ignored — it never reaches the query or the CSV/PDF export, so the filters you see on screen always match the filters applied to the exported file.
- **Date bounds accept an absolute or a relative form.** *Date after* and *Date before* take either an exact date (`2026-01-15`) or a relative offset (`-30 days`, `-12 months`, `+7 days`; the unit may be `day`, `week`, `month` or `year`, singular or plural). A relative bound is resolved against today each time the report is opened, so a saved link keeps meaning "the last 30 days" rather than freezing to the day you saved it. A bound that is neither — a typo like `30 dayz ago`, or an impossible date like `2026-02-30` — is **dropped**, and the report is shown without that bound. It is never guessed at: a filter that silently narrows to the wrong window is harder to spot than one that plainly isn't applied.

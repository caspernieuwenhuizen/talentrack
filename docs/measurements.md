---
title: Measurements & testing
group: performance
summary: Recorded test values per player over time, and the trends they make.
audience: [user, admin]
views: [measurements, measurements-entry, measurements-coverage, measurement-tests, test-results, test-trends, player-bmi]
module: TT\Modules\Measurements\MeasurementsModule
order: 80
---

# Measurements & Testing

A **measurement** is one recorded value of a test for one player on a date —
a sprint time, a height, a jump, a bleep-test level. Measurements give a
player's physical and athletic development a chronological, comparable
record alongside their evaluations and goals.

This page describes the foundation: the data model and who can see what.
The setup wizard, result-entry screens, and the per-player trend view roll
out on top of it.

## The pieces

- **Test (definition)** — a thing you measure (e.g. "Sprint 30m", "Height").
 Each test belongs to a **category** and has a **value type**, a **unit**,
 a **recurrence**, and a **direction** (is higher or lower better?).
- **Category** — the grouping a test sits under. Seeded with
 *Anthropometric*, *Physical*, *Technical*, and *Mental*; an admin can edit
 the list.
- **Unit** — the unit of measure, and what kind of quantity it is. Seeded with
 real units (s, min, ms, m, cm, mm, km, kg, g, reps, bpm, %, level); a test
 picks one **or** supplies its own custom unit.
- **Recurrence** — how often the test should run: annually, twice a year,
 quarterly, monthly, or ad hoc. This powers "who's due".
- **Session** — a planned testing moment for one team: one test, one date.
 Staff enter one value per player against it.
- **Target** — a per-age-group band (green / amber) for a test. A recorded
 value is flagged green, amber, or red against the band for the player's
 age group, respecting the test's direction. The band is what a player
 should *reach*, so beating it counts as green: on a lower-is-better test a
 time faster than the green band is green, and on a higher-is-better test a
 value above it is green. You never enter a red threshold — red is simply
 everything past amber on the worse side. A **neutral** test is the one case
 where both edges bound, because the value is meant to land inside a range
 rather than get as far past it as possible.
- **Status levels** — for the **status** value type only: an operator-defined,
 ordered set of coloured levels (e.g. *At risk* red, *Watch* amber, *On
 track* green). A status test records a level per player rather than a
 number, and the player's latest level shows as a coloured chip on their
 profile.

## Units carry a dimension

A unit is not a label printed after a number. Every unit in the list belongs to
a **dimension** — time, length, mass, count, rate, percentage or level — and
knows how it relates to that dimension's base unit: seconds, metres, kilograms.

That has three consequences worth knowing when you set a test up.

- **Values are stored in the base unit and shown in yours.** A height recorded
 as `182 cm` and one recorded as `1.82 m` are the same stored number. Each
 test displays its own unit, so the screens read the way the people using them
 measure.
- **A result remembers what it was entered in.** Change a test's unit later and
 the readings already recorded keep their meaning: they were stored against a
 dimension, not against the caption the test had that day.
- **A custom unit has no dimension.** Type your own unit (say `watt/kg`) and
 the value is stored exactly as entered, never converted and never compared
 across units. That is the trade for being able to measure anything you like.

### Times as mm:ss

A test measured in **minutes or hours gets mm:ss by default** — choosing a unit
like that is already the statement that its readings are times. A result is
typed as `5:30` and reads back as `5:30`, its target band is written the same
way, and it is stored in seconds so trends, averages and target flags all work
on the real quantity.

Tests measured in **seconds or milliseconds stay decimal**, because a 10m sprint
is `2.05 s`, not `0:02.05`.

**Enter and show as mm:ss** sits directly under the unit picker and overrides
the default in either direction. Untick it on a minutes test to enter decimal
minutes; tick it on any other time unit to enter mm:ss.

Without mm:ss, a time behaves like any other number: `5.5` on a test measured in
minutes is five and a half minutes. Entering `5:30` in a field that is not set
to mm:ss is refused rather than guessed at.

A single reading is capped at four hours. That covers a match, a Cooper test and
any circuit, and it is what catches a typo like `1130` on a decimal minutes test
— which would otherwise have been stored as nearly nineteen hours.

## Status tests (a manual player status)

A **status** test is a simple, manually maintained, dated player status — a
stopgap until the computed player-status signal is rich enough to maintain
directly. It rides the measurement framework, so it gets dated history and
profile surfacing for free.

- Choose **A status (coloured levels)** as the value type when creating the
 test. The wizard then lands you on the test's edit screen.
- On the edit screen, define the **status levels** from lowest to highest:
 each level has a label and a colour picked from a curated palette (green,
 lime, yellow, amber, orange, red, cyan, blue, grey). Clear a level's label
 to remove it;
 the row order is the saved order.
- Record a status the same way as any other test — *Record measurements*
 shows a coloured **status picker** per player instead of a number field:
 a dropdown whose closed control and every open option show the level’s
 colour square next to its label, sized so the longest label never clips.
 The picker is fully keyboard- and touch-operable (open with Enter/Space or
 the arrow keys, move with ↑/↓, type-ahead, Escape to close); with
 JavaScript off it falls back to a plain native dropdown.
- On the player profile, the latest level appears as a coloured chip in the
 **Measurements** tab, painted in that level's colour. Status tests have no
 green/amber target band — their colour comes entirely from the picked
 level.

Every status change is a dated entry on the player record, so the player's
status history is queryable and visible over time. A seeded **Player status**
category is available to group these tests.

## Who can see what

Visibility follows the authorization matrix — no role is hard-coded:

| Persona | Sees |
| --- | --- |
| **Player** | Only their own measurements and trend. |
| **Parent** | Only their own child's measurements. |
| **Assistant / head coach, team manager** | Their team's results and sessions. |
| **Head of development, academy admin** | Every team's results, academy-wide. |

Coaches enter and edit results for their own team. The test catalogue
(definitions and targets) is set up by the head of development or an academy
admin. An academy admin or head of development can change any value.

## Recurrence values

| Value | Meaning |
| --- | --- |
| `annual` | Once a season |
| `biannual` | Twice a season |
| `quarterly` | Four times a season |
| `monthly` | Monthly |
| `adhoc` | No fixed cadence |

## Viewing a player's measurements

Players and parents get a **My measurements** tile that opens the
*Metingen* view: every test grouped by category, each showing its latest
value, a green/amber/red flag against the player's age-group target, a
small trend line, and how often it runs. A parent sees their child's view.

Staff see the same thing **in context** on the player's profile: open a
player and switch to the **Measurements** tab (beside Evaluations). The
tab badge counts how many tests the player has results for.

### The full history behind a test

The small trend line answers "which way is this going?" at a glance. For
the rest, every test with more than one result carries a **Show history**
link that opens the readable version underneath it. What appears there
depends on the kind of test, because a trend only means something in the
terms of the test it belongs to:

| Kind of test | What the history shows |
| --- | --- |
| A number where **higher or lower is better** (sprint time, jump height) | A dated chart with the value axis, every reading labelled, and the **age-group target shaded** so you can see when the player crossed into it. |
| A number with **no better or worse** (height, weight, shoe size) | The **readings per date, in columns** — no chart, no target, no verdict. See below. |
| A **status** test (levels such as *On track* / *Watch*) | One block per recorded date in that level's own colour. No line: levels are named states, not distances, so joining them with a slope would invent precision the data does not have. |
| **Passed / not passed** | A tick or cross per date plus the tally (*3 of 4*). |
| Any test with **one result** | A sentence saying so. A chart drawn around a single point reads as missing data rather than as a starting position. |

On a chart where **lower is better**, an improving line goes *down*. That
is stated in words under every such chart — the slope alone is not allowed
to carry it, because a falling line reads as decline to anyone who has not
been told which way is good.

### Tests with no better or worse

Height, weight and shoe size are measured and tracked, but a higher value
is not a better one. These tests are grouped together per category and
shown as **values per date in columns**, with a plain **Change** column at
the end (`+6`).

They get no chart, no target band and no ranking on purpose. A rising line
would imply progress, a shaded band would imply a norm, and a
"most improved" list would imply the tallest player is performing best —
all three are untrue. A missed measuring moment shows as `—`, never as a
zero, and the change is worked out over the dates that do have a reading.

The player's **At a glance** panel also carries a **Measurements** signal
beside Avg rating, Attendance and Goals: the number of tests the player
currently has a value for, with a hint of how many sit *below target*
(amber or red against the age-group band) — or *on target* when none do.
It links straight into the Measurements tab for the full per-test
timeline. The signal only shows for viewers who can read measurements, so
the standing never leaks to a role that can't open the underlying tests.

## Recording results

Staff get a **Record measurements** tile. Pick a team, a test, and a date,
then enter one value per player and **Save all** — it saves the whole
roster in one go (blank players are skipped) and ties the values to a
testing session for that date. Numeric tests show a number field with the
unit; pass/fail tests show a dropdown. A coach can only record for their
own teams; the head of development and academy admin can record for any
team.

### Height and weight also update the player's profile

A player's profile carries a height and a weight, and they used to be
whatever somebody typed when the player was first entered. For a growing
13-year-old that goes out of date within months, and nothing on the
screen said so.

Now, whenever you record a height or a weight, the profile follows it.
The moment a new reading is the player's most recent one, the profile
shows that number instead.

Both appear on the **Identity** card of a player's Profile tab, each with
the date it was measured — "172 cm · measured 18 Aug". A figure someone
typed on the player form rather than measured shows without a date,
because there isn't one. A player with neither gets no row at all.

A few details worth knowing:

- **The test has to be recognised.** For height, name it `Lengte`,
  `Height`, `Length` or `Stature`; for weight, `Gewicht`, `Weight` or
  `Mass`. The academy owns its own test names, so this is matched on the
  name rather than on a fixed test. Capitals and spacing don't matter —
  but a name with extra words in it, like `Lengte (cm)`, won't be
  recognised.
- **The two are independent.** Recording a weight re-checks only the
  weight; it has no bearing on which height is the most recent.
- **The most recent reading wins, not the last one you typed.** If you
  correct a measurement from last January, the profile stays on the
  newer one. If you move an old reading forward so it becomes the most
  recent, the profile follows it there.
- **Deleting the last reading does not blank the profile.** The value
  sitting there may predate your testing altogether, and an old number
  is more use than none.
- **A clearly mistyped reading is not copied across.** A height has to
  be between 50 and 250 cm and a weight between 10 and 200 kg to reach a
  profile. The reading itself is still recorded as you entered it.
- **You can still edit both on the player form.** It is the right thing
  to do for an academy that does not run testing sessions — but a
  recorded measurement will take over as soon as there is one.
- **Readings you already had were caught up on upgrade.** This only
  started following the measurements partway through, so a one-off pass
  brought every existing profile in line with the readings behind it.
  Nothing was blanked: a player with no usable reading kept the value
  that was already there.

The BMI report does not use the profile height, and deliberately so — a
BMI needs the height that was true on the day of the weigh-in, not the
latest one. See [BMI-for-age](#bmi-for-age).

## Testing coverage (who's due)

Staff also get a **Testing coverage** tile. Pick a team and the screen
shows, for every test that has a recurrence, how many of the squad are
**up to date** versus the gap — and names the players who are **overdue**,
**due soon**, or have **never** been tested. It's player-centric: it starts
from the roster and surfaces exactly who still needs a test this cycle, so
you can plan a session. Tests with no recurrence (*ad hoc*) don't count
toward coverage. A coach sees their own teams; the head of development and
academy admin see every team. The same data is available over REST at
`GET /wp-json/talenttrack/v1/teams/{team_id}/measurement-coverage`.

## Creating a test

The head of development (or an academy admin) creates tests with the
**Add test** — the *New test* wizard — reachable from the *Record measurements* screen.
It walks through three steps:

1. **Details** — the category, a name, and the value type (a number, a
 scale score, pass/fail, or a status with coloured levels).
2. **Unit & recurrence** — the unit (from the unit list or a custom one),
 whether higher or lower is better, and how often the test runs.
3. **Targets** — optional per-age-group green and amber bands; a recorded
 value flags against the band for the player's age group. You can leave
 these blank and add them later.

Finishing creates the test and its targets in one go.

## Managing the test catalogue

The head of development (or an academy admin) gets a **Manage tests** tile
under *Configuration*. It opens a list of every test your academy has set
up — name, category, unit, direction and cadence — with its **Active** or
**Inactive** state, and three actions per row:

- **Edit** — open the test in a flat form. You can change the name,
 category, value type, unit (from the list or a custom one), scale bounds,
 direction, cadence, the active toggle, and whether the test's results
 **show on the player profile**, and edit the per-age-group green/amber
 target bands inline. **Save** commits; **Cancel** takes you back to the
 list (or to wherever you came from). Pass/fail tests have no target bands.
- **Show on the player profile** — a per-test checkbox (on by default). Clear
 it to keep a test out of the player-profile measurements view while it
 still records results and appears in the results browser, reports and
 exports. Useful for internal or experimental tests you don't want to show
 players and parents yet. Every existing test stays visible after the
 upgrade.
- **Activate / Deactivate** — an inactive test stays in the catalogue and
 keeps its history, but is hidden from the *Record measurements* picker so
 staff can't log new results against it.
- **Export Excel** — downloads every recorded result for this test as a
 formatted `.xlsx` workbook (see below).
- **Archive** — soft-deletes the test into the recycle bin. Nothing is
 lost; an admin can restore it.

### Exporting a test's results

Each test row — and the test's edit view — carries an **Export Excel**
action. It produces a formatted workbook for that one test: a header block
(test name, unit or *status*, date range and club) over a frozen, bold
column-header row, then one row per recorded result with the **player,
team, recorded date, value, age group and recorded-by**. Results are
grouped per player so a player's longitudinal series reads together.

For a **status** test the value column shows the recorded **level label**
(e.g. *On track*), and the cell is filled with that level's colour so the
sheet reads at a glance the same way the player-profile chip does. Numeric
tests show the number with its unit.

The workbook has a second **Trends** sheet that shows each player's results
**over time**: one row per player, one column per recorded date (in
chronological order), with the recorded value in each cell. A **line chart**
below the table plots every player as a series over the shared date axis, so
you can see who is improving, plateauing or declining at a glance. Numeric
and scale-score tests are charted; a **status** test lists each player's
recorded level per date for reference but has no chart (there is no numeric
axis to plot).

The export reuses the central export pipeline and is gated on the same
`measurements` *read* permission as the rest of the module — only staff who
may see a test's results may export them. The workbook carries exactly the
rows that person can see on screen: a team-scoped coach gets their own teams'
players, and choosing a team they do not coach is refused rather than
quietly widened.

Creating a test still runs through the *New test* wizard, opened with **Add test**, reachable
from the top of this list as well as from *Record measurements*. The same
catalogue is available over REST at
`/wp-json/talenttrack/v1/measurement-definitions` for integrations and the
SaaS front end.

## Browsing results — the Test results surface

The **Test results** tile (in the **Analysis** group of the dashboard)
opens a browser for reading every recorded result in one place, organised
per player. It answers "how is each player doing on this test, right now?"
without opening profiles one by one.

1. **Pick a test.** Until you choose one, the grid prompts you to. The
 picker lists every test in the catalogue, grouped by category.
2. **Optionally narrow** by **team**, **age group**, and a **date range**
 (from / to). The filters re-run the grid when you press *Show*.
3. **Read the grid.** One row per player who has a value for the test,
 showing their **latest value in the window**:
 - **Status tests** show the level's **colour chip and label** (e.g. a
 green *On track*), the same colours the player-profile chip uses.
 - **Numeric and scale tests** show the **value with its unit**, a small
 **trend arrow** (▲ improved, ▼ declined, ▬ unchanged) versus that
 player's previous result, and a **flag** — green *on target*, amber
 *below target*, red *well below target* — against their age-group
 band.

The grid is **sortable** (tap a column header on tablet and desktop) and
every **player name links to their profile**, arriving with a back-pill so
one click returns you to the browser. An **Export Excel** button
downloads the current test (honouring the team and date filters) through
the same formatted workbook the *Manage tests* export produces.

Team-scoped staff (coaches who hold *read* on their own teams only) see
results for their teams only; academy-wide readers see everyone. A coach
with no team assignments sees nothing here, not the academy. The same rows
are available over REST at
`/wp-json/talenttrack/v1/measurement-results?definition_id=…` (filters:
`team_id`, `age_group`, `from`, `to`), gated on the same `measurements`
*read* permission and narrowed to the same teams as the screen — omitting
`team_id` does not widen the answer, and naming a team outside your scope
returns `403`. For integrations and the SaaS front end.

## Test trends — one test, every player, over the season

*Test results* answers "how is each player doing on this test **right
now**". **Test trends** (Analysis group) answers the other half: **who is
developing and who is stalling**. It is the Excel export's *Trends* sheet
brought on screen.

Pick a test, optionally narrow by team and a date window, and press
**Show**. What you get depends on the test — the same rule the player
timeline follows, because a trend only means something in the terms of its
own test:

- **A test with a direction** (sprint time, jump height) leads with the
 numbers: a table of each player's value on every measuring moment and
 their **change**, then **Most improved** and **Fallen back**, and last a
 **chart** with one line per player over the shared date axis, plus a
 heavier dashed **squad-average** line so the aggregate never reads as
 another player.
- **A test with no direction** (height, weight) gets the readings per date —
 no chart and no ranking, because there is no better or worse to rank. The
 change is still shown, with a grey ▲ or ▼ saying only which way the value
 moved.

**Finding your player in the chart.** Every player has their own colour, and
the same colour appears as a short line in front of their name in the table
and in the two ranking lists — that line is the legend. A squad larger than
ten runs past the ten colours a reader can reliably tell apart, so the
eleventh player takes the first colour again **dashed**, the twenty-first
takes it **dotted**. Colour and pattern together mean a full squad stays
readable, on screen, in a black-and-white print, and for a colour-blind
reader.

**Step by step, and overall.** Between each pair of dates sits a **Δ** column
carrying the move from the previous measuring moment; the last column,
**Total**, spans every moment the player has. With two measuring moments the
two agree. With three or more they stop agreeing, and that is the point: a
player who gained 2 kg and lost 1,5 kg has the same total as one who gained
0,5 steadily, and only the steps tell them apart. Where a reading is missing on
either side of a pair the step shows `—`; it is never stretched across the gap
to the reading before it.

**Reading the change column.** Every change — each step and the total — carries
an indicator, not just a colour, so the report stays readable in black and
white and for a colour-blind reader:

| Indicator | Meaning |
| --- | --- |
| green ▲ | improved, in the terms of this test |
| red ▼ | fallen back |
| grey ▬ | unchanged (less than 2% either way) |
| grey ▲ / ▼ | the value rose or fell on a test with no better or worse |
| — | no earlier reading to compare against |

The arrow follows the **verdict**, never the sign of the number. On a test
where lower is better, a change of −0,08 s is progress and shows a green ▲.
Hovering the indicator (or reading it with a screen reader) gives the word.

Player names in every table and in the two ranking lists link through to the
player's record, and show a summary card on hover.
- **A status test** gets a player × date matrix of levels in their own
 colours. No lines: levels are named states, not distances.
- **Pass / fail** gets a tick or cross per date, each player's tally, and
 the **pass rate per round** — the only figure that says something over
 time without treating two outcomes as a scale.

**The change is read in the direction of the test.** On a test where lower
is better, −0,08 s is an improvement: it shows green, it reads *improved*,
and it ranks under *Most improved*. A player whose change is smaller than
**2%** counts as *about the same* and appears in neither ranking — a
one-percent move on a hand-timed sprint is inside the noise, and calling it
progress would overstate what was measured.

Every player name links to their profile with a back-pill. A team-scoped
coach sees only their own teams, and a link to another team's data is
refused rather than quietly widened. Integrations read the same numbers
from `GET /wp-json/talenttrack/v1/reports/test-trends?definition_id=…`.

An administrator can hide the report under **Settings → Features → Test
trends**; with it off, the tile disappears and a direct link is rejected.

## Moving between the surfaces

**Tests & measurements** has four staff surfaces — *Manage tests* (set up
the catalogue), *Record measurements* (enter results), *Testing coverage*
(review who's due), and *Test results* (browse everyone's results) — and
the management surfaces cross-link so you don't have to return to the
dashboard:

- *Record measurements* shows a **Manage tests** link beside **+ New test**,
 so you can jump to editing a test's cadence or bands and come straight
 back.
- *Manage tests* shows **Record measurements** and **Testing coverage**
 links at the top of the list.
- *Testing coverage* shows a **Manage tests** link (only for staff who can
 edit the catalogue).

Each link carries a contextual back-pill on arrival, so the destination
offers a one-click route back to where you came from.

## BMI-for-age

**Player · BMI-for-age** reads the height and weight you already record and
places them on a published growth curve. It lives under **Reports**.

The latest figure also appears at the top of a player's **Measurements** tab,
as the number and its percentile alone. Everything that explains a BMI — which
curve it is measured against, how far apart the two readings were, and how the
figure has moved — lives on the report, which is the screen you open when BMI
is what you came to read. The tab is a glance in passing.

BMI on its own says very little about a young player. The same figure that is
unremarkable for a sixteen-year-old can be high for an eleven-year-old, which is
why every number here is shown as a **percentile** for that player's age and sex
rather than on its own. A percentile answers the only question worth asking:
where does this player sit compared with others of the same age?

Growth data is among the more personal things the system holds about a child,
so both halves of the report stay inside your team scope: the roster shows the
teams you coach, and opening one player's curve is refused for a player outside
them — including by a hand-typed link. An academy-wide measurements grant still
sees every player.

### What you need first

Your test catalogue needs a **height** test and a **weight** test. Any of the
usual names work — *Height*, *Lengte*, *Weight*, *Gewicht* — because the report
matches on the test name, not on a fixed identifier. If either is missing, the
report says so instead of showing an empty grid.

A player also needs a **date of birth** and a **sex** on their record. Without a
date of birth there is no age, and without a sex there is no curve, so the report
shows the BMI and leaves the percentile blank rather than guessing.

### How a BMI gets built

A weight is paired with the nearest height recorded **within 30 days**. Outside
that window no BMI is calculated, because a height taken two months earlier
describes a different body on a growing child. On the report, every figure lists
how many days apart the two readings were, so you can judge it yourself.

Players with no usable pair still appear in the table, with the reason. Knowing
who you have no data for is usually the first thing to act on.

### What the report will not do

It will not tell you a player is overweight or underweight. There are no red
rows, no warning colours and no thresholds. It reports a position on a curve and
how that position has moved since the last measurement — the **Change** column
shows the shift in standard deviations, which is the figure that matters over a
season.

Reading a growth curve clinically is a job for someone qualified to do it. These
are children, and a screen that labels one in front of whoever is standing behind
the laptop is not something this system does.

### The reference

Percentiles use the **WHO 2007 growth reference for 5–19 years**, which is named
on the report so you always know which curve you are reading. It covers ages 5
to 19 inclusive; a player outside that range shows a BMI with no percentile —
on the player's tab too, which says so in as many words rather than leaving the
figure looking complete.

The reference is pluggable: if your academy needs a different one, it can be
swapped without changing anything else about the report.

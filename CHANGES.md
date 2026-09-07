# TalentTrack v4.115.5 — The match sheet is one landscape page again (#3297)

The exported match-prep PDF was readable but ran to two pages — a touchline
sheet you have to turn over is a sheet you lose. It is one page now, laid out
for paper rather than for the screen: selection with the roles underneath it
on the left, the two half-pitches and the match goals down the middle, and
Doen per speler filling the right-hand column.

That block now always reserves fifteen rows — the players who already carry a
note, then ruled lines to the count — so the room you write in during the
warm-up is there whether or not you filled it in beforehand. Empty goal boxes
print as writing lines too, and a full 22-player squad still fits the page.

The Print match sheet route prints the same layout, so the page you print and
the PDF you export are finally the same document.

# TalentTrack v4.115.5 — You can save a view you built from the dropdowns (#3327)

Narrowing a list with its Team, Position or Archive dropdowns gave you no way
to save that view — the bookmark control simply was not there. Typing
something into the search box made it appear, which made the whole feature
look intermittent.

The control now appears whenever anything is filtered, however you filtered
it, and a list you open on a saved view says so again.

# TalentTrack v4.115.4 — Saved views moved into the filter bar (#3296)

Saved views used to sit in their own band above the filter bar. On a phone that
meant a list opened with a saved-views strip, then a filter bar, and only then
your rows — two rows of chrome before any content.

They are now part of the bar itself. Your views appear as chips at its
right-hand end and still apply in one tap.

**It tells you which view you are on.** The view matching the filters on screen
is highlighted; before, only the starred default was marked, so applying any
other view left nothing on screen to say so.

**Saving lives behind a bookmark icon** beside the chips, along with rename,
replace, set-as-default and delete. The icon only appears when there is
something to do with it — on an untouched list, for someone who has never saved
a view, it is not there at all.

**Several views no longer crowd the bar.** Three chips show on a wide screen
and one on a phone; the rest collapse behind a **+N** you can tap to see them
all.

Saving now offers a **Cancel** beside it, so opening the name field by mistake
is one tap to back out of.

# TalentTrack v4.115.3 — Filter chips now name the team, the type and the toggles you set (#3318)

The filter bar's summary chips — the ones that say what a list is filtered
by, each with a ✕ to take it off — reached only text boxes and the one-tap
pill groups. A team, a position, an age group, a severity or any on/off
switch produced no chip at all, so on most screens the bar still could not
tell you why you were seeing the rows you were seeing.

Every filter type now chips, and each ✕ removes just that one filter and
leaves the rest of the list as it was.

# TalentTrack v4.115.3 — The ⋯ menu sits at the far right of the filter bar again (#3319)

On a wide screen the archive menu had drifted into the middle of the bar,
with the active-filter chips and Clear sitting to its right. It now ends the
bar, where a utility control belongs: filters first, then what you have
applied, then the pills and the ⋯ hard against the right edge.

The order no longer depends on how a screen happens to declare its filters
either — the ⋯ is last on every list that has one.

# TalentTrack v4.115.3 — The alerts inbox says which filters are actually on (#3320)

The inbox showed a "State: Open" chip the moment you opened it, even though
Open is simply the state the list starts in and nothing was filtered — and
its ✕ led back to the state you were already on. Meanwhile the Area and
Severity you had actually picked showed no chip at all, and the little
number on the Filters button counted something different again.

The chips now name exactly the filters you set, the number on the button is
the number of chips, and an inbox you have not filtered says so.

# TalentTrack v4.115.2 — Applying a date or text filter on a phone now works (#3288)

On a phone, a date range or free-text filter set inside the **Filters** sheet
was thrown away. You picked From and To, tapped **Apply**, the sheet closed and
the list was exactly as before — with nothing to say the filter had not been
applied.

**Apply** now applies. The button was closing the sheet and nothing else.

Dropdowns and toggles were never affected, which is what made this hard to
spot: set a team as well as a date range and the page reloads, so the dates
look like they worked too.

This affected the grids and reports that carry a From/To or a search box —
attendance and minutes grids, the attendance and minutes reports, standard
reports, the audit log, comparison and the message log.

# TalentTrack v4.115.2 — Clear and the applied-filter chips now show on desktop too (#3289)

On a wide screen the filter bar offered no way to clear filters and no
readback of what was applied. Set a team, a position and an age group and the
only way back was to walk through every control by hand — and for the period
pills and the archive menu there is often no "none" option to walk back to.

Both were rendered by every surface all along; they were only ever visible
below 1024px.

The bar now ends with the active-filter chips and a **Clear** button, on every
screen size. Clicking Clear returns the unfiltered list, exactly as it does on
a phone. The **Filters** button and its count stay mobile-only, since desktop
shows the controls inline; the sheet keeps its own Clear where a phone user
expects it.

# TalentTrack v4.115.2 — The filter bar's utility controls sit where they were meant to (#3291)

On a desktop filter bar the `⋯` menu and the status pills are meant to sit hard
right, set apart from the filters proper. They sat mid-row, flush against
whichever control happened to precede them, with empty space to their right —
so the `⋯` read as one more filter instead of a utility.

The rule that was supposed to push them right had never matched anything, on
any screen, since it was written.

They now right-align as intended. On a bar carrying both the status pills and
the `⋯`, the two stay together as one cluster rather than being pushed apart.

# TalentTrack v4.115.2 — Filter chips say what is applied — and can be taken off (#3292)

The chips beside **Filters** were the only place the bar named which filters
were applied, and they were hidden from screen readers entirely: the
announcement was "Filters, 3", with no way to learn what the three were. They
could not be tapped off either, so dropping one filter meant opening the sheet
and hunting for the control that set it — and for the period pills and the
archive menu there is often no "none" option to go back to.

Each chip now names its filter and carries a ✕ that removes only that one,
leaving the rest in place. The chips are readable by screen readers and
reachable by keyboard, and the count on the **Filters** button is the number of
chips, so the two can no longer disagree.

The alerts inbox and the player comparison, which never showed chips at all,
get them.

# TalentTrack v4.115.2 — The period pill stops contradicting the report (#3293)

On the grids and reports that offer both a period pill and a From/To range,
setting your own dates re-ran the report on them — but the pill went on
showing "This month". The chrome said one thing and the numbers were another,
with nothing to tell you which.

Set your own window now and no pill claims to describe it.

Related: on seven of those surfaces a manual range was not counted at all, so
setting only From/To left **Filters** with no badge and no chip — the bar
reporting "nothing filtered" over a filtered report. The window now shows as a
chip like any other filter.

Clearing your dates returns to the season default with the season pill active,
as before.

# TalentTrack v4.115.2 — The filter sheet behaves like the modal it claims to be (#3294)

On a phone, tabbing through the **Filters** sheet walked straight out of it and
into the list behind — which is covered by the overlay, so the focus ring was
invisible and you ended up operating a page you could not see. A screen reader
could wander into the same covered content, and dragging on the dimmed area
scrolled the list underneath instead of doing nothing.

The sheet now uses the browser's own modal dialog, the same one the confirm
prompts and the saved-views dialog already use. Focus stays inside it until you
close it, the page behind is properly hidden from assistive technology and no
longer scrolls, Escape closes it, and tapping the dimmed area closes it as
before.

# TalentTrack v4.115.2 — PDP file detail: three chrome fixes (#3299)

**One help icon, not two.** The summary card carried its own `?` button next to
the Status pill, opening the same drawer on the same topic as the header's help
icon. It is gone; the header icon is unchanged.

**The Template link looks like a link in this app.** It was a bare anchor, so a
theme's own styling applied — blue and underlined, next to the status badges in
the same row. It now matches the record links in every other table.

**The conversations list reads on a phone.** Below 640px the table becomes
stacked cards, with each value labelled by its column. This table carried no
labels, so a card read "1 / Begin seizoen / 2026-09-30 / Gepland / Nog niet"
with nothing saying which was which. Each value is now named.

# TalentTrack v4.115.2 — PDP files archive like everything else, instead of being deleted outright (#3300)

A PDP file's detail page carried a red **Permanently delete PDP** button — on a
live, in-progress development file for a minor, as the loudest element on a
page whose actual purpose is recording a verdict. It was the only record type
in the plugin that worked this way.

The button is gone. A PDP file now archives like a player, a team or a goal,
and permanent deletion lives where it does for everything else: in the recycle
bin, once the file is already out of the way, behind the recycle-bin
permission.

The underlying reason it was different: PDP had never been registered with the
recycle bin at all, so an archived file appeared nowhere, could not be
restored, and was never picked up by the retention cleanup. It is registered
now — listed under **PDP file**, and named by player and season so you can tell
whose file you are looking at before you act on it.

Permanently deleting a PDP file still removes its conversations, verdicts,
uploaded files and goal-evidence links.

# TalentTrack v4.115.2 — Record links look like links, and keyboard focus is visible again (#3308)

Player names and other record links across the app were styled to look exactly
like body text until you hovered them — which on a phone means never. Twelve
names in a PDP block read as a paragraph rather than as the list of people you
can open.

They now carry a little more weight at rest, so they read as tappable without
turning every name in the product a link colour.

Tabbing to one shows a proper focus ring again. The old rule removed the
outline and offered a barely-visible shadow instead, which is not a focus
indicator.

Hovering tints the name rather than lifting it on a shadow. The lift was a card
treatment applied to a single word: it nudged the text off its baseline, so a
table rippled as the pointer crossed it, and the shadow hugged the letters.
Nothing moves now, and the tint follows your theme instead of being fixed blue.

# TalentTrack v4.115.1 — The match prep PDF is readable, and says which match it is (#3272)

The exported sheet printed at roughly 5pt with most of the paper left blank,
and nowhere on it said who was playing whom.

Three things changed.

**It prints landscape and fits the page.** The grid is a wide, three-column
sheet that was being squeezed into portrait width — that squeeze was the tiny
type. The export now lays it on landscape A4, fits it to the page in both
directions and centres it, instead of fitting the width alone and leaving a
band of content across the top of an otherwise empty sheet. Where the sheet is
still taller than one page it is printed at full size across two, rather than
shrunk to something you cannot read on a touchline.

**It names the match.** A header band carries the fixture, date, kick-off,
home or away and the venue, alongside the formation, the half length and how
much of the squad is available. Whatever your academy has recorded appears;
what it hasn't is left out rather than printed empty.

**The same match exports the same document.** The sheet is composed at the
page's own width rather than at whatever window you happened to press the
button in, so a phone and a desktop no longer produce two different PDFs.

# TalentTrack v4.115.1 — Positions your academy adds itself now read as their label, not their key (#3276)

A position added through **Configuration → Lookups → Positions** printed its
internal database key on every screen that showed it — a profile reading
*Verdedigende middenvelder · rechter_middenvelder*, with the seeded positions
translated and the academy's own ones not. The label the operator typed was
stored correctly all along; no read surface asked for it.

Twelve surfaces are fixed by one resolver: the profile header, identity card,
sidebar and player card, the teammate card, my profile, the overview, the coach
and player dashboards, the rate-card hero, the blueprint roster, the goal
wizard's link step, journey position-change events, and human-facing CSV / XLSX
exports.

An academy that renames a **seeded** position to its own vocabulary is now
obeyed too, and a position with no label anywhere reads as words rather than as
a key. The stored code is unchanged and stays the matching key, so renaming a
position moves nothing between chemistry buckets, formation slots or squad
selection.

# TalentTrack v4.115.1 — The player's Activities tab lists newest first (#3277)

The tab showed a player's most recent 25 activities oldest-at-top, so the latest
one — usually the reason for opening the tab — sat at the bottom of the list.
It now reads newest first.

Anything already planned for the player sorts above today, so the tab opens on
what is next and then reads back through what happened. The window is unchanged:
still the most recent 25, never the oldest 25 of a player's career.

Other activity lists are untouched and stay in chronological order.

# TalentTrack v4.115.1 — The player's Measurements tab shows the BMI figure, without the explainers (#3278)

The BMI block at the top of a player's Measurements tab carried more prose than
figure: two paragraphs explaining the growth reference and how to read a BMI, a
line saying how far apart the two readings were, and a line about how the figure
had moved. On a player's file, opened mid-task, that is a wall to read past.

The tab now shows the BMI and its percentile, and nothing else.

Nothing is lost — **Reports → Player · BMI-for-age** is unchanged and still
carries the caveat, the days-apart column and the change column. That is the
screen you open when BMI is what you came to read.

A player the growth reference does not cover still says so on the tab rather
than showing a figure that looks complete.

# TalentTrack v4.115.1 — Team attendance report: the player drill-down now lists players (#3279)

Expanding a team row on **Reports → Team attendance** always said "no player
attendance in this window", even where the same row reported 15 activities and
93,4% present. The accordion read the player rows off the wrong level of the
REST response, so it received nothing and printed the empty state — on every
install, since the drill-down shipped.

The rows were always there. Expanding a team now lists its players worst-first
with their present percentage, and the at-risk badge appears on flagged
players — which, being fed from the same rows, had never been seen either.

# TalentTrack v4.115.1 — A player's height and weight are on their profile again (#3280)

The Identity card on a player's Profile tab showed date of birth, position,
foot, jersey, consent and status — but not height or weight. The player's own
*My profile* screen showed both, so a coach opening a player's file could not
read a number the player could.

Both are back, each with the date it was measured: **172 cm · measured 18 Aug**.
The figure comes from the dated measurement series rather than from whatever was
typed when the player was entered, so it is right even where the profile field
behind it has not caught up. A number typed on the player form rather than
measured shows without a date, because there is none; a player with neither gets
no row rather than a blank one.

A viewer without access to measurements sees the profile field instead of the
reading.

# TalentTrack v4.115.1 — The profile weight follows the measurements, like the height already did (#3281)

Recording a height has updated the player's profile since the last release.
Weight did not, so an academy weighing its squad every cycle had a correct
dated series and a profile still showing whatever was typed at signup.

Now both follow the readings. Record a weight and the profile shows it as soon
as that reading is the player's most recent one, under the same rules height
uses: the most recent measurement wins rather than the last one you typed,
deleting the last reading leaves the profile value alone, and a clearly
mistyped number is not copied across.

The recognised test names are `Gewicht`, `Weight` and `Mass`, matched the same
way as the height names — capitals and spacing don't matter.

# TalentTrack v4.115.1 — Existing height and weight readings are caught up on the profile (#3282)

The profile height and weight only started following the dated readings
partway through, and by design they only updated when a reading was saved —
so on any existing academy a player measured before that shipped still showed
whatever was typed at signup, and there was nothing on screen to explain why.

Upgrading runs a one-off pass that brings every profile in line with the
readings behind it.

Nothing is blanked or guessed: a player with no usable reading keeps the value
that was already there, an archived reading does not count, and a clearly
mistyped number is refused exactly as it would be on a fresh save.

# TalentTrack v4.115.0 — The unit of measure is now part of the measurement (#3273)

A test's unit used to be a caption printed after a number. It is now a property
of the datum: every unit belongs to a dimension — time, length, mass, count,
rate, percentage or level — and knows its factor to that dimension's SI base.
Values are stored canonically (seconds, metres, kilograms) and shown in the unit
the academy measures in, and each recorded result also keeps the unit and the
number it was entered in, so changing a test's unit later can no longer rewrite
the meaning of readings already taken.

This fixes a silent data error in the growth surfaces. The BMI series and the
player's `height_cm` both identified the height test by name and then assumed
centimetres; `m` has always been a selectable unit, so an academy recording
height in metres got a BMI two orders of magnitude out with nothing reporting a
problem. Both now convert through the unit instead of dividing by a hundred.

Time tests can be entered and shown as **mm:ss**. Tick "Enter and show as mm:ss"
on a test measured in a time unit and a result is typed as `5:30`, reads back as
`5:30`, and is stored in seconds — so trends, averages and target bands work on
the real quantity. Target bands are typed in the test's own unit throughout.

A custom free-text unit still works, and is now explicitly dimensionless: its
values are stored exactly as entered and never converted or compared across
units.

# TalentTrack v4.114.0 — Trial inputs are frozen once the trial is decided (#3238)

A staff input is the evidence behind a decision about a child — whether the academy wanted them, and why. It could be rewritten through the API after that decision had been made, with no earlier version kept and nothing on any screen saying it had changed.

Inputs now freeze when the case leaves **Open** or **Extended**. Up to that point an assigned coach can still correct their own wording, including after submitting it — re-reading what you wrote an hour later and fixing a sentence is normal and should not need a manager. After it, nothing can change them, and an attempt is refused with a message saying why rather than quietly doing nothing.

The rule already existed on the Staff inputs tab and now lives in one place that both the screen and the API read, so the two cannot disagree again. Freezing an input does not close the case to the coach who wrote it — they can still open a decided case and read what they said.

# TalentTrack v4.114.0 — The demo academy has behaviour, potential, and players old enough to have them (#3242)

The demo academy had **no potential and no behaviour data at all**, and its oldest player was seven. Two of the traffic light's four inputs were empty for every player, so the status it showed was not the status the product produces for a real club — and the potential trajectory, the team-chemistry contribution and the PDP evidence packet all rendered blank. A feature that shows nothing looks like a feature nobody uses, and this is the academy a prospective club is walked through.

Demo squads are now **spread across your age-group ladder** instead of taken from the youngest end, so a three-team academy gets a young squad, an older one and something in between. Age groups whose name carries no age — a *Senior* catch-all — are skipped, because the generator reads a player's birth year out of the group name and would otherwise fill a senior squad with children.

Behaviour ratings are seeded across the window for every squad. Potential is seeded as a **dated history** rather than a single row, so the trajectory has something to draw, with at least one player per squad revised *down* — the case the trajectory exists to make visible.

The gaps are deliberate. About one player in five old enough for a band does not have one, and one per squad is left overdue, so the *Potential not revisited* alert has something true to say on a demo install. Potential is not seeded below age 13 at all, matching the rule the product now applies.

Also fixes the demo-coverage check, which could not see tables created by migrations that build the table name into a variable first. Six such tables were invisible to it; all six now have a decision recorded.

# TalentTrack v4.114.0 — Switching a module off now actually removes its screens (#3254)

With **Training plans** switched off, the activity page still offered **Execute training** and `?tt_view=training-run` still opened the sideline view. Training was not special — it was the one somebody noticed.

Two causes, both now fixed. A screen's owning module was worked out from the tile the module registers itself, and a switched-off module registers nothing — so the check for "is this module off?" had nothing to read in exactly the situation it exists for. Ownership for 45 screens now lives where it cannot disappear. And the buttons that link between screens only ever asked whether the user had permission; a WordPress administrator passes every permission check by design, so the operator who had just switched the module off was the one person still being offered it. Those buttons now ask whether the screen exists on this install before asking who may open it.

A new CI check walks every screen the dashboard can route to and refuses a module screen whose ownership is not declared, so the gap cannot reopen.

# TalentTrack v4.114.0 — Sorting or searching a list no longer serves the blog index (#3256)

On an install whose front page is the TalentTrack dashboard page — which is
every install created through onboarding — clicking a column header or
running a search on any list view served the theme's blog index instead of
the list. WordPress only substitutes the static front page when the URL
carries no core public query var, and `order`, `orderby` and `search` are
all core public query vars, so the substitution was skipped and the
dashboard shortcode never ran.

The dashboard now claims those requests back. Sorting and searching work on
every list view regardless of what the site's front page is set to. Sites
that serve posts on the front page, front pages belonging to some other
page, and wp-admin are all untouched.

# TalentTrack v4.114.0 — Setup asks how much product you are running, in the app (#3259)

**How much product** — the step that decides the shape of the whole install — was the one step in the in-app Setup flow that still sent you to the WordPress admin. It now runs where the rest of Setup does.

It goes further than the admin version in one way. Each profile shows **What would change** before you pick it: which modules and features would be switched on, which off, and which your plan does not carry. The admin screen makes you choose and then tells you.

Nothing about how a profile is applied has changed — it is the same code behind both screens, so you can still start the step in one and finish it in the other. An install that has already been configured by hand is still sent to Modules → Install profile, where changes can be picked over row by row.

Two steps are still admin-only: **Import your squad** and **Add your staff**.

# TalentTrack v4.114.0 — Setup can import your squad in the app (#3260)

**Import your squad** — the step that decides whether a new academy's first experience of TalentTrack is a spreadsheet that loaded or one that did not — was still sending you to the WordPress admin. It now runs where the rest of Setup does: download the template, upload it, read what it found, confirm.

Nothing about how a workbook is read has changed. There is still exactly one importer behind both screens, so what counts as a valid file, what it reports and how it tags the rows are identical whichever one you use — and you can still start the step in one and finish it in the other.

The two-pass rule is intact: the first upload tells you what the file contains and writes nothing, and a workbook with problems never reaches the second pass. You choose the file again to confirm, because a browser will not let a page re-send a file it was handed, and holding your squad list on the server on the chance you press the button is not something to do quietly.

Only **Add your staff** is still admin-only.

# TalentTrack v4.114.0 — Potential is no longer asked about children (#3265)

The potential bands describe how far a player might go **as a professional**. TalentTrack was putting that question to coaches about seven-year-olds, and the *Potential not revisited* alert was flagging every one of them for never having answered it.

Potential is now asked from age 13. Below that the **Set potential** card says so instead of offering the bands, the API refuses a write with the same reason, and the alert skips those players entirely — it was previously unresolvable on any academy running young squads, since the only way to clear it was to record exactly the judgement the rule now prevents.

Behaviour ratings are unchanged at every age. Bands already recorded on younger players stay visible and still draw the trajectory; what stops is being asked again. A player with no date of birth on record is still asked — a missing field is not evidence of being too young.

# TalentTrack v4.114.0 — The Excel import accepts the template again (#3269)

Download the squad template, fill in a team and a couple of players, upload it — and every upload was refused, with a thousand errors saying `Name is required` for rows nobody had touched. The documented way to get a club's squad into TalentTrack could not succeed, on either the WordPress admin Setup step or the new in-app one.

The template's key column is a formula. The importer was reading the formula's *text* rather than what it works out to, so all 200 pre-formatted rows on each of the five sheets looked like rows somebody had filled in — and each then failed for having no name.

The same read is what cross-sheet references depend on, so this also fixes players never matching the team they name: an import that got past the errors would have landed every player with no team at all.

A row you genuinely started and left half-finished still stops the import and still tells you which row it was.

# TalentTrack v4.113.0 — Opening a trial now welcomes the family, and the message no longer has empty headings (#2605)

The trial welcome message has shipped since v3.110.18 with nothing to trigger it,
and its wording promised three things a trial case has never recorded: a team, a
location and a list of what to bring. Sent as written, the first message an
academy ever sent a family would have read "Where:" and "What to bring:" with
nothing after them.

The wording now promises only what a trial knows — the player and the start date
— and says a coach will follow up with the time, the place and the kit, which is
what actually happens. The message fires when a trial case is opened, from any
screen that opens one, and goes to the parents of a youth player through the
usual youth-contact rules. It can be switched off per template like every other
message.

# TalentTrack v4.113.0 — Required custom fields are now enforced when a record is created (#3217)

If your academy marked a custom field **required**, that was only enforced on
screens that actually showed the field. Create paths that do not render the
custom-field block — the new-player wizard, and creating a trial player inline
on the trial form — skipped it silently, so you could end up with a player
record missing a field you had made mandatory, with nothing to say it had been
skipped.

Creating a record now refuses when a required custom field is missing, and names
the field so you know which one.

Editing is unchanged, deliberately. A form that does not show a field still
leaves that field's stored value exactly as it was — that is what stops a short
edit screen wiping data it never displayed, and it is the reason the create case
was wrong in the first place: the two look identical in the code and are not the
same question.

# TalentTrack v4.113.0 — A player's profile height now follows their recorded measurements (#3219)

The height on a player's profile used to be a single undated number, typed when
the player was first entered — which for a growing 13-year-old is wrong within
months and gave no sign of it. Record a height measurement and the profile now
shows it. Correct an older reading and the profile stays on the newer one;
remove the last reading and the existing value is left alone rather than
blanked. Name the test `Lengte`, `Height`, `Length` or `Stature` for it to be
recognised. The BMI report is unaffected — it still pairs each weight with the
height that was true at the time, which is what a BMI needs.

# TalentTrack v4.113.0 — You can now publish a training plan, and the coaches get told (#3220)

A plan you are still working on and a plan your coaches should read looked the
same in the list, and telling them it was ready happened in a group chat.

A plan's page now says whether it has been published, with a **Publish and tell
the coaches** button. Publishing sends the head coaches the plan is for a
message with its title, its focus and a link to it — the one team's if the plan
names a team, every team's if it is club-wide.

Publishing announces; it does not lock anything. The plan stays fully editable
afterwards, and fixing a typo sends nothing. Coaches are told once: pressing
Publish on a plan that is already published does nothing at all. Unpublish is
there to correct a mistaken publish — it clears the mark, sends nothing, and
cannot unsend a message that has already gone.

Templates cannot be published; there is no squad they belong to.

This also gives the **Methodology / activity plan delivered** message something
to fire from. It has shipped since v3.110.18 with nothing behind it, because the
product had no idea what publishing a plan meant. Now it does. Whether an
install actually sends it is the messaging switch's business, as with every
other message.

# TalentTrack v4.113.0 — Opening a trial case is now a guided flow (#3221)

Opening a trial case meant one long form: player, track, dates, three staff
slots and notes, all at once. It is now three short steps — who is trialling,
which track and for how long, and who is watching — with a summary of what is
about to be created before you finish.

Two things this makes better beyond the shape. **Nothing is written until you
finish**, so backing out halfway no longer risks leaving a half-made player
behind. And **the summary step says what will happen**: the case opens, the
player's status becomes Trial, and the trial goes on their journey from day one.

The single-page form is still there for anyone who prefers it, and academies
with the guided flows switched off keep it as the default. Both now go through
exactly the same code to open the case, so they cannot drift apart — which is
what went wrong twice before, when one path forgot to record the player's
arrival and another forgot to put the trial on the timeline.

# TalentTrack v4.113.0 — An assistant coach assigned to a trial case can now open it (#3222)

Assigning an assistant coach to a trial case gives them the right to write their
input on it. Until now they could not open the case to do it: the screen let you
in only if you could also read the other coaches' synthesis, which an assistant
coach cannot. The capability was real and no screen could reach it.

Opening a case now asks the right question — may this person read the synthesis,
**or** write an input — and both still require being assigned. Nothing is
widened: the **Execution** tab, which gathers what the other coaches have said,
still needs the synthesis permission, both in the tab strip and when the tab
itself loads.

The Trials documentation was also wrong about how this works. It said other
coaches see nothing "unless they are assigned to it", which reads as though
assigning somebody grants access. It does not — whether a role can reach trial
cases at all is set in the authorization matrix, and assignment narrows it from
there. Both languages now say so, because "just assign them" was the wrong first
thing to try.

# TalentTrack v4.113.0 — Trial letters are reachable over the API (#3223)

Generating and reading a trial letter was only possible from the trial-case
screen — the code behind it could not be reached any other way. That made the
letters the one part of the Trials module a reporting tool, a mobile app or any
integration could not touch.

`GET /trial-cases/{id}/letters` now lists what has been generated for a case,
and `POST` to the same route generates one. Both need the same permission as
the Letter tab itself, so nothing new is visible to anybody: a letter telling a
family whether the academy wants their child is not something an assigned coach
can produce.

The list gives the audience, when it was generated, who by, and which one is
currently the live letter. Generating a new letter supersedes the previous one,
the same as it always has on screen — a case has one letter that counts, plus
the record of what it replaced.

# TalentTrack v4.113.0 — A new alert when nobody has revisited a player's potential (#3225)

Potential is meant to be a quarterly judgement, and nothing in the product ever
said so. A band set at intake and never revised stayed on the record looking
current, while still counting toward the player's traffic-light status, their
team-chemistry score and their development plan — an out-of-date number quietly
shaping decisions nobody was re-examining.

**Potential not revisited** now appears when a player's potential has gone two
quarters without being set or confirmed, or has never been set at all. The clock
starts at whichever is later, the last entry or the day the player joined, so a
player who signed three weeks ago is not overdue and a player nobody has ever
assessed is covered rather than invisible.

It goes to the people who can act on it — the head of development and the club
admin by default, plus any head coach your academy has granted that right. A
head coach who can only read potential is not told, because there would be
nothing they could do. Trial players are left out.

Like every alert, it clears itself: set the potential and it is gone on the next
pass, with nothing to dismiss. The window is
`alerts_potential_stale_days`, 180 days by default.

# TalentTrack v4.113.0 — The player profile now shows how potential has changed, not just where it is (#3226)

Potential has always been stored as dated entries — setting it appends, it never
overwrites — so the record of how the club's view of a player has moved was
already there. Nothing showed it. Every screen displayed the current band alone,
and the profile's "View potential history →" link landed on a page with no
history on it.

The **Behaviour & potential** screen now lists the sequence under the current
band: each entry with its date, who recorded it, any notes, and whether it was
revised up, revised down or reaffirmed. The direction is written in words as well
as shown with an arrow and a colour, so it reads the same to somebody who cannot
distinguish the colours. A player with a single entry gets no history section —
there is no trajectory yet.

The player profile gains a **Potential** row showing the current band, with a
history link when there is more than one entry. Staff only, like the status
history link beside it.

Two downward revisions in a season is the case this exists for: a strong signal
about a player's development that was in the data and invisible.

`GET /players/{id}/potential` returns the same series for integrations.

# TalentTrack v4.113.0 — The Staff role can now do a physio's job, and can no longer do an admin's (#3232)

**Staff can record measurements and injuries** for the players on their teams.
Until now the role could not: recording a height, a sprint time or a hamstring
strain was closed to it, which made "physio" a seat that could not do the two
things a physio is there for.

**Read this before handing the role out.** Staff is one role covering physios and
kit managers alike, so anyone with it can now see and record **injuries** —
medical information about minors — for their own teams. That is right for a
physio and more than a kit manager needs, and until the role is split there is no
way to give one without the other. If it is more than you want somebody to see,
attach them to the team without the Staff role. Neither injuries nor measurements
can be deleted by Staff; that stays with the head of development and the academy
admin.

**Staff no longer holds "manage players".** That capability was never about the
roster: it carries season rollover across the academy, creating login accounts
for players, editing install-wide custom-field definitions, and deleting player
records. On academies not yet using the permission matrix, a physio or kit
manager could reach all four. They can't now.

Nothing useful is lost with it. The one thing it legitimately reached — setting
up a test in the catalogue — now asks about the test catalogue instead, so the
heads of development and academy admins who had it still have it.

# TalentTrack v4.113.0 — The behaviour and potential forms now say what they are asking for (#3241)

Both capture forms showed a scale and a list of bands and explained neither, and
nothing anywhere said how often potential is meant to be revisited. Two coaches
guessing at what *Semi-pro* means is how the same player gets recorded
differently — and three things downstream read those numbers as if they meant
the same thing.

**Behaviour** now names the ends of your own configured scale and says the
rating is about the week you just watched, not the player as a whole. A single
low week is information; the status reads the trend.

**Potential** asks how high the player can reach *at their peak*, not where they
are now, and carries a **What the bands mean** section next to the picker — one
line each for First team, Professional elsewhere, Semi-pro, Top amateur and
Foundation.

**The cadence is on the screen.** It says potential is a quarterly judgement,
when this player's band was last set and by whom, how long ago that was, and
whether it is now overdue. The threshold is your own
`alerts_potential_stale_days` setting, so the form and the *Potential not
revisited* alert can never disagree about what late means. A player who has
never had a band set says exactly that.

The **Set potential** popover on the player profile carries the same one-line
explanation, so the two ways in do not tell you different things.

# TalentTrack v4.113.0 — Potential rating can now be switched off, like behaviour rating (#3243)

Behaviour rating has been switchable since v3.x; potential never was. An academy
that does not work in potential bands was still shown a **Set potential** button
on every player profile and a potential form on the capture screen, with no way
to stop it.

**Modules & features** now has a **Potential rating** switch alongside Behaviour
rating. Turning it off hides the profile affordance and the potential half of
the capture screen, and stops the API accepting new bands.

**What you already recorded stays.** The band on a player's profile and the
potential history behind it remain exactly as they were, and reappear in the
forms if you switch it back on. Off means stop asking, not hide the record.

**The reminder follows the switch.** The *Potential not revisited* alert goes
quiet when the feature is off, so you do not also have to find the alert screen
to stop being nagged about work you have deliberately stopped doing.

Switching capture off does **not** by itself remove potential from the
traffic-light status — that stays a separate choice on the player-status
methodology screen, because an academy may stop recording new bands while still
wanting the last one to count. The documentation now sets out all three switches
and what each one does.

# TalentTrack v4.113.0 — Positions carry their own abbreviation, per language (#3246)

The short code next to a position — `GK`, `CB`, `CDM` — was never a field. It was the internal key, printed raw. The eleven positions TalentTrack seeds got away with that because their keys happen to be football codes; a position an academy adds itself did not, and showed up on the player form as `linker_middenvelder`.

Positions now have an **Abbreviation** field in Configuration → Lookups, with a slot per language: `GK` in English, `K` in Dutch, without either of them touching the identifier the rest of the system joins on. Where a position has no abbreviation the full translated label is shown — never the key again — so a newly added position reads correctly whether or not anyone fills in a code. The position filter on the players list, which had the same defect, now shows labels too.

The seeded positions keep their English codes, so nothing changes on an existing install. Dutch codes are deliberately not seeded: the obvious ones collide (*linksback* and *linksbuiten* both want `LB`), and an English code an operator can see needs replacing beats a wrong Dutch one.

The abbreviation is display only. Chemistry, formation slots and squad selection all still key on the internal key, and a test pins that.

# TalentTrack v4.113.0 — Spond Test shows what would actually sync (#3247)

**Test** on a team's Spond connection proved the password and stopped there — which is not the question someone has after linking a group. They want to know whether the right calendar is behind it.

Test now logs in and then runs the dry-run preview that already existed on the Spond monitor, and reports it inline: how many events would be new, how many would update an existing activity, how many stored activities would be archived, plus the first few events with their dates. Nothing is written, and the panel says so. A link goes through to the monitor for the field-by-field comparison.

A login that works against a team with **no group linked yet** now says exactly that instead of reading as a failure — it is the normal state halfway through setup. A failed login still stops at the login error.

The two screens that enqueue the Spond script each carried their own copy of its string bag, and the copies had already drifted: the group-picker strings existed on one and not the other, so the same control fell back to English on the club-wide page. Both now read one shared bag, with a test that fails if the script ever reads a key the bag does not provide.

# TalentTrack v4.112.0 — Goals reach the player's journey whichever screen wrote them (#3131)

`tt_goals` had six write paths and exactly one of them — the REST endpoint —
announced the new goal, so only goals created that way reached the player's
journey. A goal set in the new-goal wizard was invisible on the timeline while
the identical goal set through the API was not, and the wizard is the path the
product steers a coach onto. The wp-admin form, the season rollover and the
development-idea spawner were silent for the same reason.

The insert, the demo tagging and the announcement now live in
`GoalsRepository::create()`, and every path goes through it. A journey entry
says which one wrote it, because they do not mean the same thing: *Goal set* for
a decision somebody took, *Goal carried over* for the season rollover, *Goal
opened from a development idea* for a spawned one. Carried-over entries are
dated to the start of the new season rather than to the day the rollover ran, so
a rollover done three weeks late still reads as the season's start.

Goals created before this stay off the timeline until the journey is rebuilt,
and a rebuild cannot tell how an old goal came to exist — every backfilled entry
reads as *Goal set*.

# TalentTrack v4.112.0 — The plan and your own two-factor are now screens in TalentTrack (#3134)

Two things that lived only in the WordPress admin are now screens in the
app: **Plan and restrictions**, which shows the plan the academy is on,
what is being used against the caps and the full feature matrix, and
**Two-factor authentication**, where you set up your own second factor and
make fresh backup codes. Both are open to everyone signed in.

The plan screen is where every locked-feature panel now sends you. Until
now that link went into the WordPress admin, which meant the feature built
to explain the plan was also the product's most-signposted route out of
the product. Finishing two-factor enrolment lands there too, instead of
depositing a parent or a coach in the admin area.

Three account actions did not move and are not going to: resetting another
person's two-factor, setting which roles must enrol, and the phone-home
diagnostic. Those are operator tools, used when something is wrong, and
they stay where their use is conspicuous.

# TalentTrack v4.112.0 — Trials that end through the workflow tasks now close on the timeline (#3138)

A trial case can end in six ways, and only three of them were being announced.
The other three are written by the trial-group workflow tasks, which wrote the
case row directly, so a trial that ended because the family declined the offered
place showed on the player's journey as a trial that started and never finished.
The accept branch of *Await team-offer decision* and the final-decline branch of
*Review trial group membership* had the same gap: no *Signed after trial*, no
*Released after trial*, and no move of the player's status.

Recording a decision now goes through one place, which announces it, and the
workflow tasks use it. Which decisions reach the timeline is a separate
judgement: *Continue in the trial group* and *Offered a team place* write nothing
on purpose, because the first says the trial is still running and the second is
mid-conversation. Writing *Trial ended* for either would be actively wrong rather
than merely missing.

The player's status is now written by one owner instead of two. Trials decided
before this stay as they were unless the journey is rebuilt.

# TalentTrack v4.112.0 — An academy that sends nothing is now told so (#3139)

A new academy starts with every message switched off, on purpose —
TalentTrack does not mail the parents of minors before somebody has decided
it should. The setup flow asks which messages you want, and that step can be
skipped.

A club that skipped it met that decision once, on the day they installed,
and never again. Nothing errored and no screen looked wrong; they found out
the day a training was cancelled and nobody turned up to be told.

There is now a quiet alert saying so, with a link to where to choose. It is
a badge rather than a banner, because a fresh install is in this state by
design and this is not a fault. Switch any message on and it disappears by
itself — no button to press, no flag stored anywhere. An academy that means
to send nothing can mute it like any other alert.

It does not mean invitations have stopped: the email that lets a new coach,
player or parent set up their account is account plumbing rather than one of
these messages, and goes out either way.

# TalentTrack v4.112.0 — The in-app Setup flow no longer dead-ends (#3140)

Four steps had been added to the setup wizard over time and only ever built
for the WordPress admin. Reaching any of them from **Configuration → Setup**
showed "Unknown step." — a dead end whose only exit was **Start over**,
which put you back at step 1 to walk into the same wall again.

**What we send** now works there. It is the step that asks which messages
TalentTrack may send on your behalf, and it matters most of the four: a new
academy starts with everything switched off, so an operator who never met
this step ended up with an install that quietly told nobody anything.

The other three — How much product, Import your squad, Add your staff — are
still admin-only for now, but they say so. Each names itself, tells you your
progress is saved, and offers to carry on in the WordPress admin or leave
setup, instead of pretending to be a bug.

The progress bar also listed five of the flow's ten steps, so it showed a
run that looked nearly finished right up to the point it stopped. It now
lists them all.

# TalentTrack v4.112.0 — Read-Only Observer and Staff accounts can see their work again (#3177)

Two roles had quietly stopped working as the permission model moved onto
the authorization matrix.

A **Read-Only Observer** — the board member, sponsor or auditor seat — was
being narrowed to the teams it was assigned to, and it is never assigned
any. The result was an empty team list, empty pickers and no academy-wide
reports: an account that could sign in and see almost nothing. It now
reads the academy's teams, players, people, evaluations, activities, goals
and reports, and the configuration screens, exactly as the role always
promised. It still cannot change a single thing, and it deliberately does
not reach safeguarding notes, injuries, coaches' private notes on a player,
parents' contact details, photographs, private messages or the audit log —
the documentation now lists that boundary where you decide whether to hand
the role to somebody outside the academy.

A **Staff** account — physio, kit manager, general club staff — had it
worse: on installs using the new permission model it was denied everything
its role granted, because the role reached no persona at all. It now works
again, scoped to the squads that person is attached to.

Staff deliberately do not get the player-management surface, which carries
season rollover, creating player login accounts and deleting player
records. That belongs with coaches and administrators.

Existing installs are updated automatically.

# TalentTrack v4.112.0 — Blueprint and formation routes now check which team they were handed (#3181)

`GET /blueprints/{id}` gated on "do you hold team chemistry access
anywhere" and never looked at the id it was given, so a coach with a
grant on one squad could read any other squad's full match-day lineup —
position, tier and player — by changing one number in the URL. The five
write siblings and the clone route had the same shape, which meant that
lineup could also be rewritten or deleted. The team's formation and
playing-style routes shared the gap.

Every route carrying an id now resolves the team first and checks the
caller's grant against that team. For the blueprint routes the team is
looked up from the row itself, reading only that column, so the refusal
lands before any lineup is loaded. A global grant (head of development,
scout, academy admin) still reaches every team, and the blueprint editor
still works with the team-chemistry sub-feature switched off.

# TalentTrack v4.112.0 — A second demo generation run builds a full academy, not a thinner one (#3184)

Generating demo data twice into the same club produced fewer rows the second
time. Two generators picked their subjects from the whole club rather than from
the batch they were writing — match analyses looked at every played match,
training observations at every completed run — so the second run met the first
run's work and skipped it. The counts on screen were honest about what had been
written and misleading about why.

Both now read only what their own run created, and the "roughly two in three"
choices key off a subject's position in that run rather than off its database
id, so the same preset and seed produce the same academy wherever it is
generated.

Staff development and knowledge courses still work from the whole club, and say
so in the docs: those are about the people the academy employs, who may already
exist rather than having been generated. Also fixed: the training-run generator
looked up trainings without filtering by club.

# TalentTrack v4.112.0 — A player created in the new-player wizard now arrives on their own timeline (#3189)

A player created through the new-player wizard had no *"Joined the academy"*
entry on their journey. The wizard's review step wrote the `tt_players` row
itself and announced nothing, so the journey subscriber never heard about the
creation and the player's story began at whatever happened to them next. It
was most visible on the trial path, which since the previous release writes
*"Trial started"* — leaving a trial with nothing before it.

The step no longer writes the row. It creates the player through the same
canonical create every other screen uses, which is also where the licence cap,
the custom-field validation, the consent stamp and the demo tagging live — the
wizard had grown its own copies of the last two, and both are gone. Existing
players created in the wizard stay missing the entry unless it is backfilled.

# TalentTrack v4.112.0 — Two demo runs started in the same second no longer share one batch (#3216)

A batch id was built from the preset, the seed and the time to the second, so
two runs begun inside the same second got the same one. A batch id is not a
label — it is how a run answers "which players, teams and activities are mine?"
— so the second run adopted the first run's rows as its own subjects and tried
to write their details a second time. On screen that was a run reporting fewer
rows than it wrote; in the log it was a wall of duplicate-key errors. A wipe
scoped to that batch also took both runs with it.

Batch ids now carry a short random suffix, so every run gets its own. Generated
content is unaffected: the seed still reproduces the same academy.

Four generators that write one row per subject — team formations and playing
styles, player attribute values, player custom-field values, and a training's
exercises and principles — now skip a subject that already has its row instead
of colliding. That matters on the path where an operator unchecks Teams or
Players to build on a squad that already exists, which legitimately hands the
generator rows an earlier run wrote.

# TalentTrack v4.111.0 — Match day and training now say which plan they are on (#3105)

Eight features that the 2026 plan map put on Pro were still open to every
install: match analysis, match preparation, the live match screen,
tournaments and their auto-balance, training plans, the exercise library and
the media library. They now refuse where they should, at the API as well as
on screen.

What refusing looks like was decided once and applies to all eight. The
screen stays where it is, with a panel naming the feature and the plan
rather than a missing menu item. **What the club already recorded stays
readable**: old analyses, plans, tournaments, training plans, exercises and
media are all still there to read, print, export and download. What stops is
writing new ones.

Media is the clearest case, and the one to remember: the club keeps every
photo it has, and cannot add more. Deleting is never refused over a plan —
removing a child's photo is an obligation, not a feature.

Auto-balance is sold below the level of the page it lives on: a Standard club
runs its tournament and plans the grid by hand, with the auto-balance button
locked in place beside it.

Integrations see the same split they always have: a plan refusal comes back
as `402 Payment Required`, a permission refusal as `403 Forbidden`.

# TalentTrack v4.111.0 — Channels and integrations now check the plan before they spend (#3106)

Six features that cost money on every use were still open on every install:
the SMS channel, push notifications, the four daily scheduled nudges, reading
a photographed plan, Spond sync and Strava connect. Each now refuses at the
narrowest point its module has — the SMS channel is not even offered in the
channel picker, and push quietly falls through to email so the notification
still arrives.

**Nothing already imported is touched.** Spond fixtures and Strava activities
stay exactly where they are, readable and exportable, and start working again
if the plan changes.

Two of these run in the background where nobody is watching, so their refusal
is written down rather than shown: a skipped scheduled send appears against
each nudge in the message log's health record, and a refused Spond sync
appears in that team's sync history. Both name the plan, so "the nudges
stopped" has an answer on the screen where you would look for it.

Object-storage backup keeps its place on the to-do list rather than gaining a
gate: there is no such destination to gate yet, and the gate ships with it.

# TalentTrack v4.111.0 — Seven more screens now say which plan they are on (#3107)

The analytics explorer, the custom-widget builder, the dashboard-layout
editor, courses, and the attendance, minutes and ratings grids are Pro
features. On Standard each one now renders locked with a panel naming the
plan — the tile stays where it is, and opening it explains itself rather
than erroring.

**The three grids deserve a sentence of their own, and get one on the
panel.** Attendance, minutes and ratings are Standard features and stay
exactly as they were: you can still mark a squad present from the activity,
enter minutes per match, and rate a player from their profile. What the grid
adds is doing a whole squad — or a whole week — in one screen. Losing the
grid is losing the fast way in, not the ability to record.

Courses lock at the module's own gate rather than at a screen, so the course
list, a lesson page and the API give the same answer. The courses stay
listed, so a club can see what the plan would open.

Everything already built stays: custom widgets keep rendering on the
dashboards they sit on, saved layouts keep driving everyone's dashboard, and
every figure already recorded through a grid is untouched.

# TalentTrack v4.111.0 — Share links and match-day PDFs now follow the plan (#3108)

The last six: the three match-day PDF exports and the three share links.
Each refuses at the URL rather than at the button that made it — a check on
the button would leave every link already sent working, which is not a check
at all.

**Share links stop when the plan drops, including ones sent months ago.** The
club still opens and reads every one of those documents inside TalentTrack;
what stops is handing them to people outside it, which is the part that was
being paid for. Someone following a link that has gone quiet gets a short
page saying so — not an error, not a "page not found" — naming neither the
plan nor the document. They are not the customer, and the contents of a link
that no longer works should not travel.

**For the PDFs the export locks and the record does not.** A Standard club
reads its match analyses and match plans on screen exactly as before, and
cannot print them. The alternative — exports working for records written
while the club was on Pro and not for newer ones — would mean a button that
works on one match and not the next, by a date nobody can see.

Backup and the account page's data export are untouched by any of this:
taking your whole academy's data out is never a paid feature.

This finishes the plan-gate rollout. Every paid feature now answers for
itself, and the list of the ones that did not is down to a single entry for
a screen that has not been built.

# TalentTrack v4.111.0 — A trial started from the new-player wizard reaches the timeline (#3130)

`tt_trial_started` was fired by three of the four places that open a trial
case. The one that did not was the new-player wizard — so a player whose trial
was started there had no "Trial started" entry on their journey, while an
identical player created through the trials screen or the API did.

Nothing errored, which is why it went unnoticed: the timeline simply started
later for some players than others, depending on which screen created them. For
a player whose relationship with the academy begins with a trial, that is the
transition the journey exists to record.

The event now fires from `TrialCasesRepository::create()` itself, once, for
every caller — the API, the trials screen, the wizard and the demo generator.
Demo players get the same journey shape as real ones, which is what makes the
demo academy a faithful preview. Journey writes are idempotent on their natural
key, so a repository that announces unconditionally cannot duplicate an entry.

Players whose trial was created through the wizard before this ship stay
missing the entry; their trial case still carries the start date, so it can be
reconstructed if a backfill is wanted.

# TalentTrack v4.111.0 — Blueprint editor no longer publishes a roster before checking who is looking (#3150)

Opening a team blueprint you do not coach printed "Access denied" in the
page while the page source already carried the names, preferred positions
and ages of every player on that team. The editor's data payload was built
by the asset-enqueue helper, which read the `?id=` out of the request for
itself and ran a thousand lines before the ownership check.

The payload is now built by the editor only, with the blueprint id that
check has already approved. The public share link, which is reachable
without signing in, no longer builds one at all — that page is rendered
entirely on the server and never used it. Player lookups behind the editor
are scoped to the club that is asking.

# TalentTrack v4.111.0 — Match-day surfaces now check whose match it is (#3151)

Match preparation, the live match screen and match analysis each read the
match id out of the address bar and rendered whatever came back. The only
permission involved was "this user coaches", which every coach holds
academy-wide — so editing the address bar opened another team's squad, and
in the case of match analysis, the notes written about those players by
name. The REST routes behind the same screens had the same gap, which let
a coach silently rewrite another team's plan.

All five now ask the same question: does this person coach the team playing
this match? Academy Admin and Head of Development still see every team.
Anyone else gets *You do not coach this activity's team.*

# TalentTrack v4.111.0 — A team's roster is no longer readable by typing its id (#3152)

`tt_view_teams` answers "may this person look at teams". Four surfaces read it
as "may this person look at **this** team" and then took the team id from the
URL. The capability is club-wide on the coach role, so a head coach could walk
`?tt_view=teams&id=1,2,3…` and read every squad in the academy — the full
active roster, the trial roster, the staff list — and reach the edit form
behind each one. `GET /teams/{id}` and the peek endpoint had the same gap.

All four now ask the caller's team scope: a global `team` read sees every
squad, everyone else sees the teams they are assigned to. That is the narrowing
`GET /teams` — the list — has always applied when deciding which rows to
return; the detail simply never asked. The two `minutes-share` routes beside
`GET /teams/{id}` carried the same club-wide gate and return per-player
minutes, so they take the same check.

Archived teams still open read-only for the coach who ran them: whether you
coach a squad is a fact about the assignment, not about whether the squad is
still running.

An administrator, and any persona with a global read on teams — Head of
Development, Academy Admin, Club Admin, Read-Only Observer — is unaffected.
A coach who finds a team page has stopped opening should check their team
assignments under People → Functional roles; that is where the scope comes
from.

The teams-manage loader also gained the club scope its sibling loader on the
same screen already carried.

# TalentTrack v4.111.0 — Team chemistry routes check which team, not just whether (#3153)

Five chemistry endpoints took a team id in the path and gated on *"do you hold
this permission anywhere"*. A head coach's `team_chemistry` grant is scoped to
their own teams, and that question answers yes for every team — so changing one
number in the URL returned another squad's suggested XI, depth chart and
coach-marked pairings, with player names attached.

The board, the lineup preview, the pairings list and the per-player fit scores
now ask whether the caller holds the grant **on that team**. Adding and removing
a pairing get the same treatment on the write side, with the delete resolving
the pairing's own team first, since its URL names the pairing rather than the
team. The per-player fit route resolves the player's team the same way.

An academy-wide `team_chemistry` grant — scout, Head of Development, Academy
Admin — still reads everything, and the sub-feature toggle still takes all of
these dark when team chemistry is switched off. The on-screen board was already
checking team membership; this brings the API into line with it.

# TalentTrack v4.111.0 — Player-status routes check the player and the team, not just the capability (#3154)

Three player-status endpoints gated on a bare capability, took an id from the
path, and never looked at it. `tt_view_player_status` goes to both coach roles,
scouts and parents, and the response is the full verdict object per player —
so `GET /players/{id}/status` returned any child's status and breakdown, and
iterating `GET /teams/{id}/player-statuses` walked every squad in the academy.

`POST /players/{id}/behaviour-ratings` was the same missing check on a **write**:
its gate is a feature-flag-plus-capability call that takes no player id, so a
holder could log a behaviour judgement onto any child in the club.

Reading one player's status now asks the same question the player's profile
asks, so a parent still reads their own child and nobody else's. Reading a
team's statuses asks whether the caller may read that team's player statuses —
scoped on player status rather than on teams, so a Head of Development granted
academy-wide status read still gets every board. Logging a behaviour rating
asks whether the caller may edit that player; every role holding
`tt_rate_player_behaviour` already passed that for their own players, so it
narrows rather than locks out.

The team predicate now lives in one place. `CohortBoardRestController` had the
only copy of it; it delegates to the shared version instead, which also gives
it two corrections it was missing — a WordPress settings admin passes without
needing a matrix row, and an archived team the caller coaches still resolves.

# TalentTrack v4.111.0 — Test results and their Excel export now stop at the teams you coach (#3155)

The Test results screen showed a coach only their own teams' players, as it
always said it did. The API behind the same screen did not: asked without a
team, it answered with every player in the academy who has a result for that
test — name, team, age group and the measured value. The Excel export of the
same data was a second door to it, and needed nothing but a login and a
coaching role somewhere in the club.

Both now answer the same question the screen does, through the same team
filter. A coach with no team assignments gets nothing rather than the club,
and asking for a team you do not coach is refused instead of quietly
widened. Head of Development and Academy Admin see the academy, as before.

Growth and physical testing is longitudinal data about a child's body. It
should reach the coaches responsible for that child and no one else.

# TalentTrack v4.111.0 — Rate cards and Player BMI hold their URL parameters to the viewer's scope (#3156)

Two report surfaces resolved the viewer's team scope correctly for their
pickers and then ignored it for the `?team_id=` / `?player_id=` sitting next to
them. On Rate cards a hand-typed `team_id` listed any squad's roster into the
player dropdown and a hand-typed `player_id` rendered that player's whole rate
card. On Player BMI the team half was already clamped; the player drilldown was
not, so a team-scoped coach could read any club player's height, weight, BMI and
percentile history — growth data on a minor.

Both now clamp the URL parameter to the scope the view has already resolved
rather than resolving it a second time. An out-of-scope team on Rate cards
behaves as if none were given; an out-of-scope player is refused on both.

Rate cards also gains a capability check. It is registered for routing only —
no tile, no entity, no capability — so the dispatcher's two gates both passed it
through, and `render()` had no check of its own. It now requires
`tt_view_reports`, the capability its own docblock has always claimed and the
one its wp-admin twin uses.

The Back-button label resolver takes the same treatment: the record id it parses
out of a caller-supplied `tt_back` URL was club-scoped but not viewer-scoped, so
a crafted link resolved any child's name — or a team, PDP or evaluation label —
into the Back pill. It now falls back to the list-level label, which is what an
unresolvable id already did.

# TalentTrack v4.111.0 — Player pickers no longer list the whole academy (#3157)

Two dropdowns were built from every active player in the club. The team
roster's **Add player** list, and — worse in kind rather than degree — the
media wizard's *Who is this about?* step, which is where a coach decides
whose photo gets stored. A head coach editing their own U13 saw every child
in the academy by name in both.

Both now offer players on teams you coach. Academy Admin, Head of
Development and scouts still see the whole academy, so the people who do
academy-wide work still can.

**This narrows what a coach can do, on purpose.** Adding a player who is on
a team you do not coach is no longer possible from the roster dropdown —
moving a child between age groups is an academy-admin act, and the
academy-wide branch still does it in one step. If a player you expect is
missing from the list, they are on someone else's team.

The team **Edit** page also now refuses without permission to edit teams.
The Edit button always checked; the URL did not.

# TalentTrack v4.111.0 — wp-admin lists and pickers now respect coach scope (#3158)

Seven wp-admin pages built their player and team lists from the unscoped
`get_players()` / `get_teams()` helpers and were gated only by a menu
capability every coach holds, while the frontend and REST siblings of the
same lists already narrowed correctly. The sharpest of them, Players,
rendered `pl.*` — date of birth, guardian name, email and phone — for every
child in the academy to any coach who navigated to wp-admin.

Players, Teams, Evaluations, Goals, Activities, Player Rate Cards and
Reports now show a coach only their own teams' players and teams. The
Players list authorises per row through the same `canViewPlayer` gate
`GET /players` uses, so the rows and the count agree. `action=edit` and
`action=view` refuse an out-of-scope id before rendering any roster, staff
or attendance panel instead of trusting the menu capability. Edit-form team
and player pickers keep the record's own current value selectable, so
saving cannot silently unassign it.

An administrator, and any persona holding a global read on the entity,
still sees everything — and now more reliably: the shared team picker asked
the authorization matrix for an entity named `teams`, which does not exist
(the seeded entity is `team`, singular). The lookup is an exact match, so
it always answered no, and every global-read persona who is not also a
WordPress settings admin — head of development, read-only observer — fell
through to the coach-assignment branch and got an empty picker.

Six queries across Evaluations, Reports and Player Comparison also gained
the `club_id` scope they were missing.

# TalentTrack v4.111.0 — Team search returns the squads you can open, not the whole academy (#3159)

The command palette's team search gated on `tt_view_teams` and then queried
every team in the club. That capability is club-wide on the coach role, so a
JO15 coach typing two letters enumerated every squad in the academy — names and
age groups. Team names encode age groups, so the result is a map of the
academy's cohorts: not player data itself, but the index to it.

Player search was already correct and is unchanged — it over-fetches and runs
each row through the same authorization the player's own profile uses. Team
search now applies the narrowing `GET /teams` has always applied: a global team
read searches everything, everyone else searches the teams they are assigned to,
and a caller with no teams gets an empty list rather than the club.

The narrowing runs in SQL rather than after the fact, so the ten-result cap is
filled with squads the caller can actually open.

# TalentTrack v4.111.0 — The prospect pipeline follows the viewer's scope (#3160)

A head coach's `prospects` grant is team-scoped, and the seed says why: so they
can follow their own age group's funnel. Nothing in the pipeline read that.

Two things were wrong at once. `GET /prospects` returned the **whole club's**
funnel — including parent contact details and who spotted each child — to any
holder of `tt_view_prospects`. Meanwhile the kanban board showed a head coach
**nothing but prospects they had logged themselves**, usually none, because the
helper that decided the narrowing meant "holds view but not manage" and had
quietly inverted: it was written when a scout's grant was self-scoped, and when
scouts moved to an academy-wide grant it stopped catching scouts and started
catching head coaches instead.

Both now resolve through one place. Academy Admin, Head of Development and
scouts see the whole funnel, as before. A head coach sees the funnel feeding
their own squads: prospects logged for one of their age groups, anyone already
promoted into one of their teams, and anything they logged themselves.

A prospect record carries an age group, not a team — they have not joined yet,
so there is no squad to belong to. A prospect with no age group and no promotion
is visible only to the academy-wide roles and to whoever logged them: when the
record does not say who it is for, less visibility is the right default for a
child who has not joined the academy.

The narrowing runs in the query rather than after it, so the counts on the
dashboard tile and the columns on the board can no longer disagree. The
prospect detail endpoint narrows to the same set as its list.

# TalentTrack v4.111.0 — The Analytics explorer asks for permission; bulk invites ask whose team (#3161)

The Analytics explorer had no permission check of its own, and nothing in
front of it had one either. On an academy that had switched the explorer
on, anyone who could reach the dashboard could open its URL and group any
figure in the system by player or by team — with a link straight to each
profile. It now needs the same permission the Analytics page beside it
does, and asks for it before it exports anything.

**Bulk invite a team** took the team from the form and never checked it was
yours. A request built by hand could create account invitations for another
team's players, addressed to their guardians. The team must now be one you
coach, unless you are someone who may invite academy-wide.

# TalentTrack v4.110.0 — Add an age profile, and a straight answer for the age groups that will never have one (#2601)

The training generator only drafts for an age group that has an age profile —
the profile is what supplies the age-safe intensity ceiling, and that is not
something to guess at for children. Five shipped seeded, U10 to U14, and there
was no way to add a sixth. An academy fielding U15 to U19 had a generator that
refused every one of those teams and nowhere to go.

**You can now add and remove age profiles** under VCT configuration → Age
profiles, and through the API. Nothing is pre-filled: these numbers decide how
long and how hard children train, so a plausible-looking suggestion would be
worse than an empty field. Adding a profile also copies the session shape from
the closest age group that already has one — U15 inherits U14's blueprint — so
the generator works for those teams straight away rather than stopping one step
later for a different reason.

Removing a profile is refused while a team is still in that age group; those
teams would quietly stop getting drafted trainings. Trainings already planned are
never affected.

**And the youngest groups now get an answer instead of an apparent gap.** U7–U9
have no load model on purpose — training load is not planned in numbers at that
age. The generator used to report a missing profile there, which sent coaches
looking for a setting that does not exist. It now says structured load planning
does not apply at this age and the session is the coach's to shape. The line
between "not modelled by design" and "not set up yet" follows the profiles your
club actually has, so adding a younger profile moves it.

Seeded numbers for U15 and up are still a methodology decision and are not
included; an academy can now set its own.

# TalentTrack v4.110.0 — Messaging gets a REST surface, a help topic, and one more live trigger (#2605)

Comms has recorded every message it sent since the module shipped and exposed
none of it, so "did the parents get the cancellation message?" still meant
writing SQL, and every in-app message ever sent landed in a room with no door.
Both tables now have a reader.

**The send log** is available at `GET /comms/messages`, filterable by player,
recipient, template, message type, status, channel and date range — and at
`GET /players/{id}/messages`, which asks the same question from a player's
record rather than from a global list. The message body is never returned and
neither is its fingerprint: the log is there to show that a message about a
child was sent, to whom, and whether it arrived — not to let anyone read what
a coach wrote about them. Reading it takes the audit-log capability, not the
send-email one; being allowed to send is not being allowed to read what
everyone else sent.

**The in-app inbox** is available at `GET /comms/inbox` with an unread count,
and `PATCH /comms/inbox/{id}` marks one read. Every query is scoped to the
caller in SQL, so no route here can reach another person's inbox — a message
that is not yours answers "not found" rather than "not allowed", because
refusing it by name would itself confirm something about another family.

**The template switch and your own opt-out preferences** are readable and
writable over REST too, so a future front end can manage both.

**A development plan now tells the family when it is signed off.** The
`pdp_ready` message has shipped since v3.110.18 with no trigger behind it. A
PDP has no "published" state, so the moment chosen is the verdict sign-off —
the point at which the plan stops being a working draft. It fires on that
transition only, so correcting a typo afterwards does not tell the family
twice.

**Messaging has a help topic** for the first time, in English and Dutch:
what sends, who receives it, the five things that can stop it, what the send
log does and does not keep, and how to work out why something did not arrive.

# TalentTrack v4.110.0 — Two screens for messages: the staff send log and a personal inbox (#2606)

Messaging recorded everything and showed nothing. Both of its tables have
been written since the module shipped and read by nobody, so "did the parents
actually get the cancellation message?" needed SQL, and every in-app message
ever sent landed somewhere nobody could open. Two screens close that.

**Message log**, under Configuration, and reachable from a player's record
under **⋯ → Messages sent** — which is the point of it: the question is asked
from the child it is about, not from a global list somebody then narrows. It
filters by player, kind of message, outcome and date range, and the player
filter offers only players the log has actually carried a message about.

Outcomes read as English rather than as database keys, in three tones rather
than two. An opt-out the product honoured and an address that bounced are both
"not delivered" and want opposite reactions from whoever is reading, so they
are not painted the same colour. Where the reason is specific it wins: "No
email address on file" tells someone what to fix, "Failed" tells them nothing.

If one of the daily detectors has been failing, a warning sits above the table
naming it and when it last ran. That is the only place the difference shows —
a detector with nothing to send and a detector crashing every night both leave
no rows behind.

The log shows no message body, because none is stored: the audit row keeps a
fingerprint of the message and nothing else. That limit is deliberate. The
screen can say that a message about a child went out, to whom, and whether it
arrived, and cannot be used to read what a coach wrote about them.

**My messages**, under Me, is each person's own in-app inbox, with the unread
count on the tile. Marking one read does not reload the page. A parent sees
their own family's messages and never another's — enforced by the query
itself rather than by a check that could be got round.

Both screens are built for a phone first: the log's table becomes one card per
message at 360px instead of scrolling sideways, and the inbox is where a parent
was going to read the message anyway.

# TalentTrack v4.110.0 — The second dashboard in the WordPress admin is retired (#2979)

TalentTrack had two dashboards. The real one is the app; the other was a
**TalentTrack → Dashboard** page in the WordPress admin showing a grid of links
to the admin screens plus five counters with a "+5 this week" figure. Nobody
tested it against the one people actually use, so it was one screen that could
quietly disagree with another.

It is gone. The old bookmark redirects to the app's dashboard rather than
breaking, and the menu entry no longer appears.

The five weekly counters are not moved anywhere first — they simply stop
existing. An at-a-glance count is worth less than not having two dashboards that
can contradict each other, and the same numbers are available from the reports.

**The Account page is unchanged.** It was in the original scope for this
clean-up and was taken back out: it carries your two-factor set-up and the plan
information, and there is no equivalent in the app yet.

# TalentTrack v4.110.0 — Match prep: revert to how it was when you opened the screen (#3006)

Autosaving surfaces gain a second range of undo. **Undo** takes back the last
change; **Revert changes**, beside it, puts the whole record back to the state
it was in when the screen was opened. It asks first — a confirm names how many
fields it will restore and says the restore cannot itself be undone.

The starting point lives in the browser, so it survives a reload or an
accidental tab close, and it does not follow the coach to another device: open
the same match on a laptop the next morning and you get the saved plan with no
revert offered, because the sitting ended. Every read and write of the store is
wrapped — a private window, a cleared browser or a record too large to snapshot
simply means no revert offered, with autosave, undo and the rest of the screen
unchanged.

Two boundaries worth knowing. Captain and set-piece picks write on their own
endpoint, which a revert could not put back, so choosing one retires the offer
for the rest of the sitting rather than restoring part of the screen and
leaving those standing. And the grids stay explicit-Save: a control that offers
to restore a change the coach has not committed yet would not mean anything.

Match prep is the only surface wired to it today; the behaviour lives in the
shared `TT.Autosave` component, so the writing surfaces moving to autosave in
the rest of epic #2881 inherit it.

# TalentTrack v4.110.0 — Match analysis saves itself, and publishes when you say so (#3007)

The match analysis no longer has a Save button. It autosaves as you write,
through the same shared component match preparation uses, so the status line,
the words, **Undo** and **Revert changes** are identical on both screens. This
is the surface the save-model work started from: a coach writing up a game on a
phone after the final whistle is composing over minutes, not filling in a
record, and losing a paragraph to a tapped Back button is the failure that
matters there.

What "abandon a half-written draft" turned into is better than what it
replaced. Every analysis is a **draft** until **Mark as final** is pressed, and
autosave only ever writes the draft. That one button is a publish, not a save:
the staff share link stays valid throughout, but shows *This analysis is not
finished yet* rather than half a sentence about a named child — so the
guarantee the link has always carried survives autosave. An analysis already
marked final stays final when it is reopened to fix a typo; reopening a
published document does not unpublish it from the people who were sent the
link.

Two people can open the same analysis — a head coach in the stand, an assistant
on the touchline. If the other person saves while you are writing, your next
save is refused rather than merged, and the status line says so and asks you to
reload. Quietly overwriting a colleague's write-up of a child, sentence by
sentence, with neither of you told, is the worse outcome. The endpoint carries
a version token for this, and a PHPUnit suite pins the three properties
autosave now depends on: absence is never deletion, the token moves on every
write whichever of the four tables changed, and a stale write is refused
without having written.

There is no Cancel on the form any more, because there is nothing uncommitted
to cancel; leaving the page leaves a draft.

# TalentTrack v4.110.0 — Evaluations, PDP conversations, goals and player notes stop losing your work (#3008)

The remaining writing surfaces from the save-model epic move onto autosave.
Editing an **evaluation**, a **goal**, a **PDP conversation** or writing a
**self-reflection** now saves as you go, with the same status line, the same
words, and the same **Undo** and **Revert changes** controls as match
preparation and match analysis. All four are places where somebody composes a
judgement about a player rather than filling in a known set of fields, and
losing that work is what stops a coach writing the next one.

**Creating still needs Save.** Autosave writes to a record, and while you are
adding a new evaluation or goal there is no record yet — pointing it at the
create endpoint would leave an empty row on a player's file behind everyone who
opened the form and thought better of it. Editing autosaves; adding does not.

**Sign-off leaves the PDP conversation form.** It was a checkbox you saved along
with everything else, which on a self-saving form is one accidental tap away
from locking a conversation for everyone, permanently. It is now its own **Sign
off** button below the form, behind a confirm. Everything above it is already
saved by the time you press it.

**Player notes work differently, on purpose.** A note is a post, not a field, so
it is still sent only when you press Send — nothing half-written reaches the
staff. What changed is that the compose box now keeps your draft: close the tab,
come back later on the same device, and the sentence you were in the middle of
is still there.

Under the hood, one data-loss bug is fixed. `PUT /evaluations/{id}` rebuilt the
whole row from the request, so any client sending only the fields it changed
silently blanked the player, the type, the date and the player-facing feedback.
It now writes only what it is given. A new test suite pins that contract on all
three endpoints, and pins that a signed-off conversation refuses further writes
in the endpoint rather than only in the screen.

The four grids are untouched and stay explicit-Save.

# TalentTrack v4.110.0 — The save model is written down (#3009)

A new help topic, **How saving works**, sets out the three ways TalentTrack
commits work and which screens use each: the screens that save themselves and
offer undo and revert, the screens that keep a Save button with a real Cancel,
and the wizards that draft and then submit. It says how far undo reaches, why
revert belongs to one device and one sitting, and why the attendance, minutes
and ratings grids keep an explicit Save deliberately rather than by omission —
a coach rating a squad on a flaky connection gets one commit point, and a
half-finished commit is worse than a lost one.

Every screen that changed in this epic now points at it, in English and Dutch.
This is the slice that stops the next surface guessing: the epic's finding was
never only that autosave was inconsistent, it was that the rule was nowhere.

Documentation and repo standards only — no behaviour changes.

# TalentTrack v4.110.0 — Scheduled report CSVs no longer touch the public uploads folder (#3080)

A scheduled report was written into `wp-content/uploads/` while it was being
emailed, under a name anyone could guess — `tt-report-<kpi>-<date>.csv` — and
deleted afterwards on a best-effort basis. That folder is served over the web,
and these reports carry player names alongside attendance, minutes and
evaluation figures.

The CSV is now rendered into the server's private temporary directory, inside a
randomly named folder, and removed on every path including a failed send. The
email attachment still arrives with its readable `.csv` name. A report that
cannot be written now fails the run and says so in the log, instead of sending
an email that promises a report and carries none.

Also hardened in the same pass: `uploads/tt-pdp-deletes/`, where the pre-delete
snapshots for development plans live, now carries the same deny-all rule as the
media store. Those files are meant to be kept, so they stay in `uploads/` — they
just are not readable by anyone who guesses the URL.

Operators upgrading an install that ran scheduled reports before this change
should delete any leftover `wp-content/uploads/tt-report-*.csv` once; nothing
writes them any more.

# TalentTrack v4.110.0 — Cancelled trainings now tell the families (#3081)

The "Training cancelled" message template has shipped since v3.110.18 waiting
on a hook nothing ever raised, so it could never send. Activities now fires
`tt_activity_cancelled` when a session moves into cancelled, and Comms listens
for it: the planned roster is resolved through the youth-contact rules —
parents for the younger age groups, the player from U12 up — and each family
is told once, even when two of their children are on the same roster. A
cancellation skips quiet hours, because a training called off tonight is
useless news tomorrow.

The event fires from the repository rather than from the buttons, because
cancellation has two write paths: the Cancel button on the activity detail
page, and an edit that sets the status to Cancelled from either the activity
form or the wp-admin page. Wiring only the button would have told half the
families and looked correct in testing. Re-saving an activity that was already
cancelled sends nothing.

Two things fixed alongside it. Cancelling from the wp-admin activities page
wrote the status but left the plan state behind, so the planner kept offering
a session that had been called off; both lifecycle columns now move together
there, as they already did over REST. And the recipient resolver was reading
the parent-link table in the wrong shape, so a player *with* linked parents
resolved to nobody at all — nothing had sent through it before, which is why
that went unnoticed.

# TalentTrack v4.110.0 — Lookup labels no longer borrow a meaning from the translation catalogue (#3082)

A lookup value with no translation row used to be handed to the plugin's
translation catalogue as a plain phrase, and whatever matched was displayed.
That is how a player's preferred foot `Left` came out in Dutch as *Vertrokken*
— "departed" — because that was the only place the word appeared in the
catalogue. The quiet version of the same fault put adjectives and lowercase
mid-sentence words into status pills: `Technical` as *Technisch* rather than
*Techniek*, `overdue` as a lowercase *te laat*.

That resolution step is gone. A lookup with no translation row now shows its
English key, which is the deliberate trade: obviously untranslated English gets
reported by an operator, a real Dutch word meaning the wrong thing does not.
Curated labels belong in the seed list and reach the database through a
migration, where they are reviewable.

A repair migration runs once on update. It replaces a stored label only when
that label is character-for-character what the catalogue would have produced
for that value and is not the curated one — a label a club typed itself is
never touched. On installs whose catalogue already agreed with the seeds it
changes nothing; it matters on installs that first migrated against an older
`.po`.

Also fixed in the curated list: the Dutch label for goal priority `Medium`,
which read *Middel* ("means / remedy / waist") instead of *Gemiddeld*; and
missing labels for the `Pending Approval` goal status and the two observation
journey event types, which had been reading English on every non-Dutch locale.

# TalentTrack v4.110.0 — Generating demo data twice no longer collides with itself (#3102)

Generating a demo preset into a club that already had one printed database
errors and quietly wrote fewer rows than the operator was told. Four generators
read their subjects from the whole club rather than from the batch they were
writing, met the rows a previous run had left, and let the INSERT fail against a
unique key instead of skipping them.

They now skip deliberately: a match that already has an analysis does not get a
second one, a training run that has already been observed is not observed again,
and a person who already has a staff development file or a course enrolment is
left alone. The counts the second run reports are the rows it actually wrote.

The other half of the same bug was subtler. Demo generation is reproducible on
purpose — the same preset and seed produce the same dataset — and uuids were
being drawn from that same seeded stream, so a second run re-minted
byte-for-byte the same uuid and collided with the `uk_uuid` key the first run
had filled. Reproducibility was always meant to cover the dataset, not the
identities, so uuids now come from the system random source. Nothing about the
generated academy changes.

Documented under *Generating twice into the same club*, so a lower second count
reads as what it is rather than as a failure.

# TalentTrack v4.110.0 — One shape for every "not on your plan" refusal (#3104)

A feature your plan does not include now always refuses the same way, and
says so in the same words. Open one and you get a panel naming the feature,
naming the plan it belongs to, and linking to the account page — the screen
stays where it is rather than vanishing, so it is obvious the feature exists
and is simply switched off.

Two things that were previously implicit are now stated in the product and in
`docs/license-and-account.md`. Anything already recorded stays readable and
exportable when a feature leaves the plan; only writing new entries stops. And
"not on your plan" is a different answer from "you are not allowed" — over the
REST API the first is HTTP 402 and the second stays 403, so a failed request
says which of the two happened.

Groundwork: the panel and the refusal helpers are shared, so the surfaces that
gain a plan gate later cannot each invent their own wording. Nothing new is
gated by this change.

# TalentTrack v4.110.0 — Invitation email is account plumbing, not a message you switch off (#3110)

The invitation email — the one carrying the link somebody uses to set a
password and log in for the first time — no longer has a switch on
**Configuration → Messages**, and is absent from that list rather than
shown ticked and locked. It sends because somebody invited a person, and
that is now its only condition.

A switch for it read as a messaging decision and behaved like an
onboarding outage: an academy that unticked it would not connect "we
switched off a message" to "new parents cannot log in". Password reset
has always sat outside the switch for the same reason; this puts the
invitation email on the same side of that line, expressed as an
`AccountMailTemplate` marker so any future account mail inherits it.

Nothing else about the message changes: it still goes through
`CommsService`, still writes its message-log row, still resolves its
recipient the same way. An academy that previously unticked it finds that
choice inert, and it is cleared the next time the page is saved.

# TalentTrack v4.110.0 — A new academy starts with every message switched off (#3111)

Installing TalentTrack for the first time no longer starts sending mail to
parents, players and staff before anybody decided that it should. A fresh
install now begins with every message type on **Configuration → Messages**
switched off, and the setup wizard is where an academy chooses what it
sends. The invitation email is unaffected — it is account plumbing and sits
outside the switch (#3110), so a new install can still onboard people.

**Existing academies are not affected.** Upgrading changes nothing: every
message that was being sent before is still being sent afterwards. The new
default is written once, at first activation, so it applies to
installations created from this release onward and never retroactively.

That also preserves the rule about later releases. This setting stores the
list of message types you have switched *off*, so a type shipped in a
future release is on nobody's off-list and lands enabled on every install
that already existed — while a fresh install seeds its own off-list from
the message types that exist at the moment it is activated.

The shipped template list moved to `Comms\Template\TemplateCatalog`, which
is readable without the plugin having booted. Activation runs long after
`init`, so seeding from the runtime registry would have written an empty
set — and failed silently, leaving a new install sending everything.
`TemplateSwitch::isEnabled()` is untouched.

The honest trade-off: an academy that skips the wizard step sends nothing
at all, including about a cancelled training. #3113 is what makes that step
hard to skip by accident.

# TalentTrack v4.110.0 — The Messages settings screen says what each message is (#3112)

**Configuration → Messages** was eighteen checkboxes wrapping across a
paragraph, each labelled with an internal name and trailed by a comma-separated
list of channel keys. Nothing on it said what a message was, who received it,
or what made it send — on the screen where an academy decides what mail reaches
the parents of its players.

It is now grouped by what the message is: *People need to know now*, *Somebody
asked for it*, *Moments in a player's season*, *Reminders and summaries*. Each
message says in one sentence what it does, who gets it, and what triggers it.

**Messages nothing fires yet are labelled as such.** Eleven of them have copy
and settings but no trigger; a checkbox for a message the product cannot send
is a lie on a settings screen. They are marked "Not sent automatically yet"
rather than hidden, so the list still shows what exists.

**Channels are now their own control.** Whether a message goes at all and how it
is allowed to travel are two decisions, and the screen used to show only the
first. Each message with more than one option now lists the ways it may reach
people, and unticking one takes it out of use for that message — an academy with
no SMS credit, or one that would rather not reach school-age players on
WhatsApp, sets that here.

The screen also now explains something it never did: a message reaches a person
on **one** channel, not all of them. TalentTrack works down the list and uses the
first that can actually reach them, so the list is a fallback order rather than
four copies of the same message. Unticking every option is refused — a message
with nowhere to go records as a failure rather than as a decision, so switching
the message off is the way to stop it.

Rebuilt mobile-first: stacked cards, 48px targets, no horizontal scroll at
360px. The screen's inline `<script>` became an enqueued asset, and its styles a
stylesheet reading the design tokens.

# TalentTrack v4.110.0 — The setup wizard asks what your academy sends (#3113)

A new step, **What we send**, between adding staff and creating the dashboard
page. It lists what TalentTrack can send, grouped and in plain language rather
than by template name, and the academy ticks what it wants.

This is what makes #3111's "a new install sends nothing" safe rather than merely
quiet. Without it the outcome is not conservative — it is a club that never tells
anybody anything, finds out when a cancelled training goes unannounced, and
concludes the product is broken.

Nothing is pre-ticked, because the honest framing is that you are choosing what
to switch on. The urgent group — training cancelled, schedule change,
safeguarding broadcast — is marked *Recommended* in a sentence; a recommendation
is not a tick made on somebody's behalf.

Skipping is allowed and says what it means: **no messages will be sent, not even
a cancelled training.** Not "you can change this later", which reads as "it is
fine either way". The Done screen repeats the count, or the warning when nothing
is on.

The step reuses the Messages settings screen's copy (#3112) and writes through
the same domain writer, so there is no second place the decision lives. Staff
invitations are unaffected whatever is chosen — the invitation email is account
plumbing and sits outside the switch (#3110).

Recovery for a skipped step is filed as #3139.

# TalentTrack v4.110.0 — A trial opened from the UI now shows on the player's timeline (#3115)

Opening a trial case through the Trials screen wrote no journey event, so the
trial was missing from the player's timeline. Opening the identical case through
the API did write one. The screen a coach actually uses was the broken half.

Creating the player inline on the same form had the matching problem: it wrote
the row directly instead of going through the normal player create, so the
player's own arrival never reached the timeline either — for exactly the players
whose journey begins with a trial, and who therefore had no timeline at all.

Both now go through the same path as everything else. Opening a case writes
**Trial started**; creating the player inline writes **Joined the academy** and
applies everything else a normal player create does — custom-field defaults, the
consent stamp, the parent link. One consequence of that: an academy that has made
a custom player field required can no longer use the three-field shortcut, and
the form now says which field is missing. Add the player from the Players screen
first, then pick them on the trial form.

# TalentTrack v4.110.0 — Recording a trial decision now moves the player (#3116)

Recording a decision updated the trial case and nothing else. An admitted player
stayed on **Trial** status indefinitely — the academy said yes to a child and the
record did not show it — unless somebody separately ran the team-offer workflow.

Recording a decision now settles the player's status:

- **Admit** → Active.
- **Decline (final)** → Released, and the record is archived into the recycle bin,
  where it stays restorable.
- **Decline (with encouragement)** → Inactive, and **not** archived. That decision
  means "not now, come back", so the player stays on the books and eligible for a
  future trial. Archiving them would tell your own system the opposite of what you
  just told the family.

Only a player still on Trial status moves, so recording a decision twice, or
deciding on a player who was already promoted another way, changes nothing.

The behaviour is identical whether the decision is recorded on the Decision tab
or through the API — it hangs off the decision itself rather than off either
screen.

The documentation claimed the letter is generated automatically. It never was,
and it should not be: someone ought to read a letter to a family before it
exists. The Letter tab button stays the way to produce it, and the docs now say
so. The docs also claimed both declines archive the player, which is the mistake
this release fixes.

# TalentTrack v4.110.0 — French, German and Spanish lookup labels that had silently never been seeded (#3117)

The curated list of lookup translations had drifted away from the vocabulary it
translates. 68 of its entries pointed at names no longer in use — journey event
types renamed to internal keys, `Match` renamed to `game`, competition types
folded into game subtypes, the behaviour scale rewritten as 1–5 — so the seeding
step matched nothing and quietly did nothing for 13 of the 20 vocabularies it
claims to cover.

Dutch mostly escaped this, because a much older update had filled Dutch labels
in from the translation catalogue. French, German and Spanish had no such
backstop, so those labels fell through to raw English: 136 of 263 lookup values
carried a label on a reference install, and the missing ones were almost exactly
the drifted set.

The list is now re-keyed against the live vocabulary and completed, and an update
fills the newly-matching labels in — 36 per language, taking coverage to 172 of
263. It only ever fills empty slots; a label your club typed itself is never
overwritten. Stale entries for vocabularies that no longer exist have been
removed, and newly-added values that had never been curated (the `Tournament`,
`Observation` and `Other` evaluation types, the `Football periodisation`
certificate, the meeting activity type) now carry labels in all four languages.

A test now fails the build when the list drifts from the vocabulary again, in
either direction — an entry pointing at nothing, or a value with no entry.

# TalentTrack v4.109.0 — Goals now say what they develop (#2566)

Every screen where a goal is written — the goal form, the quick-add box on the
coach dashboard and the wp-admin form — now asks **What does this goal
develop?** and offers the principles of the club's active methodology. Tick as
many as apply; a goal can serve more than one, and the field is skippable
because a goal without a principle is still a good goal.

That link is what the rest of the system aims at. Training plans rank exercises
by how many of a squad's open development targets they touch, and the
per-principle reporting on the persona dashboard counts tagged goals — both of
which resolved to nothing on a real install, because the field existed on one
screen a coach never opened and was set on none of 109 goals.

Existing goals are left exactly as they are: a principle inferred from a goal's
title would be a guess, and guessed data in a coverage panel is worse than a
thin one. The panel fills up as goals are re-authored. A freshly generated demo
academy now shows the mechanism working end to end.

# TalentTrack v4.109.0 — Every email the academy sends now goes through Messages (#2604)

Trial reminders, scheduled reports, thread notifications, task assignments,
scout report links, the "email me the link" button and anything typed into the
in-product composer used to leave the building on their own. They now go out
the same way every other message does, which means a person's opt-out is
honoured, quiet hours are respected, the academy's per-message switch applies,
and each one leaves a row in the message log — so "did that family ever get
told?" has an answer.

The composer also stops claiming success it cannot vouch for. It now says what
became of the message — sent, held until quiet hours end, refused because the
recipient opted out — instead of a bare confirmation, and warns before you
write if the recipient would not receive it.

Password reset and backup delivery deliberately stay outside this system: a
suppressed reset would lock someone out of their own account, and a backup is a
file for whoever holds your backups rather than a message about a person.

# TalentTrack v4.109.0 — Help topics render through the same engine as the courses (#2663)

The plugin had two markdown renderers: one for help topics, one for the course
reader. They have been folded into one, so a fix or an addition on either
surface now lands on both.

For a reader, help topics gain what the course reader already had. **Tables in
a topic render as tables** instead of rows of pipe characters, and a wide one
scrolls inside its own box rather than pushing the page sideways on a phone. A
bullet whose text wraps across two lines now stays one bullet instead of
breaking into a stray paragraph.

Topic styling moved off hardcoded greys onto the design tokens, so the help
reader inside the app finally picks up the club's colours instead of wearing
WordPress's. The wp-admin Help & Docs page keeps the look it had.

# TalentTrack v4.109.0 — A season of match analyses, read as a trend (#2725)

Match analyses have been recording, per game, how each phase of play went and
which players stood out. One of those is a note; ten are a trend, and until now
nothing read them back.

Two new places do. **Reports → Team · Match analysis trends** shows, for a team
and period, how often each phase was rated *Went well*, *Mixed* or *Needs work*
— the sentence a head of development wants before setting next month's training
theme. The **Match analysis** tab on a player's file shows what that player has
repeatedly been marked as and in which phase, above the individual notes their
journey already carries.

Both are careful about what they claim. They count occurrences and never
average: three ordered words are not a score, and a 1.8 would invent precision
nobody entered. A phase a coach deliberately left unrated is excluded rather
than counted as neutral. And below three rated matches you get an explicit "not
enough matches yet" rather than a trend drawn through one data point — which on
a young academy is the honest answer, and tells you what the threshold is.

# TalentTrack v4.109.0 — Phone page headers show two buttons, the rest behind a menu (#2809)

Page headers on a phone now show two buttons and put the rest behind the **⋯**
menu. The worst case was an activity page opening with nine full-width buttons
stacked above anything you came to read; it now opens with the two that matter
and everything else one tap away.

Nothing is hidden — every action is still there, and on a desktop they all stay
laid out in the row as before.

The menu also behaves like a menu now: Escape closes it and puts you back on the
button you opened it with, opening it moves straight to the first item, and
clicking anywhere else closes it.

# TalentTrack v4.109.0 — The bottom bar's four shortcuts are now chosen per role (#2810)

The four shortcuts in the bar at the bottom of a phone screen are now chosen per
role instead of being whatever happened to come first in the menu. A head coach
gets Activities, Players, Teams and My tasks; a parent gets their child's
activities, profile, evaluations and development plan; a scout gets the intake
pipeline and their visits. Everything else is still one tap away under **More**.

The clearest fix is for academy admins, who were being shown Evaluations,
Activities, Tournaments and Goals — with no Players and no Teams. They now get no
bottom bar at all, because their work is the setup screens the bar deliberately
leaves out, and a bar that points somewhere other than where they work is worse
than no bar.

If your academy has already configured its own shortcuts, those still win.

# TalentTrack v4.109.0 — The "this needs a desktop" page says which screen, and why (#2811)

The "this needs a desktop" page now tells you something useful. It names the
screen you were trying to open, says why that particular screen needs the width
— a roster grid, a printed team sheet and a settings page all get different
answers — and where there is a phone-friendly way to do the same job, it offers
it by name. Taking attendance is the clearest case: the page now points you at
the activity itself rather than leaving you stuck.

**Show it anyway** is a button on the page. Until now the only way past was to
know about a URL parameter, which is not much of an escape hatch for something
you meet as often as this.

The page also had to earn its own advice: it is now laid out properly on a
360px screen, with tap targets you can actually hit.

# TalentTrack v4.109.0 — Tab strips and grid switchers stop drifting apart (#2822)

Five surfaces had grown their own tab strip rather than using the shared one,
all of them rendering under the 48px tap-target floor — which is why the
trial-case tabs, the Custom CSS panes and the Functional roles sections each
looked and behaved slightly differently, and were fiddly to hit on a phone.

The three that really are tabs within one subject — trial case, Custom CSS and
Functional roles — now come from the shared record spine, so they get its
sizing, keyboard order and active state for free.

The two grid switchers do not: Attendance | Minutes changes *what the screen
shows*, not which part of a record you are looking at, and there is no record
on those screens at all. They get a new shared segmented control instead, along
with the Custom CSS surface switcher, which likewise picks which stylesheet you
are editing rather than a section within one. Same 48px floor, one
implementation, and the control is no longer announced to screen readers as a
tab strip when it is a row of links.

# TalentTrack v4.109.0 — An academy can now edit its own methodology without entering wp-admin (#2976)

The methodology is the one thing this product is most opinionated about — it
is the vocabulary every learning goal, exercise and evaluation is written in.
It was also the one thing an academy could not change without being dropped
into WordPress: nine separate wp-admin pages maintained the vision, the
principles, the phases, the influence factors, the positions, the learning
goals, the set pieces, the primer and the football actions.

All nine now live on one screen — **Methodology vocabulary**, under
Configuration → Methodology. A picker across the top switches which
vocabulary you are looking at; underneath it are that vocabulary's entries,
with a form to add, edit or remove one. Vision and Primer are single records
for the whole academy, so they offer editing only, and shipped reference
content stays marked read-only exactly as it was.

Every field a coach reads is written twice, once per language, side by side.
Filling in only one is fine and shows you plainly which one you filled in,
rather than leaving the gap to surface later in somebody else's locale.

Positions keep their shape: they belong to a formation, so you pick the
formation first and then work through its shirt numbers.

The wp-admin pages are untouched and still work. This adds a route; it removes
none.

# TalentTrack v4.109.0 — Undo the last change on an autosaving surface (#3005)

Match preparation saves as you work, which means a mis-tap is stored the
moment it settles. An **Undo** button now sits next to the save state and
takes back the last committed change — the slot you just filled, the half
length you just typed, the focus note you just wrote.

The undo is itself saved, so it survives a reload rather than being a
screen-only revert. It is one step, not a history: once used, the offer
retires until the next change. It appears only on a settled record, so it
can never race a save still in the air, and a failed undo says so and
leaves the screen showing the value the server still holds.

Built into the shared `TT.Autosave` component rather than into the
surface, so the writing surfaces moving to autosave later in epic #2881
inherit it. Captain and set-piece picks write on their own endpoint and
are outside what one payload snapshot describes, so they retire the offer
instead of letting Undo revert something older than the coach's last
action.

# TalentTrack v4.109.0 — Context-aware help now covers eight screens it was silently skipping (#3022)

The help drawer's promise is that every screen knows which topic explains
it. Eight screens were exempt from that promise without anyone deciding
they should be — the alert settings and alert policy screens, the
invitation acceptance page, the two match share links, both password-reset
screens and the second-factor prompt. Each was reachable, none was checked.

Alert settings and alert policy now open the Alerts topic. The other six
are pre-authentication screens with no help button on the page at all, and
are recorded as deliberately topic-less with the reason why, so nobody has
to rediscover the question.

Underneath, the three checks that ask "which screens can a visitor reach?"
now share one answer instead of computing three different ones.

# TalentTrack v4.109.0 — Permission to read something no longer permits changing it (#3023)

Five wp-admin screens gated their **save** on a capability whose name says *view*: Category Weights, Custom Fields, Evaluation Categories, Eval Type Categories, and People. On each one the menu entry that leads there was already gated on the narrower read capability, so the page was reachable by URL for someone the entry point deliberately hides it from — and the write behind it was authorised by permission to read. Category Weights was the sharpest case: a view capability decided who could rewrite the weighting behind every composite rating in the academy.

Each now reads with its view capability and writes with its edit one. Both already existed in every case; the pages simply did not consult them.

**This changes who can do what on existing installs, chiefly for Head of Development.** They hold every per-area *view* capability by design, which adds up to the `tt_view_settings` umbrella — and their edit capabilities were deliberately removed when the settings permissions were split. These pages were handing the edit back through the view umbrella. A Head of Development who genuinely needs to change one of these should be granted the matching edit capability, which is now a deliberate act. Club Admin and administrator are unaffected; coaches and team managers never held the umbrella.

Two write actions still name a read capability and are recorded in the access-control guide rather than quietly widened, because no narrower capability exists yet and inventing one is a change to the permission model in its own right: granting or revoking a role on a person, and archiving a scheduled report.

# TalentTrack v4.109.0 — The install wizard now shows its Done screen (#3025)

Finishing the dashboard-page step dropped straight to a bare "Setup is
complete" line. The summary of what was set up, the recommended next steps and
the link to the dashboard just created were written and translated but
unreachable on every install — the completion flag was set in the same request
that moved to the last step, and the page's completion check fired first.
Leaving the Done screen still gets you the short line on a later visit.

The step indicator also skipped the Import and Staff steps, so it highlighted
nothing while you were on either of them.

# TalentTrack v4.109.0 — Demo data actions no longer dead-end on a permission error (#3026)

Generating, wiping or switching demo mode redirected to `tools.php?page=tt-demo-data`,
left over from when the page lived under Tools. It is registered under the
TalentTrack menu, so WordPress answered that URL with "Sorry, you are not
allowed to access this page" and every demo action ended there. The install
wizard's "Try with sample data" button and the demo-mode admin-bar badge hit the
same dead end. All of them now land back on the demo page with their notice
intact.

# TalentTrack v4.109.0 — Six tiles were rendering without an icon (#3027)

The Injuries, Team development, PDP cycle, Trials and Notes surfaces declared
icon keys that had no SVG in either icon set, so their icon chip painted blank.
The Media tile fell back to a line icon among duotone neighbours. All six now
have a duotone glyph: a first-aid kit for Injuries, so a medical record does not
read as an error state.

# TalentTrack v4.109.0 — Test results: beating the target band is now green, not red (#3028)

Target bands were treated as closed ranges regardless of the test's direction,
so on a lower-is-better test a player who ran faster than the green band fell
outside every band and was flagged red. In the reported case the three fastest
sprinters in a U12 squad showed red while the three slowest showed green. The
band is now open on the better side: beating it counts as green, amber and red
sit past it on the worse side only, and there is still no red threshold to
enter. Neutral tests keep both edges, since those values are meant to land
inside a range. The player profile's trend chart shades its band the same way.

# TalentTrack v4.109.0 — Demo data now records minutes played, so the minutes surfaces have something to show (#3029)

A demo install had matches, line-ups, substitutions and goal events but no
minutes played, so every minutes surface was empty on the dataset the product is
demonstrated with — the minutes report, the minutes audit, minutes share, and
the player profile's minutes figure.

The substitution stream already held the answer; nobody derived it back onto the
attendance record. Now a starter who played the whole match gets the full match,
one taken off gets the minute they went off, and a substitute gets what was left
when they came on — so a team's total lands exactly on squad size times the match
length, and no player exceeds it. Demo match analyses pick the same numbers up.

A player who stayed on the bench has no minutes rather than zero, because "did
not feature" and "played nothing" are different facts and the minutes surfaces
exist to tell them apart.

# TalentTrack v4.109.0 — Demo data now reaches into the future, not just the past (#3030)

Every generated activity used to land on or before today, so a demo install had
nothing planned: no next match, nothing for the week planner, match prep or the
upcoming-activity alerts to point at. Half the product is about what happens
next, and it looked empty in the dataset used to show the product. Each preset
now generates four weeks ahead of today as well as its weeks of history. Future
activities are planned and carry no result — no attendance, no minutes, no
ratings, no match execution — while match prep is written for them, which is
what a coach's screen looks like mid-week.

# TalentTrack v4.109.0 — Existing academies get "Links" back for a left-footed player (#3031)

Splitting the two senses of "Left" stopped the confusion happening again, but
it could not undo it: on academies that had already upgraded, the wrong Dutch
label was stored in the database and stayed there. Updating now corrects it, on
the player profile, the player list, the rate card, the goal-intake print and
the exports.

A label your academy renamed itself is left exactly as you set it — only the
one wrong word is replaced.

# TalentTrack v4.109.0 — "Left" no longer means two different things at once (#3031)

A player whose preferred foot was Left could read "Vertrokken" in Dutch — the
sense of having left the academy. One three-letter msgid was serving both the
media-retention table's departure column and the preferred-foot lookup value,
and whichever sense was translated first won on every surface. The departure
column now carries its own translation context, leaving the bare word free to
mean the direction.

# TalentTrack v4.109.0 — Goals tab: the Dutch deadline badge no longer overlaps the goal title (#3032)

On the player profile's Goals tab in Dutch, the due-date badge's label
("DEADLINE") was wider than the fixed 44px column it rendered into and painted
across the goal title beside it. The column now sizes to its content with 44px
as the floor, so short month badges are unchanged and a longer label in any
locale gets the room it needs.

# TalentTrack v4.109.0 — One definition of an active goal on the player file (#3033)

The Goals tab of a player's file used to list goals whose own pill read
"Voltooid" under the heading "Actieve doelen", and the number on the tab, the
number in the heading and the goals figure in the at-a-glance panel could all
disagree for the same player — three surfaces had each written their own idea of
what "active" means, and one of them had no status filter at all.

There is now one definition, held in the goals repository and read by all three,
so the numbers cannot drift apart again. The list holds only goals the player is
still working on; achieved and abandoned goals move into a **Completed goals**
section directly beneath it, collapsed by default, so a finished goal stays part
of the player's file instead of vanishing from it.

The tab badge also missed its club filter, which is fixed in the same pass.

# TalentTrack v4.109.0 — The alert bar is half as tall, and you can put an alert off for a day (#3034)

Each alert stacked its severity label, its sentence and its button on three
separate lines, so three alerts pushed the page you came for below the fold.
They are now one row each. Every alert also carries **Not today**, which hides
it until tomorrow and brings it back if the thing is still unfixed — the snooze
had been built for some time and the banner simply had no control wired to it.
Alerts about a child's safety carry no such button, matching the fact that they
cannot be muted anywhere else either.

# TalentTrack v4.109.0 — Install profiles: Basics and Full academy (#3035)

An install can now be described by a named profile instead of fifty separate module decisions. Two ship: **Basics**, which keeps the development loop — players, teams, people, evaluations, goals, activities, measurements, the journey and the reports that read them back — and switches off match day, training plans, the knowledge library, the integrations and the developer surfaces; and **Full academy**, which is everything the plugin ships and is what an install gets when no profile is chosen.

Two choices inside Basics look like mistakes and are not. Analytics stays on, because the reports and the dashboard figures read the analytics engine directly and only the separate explorer surface is switched off. Communication stays on, because that is what invitations and account mail travel over; only its two cost-bearing extras go.

A profile is an association rather than a copy: the install remembers which one it is on and works out afresh how far it has drifted, so switching something back into line clears the drift immediately. Applying a profile never deletes data — a module it switches off keeps every row it owns — and it never overrules the plan an install is on: anything above the entitlement is reported as skipped, with the reason, instead of being switched on and failing later.

This ship is the mechanism only. Nothing on screen changes yet: the Modules-page strip, the preview-and-confirm screen and the Setup-wizard step follow.

# TalentTrack v4.109.0 — Install profiles over REST (#3036)

Install profiles are now readable and applicable through the API as well as from PHP: `GET /profiles` lists what ships and which one this install is on, `GET /profiles/{slug}` returns that profile with the full list of what applying it would change, and `POST /profiles/{slug}/apply` applies it, honouring a list of rows the caller chose to hold back.

The preview is a plain read of the same route the apply uses, so a front end that is not WordPress gets exactly the answer the plugin's own screens will get. Every route is gated on the capability that already governs the Modules page, and a request for a profile that does not exist comes back as a missing resource rather than a bad request.

# TalentTrack v4.109.0 — See which install profile you are on, and change it (#3037)

The Modules page now opens with a strip saying which install profile this academy is on and how far it has drifted from it — "Basics · 3 changes since" — or "Not on a profile" for an install that predates them. Beside it, a chooser and a **Review changes** button.

Review changes opens a preview, which is the only screen in the product that applies a profile. It lists what would be switched on, what would be switched off, and anything above what the plan includes with the reason it cannot be applied. Every change is ticked; untick anything you would rather keep as it is. **Nothing is written until you press Apply** — opening the preview and walking away changes nothing — and switching a module off never deletes a record, only the surfaces that show it.

A profile that would change nothing says so and offers no button to press.

# TalentTrack v4.109.0 — Setup asks how much product you are running (#3038)

The setup wizard has a new step between **Academy basics** and **Import your squad**: pick an install profile. **Basics** keeps the development loop and switches off match day, training plans, the knowledge library, the integrations and the developer surfaces; **Full academy** is everything. Each card lists what it includes, grouped the way the Modules page groups them.

It is asked there deliberately — after you have named the academy, before you bring your squad in, so you are not importing into a shape that is about to change. Skipping gives you the full academy, which is exactly what an install gets today, and the step says so rather than leaving it implied. Choosing one shows you what was switched before you continue; nothing is deleted, and you can change it any time from Modules.

Re-running setup on an install that has already been configured by hand does not apply anything: the step sends you to the Modules page's profile preview instead, where you see the whole list of changes before any of them happen.

# TalentTrack v4.109.0 — Your install profile tells you when a release changes what it covers (#3039)

Every new module ships switched on, so an academy deliberately put on Basics would quietly re-accumulate surfaces it was never sold, one release at a time. That no longer happens.

When a release changes what your profile includes, the strip on the Modules page says so — "Basics now covers Training plans. Nothing has changed yet." — with **Review**, which opens the preview showing only those changes, and **Dismiss**, which records that you have seen them and decided against. A dismissed change does not come back on the next unrelated release; one a later release changes its mind about again does, because that is a new decision rather than the same one repeated.

Nothing is ever applied automatically, under any setting. A release happens with nobody watching, which is exactly the wrong moment for something to be switched on unasked. There is no scheduled task behind this either — it is a comparison made when an admin opens the Modules page.

The notice tells the two kinds of difference apart: a module **you** switched off is your decision and is never reported as a profile change, and a module the **profile** newly includes is never reported as something you did. An install on Full academy, or on no profile, sees no notice at all.

# TalentTrack v4.109.0 — The large demo preset finishes on hosted installs (#3041)

Generating the large demo set used to die with an Apache **Proxy Error** and
leave the dataset half-written, with nothing on screen to say how far it got.
The generation ran inside a single request, and while the plugin raised PHP's
own time limit, the proxy in front of a hosted install gave up long before PHP
did — which no setting inside the request can change.

A run is now a list of steps, and each step is its own short request. The
overlay names the one it is on — *Step 7 of 24 — Evaluations* — instead of
spinning indeterminately, and no single request has to outlive the gateway.

An interrupted run is now visible rather than silent. Close the tab
mid-generation and the page tells you next time: how many steps finished, which
batch, and a choice of **Resume this run** or **Discard it**. The rows already
written stay tagged either way, so a wipe still reaches them. A second run
cannot start while one is unfinished.

The dataset is unchanged in shape and still reproducible from a seed: the same
seed and preset produce the same academy, whether it was generated in one
request or thirty. With JavaScript switched off the run happens in one request
exactly as before.

# TalentTrack v4.109.0 — Choose how big a demo dataset to generate (#3042)

The demo generator offered four presets and nothing else, and every one of them
built 12 players per team — so the number of players, and with it the number of
demo accounts, could not be changed at all. Twelve suits a U15 squad and not a
U8 one playing six-a-side. **Set my own numbers** under the preset now opens
teams, players per team and weeks of history, prefilled from the chosen preset
and overridable per run, with a live count of the players and accounts the
current numbers will create. Leave a field empty and the preset's value is used,
so touching nothing generates exactly what it always did.

# TalentTrack v4.109.0 — A malformed release note fails its own PR, not the release (#3043)

The check that every code change ships with a release note only confirmed a
note existed — it never read one. A note written without its title line became
a changelog entry titled "Bump: minor" and quietly shipped as a patch release
instead of a minor one; seven of the nine notes in one batch were wrong that
way, and it only came to light while cutting the release.

The check now reads each note and fails the pull request that introduced a
broken one, and the release script refuses to run rather than guessing at a
title. Three notes already waiting to ship were malformed and have been fixed,
so the next release names them properly.

# TalentTrack v4.109.0 — A team now records how many a side it plays (#3044)

Six-a-side, eight-a-side and eleven-a-side are different games, and TalentTrack
only knew the third one. Every seeded formation was an eleven-player shape, so
a U9 coach opening the team blueprint was offered a back four and a front
three for a team that fields six.

A team now has a **football form**. It is set to *follow the age group* by
default, and what that resolves to is maintained under **Configuration →
Football form** — one row per age category, pre-filled from the age groups you
already have. Set it explicitly on a team for the exception: the club that runs
its U13 at 8v8, or an U12 already at 11v11.

The team blueprint offers only the shapes a team can actually field, and four
small-sided formations ship with it — 3-2-1 and 2-3-1 for six-a-side, 3-3-1 and
3-2-2 for eight. The blueprint wizard groups formations by form and refuses one
from the wrong group.

The forms themselves are a vocabulary under **Configuration → Lookups →
Football forms**, seeded with 6v6, 8v8 and 11v11. If your federation plays 4v4,
7v7 or 9v9, add them and they work everywhere.

Two smaller things fall out of it. The tournament wizard used to promise that
the format was worked out from the team's age group, which nothing did; it now
says where the form actually comes from. And the demo data reads the same
answer as the product instead of its own copy.

Existing teams need no attention: an unset form resolves through its age group,
and every formation already in the system is recorded as eleven-a-side.

# TalentTrack v4.109.0 — Goals and assists on the player's activity line (#3045)

The Activities tab on a player's profile now shows what the player produced in
each match, on the row itself: "2 goals scored", "1 assist". It reads the same
goal log as the profile's Goals scored tile, so the numbers agree — ours only,
own goals and reversed goals excluded. Matches with neither, and every
training, show nothing rather than a row of zeroes.

# TalentTrack v4.109.0 — Configuration is grouped by module instead of by filing decision (#3046)

Configuration used to present six sections — Appearance, Dashboard, Data &
vocabularies, Methodology & cycles, Integrations, System — that described the
kind of screen rather than the part of the academy it belonged to. Anything a
module owned could land in any of three of them depending on which felt closest
the day it was added, so finding "how are trials organised" meant already
knowing where each piece had been filed.

Configuration now groups by module. Trial tracks and trial letter templates are
under Trials. Workflow templates are under Workflow. Everything that belongs to
the whole install rather than to one module — appearance, date notation and
locale, lookups, seasons, backups, the audit log, the wp-admin menus, the
configuration export, the recycle bin — is together under Academy-wide, first
on the page.

The grouping is derived from what each module already declares, so a module's
new settings screen appears under its own heading with nothing to file, and
switching a module off takes its section with it. Every existing link and
bookmark still resolves; nothing moved to a new address.

On the dashboard, a group that mixes work with setup no longer drags the setup
half into "Today's work". The Trials group keeps its cases list up top and its
trial tracks in the collapsed setup section below.

# TalentTrack v4.109.0 — The access-control matrix is one scroll, not two (#3047)

The permissions grid used to sit in a box with its own scrollbars inside a page that also scrolled: reading one persona's grants meant dragging two of them, and losing the row and column headings on the way. The grid now scrolls sideways only, and the page owns the vertical axis, so reading an entity list top to bottom is one continuous scroll.

The entity column still stays put while the persona columns move under it, and every category band now repeats the persona names, so the column you are looking at is identified within a band rather than only at the very top of the table.

Scope has moved out of the cells onto its own line. Each entity row carries a small **Scope** button that opens a row of scope dropdowns underneath — still one per persona, so nothing about who can see what has changed; it simply no longer makes every entity two rows tall whether you are looking at it or not.

# TalentTrack v4.109.0 — Mark each match-analysis note good or bad (#3091)

A phase rated *Wisselend* with four bullets under it read, six weeks later
and to whoever the share link went to, as four undifferentiated sentences.
The coach knew which two were the good half while typing and the surface
threw it away.

Every note — phase bullet and player note — now carries an optional **+** or
**−**. Leaving it unmarked stays the normal case: an observation is not a
verdict, and nothing forces a grade onto one. The signs are deliberately not
the ▲ ● ▼ of the phase rating and the player marker, which grade a whole
phase or a whole match; two granularities that looked identical in one card
would be worse than no mark at all.

Player notes become two rows rather than one, each with its own mark, for
the case the old shape could not hold: a player who did one thing well and
one thing badly in the same match. Two is the cap — an open-ended list on a
fourteen-player squad is twenty-eight text boxes on a phone. The three-way
marker stays what it was; the notes are the evidence under it. A player with
two notes still gets one timeline entry for the match, with both notes on it.

The mark is a column, not a `+ ` typed into the sentence. That is what lets
the match-analysis trends count it, and it means a bullet a coach opens with
a hyphen stays a hyphen instead of being read as a judgement.

Existing analyses keep every word. Player notes inherit their marker's
verdict — stood out becomes a plus, below par a minus — because with one
note per player the marker *was* the verdict on that sentence; phase bullets
come back unmarked, because nothing in the old data says otherwise. The
previous text columns are left in place, unwritten, as a rollback net for
one release.

Works with no JavaScript: the control is a real radio group, like the phase
rating it sits under.

# TalentTrack v4.109.0 — Paste a screenshot straight into the media uploader (#3092)

On a computer, Ctrl+V (Cmd+V on a Mac) now adds an image from the clipboard to
the upload list, so a screenshot no longer has to be saved to disk and found
again in the file picker. It goes through the same size check, progress bar and
cancel button as a picked file, and is named after the moment it was pasted so a
grid of screenshots stays readable.

Pasting into the video-link box still pastes text there, a paste carrying no
image is left alone, and a target that only accepts documents refuses a pasted
image with the same message it gives a refused upload.

# TalentTrack v4.109.0 — Tag players while you add media, with an @ field instead of a hidden checkbox list (#3093)

Tagging used to live in one place only: a collapsed "Tag players" disclosure on
each photo, after the upload was already finished. The wizard that adds the
media never mentioned tagging at all, so a coach who added eight photos from a
training had to go back to the grid and open a disclosure eight times. Media
that is not tagged never reaches the tagged player's own record, so a control
nobody found was quietly costing the feature its point.

Step 3 of the add-media wizard now has a **Tagged players** field, applying to
everything added in that batch, and the photo's own control is the same field
rather than a checkbox list. Start typing a name and pick from the list, or type
**@** in the description — the name goes into the sentence and the player is
tagged. The chips under the field are the tags; editing the sentence afterwards
never silently untags anyone. The confirm step now says whose records the media
will also appear on before it is saved.

Keyboard throughout: arrows move, Enter picks, Escape closes, Backspace on the
empty box takes back the last chip.

# TalentTrack v4.109.0 — Minutes grid becomes Minutes + statistics (#3094)

Goals and assists could only ever be recorded by running a match on the live
match sheet. A club that does its admin on a Sunday evening rather than with
a stopwatch on the touchline therefore had players whose minutes were
complete and whose output was permanently blank — on the player record, in
the reports, and in everything built on top of them.

The minutes grid now has a **G** and an **A** box beside every **Min** box,
and is called **Minutes + statistics**. Tab runs Min → G → A → next match,
the way the spreadsheet this grid imitates behaves. The Goals and Assists
columns switch off from a chip above the table when a coach only wants
minutes, and that choice is remembered per person.

Two things a manually recorded goal deliberately does not carry. It has **no
minute** — the coach does not know it, and a fabricated 34th minute would
flow into the match timeline as though somebody had watched it happen. And
it **never touches the scoreline**: the score is what happened, attribution
is what we know about it, and letting the second rewrite the first is how
the two came to disagree in the first place. A new footer row reconciles
them instead, reading `2/3` where a goal has no scorer against anyone's name
— information, not a validation gate.

An assist attaches to a goal that is already there rather than inserting one
of its own, which would inflate the team's score. Where no goal is free it
records a goal with no scorer, the honest version of "somebody finished his
pass and I can't remember who". Correcting a count downwards reverses rather
than deletes, and undoes typed entries before live-recorded ones, so a
correction can never destroy something that was actually observed.

Goals recorded this way count exactly like live ones everywhere — the same
store, read by activity rather than through a match execution.

# TalentTrack v4.109.0 — Match analysis share link: see whether anyone opened it (#3096)

A coach sends a match analysis to the staff group and then hears nothing.
The share block now carries one more line — *Seen by 4 people · last opened
2 days ago* — so "did this land?" has an answer that is not a guess.

It counts browsers rather than names: a share page has no login, so there
is nothing to put a name to, and the wording says so. There is no per-visit
log and no way to ask who opened it; a document shared between colleagues
should not double as a record of who looked at it. Link previews from
WhatsApp and Slack are ignored, and the page is no longer cacheable — which
is what makes the count reliable, and which a page naming children should
not have been anyway.

A returning reader is recognised by a first-party cookie holding a random
number and nothing else — strictly functional, no consent banner. Where
cookies are refused, a one-way salted fingerprint of the connection stands
in; neither the address nor the browser version is stored. Those records are
deleted after 90 days, matching the alert retention window and for the same
reason, and the totals survive the deletion so the count never walks
backwards.

The store (`tt_share_views`) is shared: match prep and the team blueprint
mint their links from the identical construction and are a call site each
away from the same line.

# TalentTrack v4.109.0 — Remove a duplicated migration file (#3103)

The team-football-form migration (#3044) shipped as `0243` after being
renumbered from `0242`, but the pre-renumber copy came back into the tree in
an unrelated pull request. Both ran, under two different ids — harmlessly,
because the body is idempotent, but the directory then told the next
migration author a number was taken when it was not.

The stray `0242` file is deleted; `0243` is untouched. No install ever
applied the stray id, because it was caught before the release that would
have carried it.

# TalentTrack v4.108.0 — Uploaded video no longer carries the location it was filmed at (#2611)

Uploaded video no longer keeps the location its camera recorded. TalentTrack
finds the parts of an MP4 or MOV where phones write coordinates and blanks them
before storing the file — without re-encoding, so the picture and sound are
untouched and the file is byte-for-byte the same length. After an upload the
queue says what happened: that location data was removed, or, in the rare case
that the file contains something TalentTrack cannot read, a warning that it may
still say where it was filmed. Photos were already stripped on upload; video was
the documented exception, and no longer is.

# TalentTrack v4.108.0 — The read-only mobile class now actually reads only (#2808)

Ten analysis surfaces are classified `read_only` — readable on a phone,
edited at a desk. The class, its config entries and `isReadOnly()` shipped
with the classification work, but nothing consumed them, so a `read_only`
surface behaved exactly like an ordinary one.

On a phone these surfaces now render without the controls that write. In
practice that is one control: the saved-views strip's save, rename,
overwrite and delete. The reports themselves carry no form that mutates
anything. The apply links stay — applying a saved view is a plain link, and
it is the reason to show the strip on a phone at all — and the script that
only exists to save and delete is no longer loaded there.

Nothing changes on desktop or tablet, the surfaces are not gated behind the
desktop prompt, and `?force_mobile=1` opts out for a visit exactly as it
does for a desktop-only surface. Controls are removed rather than disabled,
and there is no banner: the class means the surface reads on a phone, and a
row of greyed-out buttons says the opposite.

The three conditions every classification gate shares — a phone, the club
setting on, no per-visit override — now live in one place
(`MobileDetector::phoneGateApplies()`) instead of being spelled out
per gate, which is how `?force_mobile=1` would otherwise end up honoured on
one class and quietly ignored on another.

# TalentTrack v4.108.0 — Opening a course on a phone now says why it wants a desk (#2872)

Opening a course or a lesson on a phone now explains itself. The "open on
desktop" page used to say the same generic line on all 41 gated surfaces; for a
course it now says what the surface is actually for — something you sit down and
study, not something you read on the touchline — so the prompt reads as an
explanation rather than a wall.

# TalentTrack v4.108.0 — A course lesson now reads as one document (#2872)

A course lesson now reads as one document. Everything carrying a sentence — the
title, the objectives, the prose, the inline checks, the action line, the
assignment, the quiz and the completion panel — sits on the same reading column,
and only figures, tables and the calculators are allowed to be wider.

Previously a lesson took its widths from four different places, which is why it
looked like several documents pasted together and why the quiz appeared to
collide with the block above it.

The column is also wider than it was. Courses are read at a desk, so a
paperback-width column left most of a laptop screen unused; the reading width
grew along with the text size, and the space that was going spare now goes to
the diagrams and tables that actually need it.

# TalentTrack v4.108.0 — Two plans, and safeguarding is never one of them (#2922)

The plan map has been re-drawn for the 2026 product. TalentTrack now has two
plans — **Standard**, the academy product, and **Pro**, which adds match day,
training, media, the analytics platform and the integrations. There is no Free
plan: the product is hosted, so an install exists because somebody is paying for
it, and "Free" is now only the state of an install whose plan has not been
recorded or has lapsed.

Everything that ships since v3.17.0 had no plan at all, which meant match
analysis, media, training, alerts, courses, tournaments and the analytics
platform all behaved as free. All of it now has a plan.

Two things stay out of the plan on principle. The audit log, permission matrix,
two-factor authentication, record deletion, the recycle bin, media consent and
subject-access requests are on every plan — the safety of children's data is not
an upsell. And a club whose plan has lapsed keeps the dashboard, player cards,
backup and export, so you can always read and take out your own data.

Player count, team count and storage are priced against what they cost to run
rather than bundled into the plan.

# TalentTrack v4.108.0 — Category weights are editable without leaving TalentTrack (#2977)

Category weights are now editable without leaving TalentTrack. Open **Evaluation
categories** and choose **Per-age-group weights**: one panel per age group, with
a running total that tells you where you are and a Save that stays unavailable
until the percentages add up to 100.

This is the setting that decides what an evaluation score *means* — change it and
every overall rating the academy reads is re-weighted — and until now it was the
one piece of evaluation configuration that could only be reached through the
WordPress admin.

An age group you have never configured shows **Equal weights** and counts every
category the same, which is a working state rather than a missing one.
**Reset to equal** puts a configured age group back to it.

The WordPress admin page still works and both write the same data, so nothing
changes for anyone already using it.

# TalentTrack v4.108.0 — The dashboard layout editor is available on the frontend (#2978)

The dashboard layout editor is now available without leaving TalentTrack. Open
**Configuration → Dashboard layouts** and tune what each persona sees when they
log in.

It is the same editor as the one in the WordPress admin, not a second copy —
both read and write the same stored layouts, so it makes no difference which one
you use, or which one somebody else used yesterday. The WordPress admin page is
unchanged for anyone already using it.

It needs a desktop: dragging widgets between three panels has no thumb-sized
equivalent, so a phone gets the "best on a larger screen" page instead.

# TalentTrack v4.108.0 — Team overview and the notification paths now name the same head coach (#2995)

The head-of-development landing's team overview resolved a team's head
coach through the functional role **or** the legacy `role_in_team` string.
Every other head-coach resolution in the product — workflow task assignees
and both alert base classes, which have shared one implementation since
#2719 — uses the functional role alone.

So a team whose coach carried the legacy string without the matching
functional-role assignment was shown a head coach who would silently
receive none of that team's alerts or tasks. The overview said one thing
and the notification engine believed another, and the failure was invisible
in the direction that matters: nobody notices an alert that was never sent.

The fallback was redundant rather than protective. `role_in_team` and
`is_head_coach` are both written from the same functional-role key on every
create and update, so they cannot diverge through the application, and any
legacy row missing its `functional_role_id` has it filled in by the
self-healing backfill that runs on every activation. Dropping the clause
leaves one answer to "who is this team's head coach", asserted by a test.

One asymmetry is deliberate and stays: the overview still names a head
coach who has no WordPress account, where the notification paths skip them.
The widget answers who the coach is; the lookup answers who there is to
email.

# TalentTrack v4.108.0 — One save indicator across the surfaces that save as you work (#3004)

Match preparation's save indicator now uses the shared component that every
autosaving surface will use, so the words a coach reads while their work is
being stored are the same wherever they are. Nothing changes about what is
saved or when.

One thing does improve: two saves can no longer be in flight at once. The old
loop let overlapping requests race, and whichever answered second won regardless
of which was typed second — so a fast typist could occasionally watch a
character disappear. There is now one request at a time, with the next carrying
whatever has been typed since.

# TalentTrack v4.107.0 — Fill the exercise library from a spreadsheet (#2613)

The exercise library can now be filled from a spreadsheet. Above **Add
exercise** there is a link to **Import exercises from CSV**: upload the file,
check the screen listing every row that has a problem and why, and only then
commit. A row that fails does not stop the rest — the good rows are saved and
the failed ones come back as a file with the reason added as a column, ready to
correct and upload again.

Two choices worth knowing. A number outside its range fails its row rather than
being rounded into range, so a column filled in on the wrong scale is reported
instead of quietly rewritten across every row. And imported drills belong to
your team, exactly as they do when you add one by hand — publishing to the whole
club stays the head of development's decision.

The screen asks you to fill in the principles column wherever you can. A drill
with no principles can still be chosen for a training, but the planner can never
prefer it, so a large library tagged with nothing behaves like an empty one.

# TalentTrack v4.107.0 — Log goals live, with a scorer and an assist (#2857)

The scoreboard's **+** now opens a goal sheet instead of nudging a number.
It asks who scored — the players on the pitch first, the bench behind a
toggle — then who assisted, with Save reachable from the first step so a
goal stays two taps for anyone who does not track assists. The minute comes
from the match clock and the half from where you are in the match, and both
stay editable, because coaches tap a goal in half a minute after the ball
crosses the line and because the same sheet is how a forgotten goal gets
added after the whistle.

Nothing in it is mandatory. **Scorer not recorded** and **Own goal** are
there so a goal is never blocked by an attribution nobody can make from the
touchline in three seconds.

**Both scorelines are now counted from the goals you log.** Previously only
the opponent's was; ours was a free-standing number stepped up and down by
hand, with nothing holding it to the goal list beside it — so the scoreboard
could read 3–1 over a single logged goal and neither figure was marked as
suspect. The away stepper was worse: a score set by hand was silently
overwritten the next time any opponent goal was added or removed.

The score is a readout now, and there is no second place to record a goal.
To remove one, undo it in the live progress feed, where what is being
removed is legible. `POST /match-execution/{activity}/score` is removed
with the stepper that called it.

# TalentTrack v4.107.0 — The review shows and corrects who scored (#2858)

Each of our goals in the post-match **Match goals** list now shows the
scorer and, where one was recorded, the assist. A goal logged without a
scorer reads *Scorer not recorded* and an own goal reads *Own goal* —
previously both rendered as "Our goal", which told a coach nothing about
which of the two it was, and only one of them is something to go and fix.

With Edit on, every one of our goals carries a scorer and assist picker, so
a goal saved mid-match without an attribution can be put on a player's
record afterwards — and one attributed to the wrong player at the time can
be corrected. When any goal still has no scorer, the section says so above
the list. It is a reminder rather than a gate: finalizing stays available,
because a coach who never found out who scored still has to be able to
close the match out.

The *Add late goal* form matches the live sheet: the scorer is optional
there too, with an assist field and an own-goal box beside it. A goal added
days later is exactly the case where nobody is certain who touched it last.

Matches recorded before goals were logged individually keep their stored
score untouched. Where that score and the logged goals disagree, the review
now says so in a short note instead of presenting a figure the goal list
cannot account for as though the two agreed.

# TalentTrack v4.107.0 — Goals and assists on the player profile and the minutes report (#2859)

Goals were invisible on a player's record. The match-execution goal log had
no reader anywhere else in the plugin, so a player could score a hat-trick
and nothing on their profile would say so — the product measured a player's
exposure (minutes) and a coach's judgement (evaluations, tracked actions),
but never their output.

The player profile's at-a-glance strip gains a **Goals scored** tile, with
assists on the line beneath it. **Team · Minutes distribution** gains
**Goals scored** and **Assists** columns beside the minutes, so the
exposure-versus-contribution comparison is one row rather than two screens.

The wording is deliberate. In this product "goals" already means a
development objective, and the tile beside the new one counts exactly
those — so the scoring sense says "scored" everywhere the two meet.

Three counting rules decide whose number moves: a goal nobody attributed
counts toward the score but toward no player; an own goal never adds to the
scorer's tally; an undone goal counts for nobody. All three live in one
`GoalContributionQuery`, which the profile, the report and the new
`GET /players/{id}/goal-contributions` endpoint all read, so no two surfaces
can drift into disagreeing about the same player.

# TalentTrack v4.107.0 — The match analysis shows the goals that made the result (#2860)

The analysis readback and the match-execution log describe the same game
and were not on speaking terms. The readback showed how the match ended and
how each team function was rated, but never when the goals came or who
scored them — so the one thing that explains a defending-phase rating lived
on a different screen.

Between the overall read and the phase tiles, the page now lists the goals
in the order they happened: minute, scorer, and the assist where one was
recorded. Minutes run straight through both halves, so a second-half goal
reads as 52' rather than restarting at 22' and the list shows the shape of
the game rather than two separate clocks. They sit above the phases
deliberately — three conceded inside ten minutes is context for the rating
underneath.

Our goals show the scorer; one logged without a scorer reads *Scorer not
recorded*, and an *Own goal* says so instead of borrowing somebody's name.
The opponent's are timed marks with no scorer, since their squad isn't in
the system.

The list is read-only — goals are logged and corrected on the
match-execution screen — and a match with no logged goals renders no goal
section at all rather than an empty list claiming nobody scored.

The same rows are on `GET /activities/{id}/analysis` under `goals`, so the
share page, the print sheet and any other consumer read one list.

# TalentTrack v4.107.0 — The impersonation log can finally be read (#2861)

Every time someone switches into another user's account, TalentTrack
records who did it, whose account they used, when, from where, and the
reason they gave. Until now nothing in the product could show you any of
it — reviewing it meant querying the database.

**Audit log → Impersonation** now lists those sessions. A session that has
not been closed reads **Still open**, because someone being inside another
person's account right now is a different matter from a session that
finished last week.

The tab only appears for people allowed to read it, gated separately from
the rest of the audit log: seeing who opened a child's record is a
narrower permission than seeing who edited what. The same data is
available over the API for academies that pull it into their own
reporting.

Impersonation is what lets staff see a player's full record — medical
notes included — while signed in as somebody else, and the audit trail is
the control that makes that acceptable. A trail nobody can read was not
doing that job.

# TalentTrack v4.107.0 — Live match screen: the sectioned layout is now a real layout (#2935)

The `Sections` value in **Configuration → Match day → Live match screen**
(and its per-coach override in **My settings**) previously resolved but had
nothing behind it. It now renders the layout it names: a match bar holding
the score and the clock that never scrolls away, one scrolling panel, the
state button in thumb reach, and a row of section tabs at the very bottom.

The tabs come from the record spine, so they behave like every other tab
strip in the plugin — arrow keys move and switch, and the strip renders
under both shells. Which tabs you get follows the match: Squad, Pitch and
Log while it is being run, plus Review and Minutes after the final whistle.
The tab that opens is the one with the work in it, and **Review match** on
the state button opens the Review tab instead of scrolling to it. A reload
comes back to the tab you were on, unless the match ended while you were
away — then it opens on Review.

Nothing about the sections themselves changed. The view still renders them
in the order it always has; the sectioned layout captures that output and
re-emits it into panels, so there is one copy of every section rather than
two. `Classic` is untouched and byte-for-byte identical to before in all
six match states, which is what keeps the setting a real rollback.

The `position: fixed` footer is gone under `Sections` — the four regions are
grid rows, so nothing can stack on top of the state button any more.

# TalentTrack v4.107.0 — Leon Hutten visual theme (#2939)

A third visual theme, built from the Leon Hutten Talenten Academie design
handoff. Pick it under Configuration → Appearance → Visual theme, or for
yourself alone under My settings → Theme.

The palette comes from the club crest rather than from a colour picker: the
crest's black banner becomes the header and the navigation rail, its diagonal
cyan-to-navy stripe closes the header and the brand block, buttons and links
take the deep blue and cyan marks the section you are on. Neutrals are
cool-cast to sit with the blues, the status colours are re-tuned to match, and
the whole theme is set in Open Sans with the squarest corners of the three
themes.

Cyan is kept off body copy and small labels throughout — it measures 3.3:1 on
white, which carries an icon, a border or a fill but not running text. The
deep blue that carries every action clears 6.5:1.

As with every theme, it changes appearance only: no permission, field or
button appears or disappears with it, your logo and academy name still render,
and setting the theme back to Default restores your own colour settings
exactly as you left them.

# TalentTrack v4.107.0 — Activity types get proper icons (#2993, #2871)

The little pictures next to an activity's type were emoji, drawn by the
operating system in full colour among a product whose icons are simple
outlines. They looked out of place, printed badly, and changed shape
depending on the device.

All five now come from TalentTrack's own icon set: a football for a match,
a trophy for a tournament, a clipboard for a meeting, a pin for anything
else, and a cone for training. They take the colour of whatever they sit
on, so they work on the type's tinted background and in a printed sheet
alike.

A match and a tournament stay easy to tell apart at a glance, which is
what the activity list needs — a tournament is a day, a match is a
fixture.

# TalentTrack v4.107.0 — Parent mail goes to the sign-in address, not a WooCommerce field (#2997)

When deciding where to email a parent, TalentTrack used to check a
WooCommerce billing address before the address on their own account. That
was left over rather than intended, and it has been removed.

**Some parents' mail moves.** If a parent has a WooCommerce billing
address that differs from their account email, and no email on their
person record, messages that previously went to the billing address now go
to the address they sign in with. Parents who have an email on their
person record are unaffected — that has always taken priority and still
does.

If your academy does use WooCommerce and relied on the old behaviour, put
the address you want on the person record and it will be used.

# TalentTrack v4.106.0 — Every screen now declares what a phone should get (#2812)

The import history screen now sends phone visitors to the desktop prompt page
rather than rendering an undo-a-whole-import control on a handset. It was the
one screen that had shipped without a mobile decision recorded against it, and
an unrecorded screen quietly behaves as though a phone is fine.

Behind that, a build check now refuses any new screen that has not said what a
phone should get. The previous list was written once and then went untouched
through roughly twenty new modules, so most screens ended up defaulting rather
than being decided — this stops that happening again without anyone noticing.

# TalentTrack v4.106.0 — An automated phone check runs on every change (#2813)

It opens each screen
a phone can reach at iPhone width and reports anything that spills off the side
of the screen, any button or link too small to tap comfortably, and any table
too wide to read.

Nothing about the app changes yet — this is the net that catches mobile
problems before they reach anyone, so that the phone fixes now landing stay
fixed instead of quietly coming undone the next time the styling is touched.

# TalentTrack v4.106.0 — Edit and Archive are icons in record headers (#2871)

On a player's profile, **Edit** was a full-width button at the far left of
its own row, with the `⋯` menu at the far right of the same row. It is now
a pencil icon sitting next to the `⋯`, which gives the header back the
space that button was using — space that on a phone was pushing the
player's own details below the fold.

**Archive** is icon-only in record headers too. It keeps its bin icon, its
red styling and its confirmation step; only the word goes.

Both keep their names for screen readers and show them on hover, so
nothing is lost by dropping the visible label.

# TalentTrack v4.106.0 — Team attendance: the drill-down no longer contradicts the row above it (#2893)

On the team attendance report a team could show real figures — 12
activities, 92.9% present — while expanding that same row said there was
no player attendance in the window. Two statements on one screen, and no
way to tell which was true.

Two things caused it and both are fixed. The report and the expansion
disagreed about who may read a team, so an academy admin could see every
team listed and be refused every expansion. And a refusal was being
reported as an empty result, so the screen said "no attendance" when it
meant "not yours to see" — it now says the latter.

Also fixes the row counter on that report, which counted each team twice
by including the hidden row its drill-down loads into.

# TalentTrack v4.106.0 — BMI-for-age report (#2895)

New report: **Player · BMI-for-age**. It reads the height and weight you already
record and places them on the WHO 5–19 growth curve, so a figure means something
at 11 as well as at 16. The latest reading also appears at the top of a player's
Measurements tab.

A BMI is only calculated when a weight and a height were recorded within 30 days
of each other, and every figure states how far apart the two readings were, so
you can judge it. Players with no usable pair still appear, with the reason —
knowing who you have no data for is usually the first thing to act on.

The report shows a position on a curve and how it has moved. It does not label
anyone overweight or underweight, and there are no warning colours: reading a
growth curve clinically is a job for someone qualified to do it.

# TalentTrack v4.106.0 — Recycle-bin rows no longer inflate counts and averages (#2906)

Fixed counts and averages that still included records sitting in the recycle
bin. A player-file tab could show a badge of 3 above a list of 2, a team's
average squad rating could be pulled by a deleted player's scores, and the
player status verdict could be based partly on evaluations nobody can open any
more. Badges, lists and KPIs now agree on what counts as deleted.

The one number that deliberately still counts binned records is the "what will
this take with it?" figure on the archive confirmation, because the cascade
really does reach those rows and understating it would be the more dangerous
mistake.

# TalentTrack v4.106.0 — Attendance statuses stored consistently (#2909)

Attendance statuses are now stored consistently. Different parts of the app
wrote "Present" and "present" into the same column, which left some checks
quietly failing and some screens showing an attendance status without its
colour. Existing records are normalised automatically on update; a status your
academy added itself is left exactly as you named it.

Also fixed the VCT training-load report attributing a session's full load to
players who were only pencilled in for it rather than those who actually
attended. Workload figures for planned-but-unattended sessions were too high.

# TalentTrack v4.106.0 — Team dropdowns follow what you may see, not what you coach (#2911)

Fixed team dropdowns that appeared empty for staff who hold a club-wide view but
coach no team of their own — a head of development could not log an injury, start
a goal, open team chemistry or a team blueprint, filter evaluations, manage a
tournament, or see the top performers, because every one of those pickers asked
"which teams do you coach?" instead of "which teams may you see?". Ten pickers
now ask the right question. The coach dashboard deliberately still shows only
your own teams, since that surface is personal by design.

Editing an activity also no longer risks losing its team: the activity's current
team stays in the dropdown even when it falls outside the editor's usual scope.

# TalentTrack v4.106.0 — Ending and finalizing a match take two taps (#2936, #2917)

**End match** and **Finalize** now ask for a second tap. The first tap arms
the button — it changes colour and label, and a bar drains for three
seconds; a second tap within that window commits, and doing nothing puts
it back with nothing sent.

Ending a match parks the clock and moves it into review, so a mis-tap
during the second half meant correcting the clock by hand on the touchline
with play still going. The ordinary transitions — starting the match,
ending the first half, starting the second — are deliberately left on one
tap, because carrying on undoes them.

Finalize previously asked with a browser dialog; it now uses the same
two-tap guard, so the two irreversible actions behave alike and neither
interrupts the sideline with a modal.

Also fixes the sideline toast, which was positioned from a copy of the
footer's height rather than the height itself. The Undo action inside it
now keeps a visible gap above the footer, and a future change to the
footer can no longer leave it behind.

# TalentTrack v4.106.0 — Admin-only screens explain themselves (#2980, #2981)

The twelve screens that live in the WordPress admin on purpose now say so. Each
one explains, at the top of the page, why it is not in the app — usually because
it is how you diagnose a problem with the app, or how you get back in when the
app itself will not load. Previously there was no way to tell whether you had
taken a wrong turn.

For administrators running the plugin from the command line, `wp tt admin-routes`
lists every WordPress admin page this install registers and whether it has an
in-app equivalent.

# TalentTrack v4.105.0 — Spreadsheet import is its own module (#2955)

The Excel import machinery moved out of Demo data into a new **Import**
module, which appears as its own toggle under Administration. Nothing about
the demo-data workbook changes — same template, same validation, same
Tools → TalentTrack Demo screen — but an academy that switches Demo data off
in production keeps the importer.

The importer no longer decides for itself how the rows it creates are
recorded. That is now supplied by the caller, which is what lets a future
import bring in a club's real squad without those records being treated as
demo data.

# TalentTrack v4.105.0 — Imported club records are no longer treated as demo data (#2956)

A spreadsheet import can now bring in a club's real teams, players and
staff. Those records are tracked separately from generated demo data, in
their own tables, so clearing out demo data can never reach them — before
this, anything the importer created was recorded as demo data and a
routine "wipe demo data" would have deleted it.

An import can also be checked before it is applied: uploading now reports
what the workbook contains, and what needs fixing, without creating a
single record until you say so.

Both are available over the API as well as in the product.

# TalentTrack v4.105.0 — A three-sheet squad template for setting a club up (#2957)

The import template asked for fifteen sheets, which is the wrong thing to
put in front of a club on its first day — a new academy has a squad list,
not a season of history. There is now a shorter version of the same
workbook: Teams, Players and People, with a short guide that explains only
those three.

It is the same format underneath, so nothing is lost by starting small: a
squad workbook and a full workbook import through exactly the same
validation, and a club that does have history to bring across can still use
the full template.

The template's guide sheet also has its headings back. Section titles were
being shown as plain text while the paragraph beneath each one was bold.

# TalentTrack v4.105.0 — Import your squad while setting the club up (#2958)

Setting up a new club no longer means typing in a squad you already have.
The setup wizard has a new **Import your squad** step: download a
three-sheet template, fill it in, upload it.

Nothing is written until you say so. The first upload only reports what
the file contains and anything that needs fixing; a file with problems
leaves the wizard exactly where it was, with the reasons shown. Only when
you confirm are the records created.

If you have no spreadsheet, the step skips in one click. And if the import
did bring teams in, the next step says so rather than asking you to add a
first team you have just added.

# TalentTrack v4.105.0 — Take a whole spreadsheet import back out (#2959)

**Configuration → Import history** now lists every spreadsheet import: the
file, when it ran, who ran it, and what it created. Each one can be undone
in a single action.

An undo removes exactly the records that import created. Other imports,
records typed in by hand and demo data are all out of its reach. Before it
runs you are shown what will go, and if any of those records have been
edited since they arrived, how many — those edits go with them, so the
number is worth reading before confirming.

Undoing an import that has already been undone does nothing and says so.
The history keeps the row either way, so there is still a record that the
file was once brought in.

# TalentTrack v4.105.0 — Messages now go to the address you edited (#2961)

A person's email and phone were stored in two places that nothing kept in
step: the address on the People screen, and the address on their sign-in
account. Different parts of the product read different ones, so editing a
coach's email in TalentTrack could leave their alert digest going to the
old address with nothing to indicate it.

Every message the product sends — digests, alerts, scheduled reports,
trial reminders, workflow tasks, thread notifications, parent
notifications and one-off composed mail — now resolves the address the
same way, and the address shown on the People screen is the one that wins.
People with no sign-in account keep their contact details as before.

Password recovery deliberately still goes to the sign-in account's own
address, so that editing someone's contact details can never redirect
their password reset.

# TalentTrack v4.105.0 — Contact details stay in step with the sign-in account (#2962)

A person's email and phone are now kept the same on their record and on
their sign-in account. Edit either one and the other follows, so the
address you can see is the address the academy's messages actually go to.

If another account already uses the email you enter, the person record
still saves and the sign-in email is left unchanged, with a message
explaining why — rather than appearing to save and quietly doing nothing.
People with no linked account keep their contact details exactly as
before.

On upgrade, records where only one of the two had an address are filled in
from the one that did. Where both had an address and they disagreed,
nothing is changed: those are written to the log for someone to look at,
because silently picking one would redirect somebody's mail.

# TalentTrack v4.105.0 — Accepting an invitation no longer leaves two addresses on file (#2963)

When someone accepted a staff invitation with a different address from the
one the academy typed when adding them, the academy kept both: the guess on
the person record, and the real one on their new sign-in account. Nothing
reconciled them, so messages could go to either depending on which part of
the product sent them.

The address someone actually accepts with is now written back to their
person record. If it replaces a different address, the old value is
recorded in the log rather than discarded, in case the club needs it.

# TalentTrack v4.105.0 — Add people now, send their credentials when you are ready (#2964)

Creating an invitation used to send it in the same moment, which made
setting up a club awkward: adding your coaches meant emailing them
immediately, before you had looked around yourself.

An invitation can now be created and held. Nobody receives anything until
you explicitly send it. Held invitations show in the list as "not sent
yet", with a count and a **Send all invitations** action, or **Send now**
on a single row.

Sending is safe to repeat — an invitation that already went out is skipped
rather than delivered twice — and a bulk send reports how many went and
how many were left alone, rather than one overall result.

Invitations created before this change are unaffected and continue to send
on creation.

# TalentTrack v4.105.0 — Add your staff during setup, and send their logins when you are ready (#2965)

Setup used to leave a new club as a one-person install: every coach had to
be added afterwards, through a screen the admin had not been shown yet.

There is now an **Add your staff** step. Add the coaches and staff who will
use TalentTrack, and an invitation is prepared for anyone you give an email
address to.

**Nobody is emailed while you are still setting up.** Invitations are held
until you send them, so you can finish, look around, and only then let
people in. When you are ready, one action sends them all. Continuing
without sending does not lose them either — they stay ready under
Configuration → Invitations.

# TalentTrack v4.104.0 — The persona switcher no longer takes capabilities away (#1982)

Someone who holds two personas — most often a coach whose own child is in the
academy — lost every staff capability the moment they switched the dashboard to
their second persona. The choice is stored on the account, so the loss followed
them across sessions, browsers and devices, and nothing on screen said why: a
coach would simply find that player notes they wrote last week had disappeared.

Authorization now resolves against every persona a user holds, and any one of
them granting access is enough. The switcher keeps doing what its name says —
choosing which persona the interface is dressed as. To act as another role with
that role's permissions, Impersonation and the matrix Preview page still do
exactly that, visibly, for as long as you leave them on.

# TalentTrack v4.104.0 — Every screen now declares how it behaves on a phone (#2806)

The mobile classification — which screens are built for a phone, which are
readable on one, and which are better left for a desk — now covers every screen
in the product. One was missing: the prospect contact-and-consent form added
earlier in this release.

Invisible in use; it is the record that lets the product decide what a phone
visitor gets.

# TalentTrack v4.104.0 — Correct a prospect's contact details and consent after logging them (#2838)

A prospect could be logged and never corrected. Phone numbers change, emails get
mistyped, and consent frequently arrives a day later by text — and a scout's only
recourse was a message to the head of development, which is exactly the "it lives
in WhatsApp" failure the onboarding pipeline was built to end.

**Edit contact** now opens from the row menu on the Prospects overview and from
the panel that opens when you click a card on the onboarding pipeline. It
corrects the parent or guardian's name, email and phone, and the date consent was
given.

Consent is the half that matters. These are minors, and a consent state that
could not be corrected asserted something about a family that may no longer have
been true, with no way to fix it short of a database edit. Clearing the date to
record a withdrawal is now exactly as easy as setting it to record agreement, and
every change is written to the audit log with both the old and the new value.

Only the contact block and the consent date are editable here — the player's
name, date of birth and how they were found stay as first recorded. Adding a
follow-up note to a prospect you have already logged is still not possible; that
is tracked separately.

# TalentTrack v4.104.0 — Attendance lists stop showing every player twice (#2862)

A player's **Activities** tab repeated entries — the same training listed twice
on the same date — and the tab's badge disagreed with the number in the card
header. On an activity's attendance panel, a fifteen-player squad was listed
thirty times beneath a summary that correctly read *15 / 15 aanwezig*.

A player can hold two attendance records for one activity: the one the planner
writes when the squad is picked, and the one recording what actually happened.
The counts had already learned to ignore the first; the lists beside them never
did.

Both lists now show one entry per player per activity, preferring the recorded
one. A completed activity's attendance panel shows the recorded roster only —
who was expected stops being an interesting question once the register has been
taken — while a still-planned activity continues to show the expected squad,
because that is all there is to show.

Planned activities still appear on the profile tab, which is why the lists were
not simply filtered to recorded rows.

# TalentTrack v4.104.0 — The Activities tab badge counts what the tab shows (#2862)

The number on a player's **Activities** tab counted only activities they had
already attended, while the list beneath it also showed what was coming up. A
badge reading 14 above nineteen rows is the kind of disagreement that makes a
coach doubt both numbers.

The badge now counts exactly what the tab renders, upcoming fixtures included.

Completes the fix started earlier in this release, which stopped the same list
showing every player twice.

# TalentTrack v4.104.0 — Attendance statuses stop showing half in Dutch and half in English (#2863)

One column on the player profile showed *Aanwezig* on some rows and *present* on
others. Two parts of the plugin write attendance status with different
capitalisation — the register writes `Present`, the planned squad writes
`present` — and only the first matched the configured vocabulary. The second
found no match and printed the stored value as-is.

Translation lookups now recognise a value regardless of its capitalisation or
whether it uses spaces, hyphens or underscores, so a status resolves to its
configured label wherever it appears. Status pills already worked this way; the
rest of the plugin now does too, so which screen you are on no longer decides
whether a value is translated.

This corrects the display. Making the two writers agree on one spelling changes
stored data and follows separately.

# TalentTrack v4.104.0 — The printed goal-intake sheet counts matches and minutes correctly (#2864)

The season-intake sheet a coach prints before a goals conversation was showing
numbers that could not both be true — one sheet read 36 matches and 140 minutes,
another 35 matches and 300 minutes, which is under nine minutes a match for a
regular starter.

It was counting every kind of activity as a match, including trainings and
meetings, and counting deleted, cancelled and not-yet-played fixtures along with
the real ones. The minutes were summing planned line-ups next to minutes
actually played. Where a figure looked plausible, it was a coincidence rather
than a sign that half of it was sound.

Both figures now come from the same place the minutes reports read, so the sheet
and **Player · Minutes played** describe the same season. The average rating on
the sheet also stops counting evaluations that were moved to the recycle bin,
which the evaluations list already hid.

This is the sheet that goes on the table at the start of a season-goals
conversation with a player and their parents. Numbers that contradict each other
undermine that conversation before it starts.

# TalentTrack v4.104.0 — Deleted evaluations stop feeding the team squad rating (#2865)

A team profile could show a squad rating — *Selectiebeoordeling 8,3* — for a
team whose evaluations list was empty in every state, including archived. The
evaluations a coach had moved to the recycle bin were hidden from every list
they could open and were still counted in the number on the team's profile.

The list and the number disagreed about what "deleted" means. The list has used
the shared recycle-bin filter since the bin shipped; this KPI still carried its
own older check, written before the recycle bin existed, which knew about
archiving but not about the bin. Both now ask the same question.

A team whose evaluations have all been deleted shows a dash rather than a
number, and restoring one from the recycle bin brings the rating back.

# TalentTrack v4.104.0 — Editing a player no longer removes them from their team (#2866)

A head of development opening a player to change something small — a jersey
number, a date — found the **Team** field showing *— Selecteer —* rather than
the team the player is actually in. Saving the form then took the player off
that team, without warning and without anything on screen suggesting it would.

The dropdown was built from "teams you coach", which is the right question for
an assistant coach and the wrong one for a head of development, who oversees
every team and coaches none. With no options, nothing could be selected, and an
empty selection was saved as *no team*.

The list now follows what the viewer is actually allowed to see: everyone with a
club-wide view of teams gets every team, a team-scoped coach still gets their
own, and **the player's current team is always in the list** whatever the rest
of the rules decide. Saving a form without touching the Team field leaves the
player where they were; deliberately choosing the blank option still takes them
off a team, because that is how it is done.

Archived teams remain out of the list.

# TalentTrack v4.104.0 — Age-category filters only offer categories that have teams (#2867)

The age-category dropdown on the players list offered every category the
academy had ever configured, so a club with two teams scrolled a list where all
but two choices returned nothing.

The filter now offers only categories that actually have teams in them.
Archived and deleted teams do not keep a category on the list, and if you are
already filtering by a category that has just become empty it stays selectable,
so a saved or shared link keeps showing what it showed before.

Forms where you *assign* an age category — creating a team, editing one — still
offer the full list. You have to be able to put the first team into a category
nobody is in yet.

# TalentTrack v4.104.0 — Season rollover only offers teams a squad can actually move up to (#2868)

The **Promote to** column offered every other team in the academy, with no
reference to age group. In a two-team academy that meant the older side was
offered the younger one as somewhere to be promoted to — the only destination it
had was a step backwards.

A team is now only offered as a target when its age group is genuinely older.
The oldest team in the academy gets no targets and keeps *No promotion / stays*,
which is the right answer for a leaving cohort: those players are handled
individually on the next step, as released or graduated.

The ordering comes from the order age groups are arranged in settings, so an
academy that names its categories its own way still gets sensible answers. Where
two categories sit at the same position — a specialist group alongside an age
band, say — neither is offered as a promotion for the other.

Moving a team *down* a category is deliberately still not possible here. That is
a correction rather than a season transition, and a screen whose whole vocabulary
is promotion is the wrong place for it.

# TalentTrack v4.104.0 — Cancel takes you back to where you came from (#2869)

Pressing **Cancel** on a form sent you to a list rather than back to the record
you had opened the form from. Opening the attendance grid from an activity and
cancelling out of it dropped you on the activities list, leaving you to find that
activity again — on a phone at the side of a pitch, several taps from where you
were.

Cancel now returns you to wherever you opened the form from, whenever the plugin
knows. When it does not — you typed the address, or arrived from outside — it
still falls back to the sensible default: the record you were editing, or the
list you were adding to.

This is handled once, in the shared form component, so every form gets it,
including ones added later.

# TalentTrack v4.104.0 — My learning is no longer offered to accounts it then refuses (#2875)

The dashboard offered **My learning** to people whose login is not linked to a
staff record, and the page then told them the section was not available to them
— naming a "staff record", which is not a thing they can see anywhere in the
interface, and giving no way forward. A head of development reading it could not
tell whether they had done something wrong, whether their role was excluded, or
whether somebody else needed to act.

The tile is now hidden for accounts that cannot use it, which is how every other
gated area of the plugin behaves.

Anyone who reaches the page by other means gets a message that explains the
consequence rather than stating a condition: progress cannot be saved, an
academy administrator can link the login under Access control, and every course
is still readable in the meantime — the same shape the course library already
used for exactly this situation.

The library itself is deliberately not hidden: reading a course works without a
linked staff record — only saving progress does not.

# TalentTrack v4.104.0 — Set potential opens on the band the player already has (#2876)

The **Set potential** control on a player's profile opened blank every time,
whatever the academy had on record. It was reading the band from the player row,
where it has never been stored — potential is kept as dated history — and a
missing value there fails quietly, so the control simply showed nothing and
nobody was told why.

Blank is not a harmless starting point for this particular judgement. A coach
who cannot see what the academy currently thinks records a fresh opinion instead
of a revision, and because every save adds a dated entry, a player's potential
history filled up with restatements that read like changes of mind.

The control now opens on the current band and says when it was set and by whom,
so a coach can tell whether the standing judgement is recent enough to be worth
revisiting. Choosing the same band again and saving no longer adds anything to
the history — though re-affirming a band *with* a note still does, because
"still first team, but the last six weeks have been flat" is worth recording.

# TalentTrack v4.104.0 — The access control matrix is reachable from Settings (#2880)

The permission matrix has been editable from the front end since it shipped, but
nothing anywhere linked to it — the only way in was typing the address by hand.

It now appears in **Settings**, under System, for the academy admins who can
manage it. That was the point of building it: an academy admin should be able to
correct a permission that is too broad or too narrow — the grants that decide
who can open a player's evaluations, notes and medical fields — without needing
a WordPress account.

# TalentTrack v4.104.0 — The knowledge library is now called Courses (#2883)

The coach-education surface was called two different things depending on where
you stood. The feature switch already said **Courses**; the tile, the page title,
the breadcrumbs and the module card said **Knowledge library**.

"Library" also promised more than is there. The surface holds courses and nothing
else, so the wrapper named a container with one kind of thing in it — and
competed with Methodology for the same territory.

It is called **Courses** everywhere now, and its address is `?tt_view=courses`.

**Bookmarks to the old address will stop working.** If you have saved a link to
the knowledge library, or sent one to a colleague, it needs updating. Links from
inside the product all point at the new address.

*My learning* and the three *Learning · …* reports keep their names — those
describe a person's own record of study rather than the catalogue.

# TalentTrack v4.104.0 — CI checks that every dashboard tile can actually be opened (#2885)

A dashboard tile names a destination. Nothing checked that the destination
exists, so a tile could be registered pointing at a screen the product does not
route — and the board would show the feature as done.

A new check fails the build when a tile's screen has neither a route nor its own
link. Tiles that deliberately open something else, like the VCT session designer
opening its wizard, are recognised as such rather than reported.

Developer tooling; nothing in the product changes.

# TalentTrack v4.104.0 — Module cards stop stretching, and long ones fold away (#2890)

On the Modules and features page, every card in a row was stretched to match the
tallest one. Because sub-feature counts are so uneven — Reports has twenty-one,
most modules have none — a row could show one full card beside two that were
mostly empty space, with the *Includes* line stranded at the bottom.

Cards now take their own height, and a module with more than four sub-features
shows a count you can expand rather than listing all of them. Reports no longer
occupies most of a screen while the modules beside it go unread.

Expanding works with the keyboard and needs no mouse, and the panel starts closed
at every screen size — opening it by default on a phone would put the same wall
back where it hurts most.

# TalentTrack v4.104.0 — CI refuses a committed git conflict marker (#2891)

A conflict marker once reached the main branch as a literal line of
`docs/rest-api.md`, committed while somebody resolved a merge. It sat there for
three days, and in the meantime every branch that touched the same file and
pulled main failed to merge — git cannot combine a file that already contains a
marker, so each of those had to be untangled by hand.

Twenty workflows already lint this repository and none of them read file bodies,
so nothing caught it going in. A new check now scans the whole corpus on every
pull request and fails with the file and line number.

It deliberately looks only for the `<<<<<<<` and `>>>>>>>` markers, never for a
bare row of equals signs on its own: in Markdown that is a heading underline,
and a seven-letter heading gets a seven-character underline, so no length test
can tell the two apart. Git always writes all three markers together, so the
angle brackets are enough to catch a real conflict without ever flagging prose.

Developer-facing only — nothing about the product changes.

# TalentTrack v4.104.0 — Share a match plan with staff who have no account (#2892)

A head coach who had laid out the lineup, the goals per phase and the notes per
player could only get that sheet to an assistant coach, an analyst or a keeper
coach by printing it or sending a PDF — and a PDF is out of date the moment a
starter changes.

**Share plan** now produces a link. Whoever you send it to opens it and reads the
plan without logging in and without an account on the install, and they see it as
it stands rather than as it was when you sent it.

It is read-only, kept out of search engines, and revocable: **Replace link**
issues a new one and stops the old one working immediately, for everyone who has
it. Worth knowing that the link itself is the key — anyone holding it can read
the plan, so it belongs with the people who need it rather than in a group chat.

The same affordance match analysis has had since it shipped, on the surface that
needs it before the match rather than after. Sharing can be switched off for the
academy under Modules and features, separately from match-analysis sharing.

# TalentTrack v4.104.0 — A player record can record sex, for growth references (#2894)

Age-adjusted height, weight and BMI are read against published growth curves,
and those curves are separate for boys and girls. The player record had date of
birth, height and weight but nothing about sex, so none of those age-adjusted
figures could be calculated at all. This adds the field the **Player · BMI-for-age**
report needs.

It is deliberately narrow. The field is labelled for what it is used for, the
help text says why it is asked, and the list is fixed at male and female because
a growth reference publishes exactly two curves — an editable list would suggest
the reference follows it, which it does not. This is not a record of how a young
person describes themselves and should not be used as one.

**Optional everywhere, and blank on every existing player.** Nothing is filled in
or guessed from a name. Leaving it blank costs that player only the age-adjusted
columns; height, weight and raw BMI still read normally.

Available on the player form, the WordPress admin player page, the API, and the
demo-data import and export, so a generated academy keeps the field on a
round-trip.

# TalentTrack v4.104.0 — Groundwork for BMI-for-age: the WHO growth reference (#2895)

A raw BMI says very little about a growing child — the same figure is
unremarkable at seventeen and high at nine. To mean anything it has to be read
against a growth curve for the player's own age and sex.

This adds that curve: the WHO 2007 reference for 5 to 19 year olds, generated
directly from the tables WHO publishes rather than typed in by hand, together
with the arithmetic that turns a BMI into a percentile.

It also adds the part that pairs up measurements. A BMI point comes from a
weight and a height taken within a month of each other; a weight with no recent
height produces no point at all, rather than being combined with a height from
last season and quietly reported as current.

Nothing is visible on screen yet — the report itself follows. Recording a
player's sex is what unlocks it, and that stays optional.

# TalentTrack v4.104.0 — Privacy and security pages: documentation points at their real address (#2921)

The privacy and security operator guides, the access-control reference and
the trust-page sources referred readers to `talenttrack.app/privacy` and
`talenttrack.app/security` for the DPA, the sub-processor list, the hosting
region and the public privacy policy. Those pages live at
`mediamaniacs.nl/talenttrack/privacy` and `mediamaniacs.nl/talenttrack/security`.
Every reference now points there, in English and Dutch. Documentation only —
no behaviour, data or query changes.

# TalentTrack v4.104.0 — Plans are set by your operator, not bought in the plugin (#2923)

TalentTrack no longer carries a marketplace integration or an in-app trial. Which plan an install is on is recorded when the install is provisioned; the install keeps a local copy so it keeps working normally if that record is briefly unreachable, and falls back to Free when there is none.

For operators this replaces the Freemius adapter and the 30-day trial → 14-day grace state machine with a single entitlement the plugin reads and never writes. The Account page drops the "Start trial" and trial-state panels and now says what the next plan adds, with plan changes going through the operator. Every feature gate, the free-tier caps and the REST enforcement behave exactly as before.

Installs running as non-commercial test instances — which is all of them today, with `TT_COMMERCIAL_MODE` false — are unaffected: every feature stays unlocked and no caps apply.

# TalentTrack v4.104.0 — RecordSpine gained in-page tabs, and they survive the classic layout (#2932)

The shared record spine could only render tabs that navigate: each one was a
link, and following it reloaded the page. A surface whose sections are really
one record seen several ways had no compliant way to switch between them
without a round trip, and the only alternative was to hand-roll a tab strip —
which is exactly the drift the shared component exists to prevent.

A tab entry can now name a `panel` instead of a `url`. Those render as real
tab buttons wired to panels already on the page, with arrow-key navigation and
the correct assistive-technology roles, and switching between them costs no
request.

They also render under the classic navigation layout, where the rest of the
spine does not. The identity strip is navigation chrome and disappearing with
the shell is correct; a section switcher is the only way into the content
behind it, so a screen whose sections vanished would simply be missing half of
itself.

# TalentTrack v4.104.0 — The navigation bar steps aside on the live match and training screens (#2933)

Running a live match and running a training session both put their own controls
along the bottom of the phone screen, because that is where your thumb is when
you are holding it one-handed at the side of a pitch. The navigation bar sat
underneath those controls and took roughly 190px of a 640px screen with it —
about half of what you were actually reading.

On those two screens the bar now steps aside. The breadcrumb trail at the top is
still the way out, the slide-out menu is untouched, and tablets and laptops are
unaffected, because the bar only exists on phone-width screens to begin with.

Which screens qualify is written down rather than assumed: each one is listed
with the reason it needs the space, and adding another is a one-line decision
somebody has to make on purpose.

# TalentTrack v4.104.0 — The live-match screen has two layouts, and coaches can pick their own (#2934)

The live-match screen is getting a new layout: the score and the clock fixed at
the top, a row of tabs within thumb reach at the bottom, so the bench is one tap
away instead of three scrolls. The old single-page layout is not going anywhere
yet, and nobody is moved onto the new one without asking.

Configuration → Match day sets the academy default. My settings → Live match
screen lets each coach pick their own, including "use the academy default",
which keeps following the academy setting when it changes.

The default stays the single-page layout, so nothing changes until somebody opts
in. That matters more here than almost anywhere else in the product: a coach
runs this screen one-handed, on a phone, at the side of a pitch, with a clock
running. Moving them onto an unfamiliar layout mid-season is how minutes and
substitutions go unrecorded. Put one coach on the new layout for a Saturday,
see how it goes, then flip the academy.

# TalentTrack v4.103.0 — Every screen now says whether it is meant for a phone (#2807)

Until now the app had only ever been told about twenty-five of its screens:
a handful were marked "needs a desktop", three were marked "built for a
phone", and the remaining hundred and twenty-five were treated as phone-
friendly by default — not because anyone had decided they were, but because
nobody had said otherwise.

All 151 screens now carry an explicit answer, each with a sentence saying
why. Fifty-six screens that a phone could open and shouldn't have — bulk
grids, permission matrices, imports, integration setup — now say so and
offer to email the link instead. Twenty-eight are recognised as phone-first
and get the mobile layout properly. Analytics, Reports and Usage statistics
open on a phone for the first time.

Academies that would rather their people squint at a desktop layout can
still switch the whole thing off in Configuration, and tablets are never
affected.

# TalentTrack v4.103.0 — Goals can carry an assist, and no longer have to name a scorer (#2856)

A logged goal now records who assisted it as well as who scored it, and
either can be left unrecorded. Until now a goal for our team was refused
outright unless it named a scorer, which left a coach who did not see the
final touch — or an own goal, which has no scorer on the side it counts
for — with nowhere to put it.

The REST surface follows: `POST` and `PATCH /match-execution/{activity}/goal-event`
accept `assist_player_id` and `is_own_goal`, and the PATCH corrects the
attribution as well as the minute. The two halves of that payload are
independent, so correcting a minute cannot silently drop a scorer, and
attributing a goal cannot reset its minute. A scorer or assist naming
somebody outside the match squad is refused, as is a player assisting
their own goal.

Erasing a player now clears them from the goals they were involved in
rather than deleting those goals. The behaviour was always documented as
such, but the goal's scorer column is `NOT NULL` and so fell through to a
cascade delete — which, with the score about to derive from these events,
would have quietly rewritten the result of a match already played.

Existing goals read back exactly as before: attributed, with no assist.

# TalentTrack v4.103.0 — The **Features** page (`?tt_view=features`) is now a capability catalog rather than a flat list. It opens with a summary of how much of TalentTrack the academy is running, then splits into **In use** and **Available to switch on**, each grouped by category, with every card carrying the module's icon, its written name and a line on what it's for. Always-on core modules and developer tooling no longer clutter the page, and anything marked *under development* that isn't switched on is left out — a feature that's flagged but already live still shows under **In use** with its amber pill. The page stays read-only: no card toggles anything or links into the management page. The catalog is also available over REST at `GET /talenttrack/v1/feature-catalog`; the existing `/feature-status` endpoint is unchanged.

The **Features** page (`?tt_view=features`) is now a capability catalog rather than a flat list. It opens with a summary of how much of TalentTrack the academy is running, then splits into **In use** and **Available to switch on**, each grouped by category, with every card carrying the module's icon, its written name and a line on what it's for. Always-on core modules and developer tooling no longer clutter the page, and anything marked *under development* that isn't switched on is left out — a feature that's flagged but already live still shows under **In use** with its amber pill. The page stays read-only: no card toggles anything or links into the management page. The catalog is also available over REST at `GET /talenttrack/v1/feature-catalog`; the existing `/feature-status` endpoint is unchanged.

# TalentTrack v4.102.1 — Help topics corrected where they described things the product does not do (#2549)

Three topics were telling admins to do impossible things.

**Impersonation** said the audit log is read at a REST endpoint that does not
exist, and described a cross-club safeguard that is not implemented. It now
says plainly that every impersonation is logged and that the log has no reader
surface yet — which is a gap worth knowing about, not one to discover while
trying to review who looked at a player's record.

**Custom widgets** and **Modules** both told an admin to enable the builder
with a `wp option update tt_custom_widgets_enabled 1` command. That option was
replaced by a feature toggle; following the instruction did nothing. Both now
point at Access control → Features.

Alongside that, the remaining changelog voice is gone from the topics a coach,
player, parent or academy admin reads: version stamps in headings, issue
numbers in prose, and "this used to work differently" asides that left a reader
unable to tell current behaviour from history.

# TalentTrack v4.102.1 — Dutch help topics can no longer go missing unnoticed (#2550)

No user-facing change. The documentation gate now also checks translation
parity: every topic a coach, player, parent or academy admin can read must have
a Dutch twin, that twin's title and summary must actually be translated, and
its group, audience and ordering must match the English.

The corpus was brought to parity earlier in this epic. These two rules are what
keep it there — before them, a topic could quietly ship English-only and nobody
would find out until a Dutch coach opened it.

# TalentTrack v4.102.0 — The Help icon opens help for the screen you are on (#2547)

The Help icon previously opened "Getting started" on about 117 of the 144
screens, because the mapping was a hand-maintained list that covered 27 of
them.

Each help topic now declares which screens it serves, and the map is derived
from that — so 142 of the 144 screens resolve to a relevant topic. The
remaining two are the help page itself and personal account settings, both
deliberate. Where help does fall back to the default, it now says so instead of
quietly showing the wrong topic.

Also fixed: the Workflow screens and the PDP screen opened help on topics that
did not exist, and fell through to "Getting started".

# TalentTrack v4.102.0 — Forty-two help topics that existed only in the repository are now readable in the app (#2548)

Match day (match preparation, live match, minutes, attendance and ratings
grids), Planning (team planner, training plans, tournaments, season rollover)
and Operator guide (security, privacy, the photo DPIA, telemetry) are new
sections in Help & Docs; measurements, injuries, the exercise library, bulk
exports, the data browser, the audit log and the six Configuration sub-pages
join the existing ones.

The three persona guides — head coach, head of development, scout — were
internal working notes and are now written for the people they are named after:
what you actually do across a season, in the order it happens, with Dutch
translations.

# TalentTrack v4.102.0 — Support docs read as documentation, not release notes (#2549)

Help topics were written in changelog voice: version stamps, issue numbers and
"used to / now / before" constructions running through prose a coach reads on a
phone at the side of a pitch. A reader could not tell whether a paragraph
described how the product works or how it once worked.

Every user-facing topic now describes current behaviour in present tense, with
the version history left where it belongs — in the changelog. Forward-looking
claims are resolved rather than softened: the player-status weights turned out
to be admin-configurable already, and the doc said they would be "in a future
release".

# TalentTrack v4.102.0 — Dutch help: the DPIA translated, and the English fallback says so (#2550)

The install is Dutch and the help corpus falls back to the English file when a
topic has no Dutch twin — silently, so a coach got English with nothing to
explain why. A small line above such a topic now says it has not been
translated yet. The fallback itself is unchanged: an English topic beats no
topic.

The photo-capture DPIA is now available in Dutch, which matters because it is a
document a Dutch academy admin has to read and sign. The English text stays the
authoritative version and the translation says so.

The Dutch twins of every help topic rewritten in the same release were rewritten
alongside them, so the two languages describe the same product rather than
drifting a release apart.

# TalentTrack v4.102.0 — A CI gate so the help corpus stops drifting (#2551)

No user-facing change. Adds `docs-lint.yml` + `tools/check-docs.php`: a gate over the documentation
corpus. It checks that every doc is either registered or explicitly dev-only,
that the front-matter keys resolve to real modules, features, tiers and
capabilities, that every routable screen is claimed by a help topic or listed
as deliberately unclaimed, that cross-references and deep links point at things
that exist, that reader-facing topics do not gain version stamps or issue
numbers, and that every file is valid UTF-8.

Fixed two broken cross-references the gate found on its first run:
`phone-home.md` linked to a doc that lives in another repository, and
`workflow-engine.md` linked to `sessions.md`, which was renamed to
`activities.md`.

# TalentTrack v4.102.0 — TileRegistry declares the shape it actually accepts (#2816)

No user-facing change. `TileRegistry::register()`'s declared array shape
described an older API — it required `slug` and `url`, which no caller passes,
and omitted `view_slug`, `label`, `module_class` and five more keys that nearly
all of them do. Every tile registration in the product therefore failed the
static-analysis gate and sat in the baseline, where each entry embeds the
literal array of its call site: editing any field of any tile produced a fresh
unbaselined error.

The shape now follows the method's own defaults, and 490 baseline lines went
with it.

# TalentTrack v4.102.0 — Activity detail: four buttons, and a ⋯ for the rest (#2830)

A planned match offered eleven header buttons across two wrapped rows, all the
same size, so the two that mattered were indistinguishable from the six that
support them. The header now carries what you came to do — Edit, match prep,
complete, cancel on a planned activity; the analysis and Archive on a completed
one — and folds everything else behind a **⋯** menu on the same row.

The three grids and *Sync team from Spond* render as icons: they are shortcuts
into a bulk surface and a maintenance action, not decisions competing with
Complete and Cancel. Inside the menu they get their words back, and every one
still names itself to a screen reader and on hover.

The menu opens on click, Enter and Space with no JavaScript, closes on Escape
or an outside click, and returns focus to its trigger. Nothing becomes
reachable by being folded away — every action keeps its capability, feature and
archived-record rules — and the menu does not render at all when everything in
it is hidden.

Also: *write the match analysis* no longer appears on a match that has not been
completed. It read "played" as "dated today or earlier", which offered writing
up a match that kicked off at seven that evening.

# TalentTrack v4.102.0 — Match prep shows the principles the match is linked to (#2831)

The screen where a coach writes what the team should do on Saturday could not
see what the academy had decided the team is working on. Match prep now carries
a read-only **Principles** panel above the goal boxes, showing the activity's
linked principles in the same O / A / V pills the activity page uses — so the
goals are written against the principle rather than from memory.

They are read from the activity, not picked again: one place answers "which
principle is this match about". A match with none linked gets a line saying so,
linking to the activity's edit form. The principles print, appearing on the
paper team sheet and in the PDF export above the goals, and a new
`GET /activities/{id}/principles` returns the same list.

# TalentTrack v4.102.0 — Player · Minutes played: only played matches, translated type, styled link (#2832)

The report listed fixtures that had not been played yet — a match kicking off
this evening appeared as a row with an em-dash for minutes and counted toward
"Matches in roster". A match now counts once its activity is marked completed;
activities recorded before the status field existed fall back to the calendar.
The rule lives in one place, shared with the team minutes report, so the two
cannot disagree about what "played" means.

Two smaller fixes in the same table: the Type column showed the raw storage key
("game", "tournament") instead of the translated activity type, and the match
name was a bare underlined link rather than the record-link treatment the rest
of the reports use.

# TalentTrack v4.102.0 — Team · Minutes distribution: what counts as played, and one squad (#2833)

The report counted a fixture kicking off this evening as already played, so a
team with one played match read "1 of 2 played matches recorded" and carried an
amber warning that a match nobody had kicked off yet was missing its minutes. A
match now counts as played once its activity is completed — or, for academies
with the guided flow switched off, as soon as it carries recorded minutes,
since those are evidence it happened.

The *Matches recorded* tile and the squad beneath it also disagreed: minutes
recorded against a player who has since been archived were counted by the tile
and dropped from the squad, which is how the report could show one recorded
match above zero players and an empty state claiming no minutes existed at all.
Both now describe the same squad.

# TalentTrack v4.102.0 — Attendance per player: an honest drill-down, and counts beside the percentages (#2834)

Clicking a player's activity count opened the activities list without the
report's activity-type filter, so a count of three trainings landed on a list
that also held matches and meetings — and the list gave no sign it had been
narrowed at all, because the player and the date window arrived as hidden form
fields. The type now travels with the drill, and a **Showing only:** strip above
the filter bar names the player and the window, each with an × that clears that
one constraint.

The table reads differently too. Present keeps its percentage and gains the
fraction behind it — *33,3% (1/3)* — because over three activities a percentage
alone is noise. Late, Absent, Excused and Injured show the count instead: two
missed sessions read as **2**, and the columns sort numerically.

# TalentTrack v4.102.0 — Minutes share: what percentage of the available minutes did each player get (#2835)

Every minutes report answered in absolutes, and 350 minutes looks fine until
you know the team played 700. **Team · Minutes share** is the missing relative
figure: every played match's own length summed into a denominator, each
player's recorded minutes over it, and the squad ranked lowest share first.

The denominator is every match the team played, not the ones each player was
available for — a player who missed six weeks injured shows a low share, which
is the honest number, and shrinking the denominator per player would hide
exactly the case the report exists to surface. Match length comes from the
match prep, the age-group default, or 35 minutes a half, so a team on
30-minute halves gets 600 available over ten matches rather than a flat 700.

Every player should reach a minimum share of the playing time — 30% by default,
editable under Configuration → Match minutes. Anyone below it is flagged with a
glyph and the words, never colour alone. `GET /teams/{id}/minutes-share` and
`GET /teams/{id}/minutes-share/{player_id}` return the same numbers.

# TalentTrack v4.102.0 — Match analysis: rating glyphs with one legend, not fifteen repeated words (#2836)

Writing a match analysis meant reading "Went well · Mixed · Needs work" once
per phase — fifteen times across the team functions and set pieces — which made
the phase name the smallest text on the card. The three choices now show as
▲ ● ▼, sized to the same 48px target, with the vocabulary stated once in a
small legend on the line that introduces the phases.

Nothing is lost: every button still carries its label as its accessible name
and as a hover title, the selected one is marked by a ring rather than by
colour alone, and the printed sheet and share page still write the rating out
in words.

# TalentTrack v4.102.0 — Test trends: a change between each pair of measuring moments (#2837)

The report carried one change column, last reading versus first. With three or
more measuring moments that flattened exactly the shape a coach opens it for: a
player who gained 2 kg and lost 1,5 kg read identically to one who gained 0,5
steadily.

A Δ column now sits between each pair of dates, carrying the move from the
previous moment with the same direction-aware glyph the overall column uses;
the last column is headed **Total** and still spans every moment the player
has. A missing reading on either side shows as an em dash rather than being
stretched across the gap. Status and pass/fail reports are unchanged — a step
between two categorical readings is not a delta.

# TalentTrack v4.102.0 — Team · Minutes distribution and Squad evaluation summary showed an empty squad (#2849)

Both reports listed their players with a query that selected `tt_players.name`
— a column the table has never had; it carries `first_name` and `last_name`.
The database rejected the statement, the result came back as nothing, and each
report rendered an empty squad rather than an error anyone could act on. That
is what the pilot saw as "1 match recorded, 0 players in selection".

Both queries now build the name from the two real columns. A regression test
runs each statement and fails on a database error, because the KPI counts that
existing tests assert on are computed by different queries that never touch
`tt_players` — which is how this survived three rounds of fixes to the numbers
beside it.

# TalentTrack v4.101.5 — Help topics follow what the install actually runs (#2546)

A topic whose module is switched off, whose feature toggle is off, whose tier
is above the licence, or whose capability the reader lacks is gone from the
table of contents, the search box, the help drawer and its own URL — not shown
with an "unavailable" badge. An academy that turns Methodology off stops seeing
the Methodology guide; a Free install no longer lists Pro documentation; a
coach without access to the Data Browser is no longer walked into a
permission-denied screen by its guide.

Turning anything back on restores its topics on the next page load, and a
topic that declares none of these keys is unaffected.

# TalentTrack v4.101.5 — The app shell's top bar fits on a phone again (#2802)

With the app shell switched on, the top bar was wider than the screen on
every single page — the search box, notification bell, demo badge, version
number, help button and account chip together needed far more room than a
phone has, so the whole page could be dragged sideways and the bar took up
two rows.

On a phone the bar is now one row: search collapses to a magnifier that
opens a full-width field when tapped, and the version number and demo badge
step aside. The bar went from 127 pixels tall to 69. Desktop is unchanged.

# TalentTrack v4.101.5 — The navigation drawer opens with the navigation in it (#2803)

Opening the menu on a phone showed a list of group headings and nothing
else — every group arrived collapsed, so an academy admin saw fourteen
headings and none of the sixty-six destinations behind them. On a small
phone a player's whole menu sat behind a single closed heading.

The menu now opens with its destinations showing. The fold arrows beside
each heading are also easier to see.

# TalentTrack v4.101.5 — Parents can move around the app without re-picking their child (#2804)

A parent linked to more than one child was sent back to the "choose a
child" screen on every single tap — including on their own settings and on
the help pages, which have nothing to do with a child. Having chosen a
child, the next tap asked again.

The chooser now appears only where a child actually has to be chosen, and
once one is chosen the app stays with that child as the parent moves
between their sections.

# TalentTrack v4.101.5 — Smaller fixes to the app shell on phones (#2805)

Three things in the app shell's phone layout. The menu button in the top
bar was slightly under a comfortable tap size. The space reserved above the
bottom bar was one pixel short of the bar itself. And the "get the app on
your phone" banner took up 337 pixels — around 40 per cent of the screen —
before any content, because its three buttons each claimed a full row.

The menu button is full size, the bottom bar no longer overlaps, and the
banner is down to 263 pixels with its buttons sharing rows.

# TalentTrack v4.101.4 — Buttons on a phone are back to a full-size tap target (#2796)

Buttons across the app were rendering 44px tall on a phone instead of the
48px the design calls for, and the smaller variant only 32px — under a
third of a fingertip. The rule setting the correct size was being undone
further down by the very stylesheet meant to look after the phone layout.

Buttons now meet the intended size on every handset. Desktop is unchanged.

# TalentTrack v4.101.4 — Help and account buttons in the top bar are now full-size (#2797)

The help button and the account chip in the dashboard top bar were slightly
under the size a fingertip needs. Because both appear on every screen in the
app, they were the most-missed targets in the product.

Both now meet the intended size. The top bar itself is unchanged in height.

# TalentTrack v4.101.4 — Breadcrumb links are now a proper tap target (#2798)

The breadcrumb trail at the top of every screen had links only 19 pixels
tall — hard to hit on a phone, and they are how you get back to a list.

Each crumb is now a full-size target. The trail still reads as one line of
text and takes only a little more room than before, because the extra
height replaces the spacing that used to sit above it.

# TalentTrack v4.101.4 — Wide tables scroll on their own instead of dragging the page sideways (#2799)

Five screens — Spond, Strava admin, Translations, Season rollover and the
alert policy diagnostics — had a table wide enough to push the whole page
sideways on a phone, so the header and everything else slid off the screen
with it.

Those tables now scroll within themselves and the page stays put. Nothing
changes on a desktop.

# TalentTrack v4.101.4 — List controls and record links are now full-size tap targets (#2800)

Opening a player, team or activity from a list meant hitting a link about
19 pixels tall, and the pagination underneath every shared list was smaller
still — 26-pixel page buttons and a per-page selector to match. Checkboxes
sat at the browser's own 13-pixel default with nothing sizing them.

All three now meet the intended size on touch devices. Desktop density is
unchanged.

# TalentTrack v4.101.4 — Expandable section headers are now a proper tap target (#2801)

The headers you tap to expand a section — advanced options, permission
groups, the dashboard's own tile groups — were as little as 19 pixels tall,
because nothing in the stylesheets ever gave them a size.

They now meet the intended size on touch devices, and keep their expand
arrow. Desktop is unchanged.

# TalentTrack v4.101.3 — The static-analysis gate can fail again (#2103)

Nothing changes in the product. This is about the check that is supposed to
catch a certain class of crash before it reaches an academy — the one that
missed the undefined variable behind the activities-form fatal in v4.63.2, and
reported success while doing so.

It could not have caught it. The step was configured to report success
whatever it found, it was never told what WordPress functions are so most of
what it saw was noise, and two thirds of the codebase sat behind a rule that
silenced every message from it. Each of those hid the next.

All three are fixed. The 3820 problems it can now see are recorded as known, so
the check passes today and fails on anything new — which is what it was always
described as doing.

# TalentTrack v4.101.3 — Four tiles that led nowhere are fixed (#2788)

Four dashboard tiles were offered to people the surface behind them then turned
away. They looked like one bug and were three.

**Holidays** stays for the people who manage the academy calendar and stops
appearing for coaches, who were only ever shown it because their planner needs
to know when the holidays are — and it still does.

**PDP planning** now opens for team managers. It is a read-only overview of
planned versus held talks, so there was never a reason to require the right to
edit a plan in order to look at it.

**Workflow templates** and **Invitations** are administrator configuration and
now say so. The head of development was being offered both and refused by both;
neither shows anything they can act on, so neither is offered any more.

# TalentTrack v4.101.2 — Saved-views form stays collapsed until you ask for it (#2793)

On thirteen list views — teams, players, people, goals, evaluations and the
rest — the "save these filters" form was permanently expanded, pushing its
name field and Save button off the side of a phone screen. Its label, meant
only for screen readers, was rendering as ordinary visible text and wrapping
across four lines.

The form now stays collapsed until "Save filters" is pressed, and wraps
inside the screen when open. The visually-hidden label style is defined once
for every dashboard surface rather than on one view, so labels intended for
screen readers stop showing up as stray text elsewhere too.

# TalentTrack v4.101.1 — Tiles nobody can open are now caught before they ship (#2008)

A dashboard tile is offered on one permission and the surface behind it
sometimes demanded a stronger one, so a coach could be shown a tile that
refused them the moment they clicked it. It had happened four times, each fixed
individually, each time discovered by the person it happened to.

The check now runs on every change: it walks every registered tile, works out
which roles are offered it and which of those the destination would turn away,
and fails the build when a new mismatch appears. The four that exist today are
recorded rather than silently allowed — each needs a decision about whether the
surface should open read-only or the role should not be offered it at all, and
that is a judgement per surface rather than a mechanical change.

# TalentTrack v4.101.1 — A hidden surface is no longer reachable by typing its address (#2570)

The dashboard decides which surfaces to offer you from what each one declares
it needs. That declaration governed the menu only — open the address directly
and the check was never made, so a surface your role is not offered could still
be opened by someone who knew or guessed its URL. Seven of those were closed one
at a time; this closes the reason they kept appearing.

The dashboard now asks the same question before opening a surface that it asks
before listing it, so the two cannot answer differently again. Nothing changes
for anyone opening surfaces they were already offered. Pages reached from inside
another surface — a wizard step, a record's detail page — are unaffected: they
have no menu entry of their own, and their own checks still apply.

# TalentTrack v4.101.1 — The academy admin can open the authorization matrix again (#2776)

The frontend matrix editor shipped refusing the very role it was built for. An
academy admin opening it was told they did not have permission, and the same
refusal came back through the API.

The permission it needed had been described twice in the same list, once as
"may edit" and once, further down, as "may reset". The second description won
silently, so the editor asked for a privilege deliberately reserved for a
WordPress administrator. Resetting the matrix is unaffected and stays where it
was — it is checked separately, and was never part of this.

# TalentTrack v4.101.1 — Page-header actions no longer run off the side of a phone screen (#2789)

On narrow screens the action buttons beside a page title were laid out at
their combined natural width instead of the width actually available. On an
activity detail page that put eight of nine actions — including the one that
opens match execution — more than a thousand pixels off the right edge, where
a coach could only reach them by scrolling the whole page sideways.

The action slot now shrinks to the room it has and the buttons stack, so
every action is on screen at 360px and above. Desktop layout is unchanged.

# TalentTrack v4.101.1 — Weekly planner PDF no longer carries the browser's URL footer (#2791)

Saving the weekly planner sheet as a PDF put the browser's own header and
footer on the paper — the page URL and page number along the bottom, the
document title and date across the top. The sheet handed its whole paper
margin to the page box, and that margin is exactly the band a browser prints
those into.

The sheet now carries the 14mm margin as its own padding and leaves the page
box at zero, so there is nowhere for the band to print. Nothing on the sheet
moves: the printed geometry is identical to before, minus the browser's
additions. Same approach the goal-intake and methodology-reference print
sheets already use.

# TalentTrack v4.101.0 — A head coach no longer sees the Scouting visits tile (#2007)

Planning and logging outbound scouting visits is the scout's work; a head coach
was being offered it because the tile authorised on the same `prospects` record
type as their own onboarding funnel, and the two had been inseparable since an
unrelated fix in v4.20.2.

The tile now has a visibility setting of its own, granted to scouts, the head of
development and the academy admin. The head coach **keeps the onboarding
pipeline for their own age group** — that was deliberate, and removing the
prospects grant to hide the visits tile would have taken the funnel with it.
Direct navigation to the visit planner and to a single visit is refused for the
same personas the tile is hidden from.

Nothing changes for a scout, a head of development or an academy admin. An
upgrade backfills the new setting, so the tile does not disappear while the
matrix catches up.

# TalentTrack v4.101.0 — The authorization matrix is editable without a WordPress account (#2654)

An academy admin can now edit the persona × entity grid from
**Configuration → Authorization matrix**, gated on a new
`tt_manage_authorization` capability granted to administrator and Club Admin.
Until now the only editor was in wp-admin behind an administrator account, so
an academy without one on hand could not correct an over-broad or too-narrow
grant at all — and those grants decide who can open a player's evaluations,
notes and medical fields.

**What a Club Admin cannot do is the reason this could ship.** Their own
persona row is locked, and so are the entities that govern the permission
model, the schema and the backups. The lock is enforced when the save is
applied, not merely in the markup: a hand-crafted form post or a direct REST
call against a protected cell is rejected and writes neither a matrix row nor a
changelog entry. Reset-to-defaults, the seed export/import round-trip and the
matrix on/off switch were not delegated and stay administrator-only in
wp-admin — which also keeps that page as the recovery path, since a bad matrix
edit can hide the frontend surfaces that lead back to the matrix.

The save-and-audit logic moved out of the wp-admin controller into a shared
`MatrixEditService`, so the two screens and the new REST routes
(`GET`/`PUT /authorization/matrix`, `POST /authorization/matrix/reset`) write
identically. Behaviour for a WordPress administrator is unchanged on both
surfaces.

# TalentTrack v4.101.0 — A training photo now waits on the phone when there is no signal (#2735)

Out of range the capture screen used to say nothing was sent and leave the coach
to retake the photograph later. It now keeps the image on the device and reads it
the moment the connection returns, landing on the same checking step as always.
It survives a reload and a browser restart, and a count of what is waiting shows
on the capture screen and on the training plans page, for the coach who walked
away from the camera.

No plan is ever created without the coach — the promise that nothing is saved
until they press the button holds for a photo that waited exactly as it does for
one taken with full signal. A waiting photo stays on that phone and nowhere else,
is deleted as soon as it has been read and checked, and is dropped after seven
days whether or not anybody looked at it. The screen says so rather than letting
it vanish quietly: a coach who believes their training is safe should not find out
weeks later that it was not. `docs/photo-capture-dpia.md` records the device as a
processing location and closes the retention prerequisite that was open there.

# TalentTrack v4.100.0 — Exports read in Dutch, values as well as headers (#2012)

The column titles were
already translated; the cells under them were not, so a squad list opened in
Excel showed *Status* over `active`, *Rol* over `coach`, and
`["CB","LB"]` where the player's profile says *Centrale verdediger /
Linksback*. Since the export is usually the file that leaves the academy — to a
parent, a federation desk or the board — that was the most visible place for a
raw database code to surface.

Players list, team roster and season stats, attendance register, staff directory
and the goals export all now carry the same labels as the screen. A position
code an academy added itself, with no label yet, still shows as the code rather
than disappearing. The demo-data round-trip, the full backup and the
subject-access export keep their raw values on purpose — something reads those
back.

# TalentTrack v4.100.0 — "Review" on an old PDP verdict reads as a state, not an instruction (#2696)

The
word was translated once for the whole product, and the sense that won was the
wizard's — *check what you entered before saving*. On a PDP verdict carried by
an older plan it means something else: the plan still has to be looked at. It
now reads **Te beoordelen** there, alongside *In behandeling*, while every
wizard review step keeps the sense it needs. The periodic conversation about a
player is unaffected — the product already calls that a PDP-gesprek, and always
did.

# TalentTrack v4.100.0 — Match prep PDF: dark block over the second-half pitch (#2756)

Exporting the match-preparation PDF while the page was scrolled — which it
always is, since the grid is taller than the window — painted a hard-edged
dark block over part of a half-pitch, usually the second half. The pitch
colours themselves were never wrong: the image capture was given the wrong
scroll offsets, so the pitch's drop shadow landed inside the pitch instead
of around it, and the pitch clipped it into a block over the line-up. The
capture now uses the page's real scroll position, and the pitch joins the
other surfaces whose shadow is dropped for the export. Both half-pitches
come out the same light blue again; the on-screen view is unchanged.

# TalentTrack v4.100.0 — A privacy registration that quietly covered nothing (#2758)

The PII registry listed
evaluation ratings against a player column that table does not have — a rating
reaches a player through its evaluation, not directly. The registration was
therefore doing nothing, while the registry reported it as covered. Ratings were
never missing from an erasure or a subject-access export, because both already
follow the parent evaluation, but the registry now says so honestly. A test
checks every registration against its table, so the next one fails the build
instead of going quiet.

# TalentTrack v4.100.0 — A translation can no longer silently revert to English after a merge (#2765)

The translation catalogues carry git's union merge driver so parallel branches
stop conflicting on them; the cost is that a union merge takes both sides. Once
the i18n sync has relocated a branch's appended entries into their sorted
position on main, merging main back leaves the branch holding both copies — and
git reports no conflict, because nothing disagreed. It happened four times in
one day on four separate branches.

Duplicate entries are what the compiler refuses, so one reaching main can break
the compiled translations for every language. The quieter case is worse: when
the two copies disagree, one translated and one emptied, gettext takes the first
and the Dutch string reverts to English with no error anywhere.

A new check fails any pull request that duplicates an entry the base branch does
not, and names the strings. It runs as pure PHP so it can be reproduced on any
machine, understands translation contexts (a contextual entry sharing a string
with its plain twin is not a duplicate), and ignores obsolete blocks the way
gettext does.

# TalentTrack v4.100.0 — One intensity scale across the product, 1 to 7 (#2767)

Three parts of the product
disagreed about it: the exercise form offered ten levels, the handbook said
five, and the engine, the shipped drills and the age profiles all used seven.
That is not a cosmetic difference — intensity is the number the age-safe ceiling
is compared against, so a coach rating a hard drill against a documented 1–5
marked it a 5 when the shipped equivalent was a 6 or 7, and a session that
should have raised a warning for a player raised none.

The form, the VCT library and the age-profile editor now all read one scale, so
a band no age group can accommodate cannot be chosen at all. The handbook now
also says what the bands *mean* — 5 is a normal training block, 7 is as hard as
any age group should ever go — because a range on its own does not tell a coach
how to rate consistently.

# TalentTrack v4.100.0 — The match day team sheet has its own switch (#2769)

One setting used to gate
both match-prep exports, so an academy that files match forms digitally could
only hide the **Wedstrijdformulier afdrukken** button by also losing **PDF
exporteren** — the sheet the coach actually takes to the touchline. They are two
documents for two readers: the coach's carries the plan, the referee's carries
identity and eligibility.

**Match day team sheet** is a new feature under Match prep, on by default, so
nothing changes for an academy that still hands paper to the referee. Switch it
off and the button leaves the toolbar, the print URL refuses, and the
server-side export on the Exports page stops offering it — while the coach's own
PDF is untouched. That last part is new too: the server-side team-sheet export
previously ran whatever the toggle said.

# TalentTrack v4.100.0 — Saving an activity no longer empties its Line-up card (#2771)

A match prep writes
its Starting XI and bench through onto the activity's attendance rows, and the
activity detail's Line-up card — and the match-day team sheet's fallback — read
nothing else. Saving the activity rewrote those rows to store status and notes
and silently dropped the two line-up columns along the way, so the card went
blank while the prep itself was untouched. Re-opening the prep still showed the
line-up, which is why this read as a card that disappeared rather than as data
that was lost.

The rewrite now carries the line-up across on both paths, planned and completed.
An explicit starter tick on the completion form still wins: the coach who ticked
the box on this save meant it.

# TalentTrack v4.99.1 — Confirm dialogs say what they do, tournaments stop offering match prep, and the line-up card fits (#2684, #2686, #2763)

**Confirm dialogs finally carry their own words.** Reopening a completed
activity asked *"Archive record"* behind a red *"Archive"* button while the
message underneath correctly described reopening. The per-action title,
label and button colour were being assembled and then dropped on the way to
the dialog, so every action wore the archive defaults: Reopen, Restore and
Sync from Spond all read as destructive.

That also brings back a feature nobody could reach: archiving a team offers
its **"archive this team's activities too"** checkbox again. Because the
checkbox never appeared, its answer was sent as *no* on every single team
archive, whatever the coach intended.

**Tournaments no longer offer match preparation or the live-match screen.**
The buttons were there and the screens behind them refused to open — a dead
end. They are gone rather than fixed, because a tournament is usually
several games in one day and match prep holds one line-up, one availability
list and one set of player goals per activity: it would have quietly
described a whole tournament as a single fixture. Tournaments keep the
minutes grid and per-player minutes entry, which handle a multi-game day
correctly.

**The line-up card on a match now spans the full width of the detail page.**
It was sharing a row, then splitting again into Starting XI and Bench, which
left each player about a quarter of the page and truncated names to things
like `#4 M...`. Names render in full now, and the Expected attendance card
beside it shrinks to its own content instead of being stretched to the
line-up's height — as do Notes, Linked principles and the other short cards.

# TalentTrack v4.99.0 — Photo and video consent can now be recorded against a player (#2744)

The media library stores photographs of children and had no way to record
whether the family had agreed to it. Academies were tracking that on paper
with nothing in the system to check against before a matchday.

Each player record now carries a **Photo & video consent** checkbox on the
edit form, beside the photo. Ticking it stores the date and the name of the
staff member who recorded it, so the entry is evidence rather than a bare
assertion. Clearing it removes both. The player's profile shows the answer
to staff — including when the answer is no, since a blank would read as
though nobody had asked.

**This records; it does not restrict.** Nothing about adding a photo checks
the box, and a coach can still add media for a player with no consent on
record. That is deliberate rather than unfinished: the real control is the
conversation and the form the family signed, and a hard block at the side of
a pitch tends to be worked around by photographing on a personal phone
instead — which leaves the child worse off than a recorded gap does. What
the field is for is answering *who may we photograph?* before the day, and
being able to show the question was asked.

Withdrawal is recorded by clearing the box. It does not reach back and
remove photographs already stored; those are removed from the player's
Media tab.

# TalentTrack v4.99.0 — Long media galleries load in pages (#2745)

A player, team or activity with a lot of photos rendered every single one
at once. On a phone after a full season that is a heavy page for no good
reason — nobody scrolls to August while looking for last weekend.

Galleries now show the 24 most recent items with a **Show more** button
underneath, adding the next 24 each time until there is nothing left and
the button disappears.

It is a button rather than loading more as you scroll, on purpose: the
oldest photo stays reachable, the browser's back button keeps working, and
there is nothing small to aim at with a thumb. Keyboard users land on the
first newly-loaded item rather than being thrown back to the top, and a
screen reader is told when more has arrived.

The count on the Media tab still reflects everything held for that player,
not what is currently on screen.

# TalentTrack v4.99.0 — Match analysis reads as one page, and sharing it is now a decision (#2748, #2749)

The finished analysis is a document instead of a stack of half-empty cards,
and the screen, the share link and the print sheet are now the same
document — so what you look at is what the person you send it to sees.

**Two chains instead of six cards.** The phases sit in two columns that each
read top to bottom: with the ball (attacking → the instant we lose it → our
own set pieces) and without it (defending → the instant we win it →
theirs). A transition only means something read next to the phase it comes
out of. Each phase carries its own points inside its own tile, so the
qualification and the specifics sit together rather than in a separate list
further down, and a phase nobody rated is a thin placeholder rather than a
full-size empty card.

**Set pieces split by side.** They shipped merged, which put a note about our
corners in the same box as one about defending their free kicks. Splitting
them also restores an exact 1:1 mapping with the four goal boxes on the match
plan, so every planned line now appears beside the phase it was planned for.
Anything already written under the merged section moves to the attacking side
and keeps its text.

**The rating moved onto the phase's own heading.** It was four full-width
pills in the same white-on-outline as the text fields below them, which read
as a row of empty inputs — and the selected one always turned green, whatever
you picked. It is now three compact chips carrying the rating's own colour,
with **Clear** appearing once something is set.

**A share link is no longer created just by opening the page.** It was:
merely looking at an analysis wrote a live, working URL nobody had asked for,
on a document that names children. Now there is **Create share link**, and
once it exists you get the URL with **Copy link** beside it and a **Replace
link** action that says plainly that the current one stops working.

**Saving keeps you where you are.** It used to reload the page and jump the
scroll to the top, which reads as having been taken somewhere else; your text
and the print and share actions stay put now.

The printed sheet is landscape A4, built to fit one page, and still real
selectable text rather than an image.

# TalentTrack v4.99.0 — Classify the exercise library in bulk (#2753)

An exercise with no principles is never suggested by the generator, and time
spent on it does not count towards what your players have been taught. Neither
consequence is visible from the library list — which is why, on a typical
install, most of the library has no principles at all and has stayed that way.

The library now says how many exercises are waiting and offers **Classify them**.
That screen is built around one action: **tick several exercises, choose the
principles they train, apply once.** A classified exercise carries around eight
principles, so doing it one at a time is hundreds of separate saves. Selecting a
whole category and applying in one go is the difference between an afternoon and
a fortnight.

Exercises are grouped by category, since drills of a kind usually train the same
principles, and **Select all shown** takes a whole group.

**"None apply" is what lets you finish.** Warm-ups, cool-downs and conditioning
work mostly should not carry a tactical principle — a warm-up does not train
building up from the goalkeeper. Marking them as looked at keeps them out of the
list, so the count reaches zero instead of showing you the same warm-ups forever.

Two things it will not do to you: adding principles never removes the ones an
exercise already had, and replacing only affects the methodology you are working
in — if your academy runs more than one, the others are left exactly as they
were.

# TalentTrack v4.99.0 — Match prep PDF: no more placeholder text or stray buttons on paper (#2755)

The exported match-prep PDF no longer prints the grey hint text from empty
fields. An unfilled goal line comes out as a blank ruled line instead of
"Doelstelling 2…", and a player with no note prints an empty cell instead of
"…". The `×` that clears a set-piece player and the `→` that copies the first
half's line-up to the second no longer print either — they're on-screen
controls, not part of the team sheet.

The export is an image capture rather than a browser print, so it never read
the print stylesheet that already handled all of this. The placeholder half
could not be fixed from CSS at all: the capture engine ignores `::placeholder`
and paints an empty field's `placeholder` attribute as ordinary text, which is
why the hints came out darker on paper than they look on screen. The attribute
is now removed from the capture clone, which every surface using the shared
image-export module benefits from. Nothing changes on screen or in the browser
print dialog.

# TalentTrack v4.99.0 — The academy logo top-left now links to the dashboard (#2764)

Clicking the crest and academy name in the top-left corner returns you to
the dashboard, in both the classic header and the app shell's sidebar —
including the collapsed icon rail. Installs without a logo get the same
behaviour on the gold initials mark. The link carries the academy name as
its accessible name and is reachable by keyboard; nothing else about the
header changes.

# TalentTrack v4.98.0 — Finishing a course now shows on your staff record, and courses can be assigned (#2649)

Until now, completing a course was something the knowledge library knew about
and nothing else did. Two changes fix that.

**Completing a course issues a certificate.** It appears on the coach's staff
record and their PDP, alongside their UEFA badges and safeguarding training,
and the academy-wide certificate overview picks it up. Nobody has to type
anything in — finishing the last lesson is what puts it there.

A course can say how long its certificate stays valid; the periodisation course
does not, so it does not expire. Where a course does set a period, the existing
expiry overview and the reminder about expiring certificates handle it exactly
as they do for any other certificate.

If a reviewer later withdraws their approval of an assignment, the course stops
being complete and **the certificate is withdrawn with it**. It is not deleted —
it shows as archived, because it was genuinely issued — but it no longer counts
as a live qualification. A certificate standing on work that was retracted would
be worse than no certificate at all.

**You can assign a course to several people at once.** "Assign a course" walks
through picking the course, picking the staff, and setting an optional
deadline, then shows what is about to happen before it does it. Staff are
filtered to the people the course is written for; if nobody matches — usually
because staff records are not linked to accounts yet — it shows everyone and
says why.

Assigning the same course to the same person twice does nothing, and the
confirmation step tells you so up front: "3 people will be enrolled, 12 are
already on this course and will be left as they are." Existing deadlines are
never quietly overwritten.

**And a team question you could not ask before:** which of the staff around a
given squad have finished a given course, including the ones who never started
it. That is the point of putting completions on the staff record — every player
in a squad has an interest in the person running their training being trained
themselves.

# TalentTrack v4.98.0 — Three new reports: who has done which course, and where people get stuck (#2650)

The knowledge library has recorded progress since it shipped, but there was
nowhere to see it across the academy. Three reports now sit in the Reports
launcher alongside the others.

**Learning · Course completion** — per course: how many are enrolled, not
started, in progress, completed and overdue, the median number of days people
take, and **the lesson readers stop at**. That last column is the one worth
looking at: it is the only number here that says something about the course
rather than about the coaches. A lesson half the group stops before finishing
is usually badly written, badly placed, or asking for something they cannot do
yet — and a completion percentage never tells you that.

**Learning · Per person** — who is on what, how far they have got, what is
overdue, and what is sitting with a reviewer.

**Learning · Staff coverage per team** — for each squad, how much of the staff
around it has finished each course. *The U13 staff are 2 of 4 on the
periodisation course.*

Three levels of access, as elsewhere in the module. A coach sees **their own
record** — not an error page — and needs the learning-statistics permission to
see anyone else's. That is enforced behind the scenes as well as on screen, so
the numbers cannot be reached another way.

Overdue and coverage are shown as labelled tags — "3 overdue", "All trained",
"2 to go" — rather than colour alone, so the tables read correctly for anyone
who does not distinguish red from green. The course report exports to CSV with
readable values rather than internal codes.

# TalentTrack v4.98.0 — Activity header actions follow the activity's status (#2685)

The activity detail page offered the same buttons whether the activity was
still to come or long finished: a completed training showed Edit, "Run this
training" and "Continue rating" alongside Reopen. Status is now read before
the action list is built, so a completed or cancelled activity gets read
affordances instead of an invitation to start work over.

Edit is offered on planned activities only — Reopen is the way back to an
editable record. Match prep reads "View match prep" once the match has been
played, and on a planned match its label finally reflects reality: "Plan
match prep" when no prep exists, "Match prep" once it does. "Start match"
and "Resume match" no longer appear on a finished activity; "View match"
is the only execution label left there. A finished training reads "View this
training", and shows no run button at all when no plan was ever attached.

The rating button now says what is left to do — "Rate players" when nobody
has been rated, "Continue rating" when some have — and disappears once every
attending player carries a rating, taking the completed-training header from
seven buttons down to six. Completing an activity does not rate anyone by
itself, so the button stays available after completion until the work is
actually done; the Ratings grid button remains either way.

# TalentTrack v4.98.0 — Alerts clear the moment you fix the thing (#2731)

An alert used to linger for up to an hour after you had dealt with it. You
marked the session completed, recorded the attendance, assigned the head
coach — and the banner, the bell and the alerts list all carried on saying
otherwise until a background job next ran. The engine was right and the
screen was stale, but from where you were sitting the product simply looked
wrong about your own data.

Alerts are now re-checked at the moment the record changes. Fix the thing
and the next screen you land on no longer mentions it. That holds for every
alert TalentTrack ships: past activity still planned, attendance
unrecorded, no coach assigned, player not evaluated, evaluation window
closing, evaluation never shared, goal past its target date, PDP cycle with
no conversation, player turning 18, parent never activated, staff
certificate expiring, no measurement this season, player without a team,
team without a head coach, and stale invitations.

The re-check runs after the page has been sent to you, so saving is no
slower than it was — including on the attendance grid, which can touch a
whole squad in one go. A save like that counts as one re-check, not forty.
Very large operations, such as importing players or rolling over a season,
deliberately leave the recount to the hourly job rather than performing
hundreds of them while you wait.

The hourly background check has not gone away and still matters: it is the
only thing that notices a condition that became true because time passed
rather than because somebody saved something — a certificate reaching its
expiry date, an invitation nobody answered, a session slipping past the
point where its attendance is overdue.

Alongside this, several parts of the app that changed records quietly now
announce it, which anything extending TalentTrack can listen for:
`tt_activity_saved`, `tt_activity_attendance_changed`,
`tt_measurement_result_saved`, `tt_staff_certification_saved` and
`tt_pdp_conversation_saved`. Evaluations created through the evaluation
wizard now fire `tt_evaluation_saved` as well — they never did, so
automatic follow-up tasks configured against that event were only ever
created for evaluations saved the other way. They now fire for both.

# TalentTrack v4.98.0 — Course reader: uses the screen, and asks you questions as you go (#2737, #2738)

Two changes from working through the periodisation course on a laptop.

**The reader now uses the width.** Lessons were capped at a paperback column,
so on a normal laptop screen more than half the window sat empty while the
week planner and the load matrix were squeezed into it — and the widest tables
scrolled sideways with 700 pixels of space beside them. The text keeps a
comfortable reading width, because very long lines are genuinely harder to
read, but everything that is a tool or a table rather than a sentence now
spans the full page. Phones and tablets in portrait are unchanged.

**There are now questions throughout the lesson, not just at the end.** Every
module used to be a stretch of reading followed by one quiz and one practical
assignment you carried out days later with your team. The reading was fine;
there was just nothing to do while you did it.

Each lesson now has three to four **quick checks** dropped into the text at the
points where people go wrong. Pick an answer and you are told straight away
whether it is right, with the reasoning either way. They are not marked and not
recorded — they do not count towards completing the lesson and they appear in
no report. They exist to interrupt: committing to an answer before you see the
right one is what makes something stick, and quietly nodding along does not.

Forty-two of them across the course, in Dutch, on the things that actually
catch coaches out: the 72 hours before a match, halving minutes rather than
partijen, why the conditietraining sits on day 3 or 4, and why overload before
the growth spurt costs you the damage without the benefit.

The end-of-module quiz is unchanged and still counts. A handful of the old
"think about it, then open" boxes have become real checks, since those asked
you to consider a question and then let you read the answer without ever
committing to one.

# TalentTrack v4.98.0 — The growth-spurt warning works again (#2739)

The printed training sheet carries a warning naming players whose growth-spurt
intensity ceiling is below the hardest block in the plan. **It was not appearing
— on any plan that was not built by the generator.**

A block took its intensity from the exercise only when the generator put it
there. Plans built in the plan builder, through the API, or from a photographed
sheet stored no intensity at all, and the check read that as "intensity zero"
rather than "intensity unknown" — so it concluded there was nothing to warn
about and printed nothing.

Nothing was wrong with the plans. What was wrong is that a sheet with no warning
looked exactly like a sheet that had been checked, and a coach had no way to tell
the difference.

Now:

- A block takes its exercise's intensity when it has none of its own, so the
  check has something to work with. This applies to plans you already have — no
  re-saving needed.
- **A plan where no block has any intensity recorded now says so on the sheet**,
  in grey, rather than printing nothing. If you see nothing, the check ran.
- An intensity you set on a block yourself is never overwritten — an adapted or
  walked-through version of a hard drill stays as you rated it.
- The same value now reaches the sideline view's record of the session.

If your exercises have no intensity set, this is worth doing: it is what turns
the growth-spurt warning from silent into useful.

# TalentTrack v4.98.0 — An uploaded photo now appears straight away (#2742)

Adding a photo or video from a player, team or activity page reported
"Added" and then showed nothing. The upload had worked and the file was
safely stored, but the grid below only picked it up when the page was
reloaded — so the natural reaction was to try again, and end up with the
same photo twice.

Uploads now appear at the top of the grid the moment they finish, complete
with their thumbnail, the Remove button and, on an activity, the control for
tagging the players in the shot. Adding several at once shows each one as it
lands, and the first upload into an empty gallery replaces the "No photos or
video yet" message rather than sitting behind it.

# TalentTrack v4.98.0 — A subject-access export now accounts for photographs and video (#2743)

When someone exercised their right to see everything the academy holds on a
player, the export left out photographs and video entirely — while the
academy went on holding them. Everything else was covered; media had simply
never been added to the list of places a player's data lives.

The export now includes a `media.json` listing every photograph and video
held of that player: what it is, when it was taken, what it was attached to
and who added it.

The files themselves are deliberately not included. A season of video runs
to gigabytes, and an export too large to produce helps nobody. The export
says this in as many words rather than staying silent about it, because a
list with no explanation reads as though there is nothing to hand over —
which would be worse than the omission it replaces. An academy that is asked
for the files sends them on separately from the player's Media tab.

Media belonging to a team or an activity stays out of an individual's
export, even where that player was present. Those belong to the team or the
session rather than to one child, and mixing them in would disclose other
families' photographs to someone with no right to them.

Erasure was never affected — deleting a player has always deleted their
photographs.

# TalentTrack v4.97.0 — Photograph a hand-written training and get a draft back (#2502)

The last wave of the training module. If your academy has photo reading switched
on, **From a photo** on the training plans page turns a sheet you wrote out by
hand into a draft plan: photograph it, check what was read, create it.

**Nothing is saved until you press the button that says so.** Close the page at
the checking step and there is no plan, no blocks and no photograph anywhere.

The checking step shows how sure it is about each line — green for a confident
match, amber for one worth a second look, red for a line it did not recognise.
An unrecognised line stays as a loose block, and the screen says what that costs:
it will not count towards what your players have been taught, because that count
is built from matched exercises. Names and durations can be changed before the
draft is created, and a line that was never really there can be removed.

**Where the photo goes is on the screen, beside the shutter, before you take it.**
Your administrator decides where photographs are sent to be read; until that
choice has been made this screen refuses to open and says so, rather than sending
anything. If you have no signal nothing is sent, and the screen tells you that
too.

Everything the wave needed on the server was already built and had never had a
screen — the extraction, the matching against your library, and the draft-plan
write. This is that screen.

**Not yet:** holding a photograph on the phone while you are out of range so it
reads itself on your way back. Today you retake it.

# TalentTrack v4.97.0 — The sideline view keeps working when the signal drops (#2552)

Pitches are where signal is worst, and until now a coach who lost it mid-session
lost that session: block timings and observations typed into a form that then
failed to save. That is the exact failure that sends people back to paper.

Now those writes are kept on the phone and sent as soon as there is a connection
again. A line at the top of the sideline view says how many are waiting —
*"2 wijzigingen wachten op bereik"* — and it survives locking the phone,
switching apps and reloading the page.

**Nothing is recorded twice.** If a change reaches the server but the reply is
lost on the way back, the phone tries again, and the second attempt lands on the
same record instead of creating a duplicate. That matters more than it sounds:
these numbers become each player's training minutes, so a change applied twice
would put a wrong figure on a child's development record.

A change that still cannot be saved after reconnecting — because you were away
long enough for your login to expire — stays queued rather than being discarded.
Reload the page and it goes.

**Opening the page still needs signal.** What this protects is a session already
underway; starting one from nothing with no connection is a separate thing and
is not covered.

# TalentTrack v4.97.0 — Alerts: see whether the engine is running, and which alerts people ignore (#2634)

The **Alert policy** screen now opens with an engine-health panel.

It answers the question nothing else can: a background job that has stopped
produces exactly the same screens as an academy with nothing wrong — empty
ones. If alerts have not been checked recently, scheduled tasks are not
running on the site and every alert screen is frozen at whenever they last
did.

Underneath it, a table per alert: how many are open, how many were cleared,
and what share people simply dismissed.

That last figure is the point. An alert most people dismiss is not informing
anyone — it is teaching them to dismiss alerts, and the useful ones go with
it. Anything above about 60%, over enough occurrences to mean something, is
flagged for review. Nothing is switched off automatically: whether an alert
earns its place is a judgement about your academy, not a calculation.

Also available through the API at `/alerts/diagnostics` for anyone wanting to
monitor the engine externally.

# TalentTrack v4.97.0 — Knowledge library: hand in a practical assignment, and review one (#2648)

Every module of the periodisation course ends in a practical assignment the
coach runs with their own team. Until now those were something to read. They
can now be handed in, and somebody reviews them.

At the bottom of a lesson's assignment there is a box to write your answer in
and a button to hand it in. If you prefer to be walked through it, "Hand in an
assignment" does the same thing in three steps — write, attach, confirm. Either
way you see afterwards exactly where the work stands.

Your submission goes to your mentor if you have one. If you do not, it goes to
a shared queue that anyone who manages the knowledge library can pick up, so it
never sits waiting on one specific person who happens to be on holiday.

Reviewers get a new **Assignments to review** page, oldest first, with the
original assignment shown above each answer so there is no need to go and look
up what was asked. Three decisions are available: approve, ask for changes, or
do not approve. Asking for changes sends it back and lets the coach revise and
hand in again — the earlier version and your feedback both stay on the record.
A reason is required for anything other than approval.

Approving is what completes the lesson. Withdrawing an approval un-completes
it again, so a course cannot stay finished on work that was later retracted.

**Attachments are documents only** — PDF, Word, spreadsheet, OpenDocument or
plain text. Photos and video are deliberately not accepted on an assignment: a
submission is attached to no player and no team, so a photograph handed in here
would sit outside the consent and visibility rules that protect players in the
rest of the system. What the assignments ask for is written work.

A new alert tells reviewers what is waiting. It is not an email per submission
— it shows what is in your queue right now and disappears by itself when you
have cleared it. Anything left for more than a week is flagged more prominently.

# TalentTrack v4.97.0 — Correction: photo capture is not off by default (#2695)

The v4.96.0 notes and the photo-capture DPIA both said the
`exercises_vision_extraction` feature was off on a default install, and the DPIA
leaned on that as a safeguard. **It is on by default.** That statement was wrong
and has been corrected.

Nothing about your install's actual safety changes. A site that has not set
`TT_VISION_ENDPOINT` and `TT_VISION_DATA_REGION` still sends nothing — the
endpoint answers `503` and no photograph leaves the server. The protection is
real; it just comes from the destination declaration rather than from the
feature flag.

What this means in practice: if you were relying on the feature being off, it is
not, and you should switch it off explicitly. Simply leaving the two destination
settings unset already prevents anything being sent, and remains the thing the
DPIA treats as the deliberate act a signature authorises.

A test now compares the document against the code's actual default, so the two
cannot drift apart again.

# TalentTrack v4.97.0 — Photo-capture DPIA: the legal decisions are recorded (#2695)

Legal clearance for photo-to-plan capture was given on 2026-08-23, and the DPIA
now records what was decided rather than listing what was outstanding:

- **Lawful basis: consent** (Art. 6(1)(a)), given by the parent or guardian,
  with the reasoning written down — the data subjects are children, and
  legitimate interest would have the academy weighing its own convenience
  against a child's privacy and marking its own homework.
- **No in-product consent step.** Consent is captured at registration. An extra
  tap on the capture screen would look like consent while collecting it from the
  wrong person: the coach is not the data subject.
- **A photo held on a phone lives at most 7 days.** Nothing is held today —
  capture shipped online-only — so this is the number the feature will be built
  to when holding lands.
- **Provider terms confirmed** by the data controller.

Two blanks remain for the academy to complete at signing: where consent is
captured, and how it is withdrawn. The product cannot know either.

The feature still cannot send anything until an administrator sets
`TT_VISION_ENDPOINT` and `TT_VISION_DATA_REGION`.

# TalentTrack v4.97.0 — Links across the app now land on the right page (#2720)

On academies whose dashboard is not the site's front page, a scattering of
links quietly went to the wrong place — the theme's homepage, with no error
and nothing to explain it. The destination only ever reads its instructions
on the page that hosts the dashboard, and these links were pointing at the
site root instead.

Twenty-nine places were affected. Among the ones most likely to have been
noticed:

- the **"my tasks" link in task notification emails**
- **Print view** close buttons on match prep, match analysis, PDP files,
  training plans and the weekly team planner
- **Help and documentation** links throughout the app, including the help
  drawer
- the **trial** surfaces — case details from the dashboard widget, the
  parent-meeting screen, the printed letter, reminder emails, and the two
  redirects after saving a trial track
- scout report links on the dashboard, the mail-compose shortcut on the
  People admin page, and the closing step of the person and prospect wizards

All of them now resolve the dashboard page properly. That resolution also
copes with the page having been renamed or moved to the trash: it finds the
live one and remembers it. Links generated by scheduled background work —
reminder and notification emails in particular — no longer risk pointing at
an internal maintenance address.

A new automated check refuses any future link built the wrong way, so this
particular mistake cannot come back a third time.

# TalentTrack v4.97.0 — A nudge when a match goes unreviewed, and observations that no longer outlive their match (#2723, #2724)

**A new alert: "Match played, no analysis".** A match played between two
days and two weeks ago with nobody's write-up on it now shows on the bell.
It appears on the badge only — never as a banner — because a missing
analysis is a prompt, not a problem with your data, and it stops after a
fortnight: by then the detail is gone and a reminder is only guilt. Writing
the analysis clears it; there is nothing to dismiss.

It deliberately stays quiet about two things. Tournaments, which cannot
carry an analysis yet, because telling a coach to do something the product
refuses to let them do is worse than saying nothing. And matches where no
attendance was recorded at all — that academy is already getting the
attendance alert, and two nudges about one match is how an inbox becomes
noise. An academy that switches Match analysis off stops being asked for
analyses entirely.

**Deleting an activity no longer leaves observations behind.** A coach's
note about a named player — from a match analysis or from a training —
emits an entry on that player's timeline. Deleting the activity removed the
note but left the timeline entry standing: a sentence about a child,
pointing at a match or training that no longer exists. Both kinds are now
removed with their activity, and the delete-preview counts them, so the
number you are shown before confirming is the number that goes.

The same fix reaches training observations themselves, which were not being
removed with their activity either.

# TalentTrack v4.97.0 — Match analysis: the roster is a tally sheet, and the wizard is styled again (#2726)

Two fixes to the match analysis that shipped in v4.96.0, one of them
visible the moment you opened the wizard.

**The wizard steps rendered unstyled.** The stylesheet was enqueued on the
first step only, and every wizard step is its own page load — so steps two
to five arrived with no CSS. That is worse than it sounds for this screen:
the marker chips are a hidden radio plus a styled label, so losing the
stylesheet turned them back into raw browser radio buttons stacked down the
page. Assets are now asked for once per step, from one place.

**Marking players is a tally sheet now, not fourteen forms.** The squad
renders as a grid of names; tap one and pick ▲ Stood out, ● As expected or
▼ Below par. The name takes that colour and the player drops into a Notes
list underneath, where the note and phase fields live. Only the players you
marked have a note field, so a squad of fourteen fits on one phone screen
and an analysis you have not started has no text boxes on it at all.

Nothing about what gets stored has changed, and the whole squad is still
listed — that is what stops the quiet players being skipped. What changed is
that the page no longer asks fourteen questions to collect two answers.

The section ratings (Went well / Mixed / Needs work) also moved from a
wrapping row to a two-column grid on phones, where the Dutch labels no
longer fit on one line.

Without JavaScript the roster falls back to the plain form — every player,
every field — so nothing is lost, it is just longer.

# TalentTrack v4.96.0 — Alerts: the bell now takes you where the number came from, and long-ignored alerts become tasks (#2635)

The notification bell counts your alerts as well as your tasks — it has done
since alerts shipped — but clicking it always landed on the task list. A coach
whose bell read "3" because of three unmarked activities arrived at an empty
inbox and reasonably concluded the bell was broken. It now takes you to
whichever list the count actually came from, and to the alerts list when it
is a tie, because that is the one that can show you everything.

Alerts that nobody deals with can now turn into real, assigned tasks. Set a
threshold per alert under Settings → Alert policy ("Turn into a task after
(days)"); leave it empty and nothing escalates, which is the default.

Two deliberate properties, because both are the sort of thing people expect
to work the other way:

- It happens **once**. An alert becomes a task one time, not once a day until
  somebody acts.
- It is **one-way**. Fixing the underlying thing clears the alert but does not
  close the task. A task carries somebody's name and a record of what
  happened; closing it behind their back would defeat the point of having
  made it a task. Close it from the task itself.

The bell's styling also moved out of the code and into the stylesheet, so it
follows your academy's theme instead of being hard-coded red.

# TalentTrack v4.96.0 — Alerts: two new data-quality alerts (#2636)

This release adds two alerts about records that are simply incomplete. They
are switched on from the moment you update, for everyone who can act on them,
so here is exactly what you will start seeing:

- **Player has no team** — an active player belongs to no team, a week or more
  after being added. A player with no team has no attendance, no minutes, no
  evaluation-coverage row, and no head coach receiving any of the other alerts
  about them; TalentTrack genuinely cannot say where they are. This one is
  quiet: it appears on the bell, not as a banner.
- **Team has no head coach** — a team with players has nobody assigned as head
  coach. Most alerts go to the head coach, so a team without one quietly stops
  receiving any of them. A coach whose assignment has an end date in the past
  does not count. Teams with no players are ignored, and so are trial groups.

Both go to whoever looks after the records rather than to a coach, because
there is no coach to send them to — that is the condition. And both are
treated as player data: an alert that names a child is only shown to someone
already allowed to see that child's record.

The one threshold is an academy setting,
`alerts_player_without_team_grace_days`. Assigning a squad is usually the next
step in the same sitting as adding the player, so a brand-new record does not
appear immediately.

This is the fifth instalment filling out the alert catalogue.

# TalentTrack v4.96.0 — Alerts: a new Onboarding alert (#2636)

This release adds one alert about invitations. It is switched on from the
moment you update, for everyone who can act on it, so here is exactly what you
will start seeing:

- **Invitation never accepted** — a player or staff invitation was sent a
  fortnight ago and nobody ever accepted it. Goes to whoever sent it, and for
  a player invitation also to the head coach of their team. Usually the email
  went to spam or to a mistyped address, and until now nothing anywhere said
  so: TalentTrack recorded the send and the acceptance, and the gap between
  them was invisible unless somebody thought to open the invitations list.

It does not fire for an invitation the system has already made redundant — a
player or staff member whose account was created directly by an admin leaves a
pending invitation behind, and chasing it would be chasing something already
done. Nor for parent invitations, which have their own alert.

The threshold is an academy setting, `alerts_invitation_stale_days`.

This completes the alert catalogue for now: activities, evaluations, goals and
PDP, people, measurements, data quality and onboarding.

# TalentTrack v4.96.0 — Lesson checks that actually check something (#2647)

Every module of the periodisation course has had five questions written for
it since the corpus shipped. None of them appeared anywhere. The payloads were
valid, the lessons declared they had a check, and there was no block on any
page to render one — so the sequential unlock waited on a quiz nobody could
take. The corpus lint now fails a PR where those two halves disagree, in
either direction.

The questions are live now, in four shapes: pick one, pick several, put a
sequence in order, and match two lists. Ordering uses a position box per item
rather than drag-and-drop — dragging is nicer with a mouse and unusable with a
keyboard, and typing a number is a real answer rather than a fallback bolted
beside a nicer one.

Marking happens on the server. That is not caution for its own sake: the file
that holds the questions also holds the answers, so anything that marked in
the browser would have to be given the answers first. The page a coach sees
with developer tools open is the page they see without them.

Options are shuffled every time a lesson is opened, which matters more than it
sounds. Every ordering and matching question in the course happens to be
stored in its correct sequence, so showing the options as filed would have
handed over the answer to all nine of them.

There is no partial credit and a skipped question counts as wrong. Half an
ordering is not half an understanding of a sequence, and a check you can pass
by answering only the questions you were sure of is not checking anything.

Every attempt is kept, passed or not — a coach who got there on the fourth try
has a different development record than one who got it first time, and the
head of academy reading that record should see both. Retakes are unlimited,
and the reason behind each answer is shown whether you got it right or wrong.

The whole thing is a plain form, so it still works with JavaScript switched
off; the script just saves you losing your place in a long lesson.

# TalentTrack v4.96.0 — Photo-capture DPIA: precise subject-access position (#2695)

Follow-up to the DPIA correction. The previous pass called the subject-access
position a "gap — either register the tables or state the limitation", which
offered an option that does not exist: `tt_activity_exercises` carries no player
identifier, and the export mechanism can only follow a column that joins to a
player.

The document now states the position exactly. Who attended a session **is**
covered, through `tt_attendance`. The extraction is not, and cannot be by that
mechanism. The real residual risk is narrower and sharper than "a gap": a player
name transcribed into the free-text `notes` column is reachable by neither a
subject-access export nor an erasure request, because erasing one player does not
delete a session that belongs to a whole team.

That is now prerequisite 7 in "Before this can be signed", with three ways to
close it: instruct the model not to transcribe names, strip them before save, or
accept and document the limitation.

# TalentTrack v4.96.0 — Photo capture will not send anything until you say where (#2695)

Photo-to-plan capture used to have a working default endpoint. An install that
had merely switched the feature on was already able to send photographs taken at
a youth academy to a destination nobody had consciously chosen — and the DPIA
said the opposite, that EU residency was enforced and that leaving it took a
deliberate opt-out.

**The default is gone.** Two settings are now required in `wp-config.php`, and
until both are present the feature reports itself unconfigured and nothing is
sent:

```php
define( 'TT_VISION_ENDPOINT',    'https://…' );          // where requests go
define( 'TT_VISION_DATA_REGION', 'EU (Frankfurt)' );     // where that processes them
```

Switching the feature on without declaring a destination now answers plainly that
nothing was sent and what an administrator needs to set, rather than reporting
that the photo could not be read.

This cannot verify a declaration — no plugin can tell whether an endpoint really
processes data where its operator says it does. What it guarantees is that the
destination is always a choice somebody made, which is the thing a DPIA can
honestly record. The declared region belongs in the signed document.

Two related corrections: the extraction prompt now tells the model to keep player
names in the structured attendance field rather than in free-text notes, where
neither a subject-access export nor an erasure request could reach them; and the
`TT_VISION_BEDROCK_*` settings, which were documented but never read by any code,
have been removed so nobody configures them believing they do something.

**If you already use this feature, it will stop working until you add the two
settings.** That is deliberate.

# TalentTrack v4.96.0 — Match analysis: write up a game per team function, and per player (#2704)

A match can now be reviewed in the app, not only planned and measured.
**Write the match analysis** appears on a match activity once it has been
played, and on the post-match sideline screen where the detail is still
fresh.

The review is structured in the academy's own methodology vocabulary: an
overall read, then a rating (Went well / Mixed / Needs work) plus up to four
short points for each of *Aanvallen*, *Omschakelen naar aanvallen*,
*Verdedigen*, *Omschakelen naar verdedigen* and set pieces. Where the match
plan asked for something in a phase, it is shown next to it — so the review
answers what was asked rather than what is remembered. An unrated section is
a valid answer and stays out of the record entirely.

Below the phases sits the roster: everyone who played, with their minutes.
Each row optionally takes a marker (Stood out / As expected / Below par), one
specific line about what the player did, and the phase it belongs to. Rows
left untouched persist nothing. Every note also lands on that player's own
timeline as *Observed in a match*, dated to the match and visible to staff —
which is what makes it a development record rather than a per-match document.
Rewriting a note updates the timeline entry; clearing it removes the entry
too.

The first draft is written through a five-step wizard; re-opening an existing
analysis goes straight to the page, because changing one line should not mean
walking five steps. Output is an A4 print (real text, so the PDF stays
selectable) and a signed staff share link that can be revoked and reissued in
one click, shutting every URL handed out before it.

Works with or without a match plan and with or without the live-match screen
— a game run off a paper team sheet gets the same analysis, with nothing to
pre-fill. Match-type activities only for now: a tournament day is several
games, and one analysis cannot say which of them it is about.

Both the module and its two outputs are switchable: an academy can keep the
review surface while turning the PDF export or the share links off.

# TalentTrack v4.96.0 — Photos and video now load, count and land where you expect (#2715, #2716, #2717)

Three defects found in the first live test of the media library, all fixed
together.

**Photos and video would not display (#2715).** Every thumbnail rendered as a
broken image. The files themselves were fine — stored, thumbnailed and stripped
of EXIF exactly as intended — but the browser was turned away at the door.
An `<img>` tag cannot send the `X-WP-Nonce` header the REST API expects, so
WordPress treated the request as coming from nobody at all and answered 401.
Media URLs now carry the nonce in the query string, which WordPress accepts as
equivalent. The session cookie is still required: a URL copied out of a page and
opened elsewhere is refused, so this does not turn a player's photo into a link
anyone can follow.

**Finishing the wizard dropped you on the site's front page (#2716).** The
"Add photos or video" wizard built its closing redirect on the site root rather
than the page that hosts the dashboard, so on any install where the dashboard is
not the front page the coach landed on the theme's homepage instead of the player
they had just added a photo to. The same three lines also pointed activity media
at a view that does not exist. Both now route through the shared link helper.

**The Media tab never showed a count (#2717).** Goals, Evaluations, Activities
and the rest all carry a number; Media was added to the tab strip without being
added to the counter behind it, so a player with photos showed a bare tab. The
badge now counts the same media the tab lists — club-scoped, archived items
excluded — so the two cannot disagree.

One limitation worth knowing: a nonce is valid for roughly a day. A gallery left
open in a tab longer than that will show broken thumbnails until the page is
reloaded.

# TalentTrack v4.95.0 — Draw an animated scene for a drill (#2501)

An exercise can now carry a **scene** — a small animated diagram of the drill,
with players, opponents, the ball, cones and goals on a pitch, and the
movements you want them to make. Open an exercise and press **Draw a scene**.

The editor is built around one gesture: drag a marker on the pitch and it
records where that marker is at the moment the playhead is on. Scrub to two
seconds, move the left-back forward, and the left-back now runs forward over
those two seconds. A timeline, a marker palette, a line tool (pass, dribble,
run, shot, press) and forty steps of undo are there for everything the drag
does not cover, and the arrow keys move a marker without a mouse.

A saved scene shows up in three places — the exercise page, the sideline view
while the training runs, and the printed A4 sheet. All three draw it with the
same code, so they cannot drift apart; on paper it becomes a still picture of
the scene's final frame, which is also what a reader who prefers reduced motion
sees on screen.

Scenes are stored per exercise and validated on the way in, so a diagram that
reaches the database is always one that renders. Coordinates off the pitch are
pulled back onto it, keyframes are sorted, and a line drawn to a player who has
since been deleted is dropped rather than left pointing at nothing.

Drawing works best on a tablet or a desktop. On a phone you can watch a scene
and move a marker, but the timeline wants more room than a phone has.

# TalentTrack v4.95.0 — Fixed: the Data browser tile stayed on the dashboard after switching the module off (#2599)

Switching the **Data browser** module off hid what it does but left its tile on the dashboard, pointing at a screen that no longer answered. The
tile now disappears with the module, as every other module's does.

Behind that, the switchability check that shipped alongside it has been taught something it was missing: a screen belonging to a module you can
already switch off does not also need a separate feature toggle. That removed 47 entries from the list of screens marked as needing a decision —
they never needed one — and left six that genuinely must always be on, each with the reason written down.

# TalentTrack v4.95.0 — Five modules now show their real names on the Modules page (#2599)

Strava, Training plans, Measurements & testing, Data browser and Knowledge library were listed on the Modules page under a slugified class
name instead of a proper label and description. They now read like the other modules do.

The rest of this change is a build-time check with no visible effect: TalentTrack now refuses to ship a module or a screen that an academy cannot
switch off, unless somebody has written down why it must always be on. The switching itself has always worked — what was missing was anything that
noticed when a new one arrived without a toggle.

# TalentTrack v4.95.0 — Alerts: TalentTrack now tells you when your data needs attention (#2631)

A new Alerts engine surfaces conditions that are true right now and need
someone to act — an activity whose date has passed but is still marked as
planned, a completed activity with nobody's attendance recorded, an activity
next week with no coach assigned. Alerts appear in a banner at the top of
the dashboard and are counted by the notification bell alongside open tasks.

Alerts are deliberately not tasks. You never mark one as done: you fix the
thing it points at and it clears itself on the next background check. That
is the whole reason for a separate engine — modelling "this activity is still
planned" as a task would leave a stale task in someone's inbox every time a
coach fixed the activity in the activities list.

Alerts go to the people who can fix them: the coach assigned to the activity
and the team's head coach. Heads of Development do not receive one per team;
an aggregate view for that role comes later. Whether a recipient may see an
alert is re-checked on every sweep, so a coach who moves off a team stops
receiving that team's alerts without anyone having to remember.

Conditions are re-checked hourly in the background rather than while your
dashboard loads, so adding alert types can never slow down signing in. The
trade-off is that an alert can linger for up to an hour after you fix the
underlying thing. A fresh install runs one check on activation so the first
dashboard load shows a true picture.

This is the foundation only. Per-person and per-club settings for which
alerts you see and where, contextual chips on list and detail views, email
digests, and the rest of the alert catalogue all build on top of it.

# TalentTrack v4.95.0 — Alerts: choose which ones you see, and where (#2632)

Alerts now have settings. **Account → Alert settings** lists every alert with
a tick per place it can appear — in the bell, as a banner on the dashboard —
so a coach who wants unmarked activities counted but not announced can have
exactly that.

Alerts you cannot change are shown greyed out with the reason rather than
hidden. A settings list that quietly omits what you cannot change teaches you
the list is complete when it is not.

Two new controls for administrators, under **Settings → Alert policy**. Each
alert can be left to the individual (the default), forced on for everyone, or
switched off for the whole club — except alerts concerning a child's safety,
which cannot be switched off at all. Switching one off also clears the alerts
it has already raised, rather than leaving rows stored where nobody can see
them. Administrators can also require an alert to be acknowledged before the
page continues, and set how long an ignored alert waits before it becomes a
real assigned task.

Individual alerts can now be snoozed for a day, a week or a month, or
dismissed outright. Dismissing removes that occurrence only: if the same
problem is fixed and then happens again, the alert comes back, because that
is genuinely new information. To stop a whole category, untick it in Alert
settings.

Message preferences — what the academy emails or pushes to you — stay on
their own screen under Account → Settings, and the two screens link to each
other. They govern different things: one is what gets sent to you, the other
is what the app surfaces about your own data.

# TalentTrack v4.95.0 — Alerts wave 3: alerts appear on the records they are about (#2633)

Alerts now surface where the fix happens, not only in a banner. A compact
severity chip appears on any activity in the activities list, on the
activity's own page, on a team's page, and on a player's record — a count,
a word, and a link into the new alerts list scoped to that record. The chip
carries its meaning in text as well as colour, works without hover, and
stays a 48x48 target on a phone.

Two rules hold the design together. The chip is the one alert surface a
person cannot mute: it is not a notification, it is the record's own current
state drawn next to the record, and hiding it would hide a row's real
condition from whoever is looking straight at that row. And on a player's
record only OPEN alerts are ever shown — resolved ones are gone, and nothing
about an alert is written into the player's journey. The journey records
what happened to the player; an alert records what staff did not get round
to entering, and at a 90-day retention a journey entry would vanish
retroactively anyway.

A new **Alerts** list at `?tt_view=alerts` carries the whole set with
area / severity / state filters, and is where every chip deep-links to.

Heads of Development and academy admins get the counterpart they were
promised: a per-team summary at the top of that list ("4 teams have records
that need attention"), read as a grouped query over the alerts that already
exist. No occurrence is written for oversight users, so the "no alert per
team for the person with the least time to read them" rule stays intact.
The summary is scoped to the teams the viewer already oversees and counts
each affected record once, even when two coaches were both told about it.

Rendering chips on a fifty-row list costs one database query for the whole
page. `GET /alerts` gained `subject_type` / `subject_id` / `player_id` /
`state` filters and `GET /alerts/rollup` returns the per-team summary, so a
non-WordPress front end can draw the same chip.

# TalentTrack v4.95.0 — Alerts: optional summary email, and a 90-day retention window (#2634)

Alerts can now reach you by email. If you do not open TalentTrack often, tick
**In the summary email** against the alerts you care about in Account → Alert
settings and your open ones arrive as a single message.

It is off until you turn it on. Nobody is signed up by this release: the app
will show you alerts in the bell and on the dashboard, but it will not put
mail in your inbox until you ask it to.

The summary will not repeat itself. An alert stays open until the underlying
thing is fixed, so without this you would receive the same items every
morning; anything already mailed, read, snoozed or dismissed is left out, and
when there is nothing to report no email is sent at all. Each line links
straight to the record that needs attention rather than to a list.

Cleared alerts are now kept for 90 days and then deleted. Alerts still open
are never deleted however old they are — one nobody has dealt with for a year
is worth seeing, not tidying away. The trade-off is that the alerts system
cannot answer questions spanning more than about a quarter; for season-long
patterns use Reports, which reads the underlying records.

# TalentTrack v4.95.0 — Alerts: three new Evaluations alerts (#2636)

This release adds three alerts about evaluations. They are switched on from
the moment you update, for everyone who can act on them, so here is exactly
what you will start seeing:

- **Player not evaluated recently** — nobody has recorded an evaluation for a
  player for longer than your academy's threshold (eight weeks out of the box).
  Goes to the head coach of that player's team. A player who has never been
  evaluated is counted from the day they joined, so a trialist who arrived on
  Tuesday will not appear.
- **Evaluation window closing** — an evaluation window is within three days of
  closing and players in your team have no evaluation in it. Goes to the head
  coach. It stops the moment the window closes: a gap nobody can still fill is
  not something worth nagging about.
- **Evaluation not shared with the player** — an evaluation was recorded but
  the player-facing feedback field was left empty, so the player and their
  parents see nothing. Goes to the coach who wrote it and to the team's head
  coach, from a week after the evaluation until sixty days after it.

All four thresholds are academy settings rather than fixed numbers, because
an academy that evaluates every block and one that evaluates twice a season
disagree about what "recently" means: `alerts_eval_stale_weeks`,
`alerts_eval_window_closing_days`, `alerts_eval_share_grace_days` and
`alerts_eval_share_lookback_days`.

As with every alert, you never mark these done. Record the evaluation, or add
the feedback, and the alert clears itself at the next hourly check. You only
receive one about a player you already have permission to see.

This is the first of several instalments that fill out the alert catalogue.
They ship one module at a time, and each release names the alerts it adds —
a release that quietly changed twelve things the app nags about would be an
ambush rather than an improvement.

# TalentTrack v4.95.0 — Alerts: two new Goals and PDP alerts (#2636)

This release adds two alerts about a player's development plan. They are
switched on from the moment you update, for everyone who can act on them, so
here is exactly what you will start seeing:

- **Goal past its target date** — a development goal has passed the date it
  was aimed at and is still open. Goes to whoever set the goal and to the head
  coach of the player's team, from three days after the date until a year
  after it. Either the player got there and nobody recorded it, or the plan
  needs changing; both are answers, leaving the goal untouched is not.
- **No PDP conversation this cycle** — a player's PDP file for this season is
  open but no conversation has actually been held. Goes to the coach who owns
  the file and to the team's head coach, from 45 days after the file was
  opened. Conversations that were scheduled but never held do not count: a
  cycle is created with all of its conversation rows already written, so
  counting rows would mean the alert could never appear.

Both thresholds are academy settings rather than fixed numbers:
`alerts_goal_overdue_grace_days`, `alerts_goal_overdue_lookback_days` and
`alerts_pdp_no_conversation_days`.

Only the current season's PDP cycles are considered. Last season's untouched
cycle is history, not a gap anyone can still close.

This is the second instalment filling out the alert catalogue, after the
Evaluations alerts. They ship one module at a time so that every release can
tell you what it added.

# TalentTrack v4.95.0 — Alerts: a new Measurements alert (#2636)

This release adds one alert about the testing battery. It is switched on from
the moment you update, for everyone who can act on it, so here is exactly
what you will start seeing:

- **No measurement this season** — a player has nothing recorded in the
  current season's testing battery. Goes to the head coach of their team,
  from 60 days into the season. Growth data is the only part of a player's
  record that is not somebody's opinion, and a season with no measurement
  leaves a permanent hole in the curve: you cannot fill it later, because the
  player has already grown.

The question is "this season", not "recently": a measurement taken before the
current season started does not count, because the academy's testing battery
runs on a season rhythm. The current season is the one marked as current in
your season settings; if none is marked, the alert stays quiet.

The threshold is an academy setting, `alerts_measurement_grace_days`. In week
one of a season this alert would fire for every player in the academy at once,
which is indistinguishable from saying nothing.

You only receive it if you already have access to measurements — the alert
names a player and says what is missing from their record, so it is gated the
same way the measurement screens are.

This is the fourth instalment filling out the alert catalogue.

# TalentTrack v4.95.0 — Alerts: three new People alerts (#2636)

This release adds three alerts about the people around a player. They are
switched on from the moment you update, for everyone who can act on them, so
here is exactly what you will start seeing:

- **Player turns 18 soon** — a player's eighteenth birthday is within 30 days.
  Goes to the head coach of their team. Turning eighteen changes the paperwork
  rather than the football: parental consent stops being the basis for holding
  their data, a youth agreement may need to become a contract, and the parent
  account's access becomes a decision rather than a default.
- **Parent invited but never activated** — a parent was invited more than a
  fortnight ago, never created their account, and the player still has no
  parent linked at all. Goes to whoever sent the invitation and to the head
  coach. A parent who was invited twice and accepted the other invitation, or
  who an admin linked directly, does not trigger it.
- **Certificate expiring** — one of your own certificates is within 60 days of
  expiring, or expired inside the last 60 days. This one goes **only to the
  person whose certificate it is**: that is somebody's professional record,
  not squad information. Already-expired certificates are included on purpose;
  dropping them would make the alert vanish exactly when the problem becomes
  real.

Thresholds are academy settings: `alerts_player_turns_18_days`,
`alerts_parent_invite_stale_days` and `alerts_staff_cert_expiring_days`. The
age of majority itself is not a setting — it is a fact about the jurisdiction
the academy operates in, not a preference.

Parent invitations are covered here; player and staff invitations get their
own alert in a later instalment, so nobody is told the same thing twice.

This is the third instalment filling out the alert catalogue, after
Evaluations and Goals/PDP.

# TalentTrack v4.95.0 — Knowledge library: the system now remembers where you got to (#2644)

Courses have shipped as files since #2642 and been readable since #2643. This
adds the half a file cannot carry: who is on which course, how far they got,
and what they still owe.

Four tables behind one rule. A lesson is finished when everything its front
matter asks for has happened — read it, pass the quiz if it has one, get the
assignment approved if it has one — and a course is finished when all its
lessons are. That rule lives in one service, because the reader, the
lesson-unlock gate and the completion report all have to give the same answer
and there is no version of this where they may disagree.

Two consequences worth knowing. Requirements are read from the course files
every time rather than frozen at enrolment, so revising a course to add a
lesson reopens the people who finished the old version instead of leaving
them certified for work they have not done. And completion is reversible: a
reviewer who withdraws an approval drops the enrolment back to in-progress,
because a certification standing on a verdict that no longer stands is worse
than no certification.

Quiz attempts are kept in full, not collapsed to the latest. A coach who
passed on the fourth attempt has a different development record than one who
passed first time, and that is exactly what a head of academy reading the
record wants to see.

Three capabilities rather than the usual two: a coach can see their own
progress without seeing their colleagues', because the roll-up is a separate
grant. Assignment attachments ride the media library rather than growing a
second upload path.

Demo data covers all four tables with a deliberately mixed cohort — some
finished, some mid-course, one overdue, one assignment waiting in the review
queue — so the completion report has something real to render before anyone
has used the feature.

Still not readable in the app: the reader view is #2646.

# TalentTrack v4.95.0 — One gate for what an install can show you (#2645)

Two corpora in the plugin carry the same four keys — `module`, `feature`,
`tier`, `capability` — and both need the same question answered: can this
install, and this reader, have this? The help topics under `docs/` and the
courses under `courses/` were about to grow two separate answers to that,
which would have drifted the first time anyone added a fifth gate or fixed a
bug in one of them.

`ContentGate` is now the single resolver, in shared space so neither module
owns it. Courses consume it today; the help corpus consumes it when its own
gating work lands.

The verdict it returns is not a boolean, because the three ways content can
be out of reach are not interchangeable to the person in front of it.
**Unavailable** means this install does not have it and no permission changes
that. **Denied** means it is here and somebody else can see it. **Locked**
means you will be able to, once you have done something first. Showing the
same message for all three is how a product ends up telling a head of academy
to ask their administrator about a feature their licence does not include.

On top of that, courses gain the two gates that are about the learner rather
than the install: a course can require another course first, and a sequential
course opens one lesson at a time.

Two decisions worth knowing. Content this install cannot have is **absent**
and returns a 404, not a 403 — a 403 confirms the thing exists here, which is
what hiding it was for. And locked content stays **listed**, because hiding a
locked lesson makes a course look shorter than it is and nobody can work
towards something they cannot see.

The gate is enforced where it can actually be walked around: submitting
progress for a locked lesson is refused, not just hidden in the reader.

An unknown key value leaves content visible rather than hiding it. A typo in a
feature name silently removing a topic is a bug found months later, if ever;
the corpus lints are what catch the typo.

# TalentTrack v4.95.0 — The knowledge library is now something you can actually open (#2646)

Three ships have built a course library nobody could read: the corpus, the
interactive blocks, the progress tables and the gating. This is the front of
it.

Four surfaces. A **library** listing the courses this reader may see, ordered
so the work in front of them comes first — in progress, then not started, then
locked, then finished, because a library that sorts alphabetically makes a
coach hunt for the course they are halfway through. A **course page** with the
lesson list, what finishing asks of them, and one button back to wherever they
stopped. The **reader** itself. And **My learning**, which sits beside My PDP
and My certifications because that is what it is: the training half of a
coach's own development.

Opening a course goes to the first lesson you have not finished, not lesson
one. Opening a lesson enrols you — a separate "enrol" step before you can read
lesson one is a step nobody would understand.

Marking a lesson read is a button, never a scroll measurement. A coach who
skims and clicks it has made a claim; a scroll listener has only measured a
thumb. It posts a real form, so the lesson still completes with JavaScript
switched off. The end of each lesson also says what it still wants — a passed
check, an approved assignment — because someone who marks a lesson read and
sees the course percentage refuse to move needs to know it is the quiz and not
a bug.

The zero-point measurement a coach takes in module 4 is still there in module
11, where the final assignment asks for it. Same for a week plan: the tools
now remember what you put in them.

Locked lessons stay in the list with the reason attached, rather than being
hidden. Hiding them would make a course look shorter than it is, and nobody
can work towards something they cannot see.

Everything here is switchable: all four routes belong to the courses feature,
so turning it off takes the URLs down as well as the tiles rather than leaving
a bookmarked lesson rendering a surface the academy switched off.

# TalentTrack v4.95.0 — How long a player's photos are kept, and who decides (#2666)

An academy asked "how long do you keep photos of my child?" now has an answer the product supports.

Media belonging to a player who has left is kept for a set period — **three years by default**, adjustable from one to ten years under
Configuration, or **Keep indefinitely** if you would rather decide case by case. When the period passes, the media appears under the new **Media
retention** screen.

**Nothing is ever deleted automatically.** The period starts a review, not a deletion. That is why a default could be shipped safely: upgrading
finds you a list to work through, never gaps in your records.

Two details worth knowing:

The clock starts when the player leaves, not when the photo was taken. A player still at the academy keeps their whole file however old — the
picture of the same player at 12 and at 18 is the point, and a period measured from the photo's own date would quietly delete the beginning of it.

Expiry applies to one player's link rather than the whole photo. A team photo showing someone who left comes off *their* file and stays on the
team, on the training, and on the other players in it. Only when nothing is left pointing at a file is the file itself deleted — and the screen
tells you which of the two just happened.

Each item can be **Kept** instead, with a reason: a safeguarding matter, an open dispute. Those are listed separately with their reasons, because a
retention policy with an invisible list of exceptions is not one anyone can check.

# TalentTrack v4.95.0 — Scene editor: correct Dutch for the line types (#2687)

The scene editor's line picker offered a Dutch coach **Geslaagd** for *Pass* and
**Uitvoeren** for *Run* — "passed" as in a test result, and "execute" as in
running a program. Both are now right: **Pass** and **Loopactie**.

`Pass` and `Run` are single English words, and the catalogue already held them
from unrelated parts of the product. Gettext returns whichever translation was
registered first, so the picker inherited a meaning from somewhere else entirely
with nothing to show for it — the English read fine and the catalogue looked
complete.

The whole diagram vocabulary — the six markers, the five line types and the four
pitch presets — is now translated under its own context, so none of these words
can pick up a sense from elsewhere, and a word added to the set later cannot
either.

# TalentTrack v4.95.0 — Photo-capture DPIA corrected against the code (#2695)

`docs/photo-capture-dpia.md` is the document an academy signs before photographs
taken at a youth academy are sent to a vision model. An audit against the shipped
code found that several of its technical assertions described safeguards that do
not exist, so the document has been rewritten to describe what the code actually
does, and now carries a prominent **not ready for signature** banner listing what
must be settled first.

The correction that matters most: the feature does **not** route to an EU-resident
endpoint by default. The document previously said it did, and that breaking that
required a deliberate opt-out. In fact the default is Anthropic's direct API,
there is no AWS Bedrock code path at all, and an operator-supplied endpoint
override is not validated.

Corrected in the safer direction: the uploaded photograph is never written to
disk. The document described a seven-day retention and a cron sweep; neither
exists, because there is nothing stored to sweep.

Also corrected: the structured extraction is **not** currently included in the
GDPR subject-access export, contrary to what the document claimed.

No behaviour changed in this release — the feature remains off by default behind
the `exercises_vision_extraction` flag. If you have already signed a copy of this
DPIA, re-read it: the version you signed misdescribed where photographs go.

# TalentTrack v4.94.1 — Test trends: numbers first, and a colour per player (#2670)

The Test trends report led with a chart in which every player's line was
drawn in the same colour — a full squad overlapped into one navy band that
no reader could trace a single player through, and nothing connected a line
to a row in the table underneath it.

The report now opens with the values: the player table, then Most improved
and Fallen back, then the chart as the summary of what they already said.
Each player's line is thinner and carries its own colour, and the same
colour appears as a short line in front of their name in the table and in
both ranking lists. Past ten players the palette reuses a colour with a
dashed and then a dotted line, so a large squad stays identifiable in
colour, in greyscale print, and for a colour-blind reader.

Presentation only — the same figures, the same
`GET /reports/test-trends` payload. Status, pass/fail and directionless
tests are untouched; they have no chart to key.

# TalentTrack v4.94.0 — What has this player actually been taught? (#2500)

The question the Training module was built to answer.

**A Training tab on every player file.** Minutes trained per principle, how
many of your methodology's principles they have touched, and when they last
trained — drawn from the trainings they actually attended.

**The principles they have never trained are listed too, at the top, marked.**
That is the whole point. A list that quietly dropped the empty rows would look
complete while hiding exactly what you opened the tab to find.

The minutes are honest about what happened rather than what was planned.
Present and late count; excused, absent and injured do not. A skipped block
contributes nothing. A block that ran twenty-seven minutes contributes
twenty-seven, not the twenty-two someone typed into the plan. A player who
guested for another team carries those minutes on their own file.

**Notes on players, from the touchline.** The sideline view now lists everyone
who is there with your academy's own scale under each name and a box for a
note. You do not have to score anyone — a note on its own is a complete
observation, and on a wet Tuesday it is the usual one. Tap a number again to
clear it. A score outside your configured range is refused rather than rounded
into it, because a rounded number on a child's record is one nobody chose.

Each note lands on that player's **Journey** straight away, dated to the
training rather than to when you typed it up.

**A coverage matrix for the head of development.** Every principle down the
side, every team across the top, and how many trainings each has spent on
each. Only "never" is marked: four shades of nearly-fine would bury the one
thing worth acting on.

**Who sees it.** Coaches for their own teams, head of development and academy
admins for everyone, and a parent for their own child. A player can switch it
off for their parent entirely, under *My settings → what your parent can see*,
alongside evaluations, goals, measurements and their PDP — training history is
a ledger of what a young person has and has not been taught, and belongs in
the same bracket as the rest.

**When it updates.** Immediately when a training is finished, for the players
who were there, and fully every night — which is what picks up a plan edited
after the fact, an exercise re-tagged with a different principle, or
attendance corrected the next morning.

**Demo academies** now carry observations as well as plans and runs:
deliberately sparse and mostly unscored, because a demo where every player has
a tidy 7 would teach the wrong idea about what this is for.

# TalentTrack v4.94.0 — MFA: a correct code no longer lands on a blank page (#2668)

Entering a valid authenticator or backup code on the two-factor challenge
could leave the user on an empty screen, still parked on the challenge URL,
with no way forward but editing the address bar. The code itself was always
accepted — only the hop to the dashboard failed.

The challenge page renders inside the dashboard shortcode, so by the time it
ran the response headers had already gone out; the post-verify redirect was
silently dropped and the `exit` behind it truncated the page. Reloading made
it worse: with the challenge now cleared, the same unguarded redirect fired
again from a second code path.

Verification, rate limiting, audit logging and the "remember this device"
cookie now resolve on `init`, before a byte of the page is written, so the
redirect is a real one. The view renders the form, the error and the lockout
countdown and nothing else. The two bounce-out cases — no challenge
outstanding, or a pending challenge on an un-enrolled account — go the same
way, and every remaining path carries a card with a link out rather than a
blank screen.

# TalentTrack v4.94.0 — Injuries overview: record an injury without opening a player file first (#2671)

The squad-level Injuries page was read-only by omission — the injury wizard
existed and was registered, but only the player file linked to it, so a coach
who opened the overview to see who was out had no way in from there. The page
now carries a **Record injury** action in its header, and the "Nobody is
currently out injured" state carries the same call to action instead of a bare
notice.

Entering from the overview starts the wizard on its team → player step, which
is scoped to the squads the coach holds. The button follows the same gate as
the player file: it is absent for roles without `tt_edit_player_medical` and
when wizards are switched off, because there is no flat-form path to fall back
on. The docs already described this entry point; the code has caught up.

# TalentTrack v4.94.0 — Fixed: uploading a photo failed with an error (#2674)

Adding a photo to a player, team or training failed — the file showed "Could not be added" and nothing was saved. Uploading video, or pasting a
video link, was unaffected, which is why the problem looked intermittent.

The cause was a thumbnail step that used a WordPress function only available inside the admin screens, so it broke as soon as the upload came from
the media wizard. Photo uploads work again, and nothing was lost: the failure happened before anything was written, so no half-saved photos or
stray files were left behind.

# TalentTrack v4.93.0 — Change a plan block by block, and see who it serves (#2498)

A training plan is no longer read-only. Open one and press **Edit blocks**.

**Reorder** with the ↑ and ↓ buttons on every block. They are the normal
control, on a phone and on a desktop alike, and they work from the keyboard —
tab to one and press Enter. On a wide screen you can drag a block by its
handle instead, but you never have to. Nothing is written until you press
Save, so rearranging costs nothing until you commit to it.

**Change a block's length** with − and +, in five-minute steps. The time strip
and the running total update as you go, so you see the shape of the session
change instead of doing the arithmetic.

**Swap an exercise** from your library — a sheet that slides up under your
thumb on a phone, a panel bottom-right on a desktop. The list is sorted by how
many of that team's open player goals each exercise would serve, and every row
carries its number, so you can see why one drill is above another rather than
having to trust the order.

**Add and remove blocks**, and write coaching points on each one. A block with
no exercise is allowed: a team talk has no drill behind it.

**The panel that makes it worth doing**: beside the blocks, the players this
plan actually works on, listed by name — and underneath, the players with an
open goal the plan misses, also by name. That second list is the one you can
do something about before Tuesday. It updates on every save, so you can swap a
block and see who it gained or lost.

**Reuse a plan** two ways. *Save as club template* makes a club-wide copy with
no team on it, so a session that worked becomes a starting shape anyone can
build from. *Copy to a new plan* makes an independent copy for the same team —
the quickest route to next week. Both copy the saved plan and say so if you
have unsaved changes; a copy never changes the plan it came from.

**Demo academies now have training history.** A generated demo academy used to
open the Training module to an empty list, which told the module's story
badly. It now comes with a plan per team per fortnight — themed, built from
the library the demo installs, with the principle links that make the coverage
panel and the coming exposure report mean something.

# TalentTrack v4.93.0 — Run a plan: on the pitch, and on paper (#2499)

A training plan stops being a document and becomes a training.

**Attach a plan** from the training in your calendar — **Run this training**,
pick the plan, done. The plan is copied onto the training as it is at that
moment, so editing it afterwards never changes what the training recorded. If
a plan is already attached the button says **Open the session** and takes you
there; attaching twice is not an error and never replaces the first copy.

**The sideline view** is the screen you hold on the pitch, and it is built for
that rather than for a desk: dark, one block at a time, big controls at the
bottom where your thumb already is.

- The timer counts up against the block's planned length. Nothing advances by
  itself — you decide when a block is done.
- Running over is a state, not a telling-off. The screen says how far over you
  are and what the block will be recorded as if you finish now, and lets you
  carry on.
- Finishing a block records how long it actually ran. Skipping one records
  that it did not happen — on this training, never on the plan, which is
  waiting unchanged for the next team that uses it.
- At the end: minutes trained against minutes planned, blocks run, blocks
  skipped.

The sideline view needs a connection. Lose signal mid-session and it tells you
the write failed rather than pretending it saved; working offline is coming
separately.

**The paper version.** Press **Print** on a plan for an A4 sheet: every block
with its start time, length, organisation and coaching points, on one page for
a normal session. If a player on that team has a growth-spurt ceiling below
the hardest block in the plan, the sheet names them — the person holding the
paper is the person who has to act on it.

**Demo academies now have a training history.** Generated academies come with
runs against their past trainings, including the blocks that ran long and the
cool-downs that got skipped, because a run record where everything went
exactly to plan teaches nobody why the record exists.

# TalentTrack v4.93.0 — Media library: foundation for photos and video on the player record (#2590)

Groundwork for attaching photos and video to players, teams and activities. This release ships the storage and data model, not yet the screens —
a new **Media library** module appears in Modules, switchable on or off like any other, and does nothing visible until the upload and gallery
surfaces land.

Files are deliberately kept out of the WordPress media library, whose addresses are public and cannot be withdrawn. Media is stored in a private
folder under randomly-generated names, with every request for a file checked by TalentTrack before any bytes are sent. Photos have their embedded
information — including the location a phone records at a training ground — read for the capture date and then stripped before storage. File types
are decided by the file's own content rather than its name, and SVG is refused outright.

Permanently deleting a player now also deletes their media. A photo attached only to that player is removed along with its file; media also
attached to a team or an activity is kept, because those records still point at it. Previously a polymorphic attachment like this would have been
missed by the deletion sweep, leaving photographs on disk after an erasure request.

Two known limits, both documented on the Media library page: video files keep their own embedded data, because stripping it needs tooling the
plugin does not ship — use a Veo, Hudl, YouTube or Vimeo link to keep footage off the server. And the folder-level block on direct web access
works on Apache but not on nginx, where TalentTrack's own permission check is the boundary.

# TalentTrack v4.93.0 — Media library: who may see a player's photographs (#2591)

The permission model for the media library. Nothing is visible in the interface yet — the screens land in later releases — but the rule that
decides who may see a photograph is now in place and enforced from a single point, so every future media screen inherits the same answer.

Staff see the media of players they are responsible for, following the same team scoping as the rest of a player's record. The player, and the
player's parent or guardian, see that player's own media. A scout reads media only for players they are actually linked to, not academy-wide —
the same narrowing that applies to evaluations, because a photograph of a child is at least as sensitive as a written judgment about them. A team
manager reads but does not upload or delete.

One consequence academies should know about: a photo or clip attached to several players is visible to all of their families. Team sport is
photographed in groups, and the alternative would hide nearly every training photo from everyone. Make sure your consent wording says so — the
Media library page explains it in full.

Existing installs pick up the new permissions automatically; no admin action is needed.

# TalentTrack v4.93.0 — Media library: the API behind photos and video (#2592)

The media library's REST surface: uploading, listing, editing, attaching to more than one record, and serving the files themselves. Still nothing
to click — the screens follow — but everything the feature will do is now reachable and permission-checked.

Photos and video are served only through TalentTrack, which checks who is asking before it sends a single byte. Video supports seeking, so a clip
can be scrubbed on a phone rather than downloaded whole. Anything TalentTrack does not recognise as a safe image or video is offered as a download
rather than displayed, and nothing served this way is stored in a shared cache.

Asking for a photo you are not entitled to see returns "not found" rather than "not allowed". That is deliberate: "not allowed" would confirm the
item exists in this academy, which is the one thing worth hiding from someone guessing.

Pasting a link to a video hosted elsewhere works for Veo, Hudl, YouTube and Vimeo. For YouTube and Vimeo the title and thumbnail are fetched
automatically, and the thumbnail is copied into the academy's own storage so viewing a clip does not tell the video provider which coach looked at
which player. Links to anywhere else are stored exactly as pasted, with a title you type — TalentTrack never contacts an address it does not
recognise.

# TalentTrack v4.93.0 — Media library: adding photos and video (#2593)

The **Add media** wizard, and the upload control behind it. Four steps: who it is for, the files, the details, and a confirmation.

Uploads are saved as soon as they finish, before the last step — so a dropped connection or a closed tab never costs you a file you already
waited for. Leaving halfway means the photos are on the record already, just without a title you can add later.

Each file shows its own progress and can be cancelled individually without disturbing the others. On a phone the camera is one tap from the
drop zone, and the largest file the server accepts is shown before you pick one rather than after the upload fails.

Video gets a thumbnail without any extra software on the server: the browser takes a frame from the clip as it uploads.

Pasting a video link works for Veo, Hudl, YouTube and Vimeo. For YouTube and Vimeo the title and thumbnail are filled in automatically; anything
else is saved as a plain link with a title you type.

The capture date is what decides where media sits on a player's timeline, so the wizard asks for the day of the training or match rather than
assuming the day of upload — and fills it in from the photo when the photo carries one.

# TalentTrack v4.93.0 — Media library: a player's photos and video, on their profile (#2594)

The first place the media library is actually visible. A **Media** tab on the player profile shows that player's photos and video, beside
Evaluations and Injuries — because a rating is a number and the clip behind it is the evidence.

The tab only appears for people whose permissions reach that player's media, and every item is checked again on the way out, so a photo attached
to a player a coach cannot see never reaches the page.

Media is ordered by when it was taken, newest first, rather than by upload time. Emptying a camera roll in November does not push August's training
above it — which is the difference between a story and a folder.

Tap to view full size, arrow keys to move between items, Escape to close. Video only starts loading when you play it, so opening the tab on a phone
costs nothing until you ask for something.

Photos and video can be added straight from the tab, and **Remove** deletes the file permanently rather than archiving it — offered only to people
who may edit that player's media.

# TalentTrack v4.93.0 — Media library: team and training media, and tagging who is in a photo (#2595)

Media now has a home on teams and on trainings, not only on players.

A team page gains a **Media** section for squad photos, tournament days and end-of-season moments. It shows what belongs to the team itself —
media of an individual player stays on their profile — and can be switched off for your own view like the other sections there.

A training or match gains its own **Media** section, and that is where the useful part lives: **Tag players**. Tick the players who are in a photo
and it appears on their profiles too, from a single upload. Each tick saves as you make it, with no Save button to forget, and reverts if it
cannot be stored. Untagging one player removes it from that player only — the photo stays on the training and on everyone else tagged.

The roster offered is the team that was actually training, so it is a short list of the people who were plausibly there rather than the whole
academy.

Worth knowing: a photo tagged to three players is visible to all three families. That is the shared-visibility policy the Media library page
describes, made concrete.

# TalentTrack v4.93.0 — Media library: demo content, storage visibility, and the off-switch verified (#2596)

The last of the media library work — the parts that make it a finished feature rather than a set of screens.

A demo academy now has media in it: a squad photo per team, a few player portraits and one external video link, so the media tabs show what they
are for instead of sitting empty. The placeholder images are drawn when the demo data is generated rather than shipped with the plugin, and nothing
is fetched over the internet, so generating a demo academy works offline.

Once an academy has media stored, the total appears as **Media stored** on the academy admin's system-health strip. Uploaded video is not small
and nothing reclaims it automatically, so the number belongs somewhere an admin already looks. There is no automatic clean-up: deciding when old
media should go is a policy question, not one to guess at.

The go-live runbook gains a media section covering the checks worth doing before an academy starts uploading — including the one that matters on
nginx servers, where the folder-level block does nothing and TalentTrack's own permission check is the only thing protecting a child's photograph.

Switching media off is now verified end to end. Turning the module off makes the whole feature unreachable; turning the feature off hides the
screens and keeps the files. Neither deletes anything, and switching back on restores exactly what was there — so an operator can try it.

# TalentTrack v4.93.0 — Messaging never fails silently any more (#2602)

A message the academy sends to a family could previously disappear without a
trace. A send whose recipient list resolved to nobody — a team with no linked
parents, say — looked exactly like a successful one, and a message naming a
template that was not registered left no record anywhere. Both now write to the
message log like any other outcome, so "was this family told?" always has an
answer.

The daily reminder run records whether each of its four checks actually ran and
whether it errored. A check that has been failing for months used to be
indistinguishable from one that simply had nothing to send.

Messaging that a person triggers can now report back per recipient — who it
reached, who has opted out, who has no usable contact details — instead of a
flat "sent". A new dry-run pass evaluates opted-out recipients, quiet hours,
sending limits and reachability *before* anything is sent, so a screen can warn
first. The surfaces that use both land with the message log and the send flows.

# TalentTrack v4.93.0 — Choose which messages your academy sends, and which ones you receive (#2603)

**Configuration → Messages** is a new screen listing every message TalentTrack can send — cancellations, selection decisions, nudges, reminders, letters — with a switch beside each one. Switching a message off stops it for everyone on every channel. It is still recorded in the message log as *switched off*, so you can always see that a message would have gone out and did not.

Previously the only lever was the whole Scheduled messaging feature: an academy that did not want goal nudges lost attendance flags, onboarding nudges and staff-development reminders along with them.

**My settings → Messages you receive** lets each person choose what the academy may send them, per message type. This was required all along and had no screen: the preference was read when sending and could not be set by anyone. Everything stays on until you change it. Safeguarding messages, and messages about getting back into your account, are shown as always-on and cannot be switched off.

The **SMS channel now defaults to off** on new installs. TalentTrack does not send SMS by itself — it needs a provider plugin — so leaving it on advertised a channel that failed every send. Installs that switched it on keep their setting.

# TalentTrack v4.93.0 — In-product notifications now follow your messaging rules (#2604)

Notifications raised inside TalentTrack — a task assigned to you, a reply on a conversation, a trial reminder — used to send email straight out, ignoring every rule the academy had set. They did not appear in the message log, they ignored quiet hours and the sending limit, and there was no way to refuse them.

They now go the same way as every other message. That means an academy can see them in the message log, they are held during quiet hours instead of arriving late at night, and **My settings → Messages you receive** has a new line for them: *Notifications about your tasks and conversations*.

A notification held back for one of those reasons is not treated as a delivery failure any more, so it is no longer retried on another channel or reported as undelivered — the message log records exactly what happened to it.

# TalentTrack v4.93.0 — Change a coach's role on a team without losing the assignment (#2608)

Functional roles → Assignments now has an **Edit** action. Promoting an assistant
coach to head coach — or the reverse — used to mean unassigning the line and
building a new one from scratch, which silently discarded the original start date.
Editing changes the role in place and keeps the assignment's history.

The change is more than cosmetic: it rewrites the person's head-coach flag on that
team, so the coach lands on the right persona dashboard on their next page load and
workflow notifications route to them. Team and person stay fixed on the edit form —
moving either is a different assignment.

An **End date** field also appears on both the create and the edit form. The
assignment record has always carried one; the form simply never offered it, so an
assignment could not be closed off anywhere in the interface.

# TalentTrack v4.93.0 — Record an injury, and record the return (#2609)

An injury is one of the transitions a player's journey is meant to carry — trial,
signing, promotion, injury, return to play. TalentTrack has modelled it since the
journey shipped, but there was never a screen to enter one, so in practice an
injury ended up in a free-text note or nowhere at all.

Players now have an **Injuries** tab. A head coach records an injury for their own
squad through a short guided flow — who, what, when — and closes it with **Record
return** when the player is back. Both ends land on the player's journey
automatically, so a coach reading the file next season can see what the player came
back from and how long it took.

A new **Injuries** tile answers the squad-level question: who is out right now,
since when, and who was expected back before today. An expected return that has
passed with nobody recording an actual one is flagged, because that row needs a
decision rather than a nudge.

Injuries stay medical data about minors: every read is audit-logged, entries on the
journey keep their medical visibility level, assistant coaches have no access at
all, and deleting one remains with the head of development and the academy admin.

# TalentTrack v4.93.0 — Buttons render in one consistent case (#2615)

Buttons rendered UPPERCASE or sentence case depending on which HTML tag they
happened to use, not on any deliberate choice. A link-styled button came out
`CANCEL` while the real button beside it in the same row read `Save`.

The casing now lives in the label rather than the stylesheet, so every button
reads the same way wherever it appears — including the sign-in card, the 404 page
and the admin screens, which sat outside the rule that was papering over it.

A side effect worth having: sentence case is roughly 12% narrower than uppercase
before letter-spacing, so button rows have more room on a phone.

# TalentTrack v4.93.0 — One create verb, one case, across every button (#2616)

Buttons that create something now all read **Add …**. The same action used to be
spelled four ways depending on which screen you were on — `+ New season`,
`New category`, `+ Add option`, `Create case` — and two labels existed in both
Title Case and sentence case at once, so `Add Goal` and `Add goal` were different
buttons for the same thing.

The leading `+` is gone from button labels. Page headers already draw their own
icon, so the glyph was duplicating an affordance the component provides — and on a
phone, where the label collapses to the icon, the `+` was invisible anyway.

A few labels also lost words they were repeating from the screen around them:
`Start 30-day Pro trial` is now `Start trial`, `Share via WhatsApp` is `Share`,
`Run Report` is `Run`.

# TalentTrack v4.93.0 — Buttons stop repeating the screen they sit on (#2617)

A button inside a section headed *Rating scale* said `Save rating scale`. One inside
*Match minutes* said `Save match minutes`. Fifty-four spellings of the same action,
each restating a title the user was already looking at. They are all just **Save**
now.

The same trim runs through the other action families: four different spellings of
"print this page" become **Print**, `Open the chemistry board` becomes **Open board**,
`Everyone was here - continue` becomes **All present**.

Where a screen genuinely has two things to save, the nouns stay — My settings keeps
its four, the PDP screen keeps Save conversation and Save verdict, and the custom-CSS
screen keeps its separate CSS and preset saves. A bare Save is only clearer when
there is one of them.

# TalentTrack v4.93.0 — English installs no longer show Dutch labels in the methodology screens (#2618)

Parts of the methodology authoring screens were written with Dutch text as the
source string — the image panel, the play-styles tab, and the Raamwerk tab label.
On an English install those rendered in Dutch, and because the source string was
already localised they could never be translated into any other language either.

The source strings are now English and carry the original Dutch as their
translation, so a Dutch academy sees exactly what it saw before while an English
one finally reads English. The image picker's "Afbeelding kiezen…" also loses its
trailing ellipsis.

The Analytics entity view drops its "← Academy view" button. It pointed at the
same place as the Analytics breadcrumb directly above it, so the screen was
offering the same route twice.

# TalentTrack v4.93.0 — Football actions are visible under every methodology set (#2620)

The Voetbalhandelingen tab came up empty on any academy whose active methodology
was not the one the plugin shipped first. The catalogue's 18 actions had been
stamped to a single set when selectable methodologies landed, and the second
shipped set was never given its own — so the Methodology library's Football
actions tab, the goal → football-action picker and the printed reference card all
showed nothing.

The catalogue is now shared across every set. A football action — "passen onder
druk" — is vocabulary of the game rather than of one club's play style, so
switching the active methodology no longer changes which actions exist. Principles,
phases, vision and formations stay per-set as before.

An action a coach adds now joins the shared catalogue instead of being visible only
under whichever set happened to be active when they wrote it, and a goal linked to
an action keeps resolving to the same action under either set.

# TalentTrack v4.93.0 — The archive filter folds into one button, and stops claiming to show everything (#2622)

Every list spent a row of buttons on a filter almost nobody touches: Alle,
Actief, Gearchiveerd. It has collapsed into a single **⋯** button at the end of
the filter row, on phone and desktop alike.

Two things were wrong beyond the wasted space. **Alle did nothing** — it
returned exactly the same rows as Actief, because a list with no filter has
always shown active records only. And it was the option highlighted when you
arrived, so the screen said you were looking at everything while showing you
active records. Alle is gone; the lists open on Actief and say so.

Switching to archived records still announces itself clearly: the ⋯ button turns
yellow and a label appears beside it naming the state, with a ✕ to go back. An
archived list can't be mistaken for an empty one.

Applies to players, teams, people, evaluations, goals, tournaments, holidays,
activities, exercises, training plans and PDP coverage. The Goals list's
Actief / Behaald / Gemist buttons are a different filter and are unchanged.

# TalentTrack v4.93.0 — One name for the archive filter across every list (#2625)

The holidays, tournaments, exercises and training-plan lists called their
archive filter `status`, while every other list called it `archived`. Same
control, two names — and `status` already meant something else on the players
and goals lists, where it selects a player's own status (trial, released) or a
goal's bucket (achieved, missed).

All four now use `archived`, and the filter is labelled "Archive" on those
screens, so "Status" consistently means a record's own status. Links you
bookmarked and views you saved before this change keep working; saved views are
migrated automatically the first time the plugin loads.

# TalentTrack v4.93.0 — Test trends: a trend you can see without colour, and names you can click (#2628)

The Test trends report showed whether a player improved or fell back in green
versus red and nothing else. That is invisible to a red/green colour-blind
reader — roughly one man in twelve — and it disappears entirely when the report
is printed in black and white, which is how it reaches most touchlines.

Every change now carries a glyph as well as a colour: green ▲ improved, red ▼
fallen back, grey ▬ unchanged. The word itself is still there on hover and for
a screen reader, so the separate Verdict column is gone and the table is one
column narrower on a phone.

Height and weight tests gained an indicator they never had: a grey ▲ or ▼ that
says which way the value moved and passes no judgement, because a taller player
is not a better one.

Player names in the tables and in the Most improved / Fallen back lists are now
proper record links — they match the colour of the text around them, and hovering
one shows the player summary card, the same as everywhere else in the app.

Both test reports now draw the indicator from one shared component, so they
cannot disagree about the same player's trend.

# TalentTrack v4.93.0 — Knowledge library: courses ship with the plugin (#2642)

TalentTrack develops players; it did not develop the people who develop
players. The knowledge library is where coach education now lives.

This first ship is the content spine. A course is a folder under `courses/`
whose `course.md` carries a front-matter block: title, summary, lesson order,
the capability and licence tier it needs, the methodology principles it
teaches, and whether its lessons unlock in sequence. Lesson order comes from
the manifest rather than from filenames, so retiring a lesson does not mean
renumbering the folder. A course also declares the language it was written in,
which is what lets a Dutch-first course sit beside an English-first
documentation corpus without either pretending to be the other.

The registry is a projection of the folder, never a list beside it — dropping
a course in registers it, deleting it unregisters it. A course the registry
cannot parse is skipped rather than fatal, so a half-written course in a branch
never breaks a reader's page; a new `course-lint` CI gate turns that silence
into a build failure, checking that every declared lesson exists, that no file
is left out of the manifest, that prerequisites resolve, and that every quiz
payload is answerable.

Shipping with it: *Periodiseren in voetbaltaal*, a Dutch trainerscursus on
football periodisation — eleven lessons, ten quizzes and a twelve-week final
assignment, built on the methodology from Raymond Verheijen's *Football
Periodisation* (World Football Academy, 2014).

Nothing is readable in the app yet: the reader, progress tracking, gating and
completion statistics land in #2643 through #2650. The module and its
`knowledge_courses` sub-feature can both be switched off.

# TalentTrack v4.93.0 — Knowledge library: lessons you can work with, not just read (#2643)

Courses are stored as markdown so they can be reviewed in a pull request and
translated like any other text. That is a storage decision, and it was never
meant to cap a lesson at prose. This ship is the render.

A lesson now carries typed blocks. Three of them are tools a coach uses
rather than reads:

The **zero-point calculator** takes the minutes a squad managed before their
action count visibly dropped and returns the overload step their next twelve
weeks start from. Guessing that step is the difference between overload and
either injury or nothing happening at all.

The **week planner** checks a proposed week against the recovery times as it
is built, and names what breaks: small-sided games on Thursday with a
Saturday match leaves 48 hours where 72 are needed.

The **pitch-size calculator** turns a game format into dimensions, and says
where the rule of thumb stops working — below 7v7 the computed width comes
out narrower than a penalty area, and a pitch that narrow quietly turns an
intensive endurance session into an extensive interval one.

Alongside them: the six-week model as three phases you can open, an action
notation that draws quality and recovery instead of asserting them, a load
matrix that recalculates for a three- or six-week cycle, callouts, and
self-check questions.

Every block renders a usable state on the server, so a reader with
JavaScript blocked still gets the tables, the model and the default matrix.
A lesson made only of prose and callouts loads no JavaScript at all. An
unrecognised block renders as a code sample rather than breaking the page,
so a course written against a newer release degrades on an older one.

Supercompensation times, step tables and pitch sizes now live in one place
that the blocks, and later the session planner, all read — a course that
teaches "72 hours" beside a planner that warns at 48 would be worse than
either alone.

Still not readable in the app: the reader view arrives in #2646.

# TalentTrack v4.92.0 — The generator: answer four questions, get a training plan (#2497)

**New training plan** on the Training tile opens a short wizard. Pick the team
and the date, choose what the session is about, confirm how long it runs and
how many players you expect — and the fourth screen is a finished session,
built from your own exercise library.

The number of players is filled in from your team's recent attendance rather
than its squad list, because a sixteen-player squad rarely puts sixteen on the
pitch. Change it whenever you know better.

Nothing is invented. Every exercise comes from your library, nothing is
proposed above the age group's intensity ceiling, and the same answers always
produce the same session. Where the library has no suitable exercise for part
of the session, that block is left blank and says so rather than being padded
out.

The last screen tells you which players' open goals the session actually works
on, by name.

Where a training cannot be drafted at all — an age group with no training
profile, so there is no age-safe intensity ceiling to plan inside — the wizard
now says so on the proposal screen and keeps you there, next to the Back button
that can fix it. It no longer walks you on to name a plan that was never going
to save.

The length you type is a request, not a guarantee: the blocks follow the age
group's training shape, so a 75-minute ask can come out at 90. When the draft
misses what you asked for by more than a few minutes, it tells you both
numbers rather than letting you find out on the pitch.

**Exercises now carry the principles they train.** The library's form gained a
"trains which principles" field, and exercises that already had a tactical
theme were linked automatically — 63 of them, across both shipped
methodologies. This is what lets the generator prefer a drill six of your
players need over one nobody is working on, and it is the same link that will
carry per-player training history later.

**Fixed:** the exercise library's intensity field offered levels 1 to 5, but
the scale runs to 10 and the older age groups train up to 7. Saving an
exercise through that form quietly reduced anything above 5. It now offers the
full range.

# TalentTrack v4.92.0 — Head coaches can correct their own squad's player records (#2584)

A head coach could not fix a jersey number, a position or a preferred foot for
a player in their own team — every correction went through an academy admin.
Head coaches can now edit players on teams they run.

Adding and removing player records stays with the academy admin: that is a
registration act with consequences for squad size, billing and safeguarding.
Assistant coaches keep read-only access, which is one of the few places where
the two coaching roles now differ.

# TalentTrack v4.92.0 — Test reports were unreadable on desktop (#2585)

The Test trends report collapsed into a row of narrow columns — chart, rankings
and table each squeezed to a sliver with text breaking one word per line —
because a styling rule written years ago for the small player card was being
applied to any panel that happened to share its name. That rule now belongs to
the player card alone, and the report lays out as intended: full-width chart,
rankings side by side, readable table.

The Test results table also wasted most of its width on the player-name column
while team names wrapped onto three lines and dates onto two. Its columns are
now sized to their content, so each row reads on a single line.

Both reports keep their mobile card layout unchanged.

# TalentTrack v4.92.0 — Test results now show how much a player changed, not just that they did (#2586)

The Trend column showed only an arrow — you could see a player had improved,
but not by how much, which is the part that matters. It now shows the signed
change beside the arrow, e.g. "▲ −0,08 s" on a test where lower is better.

Players with only one measurement still show a dash rather than a made-up
zero. The number follows the site language, so a Dutch install reads −0,08.

# TalentTrack v4.91.1 — Staff Certifications unusable on a fresh install (#2490)

The certificate-type vocabulary the screen depends on was never seeded, so
Staff Certifications could not be used at all until an admin added types by
hand — and nothing on the screen explained that this was the blocker.

Installs now arrive with UEFA-A, UEFA-B, UEFA-C, First aid, GDPR awareness and
Child safeguarding, translated into Dutch, German, French and Spanish.
Academies that already added their own types keep them.

# TalentTrack v4.91.1 — New-evaluation wizard offered coaches the whole academy (#2567)

The player picker in the new-evaluation wizard listed every team and every
player, not the ones the coach actually coaches. It gated on a capability that
reads like an admin check but that every coach turns out to hold, so an
earlier attempt at this same fix never took effect.

It now asks whether the user holds academy-wide player access. Head coaches
and assistant coaches see only their own squads; Head of Development and
academy admins keep the full list. Scouts, who previously got an empty picker
despite being able to record evaluations, now see the academy-wide list their
role is meant to have.

# TalentTrack v4.91.1 — Lookup labels rendered in English on translated installs (#2568)

Every lookup carries an English row copied from its canonical value, and that
copy was being returned in place of the translation — hiding translated labels
the plugin already ships. Most visibly the evaluation Type dropdown, which
read "Training / Wedstrijd / Oefen / Tournament / Observation / Other" on a
Dutch install.

Dutch, German, French and Spanish installs now show the translated label
wherever one exists. An academy that has deliberately renamed a lookup in
English still sees its own wording. Tournament, Observation and Other are also
seeded with translations for all four languages, since one of them had no
translation anywhere to fall back on.

# TalentTrack v4.91.1 — Admin screens were reachable by any coach via their address (#2569)

Configuration, Custom fields, Evaluation categories, Application KPIs, Lookup
normalisation, Roles & rights and Migrations all gated on a capability that
reads like an admin check but that every coach holds. Their tiles were hidden
from a coach's navigation, so this stayed invisible — but typing the address
was enough. A coach could also call the lookup-normalisation endpoints, which
change vocabulary academy-wide.

Each screen now gates on the permission that matches what it actually does.
Coaches are refused, academy admins are unaffected. Head of Development keeps
Evaluation categories and Application KPIs and no longer reaches the
configuration screens, matching the read-only-on-config intent already applied
to their dashboard.

# TalentTrack v4.91.1 — Evaluations list showed a coach nothing while the team rating showed data (#2571)

Team assignment is stored in one table and team *scope* — the thing that
answers "which teams is this coach responsible for?" — in another. The demo
generator and the Excel importer wrote the first without mirroring into the
second, so affected coaches held no team scope at all and the Evaluations list
quietly narrowed to "evaluations I personally authored". The team rating and
the player's Evaluations tab aren't coach-scoped, which is why they kept
showing the same players' data and the contradiction looked like a phantom
rating.

A migration backfills the missing scope rows, both import paths now mirror
correctly, and deleting a team clears its scope rows instead of leaving them
behind to outlive it.

# TalentTrack v4.91.1 — Season-intake batch printing cascaded pages into each other (#2572)

Printing a whole squad's season intakes produced a couple of pages with the
sheets running into one another instead of one sheet per page. The batch was
assembled by pulling each player's sheets back out of rendered HTML, which cut
them short and left the markup unclosed, so the browser nested the sheets
inside each other and page breaks stopped working.

Printing a squad of twelve now yields the expected 36 pages — three per
player. Printing a single player's intake was never affected and is unchanged.

# TalentTrack v4.91.1 — Players with no join date showed "2028 yrs in academy" (#2573)

A player whose join date was never set rendered an absurd academy tenure on
their status chip and a raw `0000-00-00` as the join date on their profile.
An unset date of that shape is neither empty nor invalid enough to be caught,
and reading it as a real date puts it two millennia in the past.

It is now treated as no date: the tenure falls back to when the record was
created, and the profile omits the join date rather than printing a
placeholder that looks like real data.

# TalentTrack v4.91.1 — Behaviour rating can be switched off per academy (#2574)

Academies that don't score behaviour were still shown a capture button on
every player, a bulk action on every team, and a step in the evaluation wizard
they always skipped. Behaviour rating is now a switchable sub-feature of
evaluations, controlled from the feature toggles screen.

It is on by default, so nothing changes unless you turn it off. Switching it
off stops new capture and hides the entry points; behaviour already recorded
is kept, and reappears if you switch it back on. Setting a player's potential
band is unaffected either way.

# TalentTrack v4.91.0 — The exercise library gets a screen (#2495)

The merged exercise library is now browsable. Open the **Training** tile and
choose **Exercises**: search by name, code or description, filter by category,
intensity, visibility or status, and open any drill to see its setup, group
size and diagram. VCT's conditioning exercises sit alongside your own, labelled
so you can tell them apart.

**Coaches can add their own drills.** A new exercise belongs to your team and
is usable in your plans immediately — nothing waits on approval. Whether the
rest of the club gets it is a separate call: the head of development sees an
"Added by teams" panel listing what coaches have written, with how many plans
already use each one, and makes the good ones club-wide.

Editing an exercise creates a new version, so plans and trainings that used the
old one keep showing it exactly as it was.

**For administrators.** The VCT permission that used to cover the exercise
catalogue, the age profiles and the macro-blocks has been split. The library
moved to the exercises permission coaches already hold; the age profiles and
macro-blocks kept a head-of-development-only permission, renamed
`tt_vct_admin_config`. Nobody gained or lost access — in particular the age
profiles, which set the age-safe intensity ceilings for U10–U14 players, remain
restricted.

# TalentTrack v4.91.0 — Moving between screens no longer flashes, and lands faster (#2517)

Clicking through the app reloaded the whole page: the screen blanked, the
sidebar and header were redrawn identically, and you waited.

Two changes make that feel like it used to look. Hovering a link now quietly
starts loading that page, so by the time you click it is usually already there.
And where the browser supports it, moving between screens cross-fades instead
of blanking — the sidebar and header hold still and only the content changes.

Neither alters how the app works: every click is still a normal page load, so
the back button, bookmarks, refresh and opening links in a new tab all behave
exactly as before. Browsers without these features simply navigate the way they
always have.

Two details worth knowing. Prefetching is skipped when your device asks for
reduced data or is on a slow connection, and it never runs ahead of a link that
changes something. And a page loaded in advance is **not** counted as a visit,
so your usage statistics still show where people actually went, not where they
hovered.

# TalentTrack v4.91.0 — **Fixed:** the app shell's sidebar now carries the academy crest and name at

the top and the signed-in user at the foot, so which academy you are in is
answerable without looking away from the navigation. Both shrink to their mark
alone when the sidebar is collapsed to icons.

**Fixed:** icon chips ignored the active visual theme. Dashboard tiles kept
their per-module colours and Configuration tiles rendered in the old green
even under a navy theme. While a theme is active, every chip now takes the
theme's colour; under the default theme the per-module colours are unchanged.

**Fixed:** with a theme active, the collapsible navigation groups still used
light-surface colours on the dark sidebar — hovering a group painted a
near-white block behind near-black text, and the hairline between groups
rendered as a bright bar. Both now follow the theme.

# TalentTrack v4.91.0 — Search where you look: type straight into the header field (#2531)

The search box in the header used to be a button in disguise — clicking it
opened a separate window with its own box to type in. Two steps to start
something already on screen.

Now it is a real search field. Type in it and matching players, teams,
activities and sections appear directly underneath as you go; click one, or
pick it with the arrow keys and press Enter. Escape closes the list and leaves
your cursor where it was. **⌘K** (Ctrl-K on Windows) jumps to the field.

The field is also about twice as wide and sits centred in the header, so long
player and team names fit on one line instead of being cut off.

# TalentTrack v4.91.0 — The navigation column now stays put and reaches the bottom of the screen (#2533)

The sidebar stopped after its last group, leaving the page showing through
below it, and it drifted with the content as you scrolled a long page.

It now fills the left side from under the header to the bottom of the window
and stays there while the content scrolls. When there are more destinations
than fit, the list scrolls inside the sidebar rather than making the page
longer.

The column is also a little wider, so the longest section names sit
comfortably instead of only just fitting.

# TalentTrack v4.91.0 — A player's test results now have a readable history (#2536)

A test on a player's **Measurements** tab showed its latest value, a flag
against the age-group target, and a sparkline about a centimetre tall. To see
whether a player was actually getting faster over the season you had to export
to Excel.

Every test with more than one result now carries a **Show history** link that
opens the full picture underneath it: a dated chart with the value axis, each
reading labelled, and the age-group target shaded so you can see when the
player crossed into it. Where a test measures something with no better or worse
— height, weight, shoe size — there is deliberately no chart: those are grouped
together and shown as readings per date in columns, with a plain change figure
and no verdict, because a rising line would imply progress and a shaded band
would imply a norm that does not exist. Status tests show one block per date in
that level's own colour rather than a line through named states, and pass/fail
tests show a tick per date with the tally.

On a test where lower is better, an improving line goes down — every such chart
now says so in words rather than leaving the reader to work it out from the
slope. A test with a single result says so too, instead of drawing an axis
around one point.

The chart is server-rendered SVG with no JavaScript, so it also works in print
and in the PDF report path.

# TalentTrack v4.91.0 — Test trends: one test, every player, over the season (#2537)

*Test results* has always answered "how is each player doing on this test right
now". The other half of the question — **who is developing and who is
stalling** — existed only inside the Excel export's Trends sheet. It is now a
report.

**Test trends** (Analysis group) takes a test, optionally a team and a date
window, and shows a line per player over the shared date axis with a heavier
dashed squad-average line, then **Most improved** and **Fallen back**, then a
table with each player's first value, latest value, change and verdict. Every
player name links to their profile and back.

The report's shape follows the test, because a trend only means something in
the terms of its own test. A test with no direction — height, weight — gets the
readings per date and nothing else: no chart, no ranking, no verdict, because
there is no better or worse to rank. A status test gets a player × date matrix
of levels in their own colours rather than lines through named states. Pass /
fail gets ticks, a per-player tally and the pass rate per round.

**The change is read in the direction of the test.** On a test where lower is
better, −0,08 s is an improvement: green, *improved*, and ranked under Most
improved. A change smaller than 2% counts as *about the same* and appears in
neither ranking — a one-percent move on a hand-timed sprint is inside the noise.

A team-scoped coach sees only their own teams, and a link to another team's data
is refused rather than quietly widened. Integrations read the same numbers from
`GET /reports/test-trends`. Administrators can hide the report under
**Settings → Features → Test trends**.

# TalentTrack v4.91.0 — Configuration: export the academy's settings and module state as JSON (#2540)

**Configuration → Export configuration** downloads this academy's whole
configuration as one JSON file: every setting from `tt_config`, the
install-level values from `wp_options`, and — the part that had no surface
before — which modules and features are switched on or off.

Each module and feature entry carries its human label and the `?tt_view=`
screens it owns, so the file answers "which surfaces does this install
actually have?" rather than just listing class names and booleans. That is
the question worth asking before writing training material for an academy:
a module that is off takes its screens with it.

Integration credentials stored in `tt_config` — the Strava app secret, the
Spond password and token, the DeepL API key, the Google service account —
are replaced with `[redacted]` and collected under `redacted_keys`. The key
name is kept so you can still see that an integration is configured; the
value never leaves the server. No player data is included.

Also available through the API at
`GET /wp-json/talenttrack/v1/exports/config_json?format=json`, gated on
`tt_edit_settings` and recorded in the audit log. Export only for now —
there is no importer yet.

# TalentTrack v4.91.0 — Help topics are registered by the doc files themselves (#2544)

Each help topic's title, group, summary and audience now live in a front-matter
block at the top of its own markdown file instead of in a separate list inside
the plugin. Dropping a documented file into the docs folder registers it — which
is what stops shipped features from having documentation nobody can reach.

Dutch titles and summaries come from the Dutch doc, so the sidebar no longer
depends on the translation catalogue for its labels.

One topic surfaces as a result: **Trial cases** was filed under a sidebar group
that does not exist, so it had never appeared in Help & Docs and could only be
reached by typing its URL. It now sits at the end of Performance.

# TalentTrack v4.91.0 — Help links stay inside the app, and can open the screen they describe (#2545)

Following a link inside Help & Docs used to hand you to the WordPress admin —
a dead end for a coach or a parent, most of whom cannot load it. Cross-references
between help topics now stay in the help viewer you are already reading in.

Help topics can also link straight to the screen they describe. Those links know
what you can reach: a link to a screen your academy has switched off, or that
your role cannot open, is shown as plain text rather than sending you to a
permission-denied page. Following one carries a back link, so you land on the
screen with one tap back to what you were reading.

The handful of topics that genuinely document WordPress admin pages still link
there, but only for administrators, and the link is marked as leaving
TalentTrack.

Also fixes cross-references between topics, which had been rendering as
unclickable text.

# TalentTrack v4.91.0 — Two-factor sign-in no longer loops back to the code screen (#2553)

**Fixed:** entering a correct two-factor code could drop you straight back on
the code screen instead of moving you into the app. The challenge had actually
been cleared, so nothing intercepted the page any more — you were signed in,
looking at a form with nothing left to verify, and the only way out was editing
the address bar.

The cause was the sign-in form's "where to go next" value, which defaults to
whatever page you are currently on. Once the address bar held the two-factor
screen — after a refresh, a back-button, or signing in again from that page —
that screen became its own destination. It is now excluded: the two-factor
prompt and the enrollment wizard can never be a post-verification landing page,
and anything else you were genuinely headed for still survives the detour.

Also fixed: an abandoned challenge left its destination live for a quarter of an
hour, so a later sign-in inside that window inherited it. The destination is now
dropped along with the challenge.

# TalentTrack v4.91.0 — The two-factor screen no longer wears the whole app around it (#2554)

**Fixed:** immediately after signing in, before the second factor had been
entered, the two-factor screen rendered inside the full application — navigation
rail with every module, global search, notification bell, persona menu, a link
into the WordPress admin, and a breadcrumb trail back to the dashboard, with the
code field sitting underneath all of it. That reads as "you're in, now also type
a code", which is the opposite of what the screen means.

Both challenge screens — the code prompt and the enrollment wizard a user is
held at when two-factor is required but not yet set up — now render on the same
centred, branded card as the sign-in and password-reset screens: academy crest,
academy name, the challenge, nothing else. A *Log out* link on the card is the
way out for someone who can't complete it, which the navigation used to provide
by accident.

Enrollment started deliberately from the Account page is unaffected and keeps its
normal in-app wizard chrome.

# TalentTrack v4.91.0 — Head coaches can create team blueprints again (#2557)

The "+ New blueprint" button on a team's blueprint list was a dead link for
head coaches: the list rendered it from the `team_chemistry` matrix (which
grants head coaches manage on their own teams) while the wizard behind it
still gated on the raw `tt_manage_team_chemistry` capability, which only
administrators, heads of development and club admins hold. Clicking the
button just reloaded the page.

The blueprint wizard now resolves its entry gate through the same
`TeamChemistryAccess::canManage()` decision the list, the editor and the REST
writes already use, via a new optional `isAvailableFor()` hook on the wizard
registry. The other seven wizards are unchanged. A read-only viewer no longer
sees an empty-state message pointing at a button they don't have.

# TalentTrack v4.91.0 — The Spond connection page for a team opens again instead of erroring (#2559)

A head coach who opened **Spond connection** from their team's page got the
site's critical-error screen instead of the panel. The page never worked:
it looked up the team id from a place that did not hold it, so it always
asked for team "nothing" and stopped there.

It now reads the team from the address as the other per-team pages do, and
opens on the connection panel for that team. An address without a usable
team id shows the ordinary "no access to this team's Spond connection"
message rather than an error page.

# TalentTrack v4.91.0 — Top bar tidied, and the keyboard hint now names a key you have (#2563)

Three fixes to the top bar in the app shell layout.

Its contents had drifted to the far left, leaving most of the bar empty — the
academy name moved into the sidebar and nothing took its place. Notifications
and help now sit on the right where they belong, and the search box is centred
in the bar rather than tucked beside them. The search box is wider again, and
the navigation column gains a few pixels too.

The keyboard hint on the search box read **⌘K** for everyone. That is the Mac
Command key, so on Windows it named a key that is not on the keyboard. It now
reads **Ctrl K** on Windows and Linux and **⌘K** on a Mac. The shortcut itself
always worked on both — only the label was wrong.

# TalentTrack v4.90.0 — One exercise library: the VCT catalogue merges into the main library (#2494)

TalentTrack held two exercise catalogues that could not see each other — the
general library and the VCT conditioning catalogue — each with its own fields.
They are now one. Every VCT exercise moves into the main exercise library,
keeping its intensity band, player range, age window and match-day
suitability, and the VCT session planner keeps working exactly as before:
the same inputs still produce the same session.

Nothing changes on screen in this release. It is the groundwork for the
Training module, where coaches browse and build from a single library instead
of meeting two.

# TalentTrack v4.90.0 — Groundwork: training plans get their own record (#2496)

Adds the foundation for the Training module: a training plan, its ordered
blocks, the methodology principles it covers, and a record of each time the
plan is actually run against a training in the calendar. Coaches, heads of
development and academy admins get a new "training plan" permission, and the
whole thing is reachable through the API before any screen exists.

Nothing appears on screen yet. The design that matters for later: a plan is a
reusable template you can keep editing, while each execution takes a permanent
snapshot of what it contained on the day. Adjusting a plan in September can
never change what a session in August says it was — which is what makes the
per-player training history trustworthy when it arrives.

# TalentTrack v4.90.0 — A Training tile, with your training plans in it (#2496)

The Training module gets its first screen. A new **Training** tile in the
Planning & tactics group opens your training plans: search them, sort them,
filter between team plans and club templates, and archive the ones you are
done with.

Open a plan to see what it holds — the total time, a colour-coded strip
showing how the training splits across its blocks, every block in order with
its coaching points, and each training the plan has actually been used for.

Read-only for now: building and editing a plan comes next, along with the
generator that drafts one for you.

One thing worth knowing while you use it. Attaching a plan to a training takes
a permanent copy of the blocks as they were that day, so you can keep
improving a plan afterwards without ever changing what a training that already
happened says it contained.

# TalentTrack v4.90.0 — App shell now fills the window, and its header stays put (#2504)

With the app shell switched on, the application was still drawn as a centred
document: on a 27" screen at 2560px that meant roughly 550 pixels of empty page
down each side, with the sidebar floating in from the edge rather than sitting
against it. The header also scrolled away with the content, taking search,
notifications and the account menu off screen on any long page.

The app shell now owns the full width of the window, with the sidebar against
the left edge, and the header stays pinned while the content scrolls beneath
it. The sidebar pins directly below the header instead of sliding underneath,
and both allow for the WordPress admin bar where it is shown.

Classic is unchanged — it keeps the centred, width-capped reading layout it has
always had. Switching between the two is still a clean swap either way.

# TalentTrack v4.90.0 — The app shell sidebar now lists every section, in collapsible groups (#2505)

The app shell's sidebar showed three entries — Activities, VCT training
designer and Open wp-admin — while the tile overview on the same screen showed
around thirty. Everything else was simply missing from the navigation, which
rather defeats having a permanent sidebar.

All **59** destinations now appear, grouped exactly as the tile overview groups
them. Because that is a long list, groups fold: the group you are working in
opens automatically, the rest stay tucked away, and the sidebar no longer turns
into a column you have to scroll. Opening a group is one click and the sidebar
remembers nothing you did not ask it to.

The sidebar is also a little wider, so longer section names fit on one line
instead of wrapping.

# TalentTrack v4.90.0 — Two identically-named entries under "My" are now told apart (#2506)

Anyone holding both staff and player access — every academy admin, and a
player-coach — saw **My PDP**, **My goals** and **My evaluations** listed twice
under "My", with nothing to say which was which except clicking one.

The staff versions now carry a small qualifier: *My PDP (staff)*. It only
appears when the same name is genuinely shown twice, so a staff member who is
not also a player, or a player who is not staff, still sees the plain names as
before. *My certifications*, which never had a twin, is untouched.

# TalentTrack v4.90.0 — Search: players and teams no longer pushed out by section matches (#2508)

Typing the first letters of a player's name into the search box often showed
only sections — Activities, Team planner, Test coverage — and never the player.
Typing `er` matched nineteen players and showed none of them.

Sections were listed first and the whole list then cut to eight, so any search
matching eight or more sections had no room left for anything else. Since there
are around sixty sections to match against, two-letter fragments hit that limit
constantly — which is exactly when you are still typing a name.

Sections now take at most three places whenever records are also matching, and
the rest of the list goes to players, teams and activities. Nothing is lost the
other way: when a search matches no records, sections still fill the list, and a
name that matches no section gets the whole list to itself. The list also holds
ten results instead of eight.

# TalentTrack v4.90.0 — Bump: minor

**Visual themes.** The frontend's colours, corners and heading type are now a
setting. Alongside the shipped green-and-gold **Default**, there is
**Federation** — a navy chrome with a gold marker on the active section,
squarer corners and a condensed heading face. The academy picks a default
under Configuration → Appearance, and each person can pin their own under My
settings → Theme.

A theme changes appearance only — no permission, field or button changes with
it. While a theme is active it supplies the whole colour scheme, so the colour
and font settings under Appearance do not apply; your logo and academy name
still do, and the Colours panel says so rather than letting you pick a colour
that does nothing. Setting the theme back to Default is a complete rollback:
the theme's stylesheet is not loaded, no theme class is written into the page,
and your colours return exactly as you left them.

**Fixed:** with the app shell on, the navigation sidebar listed only three
destinations while the tile overview on the same screen showed thirty. Every
section is now reachable from the sidebar (#2505).

# TalentTrack v4.90.0 — Attendance reports count only activities marked completed (#2521, #2522, #2523)

Attendance statistics counted sessions that had not been held. An activity
reading **Status: Planned** on screen still reached the reports, because the
reports gated on an internal planner column that arrives set to "completed" on
every activity the planner did not create — which is every Spond import and
every activity added from the form or the wizard. The team and player
attendance reports, the leaderboard, the at-risk panel, the daily
attendance-flag notification, the team KPI tiles and the **Activities** badge on
a player's file now all read the status shown on the activity page, so a
planned session contributes nothing until it is marked completed and the
figures can be checked against what is on screen.

Because recording who was there is itself the statement that a session took
place, **saving the attendance grid now marks the past-dated planned activities
it wrote to as completed** — after asking. A column for a past-dated session
still marked planned carries an amber underline, and pressing Save opens a
dialog naming every activity whose status is about to change, with **Save and
mark completed** or **Back to the grid**; nothing is written until you choose,
and a save that changes no statuses shows no dialog. The save bar then reports
how many were marked. Future-dated sessions and activities marked Cancelled are
never completed this way. This replaces the previous behaviour, where grid entry
deliberately left completion to a separate click — under the new gate that would
have meant a coach's entry never reaching the reports.

An activity's **Attendance** card and the **Present** figure in its stat strip
counted the planned roster on top of the recorded register, so an activity with
both could report more players present than the squad holds — "28 / 15
present". Both now count recorded attendance only. The **Present** figure also
waits for the activity to be completed, matching the Attendance card below it,
rather than stating a turnout for a session that has not happened.

# TalentTrack v4.90.0 — **Fixed:** the *Visual theme* selector was on Configuration → General instead

of Configuration → Appearance, two clicks away from the colours and type it
governs — and away from the notice on Appearance explaining that a theme
overrides those colours. It now sits at the top of the Colours panel on
Appearance, directly above that notice. Navigation layout is unchanged and
stays on General.

# TalentTrack v4.89.1 — Demo data: selective generation lost its coaches, and with them every evaluation (#2503)

Unticking **Generate teams** on the demo form and generating on top of your own
squads quietly produced no evaluations at all. The run reported success.

`head_coach_user_id` is not a column on the teams table — it is attached to the
team objects the generator builds, and every downstream generator reads it from
there. The path that loads existing teams did a plain `SELECT *`, so the coach
was simply absent: activities were filed under user 0, and the evaluation
generator skipped every team because it had no coach to attribute the
evaluation to.

The coach is now resolved from the team roster, and a team with nobody marked
head coach falls back to whoever ran the generation, with a notice naming those
teams so the silence is visible.

The same shape problem hit player archetypes, which drive each player's rating
trajectory. Without them every player fell back to the same "steady" curve, so
a selective run produced a flat line for the whole squad; archetypes are now
recovered for previously generated players.

On a three-team academy this is the difference between 0 and 516 evaluations
with 12,900 ratings, spread across the configured scale instead of pinned to
one value.

# TalentTrack v4.89.0 — Demo data: coverage manifest, and journey events are wipeable again (#2462)

The demo-data module now declares its coverage in one place. Every table the
schema creates is classified in `DemoCoverage` as generated, planned, or
exempt with a stated reason, and the wipe, the generate form and the wipe
form all derive from that declaration instead of four hand-maintained lists
that had to agree.

The immediate fix an operator will notice: journey events generated during a
demo run were never tagged, so no wipe could ever reach them — an install
seeded with the `small` preset was carrying 606 orphaned timeline rows that
survived every "wipe demo data". They are tagged now and wipe with their
players. Excel-imported trial cases had the same gap and are also reachable.

Generated output is otherwise unchanged: the same seed and preset produce the
same academy, byte for byte, as before.

Two CI gates keep it that way. A migration that adds a `tt_` table now fails
the build until it is classified, and a self-check proves the delete order is
dependency-safe and that no generator can write rows the wipe cannot reach.

# TalentTrack v4.89.0 — Demo data: guardians, injuries, player profile and reports (#2463)

A generated player is now a dossier rather than a roster entry. Demo runs
fill in the guardian link and its parent-visibility grants, injury records
with return-to-play dates, age-group history, the full attribute matrix the
chemistry surfaces read, the club's own custom fields with values, links from
goals back to the evaluation that prompted them, and a spread of player
reports.

Injuries go through the same repository the Injuries screen uses, so they
raise the same timeline events and the same recovery-due workflow task a real
injury would — a demo timeline reads exactly like a production one.

Two deliberate limits. Guardians attach to the demo parent accounts rather
than minting an account per player, so each parent account gets a family of
one to three children and the rest of the roster has no linked guardian —
enough for the parent persona to sign in to something real, without a dozen
welcome emails per run. And generated reports carry no share token and no
recipient address, so nothing hands out a working public link.

# TalentTrack v4.89.0 — Demo data: measurements and the PDP cycle (#2464)

Two of the screens that best show what TalentTrack is for rendered empty on a
demo install. Both are filled now.

Demo runs create a testing battery an academy would actually use — height and
weight, 10 m and 30 m sprints, countermovement jump, shuttle run, juggling,
passing accuracy, a dribble circuit and a focus self-assessment — with target
bands per age group, team testing sessions across the window, and a result per
player per session. Each test declares which direction is better, so a sprint
time and a jump height are graded the right way round.

Results follow a per-player trend rather than noise: a player sits
consistently above or below their age group and improves across the season, so
the progression charts show something real. A few players miss a round, so the
coverage indicator isn't a flat 100%.

The PDP side gets the season, a development dossier per player, its
conversation cycle, calendar links on what's still scheduled, and verdicts on
the dossiers that have closed. Conversations that have already passed are
conducted and signed off while the next one stays open, so both halves of the
screen have something in them.

All of it goes through the PDP repositories, so the conversation cycle is
spaced by the same planning-window rules as the real flow and a signed-off
verdict raises its timeline event.

# TalentTrack v4.89.0 — Demo data: training content and match day (#2465)

A generated training used to be a calendar entry with an attendance list, and
a generated match had no result. Both have content now.

Trainings get four to six exercises from the club's library in order, with
durations that add up to roughly the session, plus the methodology principles
they work on. Per-team exercise overrides and the season's holiday windows are
filled in too.

Every fixture gets match prep — availability, a starting eleven, roles and
per-player intent — and every fixture already played gets a result, goal
events, substitutions and a light tracked-event stream. Fixtures still ahead
get prep and no result, which is what a coach's screen looks like mid-week.

Squad size follows the age group, because youth football is small-sided: an
under-9 team fields six, an under-12 eight, and eleven only from the early
teens. A twelve-player under-8 squad was never going to produce an eleven, so
without this the youngest teams generated no match data at all.

The generated match data is internally consistent, which matters because
reports read it as though it were real: availability never marks a player
present on a date their injury record says they were out, goal scorers come
from that match's lineup, and substitutions take a starter off for a bench
player, so derived minutes-played never exceed the match length and a team's
total lands exactly on squad size times it. That last point is what makes the
minutes reports usable on a demo install for the first time.

# TalentTrack v4.89.0 — Demo data: team development (#2466)

Each generated team now has a shape and a way of playing: an age-appropriate
formation from the shipped templates, a playing-style mix across possession,
counter and press, a match-day blueprint with its slot assignments, and a few
coach-marked pairings.

Chemistry snapshots are computed by the chemistry engine from the team's own
blueprint lineup rather than invented, so the stored score agrees with what a
recompute produces. The series runs across the generated window so the trend
view has a line rather than a single point.

Formations, position profiles and set pieces are shipped methodology content
that migrations already seed, so the generator assigns and uses them instead
of building a parallel set — a demo club with two formation libraries would be
worse than one with none.

# TalentTrack v4.89.0 — Demo data: scouting, trials and tournaments (#2467)

The intake pipeline was invisible on a demo install: no prospects, no scouting
visits, and trial cases only if you happened to upload a workbook containing
them. All three are generated now, along with tournaments.

Most generated players carry a historical trial case, closed with an admit
decision and dated before they joined the roster. That matters more than it
sounds: without it a demo academy's players appear fully signed from nowhere,
and the player journey the product is built around has no beginning. A couple
of players keep an open case so the surface a scout works on every week has
something on it. Each case has a staff panel of two or three, assessments from
most of them, and extensions on some of the open ones.

Trial cases fire the same hooks the Trials module fires, so the timeline gets
its trial-started and decision events in exactly the shape production writes
them.

Scouting visits run across the window in all three states — completed, planned
and cancelled — with prospects attached to the completed ones, named from the
same Dutch pools the roster uses so the pipeline reads like the same club.

Tournaments get a squad with target minutes, four short fixtures, and
per-period assignments that rotate through the squad so nobody sits out —
which is the point of a youth tournament planner.

# TalentTrack v4.89.0 — Demo data: staff development, messages and operator records (#2468)

The last uncovered corner. Demo runs now fill in staff development —
development plans, goals, evaluations with per-category ratings, and mentor
pairings — plus the conversations and operator records that make an install
look used rather than newly installed: threads with uneven read state so
unread badges are actually non-zero, saved filters, report presets, workflow
tasks, and invitations in all four states.

Nothing here sends. Invitations and workflow tasks are written directly rather
than through the services that dispatch them, so the invitations screen shows
pending, accepted, expired and revoked rows without anyone receiving anything
and without the workflow engine firing.

Staff certifications are the one thing that stays empty: they require the
club's certificate-type vocabulary, which has no default seed (#2490). The
generator skips them rather than inventing lookup entries.

With this, every table the schema creates is either generated by a demo run or
recorded as exempt with a reason — no table is unaccounted for.

# TalentTrack v4.88.0 — Usage statistics: "last N days" windows now use the site's timezone (#2444)

Every "last N days" figure on the usage-statistics surfaces was off by the
site's UTC offset. Events are stamped in site-local time, but each window
boundary was built in UTC, so on a Dutch install the window started two hours
late: activity between 00:00 and 02:00 on the oldest day of the window was
left out, and the same two hours from the day before were counted in. The
daily-active-users chart and the "events on this day" drill-down could also
disagree at those edges, filing a 00:30 event under the neighbouring day. The
90-day retention prune deleted two hours early for the same reason.

All of these boundaries are now built in the site's timezone, so the numbers
line up with the calendar days people actually worked. Counts on an offset
install will shift slightly — that shift is the correction. No data changed:
the stored events were always site-local, this fixes how they are read.

# TalentTrack v4.88.0 — Buttons that rendered as grey native controls are now properly styled (#2445)

A group of buttons across the app rendered as raw browser-default controls —
grey, square, system font — instead of TalentTrack buttons. The most visible
were on the evaluation wizard's Attendance step ("Everyone was here —
continue" and "Mark all present"), but the same fault affected the rate-confirm
Yes / Skip fork, the trials list and its tracks and letter-template editors,
the trial parent-meeting actions, the tournaments squad step, the wizards admin
page, the activities reopen-rating button, and the MFA and desktop-only
prompts.

The cause was a class name that never existed: `tt-button` and its
`-primary` / `-secondary` / `-small` variants have no styling defined
anywhere, so every element carrying one fell back to the browser default. All
32 occurrences now use the real button system, and a CI check fails any future
pull request that reintroduces the phantom name — it kept coming back because
nothing ever complained about it.

The wizard's own Cancel / Back / Next / Save-as-draft bar is unchanged: it was
already fully styled by its own rules, so it simply drops the dead class
rather than gaining a new one.

# TalentTrack v4.88.0 — Saved views are now part of the standard filter bar (#2448)

Saved views — the named filter combinations you re-apply with one click —
shipped for the five attendance and minutes reports. They were built as a
separate strip bolted on above the filter bar, wired report by report, which
meant no other screen could offer them.

They are now part of the shared filter bar itself. Nothing changes on the five
reports: the same views, saved under the same names, keep working exactly as
before. What changes is underneath — any screen built on the standard filter
bar can now switch them on, which is what lets the players, teams, evaluations
and goals lists get them next.

Two details worth knowing. Which filters a saved view captures is now worked
out from the filter bar's own configuration rather than a fixed list, so a
screen can't be wired up to save an empty view by accident. And each screen's
saved views are gated on that screen's own permission instead of the reports
permission, so a saved view can never expose a screen the user isn't allowed
to open.

# TalentTrack v4.88.0 — Saved views arrive on the lists and the standard reports (#2449)

Saved views — name a filter combination, re-apply it with one click — were
only on the five attendance and minutes reports. They now appear on the
surfaces coaches actually work in: the players, teams, people, evaluations,
goals, tournaments and holidays lists, the activities list, the audit log, and
all six standard reports.

On a list, a saved view remembers more than the filters: the search term and
the sort order go with it. Restoring a view that put the filters back but
quietly reset the sort would not be the view you saved.

Views stay personal — only you see yours — and each belongs to the one screen
you saved it on, so a players view never turns up on the teams list. Each
screen's views are gated on that screen's own permission, so a saved view can
never reveal a screen you would not otherwise be allowed to open.

Not included, deliberately: the attendance and minutes entry grids (data-entry
screens rather than browsing ones, where the strip would compete with the
grid's own controls), the custom-fields settings screen, and the trials list,
player comparison and My activities — those three decide access with composite
rules rather than a single permission, so they need their own pass rather than
a guess.

# TalentTrack v4.88.0 — Saved views: pick one to open by default (#2450)

A saved view is one click. Now it can be zero. In a saved view's **…** dialog,
tick **Open this view by default on this screen** and that view is applied
whenever you open the screen without filters of your own — arriving at the team
attendance report already scoped to your team and this season, rather than to
everything.

One default per screen, per person. Marking a new one releases the old one.
The default view is marked with a star in the strip so it is always clear which
lens you are looking through, and the address bar shows the filters that were
applied, so the page can still be bookmarked or shared.

Your default never overrides a deliberate choice. Following a link that already
carries filters, returning through a **← Back to** pill, or opening a URL
someone shared all show exactly what those addresses ask for. To see everything
unfiltered, use **Clear** in the filter bar — that escapes the default for the
visit rather than bouncing you back into it.

Available on the team, player and leaderboard attendance reports and the two
minutes reports. The lists gain it in a later release.

# TalentTrack v4.88.0 — Saved views: rename them, update them, and clearer confirmations (#2451)

Changing a saved view used to mean deleting it and saving a new one, which lost
its place in the list. Each saved view now carries a **…** button that opens a
small dialog where you can rename it, tick a box to replace its filters with
the ones you have set right now, or delete it — without losing anything else
about the view.

Saving a name you have already used on the same screen is now refused with a
message saying so, instead of quietly creating a second chip with the same
label that you cannot tell apart. The same name on a different screen, or the
same name used by a different person, is still fine.

The confirmation and error messages have moved from the browser's plain grey
pop-ups to the app's own dialog, so they are translated, readable to a screen
reader, and harder to miss. Deleting asks twice, because Delete sits next to
Save in the same dialog.

The single manage button replaces what would otherwise have been three small
icons per chip — at the size needed for comfortable tapping they did not fit
side by side on a phone, and a screen with five saved views would have carried
fifteen of them.

# TalentTrack v4.88.0 — Navigation layout is now a setting (#2456)

TalentTrack can now render its frontend in a persistent **app shell**: a grouped
navigation sidebar at laptop widths, collapsible to a strip of icons, and a
slide-out menu behind a ☰ button on smaller screens. The entries come from the
same registry that builds the tile overview, so everyone sees exactly the
sections their role already had — same names, same order, same permissions, now
always on screen instead of a trip back to the tile overview.

The layout is a choice at two levels. Academy admins set the default under
*Configuration → General → Navigation layout*; anyone can pick their own under
*My settings → Layout*, either following the academy or pinning a layout for
themselves. **Classic remains the default**, so nothing changes until someone
opts in, and switching back restores the previous chrome exactly.

# TalentTrack v4.88.0 — The player stays on screen while you scroll (#2457)

Under the app shell, a player's photo, name and team now stay pinned to the top
of their profile along with the section tabs, so scrolling a long Evaluations or
Measurements pane no longer leaves you wondering whose record you are in — and
you can switch section without scrolling back up.

The full player header still greets you on arrival; it is the slim strip
underneath that follows you down the page. Classic layout is unchanged.

# TalentTrack v4.88.0 — Jump to anything, and look without leaving (#2458)

Two additions to the app shell.

**Search.** A search box in the top bar — or ⌘K / Ctrl+K — opens a jump-to
overlay that finds sections, players, teams and activities from a few
characters. It opens showing the sections you can reach, so it works as a
launcher before you type anything. You only ever see records you already have
access to.

**Preview.** On a laptop, following a link to a player, team or activity from
somewhere else now opens a preview panel beside what you were reading instead of
navigating away. Check the detail, then either open it properly or close the
panel and carry on exactly where you were — no more losing your place and your
scroll position to answer a small question. On phones and tablets the link
navigates as before.

Both are app-shell only; classic layout is unchanged.

# TalentTrack v4.88.0 — Thumb-zone navigation bar on phones (#2459)

Under the app shell, phones now get a fixed navigation bar along the bottom of
the screen — four destinations plus **More**, which opens the full tile
overview. It sits in the thumb zone and clears the iOS home indicator, so the
things you reach for at the side of a pitch are one tap away instead of a trip
through the slide-out menu.

Which four you get is derived from your role: the first four everyday sections
you have access to, in the standard group order. Setup and configuration
sections are never placed there. The slide-out menu still carries everything, so
nothing is hidden — the bar is a shortcut, not a filter.

# TalentTrack v4.88.0 — Ratings grid: collapsed categories no longer pull the header off its columns (#2474)

Opening the ratings grid on an activity whose categories have sub-categories
showed a header detached from the data: the first main category stretched
across every score column and the ones after it sat over empty space. It hit
every not-yet-rated activity, because groups start collapsed until a
sub-category holds a score.

The main category headers were spanning their sub-columns even while those
were folded away. A folded column is removed from the table altogether, so the
extra width was columns no row ever filled, and each following group drifted
one block to the right. The header now spans what is actually on screen, and
follows along when a group is folded open or shut. A main category with no
score of its own keeps an empty placeholder column while collapsed, so its
label and expand toggle still have a column to sit over.

# TalentTrack v4.88.0 — Teams, activities and staff stay pinned while you scroll (#2479)

Under the app shell, team, activity and staff pages now keep a slim strip at the
top carrying the record's name and a line of context — the age group, the date,
the role. The full header still greets you on arrival and scrolls away; the strip
is what follows you down the page, so working through a long roster or an
attendance list no longer leaves you checking which record you are in.

Same treatment players got in the previous release, now shared rather than
rebuilt per page. Classic layout is unchanged.

# TalentTrack v4.87.3 — Explorer: relative date bounds now actually narrow the results (#2440)

The dimension explorer offered a relative date bound — its *Date after* box
even suggests `-30 days` — but nothing ever expanded it. The raw text went
straight into the query, where MySQL read it as `0000-00-00` and matched every
row, so the filter looked applied while quietly doing nothing. Four KPIs that
ship a 30-day default window were unbounded for the same reason.

Relative bounds are now resolved to a real date before the query runs.
`-30 days`, `-12 months` and `+7 days` all work, in `day` / `week` / `month` /
`year`, singular or plural. They stay relative: a saved explorer link keeps
meaning "the last 30 days" instead of freezing to the day it was saved.

A bound that is neither an exact date nor a recognised relative form — a typo
like `30 dayz ago`, or an impossible date like `2026-02-30` — is now dropped,
and the report renders without that bound rather than guessing at one. A filter
that silently narrows to the wrong window is harder to catch than one that
plainly isn't there.

# TalentTrack v4.87.3 — Save buttons follow your button colours again (#2446)

The shared Save button helper mishandled its own default. When a form didn't
name a button style explicitly — which is nearly all of them, 50 of the 55
call sites — the helper emitted a PHP warning and then rendered the button
without its `tt-btn-primary` class.

The visible consequence was that those Save buttons ignored the Buttons colour
settings under Design: instead of your configured button background, text and
hover colours, they fell back to the brand primary colour. On an install that
hasn't customised those tokens nothing looked wrong, which is why it went
unnoticed.

Save buttons now get the primary style whenever no style is named, so they
follow the Design settings like every other button. Forms that explicitly ask
for a secondary or danger button are unaffected.

# TalentTrack v4.87.2 — Ratings grid: category column headers now follow your language (#2430)

The ratings grid showed its evaluation-category column headers in English even
on a Dutch install, while the rest of the screen was translated. The grid's
read model was reading the stored category label straight out of the database
instead of resolving it the way every other evaluation surface does, so the
translation layer never got a look in.

Headers now resolve through the same display-time translator the evaluation
form, the evaluation detail view and the radar-chart legends use, which means
operator-maintained translations show up here too. A category nobody has
translated keeps its stored name, so nothing goes blank. Stored data is
untouched — scores still write against the category, never against its label.

# TalentTrack v4.87.2 — Ratings grid: out-of-range scores are flagged as you type, and can no longer fail silently (#2431)

A score outside the academy's rating scale used to be accepted by the grid,
dropped by the server, and then reported back as saved. The grid cleared its
unsaved markers and announced that all changes were stored, so a coach who
typed 12 on a 5–10 scale had no way to know the score never landed — and
because the rejected value became the new baseline, pressing Save again
wouldn't retry it either.

Scores are now checked against the configured scale as you type. An offending
cell is marked, the line under the grid says what the allowed range is, and
Save stays disabled until it's corrected. Nothing you typed is rewritten
behind your back: an out-of-range score stays on screen for you to fix rather
than being clamped, and a score that misses the scale's step is refused
instead of being quietly rounded to the nearest one.

The bulk ratings endpoint now reports refused cells separately from blank
ones, so a partial save is honest about what it did and didn't write. Valid
cells in the same batch still save, so one bad score can't cost a whole
squad's worth of typing.

# TalentTrack v4.87.2 — Ratings grid: main and sub categories are now visibly separate columns (#2432)

The grid's column headers were a single flat row, so there was no way to see
which columns were main categories and which were sub-categories underneath
them — the structure every other evaluation screen makes visible was lost
here. Worse, the columns were sorted on display order alone, which did not
keep a sub-category next to its own parent, so related columns could end up
scattered across the grid.

The header is now two rows. A main category spans its own block, its
sub-categories sit underneath it, and a main you rate directly keeps its own
column labelled *Main score* alongside them — so you can score at main level,
sub level, or both. Sub-categories are always adjacent to their parent, and a
separator marks where each main's block begins so the eye can track it while
scrolling sideways.

Sub-categories start collapsed and each main expands on its own, which keeps a
detailed methodology from spreading a squad across an unusably wide grid. A
main whose sub-categories already hold scores for that activity opens expanded,
so reopening a detailed rating shows what was entered rather than hiding it.
Collapsing never hides pending work: the header counts the unsaved scores
folded away, those scores still save, and a score outside the scale forces its
main back open because it blocks saving until corrected.

Keyboard navigation now walks the visible cells rather than counting header
cells, so the arrow keys stay correct across two header rows and after any
expand or collapse.

# TalentTrack v4.87.2 — Minutes reports: honest match count, and a filter bar on both (#2433, #2434)

The Team · Minutes distribution report could show a match count that
contradicted the squad beside it — "19 wedstrijden" next to an empty player
list. The match count was the only query on the page that carried none of the
exclusions its sibling queries carry, so archived, binned, cancelled and
not-yet-played fixtures all counted towards it.

The tile now reports what the report can actually account for. *Matches
recorded* counts the matches that produced recorded minutes — the same matches
the player bars are built from, so the two can never disagree — and carries the
honest denominator underneath: how many matches were played in the window. When
they differ the tile is flagged ("3 gespeelde wedstrijden hebben geen minuten"),
which names the gap as a recording gap rather than leaving a coach to guess.
Fixtures dated in the future no longer count as played. The counting rule moved
out of the view into `MinutesQuery::matchCountsForTeam()`, beside the
predicates it has to agree with, and is covered by tests.

Both minutes reports also gained the shared filter bar the other standard
reports have had since v4.80: period pills plus a manual From/To range. Every
figure follows the chosen window — KPI tiles, per-match rows, each player's
drill-down and the Explorer link. The default is unchanged at a rolling 12
months, so no existing number moves; the empty state's "widen the window"
advice is now something a user can actually act on. As a side-effect the
Explorer drill-through is bounded for the first time: it previously passed the
literal string `-12 months` as a date, which matched every row.

# TalentTrack v4.87.2 — Timestamps no longer render hours into the future (#2437)

Dates and times shown across the plugin were read as UTC and then printed
in the academy's timezone, adding the offset twice. On a Dutch install the
"Team last synced from Spond" line on an activity claimed a sync two hours
into the future — a sync at 22:24 printed as 00:24 the next day. The same
skew quietly affected the created/changed audit footer, PDP sign-off and
acknowledgement stamps, and the scout-report history.

Timestamps stored by the plugin are now read in the academy's timezone, so
they print the wall-clock time they were recorded at. Two columns that
genuinely hold UTC keep converting first: a scout link's expiry date now
shows the same moment the expiry check uses, and new scout-report rows
record their creation time in the academy timezone instead of whichever
timezone the database server happens to run in. Date-only values (activity
dates, evaluation dates) also stop slipping to the previous day for
academies west of UTC.

# TalentTrack v4.87.2 — Force a Spond re-sync straight from the activity (#2438)

An activity imported from Spond now carries a **Sync team from Spond**
button in its page header. A head coach who spots that an event moved in
Spond, or that the roster changed, pulls the team's calendar again on the
spot instead of waiting for the scheduled sync or asking an academy admin.

It re-pulls the team's whole calendar — Spond offers no way to re-fetch a
single event — so the button and its confirmation say "team", and the
change you were after may land on a different activity in the list. When
the team synced less than a minute ago the confirmation says so, so a
second click is an informed one. The button appears only for someone who
may manage that team's Spond connection: an academy admin for any team, a
head coach for their own. Archived activities don't show it.

# TalentTrack v4.87.1 — MFA QR codes now scan (#2425)

The QR code on the MFA enrollment step could not be read by any authenticator
app. The encoder wrote the 15 format-information bits in reverse order, so the
result was not a valid BCH(15,5) codeword — conforming scanners locate the
symbol, fail format validation, and stop before reading any data. Every QR
version the encoder can emit (v1–v10) was affected, so scanning has never
worked; only the manual-entry fallback did.

The fix is one expression in `QrCodeRenderer::writeFormatInfo()` — the bits are
now placed most-significant-first per ISO/IEC 18004 §7.9.1. The rest of the
encoder was already correct: data encoding, error correction, mask selection,
alignment patterns and version-info blocks all verified module-for-module
against an independent encoder.

The round-trip CI gate missed this because its decoder shared the encoder's bit
order — it read back LSB-first what the encoder wrote LSB-first, recovered the
right mask, and passed. Two encoders agreeing proves nothing when one wrote the
other. The verifier now reads the strip most-significant-first and additionally
asserts the format bits are one of the 32 legal BCH codewords encoding ECC
level L, and that the primary and mirror copies agree. That check needs no
third-party decoder and fails loudly if the bit order is ever reversed again.

Users who enrolled via manual entry are unaffected and need not re-enroll.

# TalentTrack v4.87.1 — MFA issuer no longer doubles the brand name (#2426)

On an install whose site name already opens with the brand, the MFA enrollment
step showed a doubled issuer — site name `TalentTrack Local` produced
`TalentTrack TalentTrack Local`, both on screen and inside the otpauth URI.
That string is what the user then sees as the account name in their
authenticator app, and re-enrolling is the only way to change it.

The guard matched only the exact string `TalentTrack`, so anything merely
starting with it fell through to the concatenation. A site name that already
begins with the brand is now used as-is; one that doesn't still gets
`TalentTrack ` prepended, and an empty site name still falls back to the bare
brand. As a side benefit the URI gets shorter — the issuer appears in it twice,
so the duplication was costing the QR-version budget double.

Existing enrollments are unaffected; the issuer is display metadata recorded by
the authenticator app at scan time, not part of the shared secret.

# TalentTrack v4.87.0 — Head coaches pick their team's Spond group themselves (#2399)

Connecting Spond for your own team stopped half-way: a head coach could save
the team's Spond login and test it, but linking the actual **group** still
happened on the team edit form, which most coaches can't open — so activities
didn't flow until an admin stepped in.

The **Spond connection** panel now includes the group picker. It appears once
the login works (listing groups needs a working Spond login, so before that the
panel says what to do rather than showing an empty dropdown), and the list is
cached for five minutes so re-opening the panel is instant.

If the group you pick is already linked to another team, the panel names that
team and warns you — then lets you save anyway. Two teams sharing one Spond
group is a normal setup for a combined age-group calendar; both teams simply
import the same events.

Access is scoped to the exact team, the same as the credential and test actions:
a coach can finish the setup for their own team and no one else's.

# TalentTrack v4.87.0 — Activities calendar keeps the filters you set on the list (#2400)

Switching the activities page to **Calendar view** used to reset the window: the
grid always showed its own default forward range and ignored the period, the
From/To dates and the activity Type you had scoped the list to. Now those carry
across, so the calendar shows the same activities over the same dates you were
just looking at.

Two things the grid states plainly rather than doing silently: it paints whole
weeks, so a window starting mid-week is drawn from that week's first day (never
less than you asked for), and with the period set to **All** there is no bounded
range to draw, so it falls back to the default forward window. The dates being
shown now appear above the grid either way.

The calendar stays a read-only glance — creating and editing activities is still
the list's job, and the editable planner keeps its own page.

# TalentTrack v4.87.0 — Archiving a team can archive its activities too (#2411)

Archiving a team used to leave its trainings and matches fully active, so a
retired age group's sessions kept turning up on planners, dashboards and
reports long after the team was gone.

The confirmation dialog now offers **"Also archive this team's N activities"**,
ticked by default, with the count taken from the team's still-active
activities. Untick it to archive the team on its own.

**Players are deliberately left alone.** A player outlives their team — they
move up an age group or transfer the same week — so their record stays active
and simply has no team until you assign one.

**Restoring the team brings those activities back**, but only the ones this
cascade archived: anything you had archived by hand beforehand stays archived,
so restoring a team never revives something you deliberately put away.

Upgrading also sweeps up the activities of teams archived *before* this
shipped, so they stop cluttering live views.

# TalentTrack v4.87.0 — Ratings grid: rate a whole squad on one screen (#2414)

A new **Ratings grid** completes the desktop entry grids (epic #2381). Open an
activity, click **Ratings grid**, and you get the squad down the rows and the
categories that activity is rated on across the columns — one score per cell,
typed directly, one Save for the lot.

It's deliberately per-activity rather than per-period like the attendance and
minutes grids. A rating isn't one number but a score per category, so a
players × activities grid would have to collapse several scores into one cell
and show a computed average instead of what you typed. Fixing the activity and
making the categories the columns keeps every cell a real score.

Details that matter in daily use: an empty cell means "not rated" and never
erases a score somebody already recorded; saving twice updates the player's
existing evaluation rather than creating a second one; edited cells stay
highlighted until you save; and arrows plus Enter move around the grid so you
can rate a category straight down the squad without touching the mouse.

The evaluation wizard and the evaluation form are unchanged — the wizard stays
the phone/pitch path, and notes and player feedback still live on the form. The
grid is desktop-only and can be switched off per academy under
*Modules → Activities → Ratings grid*.

# TalentTrack v4.86.1 — Updates: hourly release check + a "Check for updates" action (#2405)

TalentTrack now checks for a new release **every hour** instead of every 12
hours, so a fix reaches a pilot site the same morning it ships rather than up
to half a day later. A **Check for updates** action was also added to the
plugin's row on wp-admin → Plugins: it forces a check on the spot and reports
what it found — the version now available, or that the site is already up to
date. The action is limited to users who may update plugins.

# TalentTrack v4.86.1 — Modules can be marked "under development", and the dashboard tile says so (#2409)

The **Under development** marker now works at module level, not just per
feature: tick the checkbox on a module's card at *Modules* and every view that
module owns shows the informational pill. A core (always-on) module can be
flagged too — the marker gates nothing, so there is no reason to exempt it.

The marker also reaches the **dashboard tile** now. A tile shows a small amber
**Under development** badge when its own feature is flagged *or* when its
module is, so people see that a surface is still being built before they click
into it rather than after. The badge appears on the persona dashboard, the
classic tile grid, the "My work" rail and a parent's child tiles.

As before the flag is purely cosmetic — it never disables or hides anything,
and it is independent of the on/off switch, so a module can be live and flagged
at once. Only admins who can manage modules can set it; everyone sees the
result. It is stored per club on `tt_module_state` and is readable and settable
through the `/talenttrack/v1/modules` REST endpoint.

# TalentTrack v4.86.1 — Archived teams no longer appear in team pickers and dashboard tabs (#2410)

Archiving a team is supposed to take it out of day-to-day use, but until now it
only greyed the team out on the Teams list: the team kept appearing in every
team dropdown in the app — creating an activity, the coach dashboard's team
tabs, the planner picker, measurement and test-result pickers, PDP, match
execution, the role-grant scope picker and every analytics team filter. A team
sitting in the **recycle bin** showed up in all of them too.

Both shared team helpers now exclude archived and trashed teams by default, and
the hand-rolled team dropdowns were moved onto the same lifecycle vocabulary, so
a retired team disappears from all of these at once. Restoring the team brings
it back everywhere. Unchanged on purpose: the Teams list's own Archived tab, and
the team's own detail page, which must still open for a retired team.

# TalentTrack v4.86.1 — Recycle bin: the delete-impact preview no longer wrongly reports "nothing depends on this" (#2413)

Before a permanent delete the recycle bin shows what else the delete would
remove or clear. Two problems made that statement untrustworthy. The preview
was gated on the settings capability rather than the recycle-bin one, so an
admin who manages the bin could be refused it — and when the request was
refused, the dialog opened anyway and reported **"No other records depend on
this one."** even though the delete could cascade across eleven tables.

The preview is now gated on the same capability as the delete it precedes, and
a preview that fails for any reason no longer opens the dialog at all: the
error is shown and the delete cannot proceed without a successful preview.
Deleting a record whose impact really is nil looks exactly as it did before.

# TalentTrack v4.86.0 — Minutes entry grid — a desktop, spreadsheet-style companion to the attendance grid (#2386)

A new desktop **Minutes grid** (`?tt_view=minutes-grid`, reachable from the
Attendance/Minutes toggle on the grid surface) records match minutes for a
whole period at once: players down the rows, matches across the columns, a
minutes box per squad cell, one Save for the lot. It's the sibling of the
attendance grid (epic #2381), restricted to match activities.

Only players in a match's squad are editable; non-squad cells are hatched and
informational, mirroring the Minutes-audit matrix. Each edit is routed through
the same minutes-ownership arbiter the per-match editor uses — a match run
through match-execution keeps your figure as an override that survives a
recompute, while a paper match writes the minutes directly — so the grid, the
Minutes-audit tool, and the Minutes-played report always reconcile.

Gated on the `tt_edit_activities` capability and a new **Minutes grid** feature
toggle (on by default; switch it off to hide the grid and block its route; the
per-match minutes editor stays available). Also exposed over REST
(`GET /activities/minutes-grid`, `POST /minutes/bulk`).

Both grids are now also reachable straight **from an activity's detail page** —
an "Attendance grid" action on every activity and a "Minutes grid" action on
matches, each opening the grid for that team pre-filtered to the activity's
date, with a back-link that returns to the activity.

# TalentTrack v4.86.0 — Activities are completable again when the guided wizard is off (#2401, #2407)

Switching the guided attendance/evaluation wizard off left an activity with no
way forward: the **Complete activity** button vanished from the activity page,
its card in the list and the edit form, and nothing on the remaining path ever
marked an activity completed.

Both halves are fixed. With the wizard off, the completion button stays and now
reads **Mark attendance**, opening the desktop attendance grid on that
activity's own column; the dashboard's **Mark attendance** hero goes there too
instead of dropping the coach on an unfiltered activities list. A new **Mark
completed** action on a planned activity flips its status, so a wizard-off
academy no longer accumulates activities stuck at "planned" — which had been
quietly distorting the attention and up-next groupings. Recording attendance in
the grid deliberately does not auto-complete anything, because one grid save can
span weeks of sessions.

With the wizard switched on, nothing changes.

# TalentTrack v4.85.0 — Attendance reports: "This season" pill now spans the whole season (#2384)

The *This season* period pill on the attendance reports (team, player and
leaderboard) now covers the full season — from the season's start date
through the season's own end date — instead of stopping at today. Picking
the pill mid-season no longer silently truncates the window to the part of
the season that has already happened. The silent default window shown when
no pill or manual range is chosen is unchanged: it still runs season-start
through today, so reports stay retrospective by default.

# TalentTrack v4.85.0 — Reports: save a filter set as a named view (#2385)

Every standard report with the shared filter bar — the team, player and
leaderboard attendance reports and the minutes reports — now has a **Saved
views** strip above the bar. Set the filters you keep returning to, click
**Save current filters…**, name it (e.g. "U17 league games"), and it
becomes a one-click chip. Saved views are personal (only you see yours) and
belong to the report you saved them on. A period pill is remembered as a
relative choice (*This season* stays relative next month); a manual From/To
range is frozen to the exact dates. Presets live in a new `tt_saved_filters`
table (club- and user-scoped, with a uuid) and are managed over REST
(`GET/POST /reports/filter-presets`, `DELETE /reports/filter-presets/{id}`)
gated on `tt_view_analytics`.

# TalentTrack v4.85.0 — "Under development" pill for features (#2387)

Admins who manage modules can now mark any feature as **under development**
from the module/feature page (`?tt_view=modules`) with a checkbox beside its
on/off switch. When set, every view that feature owns shows a small,
informational amber "Under development" pill at the top, visible to everyone
(coaches, players, parents) so they know the surface is still being built and
may change. The flag is purely cosmetic — it never disables or hides
anything — and is independent of the on/off switch, so a feature can be live
and flagged at once. The flag is stored per club on `tt_feature_state` and is
readable/settable through the `/talenttrack/v1/features` REST endpoint.

# TalentTrack v4.85.0 — Head coaches connect their own team's Spond account (#2388)

A head coach can now link their team's own Spond account themselves, from a
**Spond connection** action on the team's page — save the team email +
password, test the login, and trigger a sync — without waiting for an
academy admin. Previously only an admin could connect Spond, on the
club-wide page.

Access is scoped to the exact team via change authority on the
`spond_integration` matrix entity (admin globally, head coach for their own
team). This also **closes a scoping hole**: the per-team Spond credential
endpoints previously gated on the any-team `tt_edit_spond_credentials`
capability, which let a head coach write another team's credentials; they
now require change authority on that specific team, and the affordance is
hidden for anyone without it.

# TalentTrack v4.85.0 — Spond sync: matches with no end time now default to kick-off + 105 min (#2389)

Spond match events frequently carry no end time, which left imported
**matches** with a blank end while trainings (which do carry ends) looked
right — the "end time is wrong only for matches" report. The kick-off +
105 minute default already used by the "+ New activity" wizard (#1863) was
never wired into the Spond sync. Now, when a Spond match gives a start but
no end, the sync fills the end with kick-off + 105 min (clamped to
end-of-day for a very late kick-off). A real Spond end always wins — the
default only fills the blank — and trainings are unaffected.

# TalentTrack v4.85.0 — Activities: switch between list and calendar view (#2390)

The activities page now has a **Calendar view** toggle in the header that
swaps the chronological list for a week-grid calendar — the same read-only
grid the Team Planner uses, days as columns, one row per team — and a **List
view** button to swap back. The choice is remembered per user. The calendar
honours the same team scope as the list, narrowing to one team when a
`?team_id` filter is set. It's a read-only glance; creating and editing
activities stays on the list and the activity form, and the full editable
planner remains on its own Team planner page. Reuses the Team Planner's
condensed multi-team grid rather than adding a second calendar.

# TalentTrack v4.84.0 — Attendance entry grid — a desktop, spreadsheet-style alternative to the wizard (#2382)

A new desktop **Attendance grid** (`?tt_view=attendance-grid`, reachable from
the Activities screen) lets a coach record attendance for a whole period at
once, the way an Excel register works: players down the rows, activities
(training + matches) across the columns, one dropdown per cell, one Save for
the lot. It is the power-entry alternative to the step-by-step
mark-attendance wizard (epic #2381) — the wizard stays the mobile/pitch path.

Columns come from the team's active roster, so a brand-new activity still
shows every player; a per-column "all present" fills a whole session in one
click; the full five statuses (present / late / absent / excused / injured)
are all available, shown as an abbreviation in the cell and the full word in
the dropdown. Edits are tracked and written in one batch, and the grid
reads/writes the same recorded attendance the reports and the wizard use, so
everything reconciles.

Gated on the `tt_edit_activities` capability and a new **Attendance grid**
feature toggle (on by default; switch it off to hide the grid and block its
route). Also exposed over REST (`GET /activities/attendance-grid`,
`POST /attendance/bulk`) for a future non-WordPress front end.

# TalentTrack v4.83.1 — My activities list: 2026 FilterBar chrome (#2074)

The player and parent **My activities** list now reads as crisply as the staff
Activities list. Its filter row already renders through the shared 2026 filter
bar (a single inline row on tablet and desktop, a bottom sheet on phones); this
release brings the table itself up to the same standard, giving the column
headings the 2026 small-caps treatment and adding a subtle row hover. Your own
attendance status (Present / Absent / …) stays the list's status column. No
change to what you see or how filtering works — only the polish.

# TalentTrack v4.83.1 — Lineup card: two-column, styled Starting XI / Bench (#2232)

The activity/match detail line-up card now presents Starting XI and Bench
side-by-side on tablet and desktop (≥768px) and stacks to a single column
on phones. Each player renders as a structured row — jersey number, name
(first name + last initial, matching the sideline convention), and a
position chip using the resolved short codes — consistently aligned and
spaced. Group headings carry a player count. No raw JSON positions, no
horizontal scroll at 360px.

# TalentTrack v4.83.1 — Printable methodology reference card follows the active methodology (#2376)

The printable methodology reference (`?tt_methodology_ref_print=1`) now reflects the **active methodology set** instead of merging every shipped set onto one card. It reads through the scoped repositories, so the Spelprincipes, Voetbalhandelingen and Leerdoelen pages show exactly the methodology the read view shows — and JO13-1's `VD`/`AV` principles, previously dropped because the card bucketed by the JO14 code prefixes, now render (principles are grouped by team-function and team-task instead). A club's own (non-shipped) active set prints too.

# TalentTrack v4.83.0 — Team · Minutes distribution: fix "18 matches / 0 players" (#2339)

The Team · Minutes distribution standard report resolved its squad from
`tt_players.team_id` while counting matches from the team's activities, so a
team whose players had no `team_id` set showed a match count but zero players
and no minutes. The squad is now derived the same way the rest of analytics
resolves a team — players with recorded attendance on the team's
match / game / tournament activities — so the player list and the match count
share one team-membership definition, and a player appears even with 0 recorded
minutes. Minutes still come only from persisted `record_type='actual'`
attendance rows (never estimated), so a match with no recorded minutes
contributes 0.

# TalentTrack v4.83.0 — Standard reports: shared filter bar + season-default window (#2345)

Team · Squad evaluation summary, Season summary, Season · Trial funnel and the
Scout report card now carry the shared filter bar — retrospective period pills
(Last week / This month / This season) plus a manual From / To range — with the
same season-default window the attendance and minutes reports use (current
season start → today, 90-day fallback). Each report's query, page sub-line and
Explorer drill now follow the selected window, replacing the hardcoded rolling
6- / 12-month bounds.

# TalentTrack v4.83.0 — Standard reports: auditability drill-downs on KPIs (#2356)

KPI tiles on the standard reports now drill to the filtered list they count:
Team · Minutes distribution's Players tile opens the team roster and its Matches
tile the activities list filtered to that team's matches; Season summary's
Active players / Active teams / Matches tiles open their lists; the Trial funnel
Prospects logged tile opens the prospects list. Every drill carries a
"← Back to …" hint and is hidden when the viewer lacks the destination's
capability.

# TalentTrack v4.83.0 — Minutes-audit per-match editor (#2367)

The Minutes-audit tool gains an editable per-match surface. From the audit
overview, admins and head coaches can now open a match and correct the
recorded minutes per player, writing the authoritative recorded value — the
same source the reports and the match-execution screen read, so a match
opened in match-execution after an audit edit reflects the changed numbers.

The editor routes each write automatically: a match with a match-execution
takes an explicit per-player override that survives every recompute and
stays correctable once finalized; a paper match writes the recorded minutes
directly. Editing is conservative — an emptied field is an explicit clear,
never a silent zero, and untouched rows are never written. The overview's
edit link is hidden for users who cannot save.

The audit overview now reflects effective minutes
(`COALESCE(minutes_override, minutes_played)`), consistent with the minutes
report and the minutes-authority arbiter.

This first version edits total minutes per player; editing the starting
line-up per half and the substitution log is a planned follow-up.

# TalentTrack v4.82.2 — Edit attendance on a completed activity, and see the roster without opening Edit (#2371)

The activity detail page now has a collapsible **Show roster** list under the
attendance breakdown — every registered player with their status (guests
tagged), so you can see who had which attendance without opening the edit
form.

A completed **training** now also carries an **editable attendance table** on
its edit form: correct a missed or wrong status (and note) per player and hit
Update activity. This restores the flat-form path as the fallback for when the
guided wizards are switched off — previously, with wizards disabled, recorded
attendance could not be corrected at all. Reuses the existing recorded-
attendance write path (no new write logic); match-type activities keep their
minutes-aware completion flow. The completed-activity wording no longer implies
attendance is still to be captured.

# TalentTrack v4.82.0 — First-class sub-principles + JO13-1 formation diagram fix (#2369)

Methodology sub-principles are now a first-class entity: the concrete per-line
coaching points that support each main principle (grouped by game phase, then
by line — aanvallers / middenvelders / verdedigers / algemeen). They have their
own read surface under the **Spelprincipes** tab, a **Sub-principes** authoring
tab, and full REST CRUD at `/methodology/sub-principles`. The JO13-1 Hedel set
ships with its complete per-line sub-principes seeded from the playing-style
document. Separately, the JO13-1 **1-4-3-3** formation now renders correctly on
both the Formaties and Visie tabs — its diagram coordinates were missing, so it
previously fell back to a generic shape.

Wizard plan: exemption (a) — a sub-principle is a lookup-like, single-line
coaching note authored under an existing principle/phase, so it takes the flat
inline-editor path like the other methodology vocabulary tabs; a multi-step
wizard would add friction without value.

# TalentTrack v4.82.0 — Match execution: minutes-authority arbiter, tracked players, frontend cleanup

Rebuilt the match-execution surface to remove the "semi-connected parts"
friction:

- **Minutes have one owner.** When a match has a running/recorded execution,
  its per-player minutes are derived from the starting XI + substitution log,
  and the only way to hand-correct a figure is the per-player override on the
  match-execution screen (Recorded minutes → Correct). The manual minutes
  field on the attendance screen now defers to the execution (it reports that
  minutes are managed there). Matches that were never run through execution
  keep manual minute entry exactly as before.
- **Tracked players.** Players flagged in the match plan (a specific goal or an
  attention note) get a live +/- counter during the match to tally a
  development action. These are recorded as their own timed events — separate
  from goals, so they never affect the score.
- **Cleaner, faster surface.** The four match-execution stylesheets are
  consolidated into one, and the last inline styles and scripts were moved out
  of the page into the enqueued sheet and JS module.

# TalentTrack v4.81.0 — Minutes-audit overview: read-only games × players matrix (#2368)

New **Minutes audit** report (Reports launcher → *Playing time*, or
`?tt_view=minutes-audit`): a read-only games × players auditability matrix that
makes recorded-minutes gaps obvious. Rows are the team's games in the window,
columns are the squad (resolved from attendance on those games, not the player's
team assignment), and each cell shows the minutes recorded for that player —
green for recorded, red for on-squad-but-zero, hatched for not-in-squad. Every
row carries a total and a completeness chip (Complete / Incomplete / Not
recorded), with a column-total footer. Four clickable gap KPIs (Games, Fully
recorded, Incomplete, Not recorded) summarise and filter the matrix. Each row
deep-links to the game's activity detail to record its minutes.

The audit reads the same recorded, actual, non-guest minutes as the minutes
report, so its numbers reconcile exactly; a team with games but no recorded
minutes shows an honest "not recorded" state rather than a misleading "0
players". Reachable via a REST read endpoint (`GET /reports/minutes-audit`) gated
on `tt_view_analytics` plus the `report_minutes_audit` toggle, with the caller's
team scope enforced.

# TalentTrack v4.80.0 — Manage methodology sets + per-team selection (#2320)

Academies can now manage their methodology sets from the frontend. A new **Speelwijzen** tab leads the methodology manage surface: it lists every set (with Actief and Shipped badges), creates and renames sets through a flat multilingual form, makes any set the install-wide active one with a single "Maak actief" action, and archives sets it no longer needs — refusing to touch shipped reference sets or strand the install with zero methodologies. Each team can override which set it uses through a new "Methodology set" dropdown on the team edit form, defaulting to the install-wide active set. The same operations are exposed over REST at `/methodology/sets`, including `PUT /methodology/sets/{id}/default`, so a future SaaS front end gets identical answers.

Wizard plan: exemption — methodology set is a single-record named container, analogous to a lookup/vocabulary edit (§3 exemption a).

# TalentTrack v4.80.0 — Second shipped methodology: JO13-1 Hedel (#2321)

TalentTrack now ships a second methodology alongside the default JO14-1 Hedel (1-4-2-3-1): JO13-1 Hedel, a 1-4-3-3 playing style converted from the club's "Speelwijze Jeugd 13-1 Hedel" document. It carries its own vision, formation with eleven position cards, sixteen coded principles (defending, both transitions and attacking), a framework primer with its phases, and learning goals — all scoped to the new set so its content stays isolated from the default. Building on the selectable-methodology foundation (epic #2316), a team can be pointed at whichever set fits its playing style, and the Methodology tabs then show that set's content. Shipped content remains read-only; clone an entry to edit your own copy.

# TalentTrack v4.80.0 — Methodology periodisation combined with VCT week-cycle (#2322)

The VCT macro-block week schedule now carries an optional per-week speelwijze theme (`tactical_theme`) alongside the existing conditioning phase and intensity multiplier, reusing the canonical `vct_tactical_theme` vocabulary. A new "Periodisering" tab on the methodology library reads the club-default cycle for the current season and shows, per week, the speelwijze theme + conditioning phase + intensity — the single surface that combines the methodology and VCT views. The VCT configuration tile gains a per-week theme picker inside each block's advanced editor, and a JO13-1 5-week speelwijze reference template ships as a seed. Feeding the per-week theme into VCT exercise selection is a deliberate follow-up and is intentionally out of scope here.

# TalentTrack v4.80.0 — Animated per-phase tactical scenes (#2323)

The methodology library gains a **Speelwijze** tab with animated per-phase tactical scenes: an SVG pitch that plays out player and ball movement for each game phase, with coaching points alongside. Scenes are grouped by aanvallen / verdedigen / omschakelen and ship for the JO13-1 Hedel set. Play / Pause / Restart controls are keyboard-accessible and honour reduced-motion (no autoplay — the final frame renders statically). Scenes are authorable over a new REST resource (`/methodology/tactical-scenes`); an in-app drag-and-draw scene editor is a planned follow-up.

Wizard plan: exemption — this ships no in-app record-creation flow. Scenes are shipped seed content, read-only in the app; creation is REST-only for a future editor. No "+ New" affordance, so no wizard applies (the drag-and-draw editor, when built, gets its own wizard decision).

# TalentTrack v4.80.0 — Clickable KPI tiles on the standard reports (#2343)

The standard-reports KPI strip can now turn a tile into a drill-down link:
each KPI accepts an optional `href` (and an optional `cap` to gate it, hiding
the link for viewers who lack the capability). Tiles without an `href` render
exactly as before, so no existing report changes. The clickable tile gains a
visible keyboard focus ring and keeps its 48px touch target.

# TalentTrack v4.80.0 — Honest, context-aware empty states on the standard reports (#2344)

When a standard report has nothing to show it now says why in plain terms —
"No matches recorded in this period", "No evaluations recorded for this team in
this window", "No prospects logged in this window", and so on — instead of the
old generic "adjust a filter and try again" copy (most of these reports have no
filter to adjust). The Season summary no longer renders a blank page below its
headline tiles when no teams exist.

# TalentTrack v4.80.0 — Standard-reports query fixes: archived join, honest window + cap, last-evaluated date (#2346)

Three mechanical corrections to the standard reports. The Season summary's
per-team match counts now exclude soft-archived activities on the join itself,
not just in the count, removing a source of inflated joins (values are
unchanged). Player · Minutes played now states its 12-month window in the page
sub-line and surfaces the "showing the 50 most recent matches" cap so a longer
history is never silently dropped. Team · Squad evaluation summary shows a
**Last evaluated** date per player so a stale row is visible at a glance.

# TalentTrack v4.80.0 — Trial funnel reconciles: pending row, window label, scout links (#2347)

The Season · Trial funnel's Per-decision table now lists the outcomes of cases
opened in the window plus a **Pending (not yet decided)** row and a **Total**
row that sums to *Trial cases opened*, so the breakdown reconciles. The
Decision rate tile carries a one-line note that its numerator (cases decided,
by decision date) and denominator (cases opened, by open date) use different
windows. Each scout name in the Per-scout table links to that scout's Scout
report card, gated on the same `tt_view_reports` capability the card enforces.

# TalentTrack v4.80.0 — One shared per-match minutes breakdown component (#2348)

The per-match minutes breakdown table used by the Team · Minutes distribution
report and the Analytics minutes-played report is now a single shared component
(`MinutesBreakdown`), replacing two near-identical copies that had already
drifted in markup. Both reports render identical rows that still reconcile
exactly to the player's total. Presentation-only — no query or data change.

# TalentTrack v4.80.0 — Minutes-played (team) report: shared filter bar + KPI strip (#2349)

The Minutes-played (team) report now uses the shared filter bar (team,
retrospective period pills — Last week / This month / This season — a match-type
select and a manual From/To range) and the shared KPI strip, matching the
attendance reports. The default window is the current season; on a phone the
filters collapse into the standard bottom-sheet with 48px touch targets. The
report's one-off stylesheet was trimmed to the few genuinely report-specific
rules that remain.

# TalentTrack v4.80.0 — Attendance leaderboard: filter + chrome parity (#2350)

The attendance leaderboard now shares the same filter bar and chrome as the player attendance report: a team picker, retrospective period pills, an activity-type filter and a manual date range, plus the leaderboard's "How many" cap. Opening it with no filters defaults to the current season. A KPI strip above the tables summarises the ranked players (total, average attendance, at-risk count), computed from the data already fetched — no extra query. Flagged players in the "Needs attention" table keep their missed-count badge, and the empty-state messages now say what to try next.

# TalentTrack v4.80.0 — Attendance reports: type filter + at-risk drill-down + season-pill state (#2351)

The team and player attendance reports now surface the silently-seeded
season-default window as an active **This season** pill instead of reading
"Custom range", so the filter bar reflects the window you're actually looking
at on first open. When a coach only sees the teams they're assigned to, the
empty-state message now says the report is limited to those teams, so an empty
window no longer reads as "the academy has no data". On the player report the
inline at-risk ⚠ badge — and each name in the At-risk players panel — is now a
link that drills to the player's missed-activities list (this player, the
report's team, the report's window), matching the existing Activities-count
drill-down and carrying a back hint to the report.

# TalentTrack v4.80.0 — Team ratings report: fix N*M query fan-out (#2352)

The admin Team rating averages report now computes its numbers with two grouped database queries instead of one query per team and per category cell. On academies with many teams and categories this cuts the report from dozens of queries to two, so the page loads noticeably faster. The displayed averages and evaluation counts are unchanged.

# TalentTrack v4.80.0 — Coach activity report: club scope guard + name fallback (#2353)

The Coach activity report now scopes its per-coach evaluation counts to the current club, so it can never surface a coach from another academy in a multi-tenant install. Coaches whose user account has been deleted are labelled **Unknown coach** instead of a raw account number, while still keeping their saved evaluations in the count.

# TalentTrack v4.80.0 — Explorer: visible row-cap notice + filter validation (#2354)

The dimension explorer now surfaces its hidden 5000-row cap: when a drill-down hits the limit, a notice under the table tells the user the tail is being dropped and to group the data to aggregate larger sets. Filters are also validated against the KPI's declared explore dimensions — a filter for a dimension the KPI doesn't offer is ignored instead of being applied, so the filters shown on screen always match the ones applied to CSV/PDF exports.

# TalentTrack v4.80.0 — Usage statistics: season default, truncation labels, better empty states (#2355)

The Application KPIs dashboard now defaults to a season-aware window instead of a fixed 30 days, picking the smallest period that spans the running season so far. Truncated tables (Active users, Dormant users) carry a "(Showing top N)" label so it's clear the list is capped, not complete. A collapsible "How these numbers are measured" note explains that stickiness is always a 30-day MAU ratio, that visits end after 30 minutes idle, and that observed time online is a lower bound. Role labels now render as shared role chips, and the empty states for "Top features used" and "Dormant users" suggest a next action.

# TalentTrack v4.80.0 — Reports launcher: honest empty state when no tiles are available (#2357)

When every report tile was filtered out — all reports switched off for the academy, or none within the viewer's scope — the Reports launcher rendered a blank grid with no explanation. It now shows a clear notice explaining that no reports are available and pointing the user to ask an administrator to enable a report or widen their scope. When any tile survives the filtering, output is unchanged.

# TalentTrack v4.79.0 — Centralized cross-view link authorization affordances (#2304)

Cross-view navigation links, tiles and buttons that point at another view are now hidden through one shared helper (`CrossViewLink`) backed by a registry that mirrors each target view's actual access guard, instead of hand-rolled inline capability checks that drifted from the destination. The measurements execution links (Manage tests, Record measurements, Testing coverage), the team-detail Planner link, the team-development chemistry and blueprint tiles, the activity methodology link, and the player "Chemistry attributes" action all route through it — same users see each link, with the player-attributes entry now correctly tightened to the per-player evaluation check the target enforces. A new diff-only CI gate stops future cross-view links from skipping the helper.

# TalentTrack v4.79.0 — Methodology sets — schema foundation (#2317)

Internal schema groundwork for selectable methodologies (epic #2316). A new `tt_methodologies` table makes a methodology a first-class, named set, and every methodology entity (principles, vision, formation, phases, learning goals, influence factors, set pieces, football actions, framework primer) gains a `methodology_id` linking it to one. Existing shipped content is backfilled into a default "JO14-1 Hedel" set, so nothing is orphaned and the read view is unchanged. No user-visible behaviour yet — selection and the second methodology land in follow-ups.

# TalentTrack v4.79.0 — Methodology sets — per-team selection + install default (#2318)

Adds the resolution layer for selectable methodologies (epic #2316): an install-wide default set stored in `tt_config` (`active_methodology_id`) plus an optional per-team override (`tt_teams.methodology_id`). A new `ActiveMethodologyResolver` picks the set for a given team — team override, then install default, then the club's default set — degrading gracefully to legacy behaviour before the tables exist. No user-visible surface yet; the read view and admin selector consume this in follow-ups.

# TalentTrack v4.79.0 — Methodology sets — content scoped to the active set (#2319)

The methodology library, its repositories and the authoring REST endpoints now read and write within the active methodology set (epic #2316). A new ambient `MethodologyScope` — parallel to how club tenancy already works — makes every list read and every create resolve to the install's active set by default, so the read view shows one methodology at a time and new content is stamped into it. REST callers can scope to a specific set with an optional `methodology_id` query param. With a single set installed there is no visible change; it's the switch that lets two methodologies coexist without their content bleeding together.

# TalentTrack v4.79.0 — Hide "Complete activity" when the user can't complete it (#2325)

The "Complete activity" button on the activity detail page (and the quick-action on planned activity cards) is now hidden when the current user can't reach the completion flow, instead of rendering a dead button that silently reloaded the page. Completing a training or paper-match routes through the evaluation wizard (which needs evaluation rights); completing a match with a running match-execution routes through its finalize view (which needs activity-edit rights). The gate now mirrors whichever destination applies, via a domain-layer `ActivityCompletionResolver::canComplete()` used by both buttons. Head coaches and evaluators are unaffected; assistant coaches who can't evaluate no longer see a button that does nothing.

# TalentTrack v4.79.0 — FilterBar: filters no longer revert on Apply (#2327)

The shared filter bar renders each control twice inside one form — a desktop inline row and a mobile bottom sheet — both carrying the same field name. On submit the browser sent both values and PHP kept the stale sheet copy, so editing the Date range From/To or changing the Team/Type select silently reverted on Apply. The change-sync that #2201 added for toggle checkboxes now covers every control: date inputs, text inputs and selects mirror their value onto the same-named sibling before the form submits, so the inline and sheet copies always agree. Progressive enhancement and the JS-off Apply fallback are unchanged.

# TalentTrack v4.79.0 — Reports default to the current season's date window (#2328)

The standard reports (player attendance, team attendance, the attendance leaderboard and team minutes) now seed their From/To filter to the current season — from the season's start date through today — when you open them without a period pill or a manual range. This matches the *This season* pill and how the academy thinks about the year, instead of an arbitrary rolling window that spanned season boundaries. When no current season is configured the reports fall back to the previous 90-day window, so they never render empty or fatal. Period pills and manual From/To ranges still override the default. The default now lives in one shared helper (`ReportFilters::seasonDefaultWindow()`) so the four reports can't drift.

# TalentTrack v4.79.0 — Archived-record actions hidden without permission + correct confirm titles (#2330)

The archived/trashed record card now hides lifecycle buttons whose REST route the
current user can't reach: "Move to recycle bin" only shows for users who can manage
settings, and "Restore to archive" / "Delete permanently now" only for recycle-bin
managers. Head coaches no longer hit a dead-end "Action failed." on an archived
record. The confirm-modal title now matches the action ("Move to recycle bin",
"Restore record", "Delete permanently") instead of always reading "Archive record".

# TalentTrack v4.79.0 — Players list: fix count/rows mismatch and unreachable players (#2331)

The players list could show fewer rows than its own total (e.g. "1–15 of 15" while only 11 rows rendered), and players sorted past the first page were unreachable. Cause: per-player view permission was applied *after* SQL pagination, so a page under-filled and authorized players beyond it were both miscounted and unpageable. The list endpoint now authorizes the full result set first and paginates the authorized players, so the total always matches the rows you can page through and every player you may see is reachable. No change to which players a user may view.

# TalentTrack v4.78.4 — Hide unauthorized navigation affordances across seven views (#2306, #2307, #2308, #2309, #2310, #2311, #2312)

Buttons and links that pointed at capability-gated destinations are now hidden from users who lack the matching capability, instead of leading to a "you are not authorized" dead end (CLAUDE.md §7). Affected affordances: the "New player" and "New team" header buttons (require the respective edit capability), the "Manage tests" link and the "Record measurements" / "Testing coverage" cross-links on the measurements surfaces (each gated on its own measurement entity), the "Team chemistry" / "Team blueprints" tiles on the team edit form (team-chemistry read access), the "Planner" link on the team detail page (plan-view access), and the "Methodology" library link plus principle pills on the activity detail card (methodology-view access — the linked principles still display, just not as links). Each affordance now checks the same capability its target already enforces; the server-side gates are unchanged.

# TalentTrack v4.78.3 — Search box on the Modules & features page (#2300)

The frontend **Modules & features** page (`?tt_view=modules`) now has a
search box at the top that filters the module cards and their nested feature
toggles live as you type, matching on name or description. A match inside a
feature auto-expands its module; empty categories drop out and an empty-state
line shows when nothing matches. With dozens of per-report and per-export
toggles, finding a specific one no longer means scrolling the whole list.
Client-side only — no reload, and the full list still renders with JavaScript
off.

# TalentTrack v4.78.3 — Player comparison and Podium are now switchable features (#2302)

The **Player comparison** and **Podium** analytics tiles can now be turned
off per academy from the Modules & features page (`?tt_view=modules`), the
same way as the other analytics surfaces. Both ship **on**, so nothing
changes on upgrade; switching one off hides its dashboard tile and blocks a
direct link to its `?tt_view` route. Until now these two tiles were
hard-wired on and had no toggle.

# TalentTrack v4.78.3 — Upgrade dompdf to 3.x (security) (#2313)

Bumped the `dompdf/dompdf` dependency from `^2.0` to `^3.0`. Every 2.0.x
release is now flagged by published security advisories, which blocked
`composer install` in CI. dompdf 3.x carries no advisories and still supports
PHP 7.4, so the plugin's minimum PHP is unchanged. PDF export behaviour is
unaffected — the renderer uses only the stable dompdf API.

# TalentTrack v4.78.2 — Holidays: "New holiday" button always available on the list (#2290)

The academy Holidays list now shows a persistent "New holiday" button in
its header, gated on the manage-holidays capability. Previously the only
create affordance was the empty-state card, which disappears once at least
one holiday exists — so a manager with full rights had no visible way to
add another and had to reach the wizard by URL. The empty-state CTA is
unchanged.

# TalentTrack v4.78.1 — Fixed the live match surface so Finalize, Re-open and the late goal/substitution forms work again. They were hitting a 404 (`/undefinedfinalize`) because the inline script read its REST config before the footer defined it; the config is now resolved when the button is tapped. (#2288)

Fixed the live match surface so Finalize, Re-open and the late goal/substitution forms work again. They were hitting a 404 (`/undefinedfinalize`) because the inline script read its REST config before the footer defined it; the config is now resolved when the button is tapped. (#2288)

# TalentTrack v4.78.0 — Spond: per-team accounts that override the club login (#2286)

A team can now sync with its own Spond account instead of the club-wide one.
Each team on the Spond page shows which account it uses ("Uses club account" or
"Own account: <email>"); expand its Account panel to set a per-team email +
password, which overrules the club login for that team's syncs. Leave the email
blank (or hit "Use club account") to fall back to the club account. Per-team
passwords are encrypted at rest and each team keeps its own cached token; the
resolution (`CredentialsManager::forTeam`) is the single seam the sync,
preview and monitor all use.

# TalentTrack v4.77.0 — Spond integration monitor — see live what the Spond API returns (#2284)

New diagnostic page (Spond → **Open integration monitor**) that fetches the Spond
API **live** for a team and shows exactly what's coming in — every event with its
classified type, date, times and location — plus a per-event **diff**: whether a
real sync would create it, or update an existing activity (and precisely which
fields it would overwrite), and which stored activities would be archived. It is a
**dry run**: nothing is written. This is the tool for answering "why does the
printed activity differ from what I set in Spond?" — a stale cache, a changed
event UID, or a field Spond owns all become visible at a glance.

# TalentTrack v4.76.3 — Week plan: keep the printed "Aftrap" (kickoff) in step with the activity's start time (#2282)

The weekly team-plan print shows a match/tournament's kickoff ("Aftrap") from the
`kickoff_time` field, but the activity edit form only ever wrote `start_time`
("Begintijd"). For a Spond-imported match — where Spond had seeded `kickoff_time`
— editing the start time in TalentTrack left the printed kickoff stale, so the
print disagreed with the form. Saving a game or tournament now mirrors the start
time into `kickoff_time` (and clears it for non-match types), so the two always
match. (This does not change Spond's re-sync behaviour, which still owns the
schedule fields.)

# TalentTrack v4.76.2 — Match execution: bench above the timeline, inline Undo, clearer "Match goals" label (#2280)

Polish on the after-match review:

- The **Bench** now sits directly above the **Squad timeline**, so a player's
  bench status reads right next to their played-minutes bar.
- In edit mode the **Undo** ("Ongedaan maken") button now sits inline at the end
  of each event row instead of wrapping onto its own second line under every
  goal and substitution.
- The Dutch label for the scored-goals section changed from the ambiguous
  "Wedstrijddoelen" (reads as match *objectives*) to **"Doelpunten"**.

# TalentTrack v4.76.1 — Match execution: fix the redesign's cards, bench minutes and opponent-goal feed (#2278)

Follow-up polish on the v4.76.0 match-execution redesign, verified against the
mockups with a headless render:

- The **bench** and **tracked-players** sections now actually show their pastel
  card backgrounds (yellow / green) — the 2026 chrome sheet was overriding them
  with white.
- A bench player's **minutes** now sit inline on the right instead of dropping
  onto their own line, and the **↑ Bring on** button no longer wraps to a second
  row in edit mode.
- An **opponent goal** in Live progress now reads "Opponent goal · <team>" with a
  distinct grey chip instead of a blank "Goal", and the **running score counts
  each side separately** — an opponent goal no longer bumps our tally.
- After a match the review screen still opens read-only, but the **Edit button is
  now prominent** (filled) so the correction controls are easy to find.

# TalentTrack v4.76.0 — Match execution: post-match squad timeline, contained cards, paired swaps, opponent goals (#2273)

The after-match review now leads with a **Squad timeline** — one 0'→full-time bar
per player showing on-pitch (green) versus bench time, substitution in/out markers,
own-goal marks and minutes played, grouped into the starting XI and the bench.

Live-match cards are now visually contained: the bench reads as a pastel-yellow card
and tracked players as a soft-green card. When a player is substituted off, their bench
row shows a transient "just came off" pill and a "Just came off for …" line that clears
after a minute.

Substitutions in the live progress feed render as a paired ▲ on / ▼ off card. In the
after-match review the coach can now add, remove, or correct the minute of an opponent
(away) goal; the away score stays in sync.

# TalentTrack v4.75.0 — Match execution: correct a substitution's minute after the match (#2273)

Coaches often log a substitution a little late, so the recorded minute is
wrong — and because playing minutes are derived from the substitution times,
that skews both players' totals. With Edit on, every substitution in the Live
progress feed now shows a **Correct minute** stepper. Changing it saves the
corrected minute and re-runs the minutes calculation, so the player who came
off and the player who came on both move to match. You fix the *time* of the
event; the minutes follow — you never edit minutes directly. The corrected
minute is range-checked and blocked once the match is finalized, the same as
every other post-match edit. New `PATCH /match-execution/{id}/substitution/{uuid}`
endpoint backs it.

This is the first slice of the match-execution redesign (#2273); the squad
timeline, contained bench/tracked cards and timed opponent goals follow in
their own changes.

# TalentTrack v4.74.0 — Match execution: timer no longer keeps running after the match ends (#2267)

The live-match screen now understands the real post-match states
(pending review and finalized) instead of a legacy value the server never
sends. On a finished match the clock stops, the state pill and the sticky
bottom action read correctly, and a reload stays in step with the server.

# TalentTrack v4.74.0 — Match execution: reject impossible subs and out-of-range minutes (#2268)

Logging a substitution now checks the roster on the server: you cannot take
off a player who is not on the pitch or bring on a player who is already on.
Goal and substitution minutes outside the match length (plus a short
stoppage allowance) are rejected instead of being silently clamped. The
same checks run in the browser so a mistake is caught before it is sent.

# TalentTrack v4.74.0 — Match execution: undo a substitution, reload-safe goal/sub undo (#2269)

Every logged goal and substitution in the Live progress feed now carries an
inline Undo that works even after a page reload, because it is keyed to the
stored event rather than a short-lived tap memory. A just-logged
substitution can also be undone straight from its confirmation toast.

# TalentTrack v4.74.0 — Match execution: sideline robustness polish (#2270)

Small reliability fixes on the live-match screen: a failed goal-undo rolls
the count back instead of drifting, the late-event forms cannot be
double-submitted, the timer stops the instant you finalize, and the header
meta line wraps rather than clipping the team names on a very narrow phone.

# TalentTrack v4.74.0 — Match execution: adjust every datapoint after the match (#2271)

After a match ends you can now correct every measured datapoint — score,
substitutions, goals and minutes — from the post-match review state, and
corrections re-run the minutes calculation so the reports stay consistent.
A finalized match is no longer a dead-end: a new "Re-open for corrections"
action returns it to review so any datapoint can still be fixed. Re-opening
is capability-gated to the same coaches who edit the match, and is recorded
in the audit log.

# TalentTrack v4.73.5 — Reopen / Cancel confirm dialog now shows the right title (#2265)

The confirm dialog for an activity's Reopen and Cancel actions showed the
title "Archive record" (it reused the shared archive modal). It now shows
the correct title for the action — "Reopen activity", "Cancel activity",
"Restore activity" — so the dialog no longer contradicts itself. The
archive dialog everywhere else is unchanged.

# TalentTrack v4.73.4 — Live match execution: sub controls visible by default again (#2261)

Fixes a regression where, during a live match, the substitution controls
(the bench "→ on" buttons and the "who comes off" panel) plus the score /
goal steppers were hidden behind the "Edit" toggle — so a coach on the
sideline saw only the bench list and couldn't sub. The read-only-by-default
edit gate now applies only to post-match editing: a live in-progress match
(first half / half time / second half) opens with the mutating controls
already revealed, while the post-match review window keeps the accidental-edit
guard (tap Edit to enable) and finalized matches stay fully read-only.

# TalentTrack v4.73.4 — Reliable plugin updates: auto-install + missing-token notice (#2262)

TalentTrack now installs its own updates automatically once a new release
is detected — no click needed. It also shows a clear admin notice when the
GitHub token is missing from wp-config.php: without a token the update
check runs unauthenticated and GitHub rate-limits it (HTTP 403) after a few
tries, which is why updates sometimes stopped being detected. The notice
explains the one-line fix (`define( 'TT_GITHUB_PAT', 'ghp_…' );`).

# TalentTrack v4.73.3 — Reports: exclude cancelled activities from minutes and attendance (#2259)

Cancelled matches and trainings no longer contribute to the minutes or
attendance reports. An activity counts as cancelled when either its
`plan_state` is `cancelled` or its `activity_status_key` is `cancelled`, and
both markers are now honoured across the team and player minutes reports, the
standard-report minutes queries, and the attendance-ranking and team
attendance reports. Previously the minutes reports counted cancelled
activities entirely, and the attendance reports only caught the `plan_state`
marker — a completed-then-cancelled activity still skewed the numbers.
Non-cancelled activities, including manual "paper match" minutes, are
unaffected. Query-only change.

# TalentTrack v4.73.2 — Reports: exclude archived + trashed activities from minutes & attendance (#2257)

Minutes and attendance reports no longer count activities that have been
archived or moved to the recycle bin. Every report surface — team minutes,
player minutes, the attendance team report, the attendance leaderboard, and
the at-risk list — now filters out both `archived_at` and `trashed_at`
activities, so an archived or binned match can no longer inflate minutes,
starts, attendance %, or activity counts. Numbers for clean (live) data are
unchanged. Query-only change.

# TalentTrack v4.73.1 — Wizard: cleaner branched progress rail + Cancel always exits (#2254)

Wizards that branch (like the evaluation wizard's "Evaluate an activity"
vs "Evaluate 1 player" choice) now show a clean progress rail: only the
steps on the path you actually picked appear, instead of listing both
branches with half of them greyed out as "Not applicable". The step
counter reflects the active path too.

Cancel no longer loops. Previously, after moving through a step or two,
the Cancel link could send you back into the same wizard (its own URL had
become the browser referer) — an inescapable loop. Cancel now always
returns you to where you opened the wizard from (the list you came from,
otherwise the dashboard), never back into the wizard. Framework-level, so
every wizard benefits.

# TalentTrack v4.73.0 — Team minutes report: planned (unrecorded) matches no longer counted as starts (#2252)

The team minutes report (Reports → Minutes played per player) could show more
starts (basisplaatsen) than matches — e.g. "3 basisplaatsen, 1 wedstrijd" —
which is impossible, and it inflated the "% available" figure to match. The
cause: starts, available minutes and substitutions were accumulated from every
planned prep line-up, including matches that were planned but never played or
recorded, while matches and total minutes correctly counted only recorded
matches. Now a match contributes to starts, available minutes and subs only when
it actually produced recorded minutes, exactly like matches and totals already
did. A planned-but-unrecorded match contributes 0 across the board, so starts can
never exceed matches. Recorded minutes totals are unchanged.

# TalentTrack v4.73.0 — Tournament minutes: recordable and counted in the minutes reports (#2253)

Tournaments are now treated as a minutes-bearing activity type just like matches
and games, everywhere. A single-game tournament can be planned and run through
the live match surface (match prep + execution) exactly like a match; a
multi-game-day tournament records minutes with the by-hand per-player minutes
entry on the attendance screen. Both write the recorded minutes to the attendance
row. The team and player minutes reports now use one consistent activity-type
set (match, game, tournament), so a player who played tournament minutes shows
those minutes instead of a 0. No fabrication: a tournament with no recorded
minutes still shows 0, and for a multi-game day the line-up-derived starts are
approximate — the recorded minutes are the meaningful figure.

# TalentTrack v4.72.0 — Complete-activity buttons launch the type-aware evaluation flow (#2245)

Completing an activity is now an explicit button, not a status dropdown.
A planned activity shows **Complete activity** on both its list card and
its detail view; the button is type-aware — training and paper matches
open the evaluation wizard (matches also collect minutes), while a
live-tracked match routes to its Resume/Finalize flow. The activity only
flips to completed when the flow finishes, so abandoning leaves it
planned. The detail view gains **Cancel activity** / **Reopen** as direct
confirmed status changes. The edit form no longer changes status or holds
the inline attendance table — it edits details only.

# TalentTrack v4.72.0 — New-evaluation wizard opens with an explicit activity/player choice (#2246)

The New-evaluation wizard now starts with a clear two-way choice —
**Evaluate an activity** or **Evaluate 1 player** — instead of guessing
the path from a hidden smart-default. Choosing an activity leads to the
activity picker, attendance and rating; choosing a player leads to the
player picker and deep rating. Previous returns to the two buttons, so
switching paths is one tap. An empty activity list now shows guidance
rather than silently jumping to the player path.

# TalentTrack v4.72.0 — One evaluation wizard behind every door (#2249)

The dashboard "Mark attendance" hero, the activity completion buttons and
the New-evaluation wizard now all reach the same unified flow. The old
`mark-attendance` wizard is now a thin alias that seeds the activity
branch, so existing links and bookmarks keep working. The activity path
is attendance → "rate now?" → quick rating; behaviour rating moved to the
"Evaluate 1 player" deep path so it isn't lost. No data-model change —
the same attendance and evaluation rows are written as before.

# TalentTrack v4.71.0 — Planned attendance is now editable on the activity edit form (#2248)

The planned (expected) roster is no longer frozen at activity creation. Edit
a not-yet-completed activity and you get a **Planned attendance** section: one
row per planned player with a status you can set — **Expected**, **Not coming**
or **Maybe** — plus an optional note (e.g. "texted, injured"). Activities
created with "Set attendance later" seed the section from the current team
roster so you can start a plan from scratch. The detail page's Expected
attendance panel now summarises who is away ("2 not coming · 1 maybe") and
links straight to **Edit plan**.

Marking a player "Not coming" early carries into the later attendance defaults
via the match-prep availability step. Planned rows are stored as
`record_type='expected'` and are written independently of recorded
(`actual`) attendance, so the attendance reports are unaffected. Reachable via
`PUT /activities/{id}` (a `planned_attendance` sub-resource) and
`GET /activities/{id}/planned-attendance`; gated on `tt_edit_activities`. No
migration — "Maybe" reuses the existing `excused` status.

# TalentTrack v4.70.0 — Frontend authoring for the club vision + framework primer (#2226)

The methodology authoring surface gains two more tabs: **Vision** and
**Framework primer** (Raamwerk). Both are single-record editors — each club
has exactly one vision and one framework primer — so the tab opens straight
onto its edit form (no list, no "+ New", no delete). The Vision tab edits the
formation, style of play, way of playing, important traits and notes; the
Framework primer tab edits the title, tagline and every intro section
(inleiding, per-theme toelichtingen for voetbalmodel, voetbalhandelingen, the
four phases, learning goals and influence factors, plus reflection and
future). Every field carries side-by-side Dutch + English inputs, and the
first save creates the record while later saves update it. The shipped sample
vision and shipped primer stay read-only. What you save is reflected on the
read view's Visie and Raamwerk tabs.

Both are also exposed over REST at
`/wp-json/talenttrack/v1/methodology/vision` and
`/wp-json/talenttrack/v1/methodology/framework-primer` (GET + PUT, read and
update only — no create/delete for the singletons), club-scoped and gated on
`tt_edit_methodology`, so a future SaaS front end gets identical answers.

# TalentTrack v4.70.0 — Methodology authoring: Formations tab with nested positions (#2227)

The frontend methodology-authoring surface gains a **Formaties** tab.
Editors can now create, edit and delete formations (slug, Dutch/English
name and description, optional diagram-data JSON) and manage each
formation's position cards (jersey number, Dutch/English short and long
names, and newline-separated attacking and defending task lists) — no
wp-admin needed. Dutch and English round-trip; shipped reference
formations and positions stay read-only.

A matching REST surface ships alongside at
`/wp-json/talenttrack/v1/methodology/formations` (and the nested
`/{id}/positions`), gated on `tt_edit_methodology` and club-scoped, so a
future non-WordPress front end gets the same CRUD.

# TalentTrack v4.70.0 — Frontend authoring for set pieces (#2228)

Academy editors can now author **set pieces** from the frontend, no wp-admin
required. The methodology "Manage" surface gains a **Spelhervattingen** tab:
list, create, edit and delete club-authored set pieces with a slug, a
kind (corner, free kick, penalty, throw-in) and side, side-by-side Dutch +
English inputs for the title, a Dutch and English coaching-point list (one
bullet per line) and an optional diagram-overlay JSON blob. Shipped reference
set pieces stay read-only, and saved set pieces show up in the read view's
Set pieces tab.

The same data is exposed over REST at
`/wp-json/talenttrack/v1/methodology/set-pieces` (GET/POST/PUT/DELETE),
club-scoped and gated on `tt_edit_methodology`, so a future SaaS front end
gets identical answers. Built on the #2225 tab-registry + REST-base scaffold.

# TalentTrack v4.70.0 — Frontend authoring for phases, learning goals and influence factors (#2229)

Academy editors can now author the framework primer's three children from the
frontend, no wp-admin required. The methodology "Manage" surface gains three
tabs, each scoped to the club's framework primer:

- **Fasen** — the four attacking and four defending phases, each with a side,
  a phase number (1–4) and side-by-side Dutch + English title and goal.
- **Leerdoelen** — coachable learning goals per side, optionally tied to a
  teamtaak, with a Dutch + English title and a per-language bullet checklist.
- **Factoren van invloed** — the factors shaping development, with a Dutch +
  English title and description plus an optional array of sub-factor cards.

All three list, create, edit and delete club-authored rows; shipped reference
content stays read-only, and a tab points the editor to the Raamwerk tab first
when no primer exists yet.

The same data is exposed over REST at
`/wp-json/talenttrack/v1/methodology/phases`,
`/methodology/learning-goals` and `/methodology/influence-factors`
(GET/POST/PUT/DELETE), club-scoped and gated on `tt_edit_methodology`, so a
future SaaS front end gets identical answers. Built on the #2225 tab-registry +
REST-base scaffold.

# TalentTrack v4.69.0 — Configurable player-profile cards (#2207)

The player-profile **Profile** tab cards can now be shown or hidden
academy-wide from **Configuration → Profile cards**. Uncheck a card —
Academy, Parents · Guardians, or Discovery — to hide it for the whole
academy; the Identity card always stays. Useful for hiding cards you do not
use, such as Discovery when you do not run scouting. The choice is stored
per club and hides a card for display only — no data is deleted, and the
existing staff-only rule on Parents · Guardians and Discovery still applies
on top.

# TalentTrack v4.69.0 — Goal detail: widen the goal pane, reduce wasted horizontal space (#2217)

On the goal detail page the left goal pane no longer sits in a narrow
column beside a large empty gutter. The `max-width: 640px` clamp on the
goal card is lifted and the desktop split is rebalanced to `1.3fr 0.7fr`,
so the goal fills its column while the conversation pane stays readable.
CSS only; mobile stays a single column.

# TalentTrack v4.69.0 — Goal detail now shows progress %, connected principle and football action (#2218)

The goal detail page (coach and player views) now surfaces three fields
that were captured on the goal but never displayed: the progress
percentage as a bar, the connected methodology principle, and the
connected football action. A goal with no progress set shows a dash
rather than a fabricated 0%; unset links are hidden. Principle and action
names are resolved in the repository layer so the coach and player
surfaces show identical values, matching what the edit form saved.

# TalentTrack v4.69.0 — My sessions: Revoke now works, and the current session is detected (#2219)

On the "My sessions" screen, revoking another device no longer fails with
"Could not identify the session to revoke." The list now enumerates
sessions keyed by their verifier hash (read straight from the
`session_tokens` usermeta) instead of via `WP_Session_Tokens::get_all()`,
which strips those keys and left the revoke form carrying a numeric index.
The active session is once again correctly marked "This session" and hides
its Revoke button.

# TalentTrack v4.69.0 — Activity detail: grouped panel + compact stat strip (#2220)

The activity detail page now reads as one cohesive record. The hero, a new
compact stat strip and the section cards sit inside a single softly-tinted
**grouping panel**, giving three tonal layers (page → tinted panel → white
cards) so the detail looks deliberate even when only a couple of sections
apply. The de-elevated hero is followed by a stat strip of the key numbers:
a match shows Present · Substitutes · Match length, a training shows
Present · Duration, each cell dropping out gracefully when its number is
unavailable. Every section card (Attendance, Line-up, Principles, Notes,
Tournament) keeps its titled header and only renders when it has content.
The line-up card's internal layout is unchanged here (its restyle is
tracked separately). Numbers are derived in the domain layer; the view only
composes them.

# TalentTrack v4.69.0 — Spond source indicator on the activity list and detail (#2221)

Activities imported from Spond now show their provenance. On the activities
list, a Spond-sourced card carries a small blue **Spond** chip alongside its
type and status pills; manually-created and generated activities show none.
On the activity detail page, Spond-sourced activities show a
**Team last synced from Spond: <time>** line in the audit footer — the
team's most recent Spond sync (the timestamp is team-level, and the label
says so, keeping the freshness claim honest). No schema change: the source
flag and the team sync time already exist. Both `activity_source_key` and
the team's `team_spond_last_sync_at` are exposed on the activity REST
payload so a future front end can render the same chip and freshness line.

# TalentTrack v4.69.0 — Match execution: completed matches are read-only, editing is opt-in (#2222)

The match-execution screen now opens read-only and hides its mutating
controls (score steppers, +action / →on buttons, and the post-match
late-goal / late-substitution panels) behind an explicit **Edit** toggle in
the header. Editing is only offered while the execution still accepts
writes — during play, half-time, and the post-match review window. A
**finalized** match shows no Edit affordance and keeps its live controls
locked, matching the read-only state the REST layer already enforced. This
removes the confusing "the match is done but the buttons still work"
behaviour. Reuses the existing `tt_edit_activities` capability — no new
permission.

# TalentTrack v4.69.0 — Match execution: pitch labels players by first name + last initial (#2223)

The vertical pitch on the match-execution screen now labels each player by
first name plus last initial (e.g. "Daan P.") instead of the surname —
matching how a coach names a player from the sideline while staying
unambiguous when two players share a first name. Single-word names render
as-is with no stray dot. Display formatting only; the label still fits the
360px pitch slot.

# TalentTrack v4.69.0 — Match execution detail: linked activity + correctable recorded minutes (#2224)

The match-execution screen now links its parent activity through the
breadcrumb chain (Dashboard / Activities / {activity} / Match execution),
so the activity is both visible and one tap away — no hand-rolled back
button. On a **finalized** execution it also adds a **Correct recorded
minutes** action: a coach with `tt_edit_activities` can edit each player's
recorded minutes with numeric inputs and Save (or Cancel back to the
read-only detail). Minutes are only correctable post-finalize, where no
auto-recompute can clobber the manual value; the correction writes through
the existing row-scoped `PATCH /attendance/{id}` path (its minutes column
now accepts a clamped 0–200 value), so the figure flows straight into the
minutes reports without reopening the locked match. No new endpoint,
capability, or schema change.

# TalentTrack v4.69.0 — Frontend authoring for the methodology library — foundation + Principles (#2225)

Academy editors can now author methodology content from the frontend, no
wp-admin required. A new "Manage methodology" surface lives alongside the
read view (`?tt_view=methodology&mode=manage`), gated on the existing
`tt_edit_methodology` capability, with a "View published methodology" link
back to the library. It opens with **Principles**: list, create, edit and
delete club-authored principles with side-by-side Dutch + English inputs for
the title, explanation, team-level guidance and per-line guidance. Shipped
reference principles stay read-only. The same data is exposed over REST at
`/wp-json/talenttrack/v1/methodology/principles` (GET/POST/PUT/DELETE), so a
future SaaS front end gets identical answers.

Under the hood this ships the reusable scaffold the rest of the methodology
entities build on: an extensible tab registry (each entity registers its own
manage tab without touching a shared switch) and a shared REST base
controller. Formations, set-pieces, visions, framework primers and the other
entities follow in later releases.

# TalentTrack v4.69.0 — Methodology authoring: Football actions (voetbalhandelingen) (#2230)

Editors can now create, edit and delete football actions
(voetbalhandelingen) straight from the frontend Methodology → Manage
surface, alongside principles. Each action has a slug, a category (met
balcontact / zonder balcontact / ondersteunend) and side-by-side Dutch and
English name + description. The same CRUD is available over REST at
`/wp-json/talenttrack/v1/methodology/football-actions`. Deleting an action
that a goal still links to is blocked (with a clear message) so the
`linked_action_id` reference is never orphaned. Club-scoped; shipped rows
stay read-only.

# TalentTrack v4.69.0 — Fixed age-banded measurement targets never resolving because the player's age group was read from a non-existent `tt_players.age_group` column; it now resolves via the player's team (`tt_teams.age_group`).

Fixed age-banded measurement targets never resolving because the player's age group was read from a non-existent `tt_players.age_group` column; it now resolves via the player's team (`tt_teams.age_group`).

# TalentTrack v4.68.0 — Test-results Excel export: a "Trends over time" sheet with a line chart (#2194)

The per-test Excel export now includes a second **Trends** sheet: one row per
player, one column per recorded date (chronological), the value in each cell,
plus a line chart that plots every player as a series over the shared date
axis. Numeric and scale-score tests are charted; status tests list each
player's recorded level per date for reference without a chart. Built from the
same result reads as the existing sheet — no extra queries.

# TalentTrack v4.68.0 — Test settings: "Direction" now saves on scale-score tests (#2195)

Editing a test on Configuration → Manage tests dropped the "Direction"
(higher / lower is better) setting on scale-score tests: the save clamped it
to "neither" for every non-numeric value type, even though the Direction
dropdown is shown for scale tests. Direction now round-trips for both numeric
and scale-score tests; pass/fail and status tests correctly stay neutral.

# TalentTrack v4.68.0 — Line-up bench: clean position codes instead of raw JSON (#2196)

The match-day line-up card's Bench row now shows a reserve player's
position as the clean short code (`LW`, `CDM`) instead of the raw stored
JSON array (`["LW"]`). Multi-position players join cleanly (`LW, CDM`) and
an empty position renders nothing. Starting XI was already clean; only the
bench fallback needed decoding.

# TalentTrack v4.68.0 — Match-prep: both half-pitches share one light background (#2197)

In match preparation the 2nd (right) half-pitch carried an orange tint
that read as a dark background. Both half-pitches now render with the same
light pitch surface, matching the printed sheet. CSS only.

# TalentTrack v4.68.0 — Match-prep PDF: empty fields print blank, not placeholders (#2198)

The match-prep PDF no longer prints empty-field placeholder text. In the
image-capture export, empty goal / attention inputs and unassigned
set-piece roles ("Goal 1…", the "…" hints, "— Pick player —") now render
blank; on-screen editing keeps its placeholders. The standalone print /
DomPDF sheet likewise drops the "—" dash for an empty attention note or an
unassigned role. CSS + printable-renderer only.

# TalentTrack v4.68.0 — Activities: Archive is now delete-class, not edit-class (#2199)

Archiving an activity is a soft delete, so it now requires the activities
create/delete capability rather than the edit capability. An assistant coach
who can only edit activities no longer sees the Archive (or Restore) button and
no longer hits a 403 on click; a head coach who can create/delete still does.
Both the detail-header buttons and the archive/restore REST routes gate on the
`activities:create_delete` matrix entity via the new
`tt_delete_activities → activities:create_delete` legacy-cap mapping — no new
matrix entity or seed migration.

# TalentTrack v4.68.0 — Activities: past-but-open activities surfaced by default (#2200)

Past activities that were never closed off (still Planned, not Completed or
Cancelled) now render in their own explicit "Past — still open" section at the
very top of the activity list — above the collapsed Past toggle — in a tinted,
orange-accented block. A coach sees overdue follow-ups without extra clicks;
completed and cancelled past activities stay in their normal collapsed Past
bucket.

# TalentTrack v4.68.0 — Activity list: the "show cancelled" toggle can be switched off again (#2201)

The "Geannuleerde tonen" toggle on the activities filter bar could be turned
on but not back off — once enabled the flag stayed set. The shared toggle
control now supports an explicit off-value: turning the switch off submits
`show_cancelled=0` (via a hidden companion field) instead of merely omitting
the param, so the cancelled filter clears and the switch reflects the off
state on reload.

# TalentTrack v4.68.0 — Goals list: status filter defaults to Active, no "All" (#2202)

The Goals list status filter no longer wraps to a second line. It now offers
three semantic buckets — Active, Achieved and Missed — rendered as pills with
coloured status dots, drops the "All" option, and defaults to Active so the
list opens on the goals a coach is actively working on. The REST endpoint maps
these buckets onto the canonical completed / cancelled status codes and still
honours raw status codes on existing deep links.

# TalentTrack v4.68.0 — FilterBar: status group always last and right-aligned (#2203)

On the shared filter bar the status pills now always render as the last
control on the inline (desktop) row and hug the right edge, regardless of
the order the calling view passes its filter groups. Other groups keep
their order and the mobile bottom sheet is unchanged. Component-wide change
— no caller edits needed.

# TalentTrack v4.68.0 — Per test: choose whether its results show on the player profile (#2204)

Each measurement test now has a **Show on the player profile** checkbox
(on by default) in the Manage-tests editor. Clear it to keep a test out of
the player-profile measurements view while it still records results and
appears in the results browser, reports and exports — handy for internal or
experimental tests. A new migration adds the `show_on_profile` column with a
default of 1, so every existing test stays visible on upgrade.

# TalentTrack v4.68.0 — Attendance leaderboard now defaults to all players (#2205)

The attendance leaderboard's *How many* field no longer defaults to 10.
Leaving it blank now ranks **every** player in the chosen window in both
the *Needs attention* and *Most reliable* tables. Typing a number still
narrows each table to that many rows, and the field is no longer capped at
50. The REST endpoint (`GET /reports/attendance-leaderboard`) follows the
same rule: an unset `n` returns all players.

# TalentTrack v4.67.1 — Minutes reports: one source of truth — actual recorded minutes only (#2193)

The minutes reports now agree with each other. Every minutes report reads
only the minutes that were actually recorded for a match — persisted on the
player's attendance row when the match was finalised or when a coach entered
the minutes by hand. Reports no longer estimate, calculate, or reconstruct
minutes from a planned line-up: a match that was played but never finalised
now shows a truthful 0 / — everywhere, instead of one report inventing an
estimate (e.g. 70′) while another correctly shows nothing.

Concretely, the Analytics "Gespeelde minuten per team" report dropped the
report-time recompute-from-line-up fallback it still carried, bringing it in
line with the Player · Minutes and Team · Minutes-distribution reports and
the minutes audit REST endpoint, which already counted recorded minutes only.
Matches that do have recorded minutes are unchanged.

# TalentTrack v4.67.0 — Archived activity detail page offers Restore, not Archive (#2183)

Opening an archived activity's detail page now shows a **Restore** action in
the header instead of a second **Archive** button. Restoring returns the
activity to the active list in one click. An archived activity is read-only
until restored — its Edit and match actions stay hidden until it is active
again. The read-only detail now resolves archived rows too, so an archived
activity no longer reads as "not found".

# TalentTrack v4.67.0 — FilterBar: explicit Apply button for the date range on the inline bar (#2184)

The shared filter bar now shows an explicit **Apply** button next to a
from/to date range on the inline (desktop) layout, so changing a date
range has a clear, keyboard-reachable way to commit — the inline bar
previously had no visible commit action for a date change. The mobile
bottom sheet keeps its single footer Apply (no duplicate). The button is a
plain submit: on a bare filter bar it reloads with the new range, and on a
list that filters live it hands off to the existing hydrator instead of
double-submitting. Every view using the filter bar with a date range
(audit log, comparison, and others) benefits.

# TalentTrack v4.67.0 — Attendance-per-player report: drill down from the activity count to the source sessions (#2185)

The **Activities** count on the player attendance report is now a link. Open
it to see the actual sessions behind the number: the activities list opens
filtered to that player, the report's team, and the report's date window,
showing only activities the player has a recorded attendance row for — and
each activity's detail shows the recorded attendance status. This lets a
coach trace any attendance figure back to real, dated sessions, mirroring
the minutes-played drill-down. The report already listed every player in the
window (worst-attendance-first) with no cap; that behaviour is unchanged and
now documented. The activities list gained optional `player_id` /
`date_from` / `date_to` filters to support the drill-down.

# TalentTrack v4.67.0 — Help buttons now hide when the Documentation module is disabled (#2186)

The contextual **Help** buttons (on goals, wizards, and anywhere else that
uses the shared help-drawer trigger) now render only when the **Documentation**
module is enabled under Configuration → Modules. Disabling the module removes
the buttons everywhere, matching the promise that a disabled module leaves no
dangling entry points. The gate reads the same module-state registry the
Modules admin page writes — no hardcoded check — and never fatals if the
disabled module class isn't loaded.

# TalentTrack v4.67.0 — Modules admin page is now matrix-driven (#2187)

The Modules admin page (wp-admin `tt-modules` and the frontend
`?tt_view=modules`) previously gated access on a WordPress role-name compare
(`current_user_can('administrator')`), which the authorization matrix could
not govern. It now checks the `tt_manage_modules` capability, bridged to a
dedicated `module_management` matrix entity, so the matrix decides who can
enable or disable modules — the same as every other admin surface. A new
migration re-seeds the grant onto existing installs (Academy Admin retains
access; WordPress administrators bypass unconditionally, so no one loses the
page on upgrade).

# TalentTrack v4.66.1 — Reports module: on/off toggles for the attendance, minutes-per-team and rate-card reports (#2126)

The Reports module-settings page now exposes a toggle for all 15 reports, not
just 10. The three attendance reports (team, player, leaderboard), the
minutes-played-per-team report and the rate cards were on the Reports launcher
but had no feature toggle, so an academy could not switch them off. They now
join the per-report catalog like the others: switching one off hides its
launcher tile and rejects a direct `?tt_view=…` link. All five default to on,
so existing installs keep showing them until an admin turns them off. No schema
change — the toggle state already accommodates new catalog keys.

# TalentTrack v4.66.1 — Test results browser: fix empty list caused by a bad age-group column (#2165)

The Testresultaten browser and `GET /measurement-results` returned no rows
because the underlying query referenced `pl.age_group`, a column that does
not exist on `tt_players` — age group lives on `tt_teams`. The query now
reads age group from the team, so the browser lists every player with a
value for the chosen test and the Leeftijdscategorie filter narrows
correctly. Repository-only change; no schema or UI change.

# TalentTrack v4.66.1 — Metingen vastleggen: status picker no longer clipped by the roster (#2166)

On the record-measurements roster, the coloured status picker's option list
was cut off — on a short roster only the skip option was visible — because
the roster used `overflow: hidden` to clip its rounded corners, which also
clipped the absolutely-positioned dropdown. The roster now uses
`overflow: visible` and the rounded-corner look is preserved by rounding the
first and last rows, so the full level list opens above the following rows
with its shadow intact. CSS-only.

# TalentTrack v4.66.1 — Trials list: filters moved to the shared FilterBar (#2174)

The Trials list filter bar (Status, Track, Decision, Include archived) now
uses the shared FilterBar component: an inline single-line row on desktop
and a "Filters" button + bottom sheet on phones and tablets. Filtering
behaviour is unchanged — same parameters, same results. The bespoke
filter-form styling was removed in favour of the shared component's sheet.

# TalentTrack v4.66.1 — Audit log: filters moved to the shared FilterBar (#2175)

The audit-log filter bar (Action, Entity, User #, date From/To) now uses
the shared FilterBar component: an inline single-line row on desktop and a
"Filters" button + bottom sheet on phones and tablets, with Clear as the
sheet's reset action. Filtering behaviour is unchanged — same parameters,
same results. The old hand-rolled, inline-styled form was removed.

# TalentTrack v4.66.1 — Player comparison: filters moved to the shared FilterBar (#2176)

The player-comparison filter block (Date from/to and Evaluation Type) now
uses the shared FilterBar component: an inline single-line row on desktop
and a "Filters" button + bottom sheet on phones and tablets. The date range
and evaluation-type filter drive the comparison identically — same
parameters, same results — and the Compare action still submits the player
picks together with the filters. The bespoke filter styling was removed.

# TalentTrack v4.66.0 — Strava console: in-context "Before you start" setup checklist (#2127)

The Strava operator console now opens with a short "Before you start" checklist
of the one-time steps that happen on Strava's side — create the API
application, set the Authorization Callback Domain to this site, then paste the
credentials and verify. It expands automatically until the app is configured,
then collapses. Dutch included.

# TalentTrack v4.66.0 — Strava console: Dutch translations + self-healing webhook subscription (#2127)

Translates the Strava operator console into Dutch (the new strings shipped
English-only) and makes the webhook subscription robust against Strava's
one-subscription-per-application rule. "Create / re-verify" now adopts an
existing subscription instead of failing when one already exists at Strava,
and the subscription status reconciles against Strava's real state on load —
so an id this install lost is recovered and a subscription deleted from
Strava's side clears here automatically. Backed by a new read of Strava's
`GET /push_subscriptions` endpoint.

# TalentTrack v4.66.0 — Attendance reports no longer count not-yet-held activities (#2135)

The team and player attendance reports — and the leaderboard and at-risk
panel that share their query — now exclude activities dated in the future.
An activity created via the normal "+ New activity" form defaults to
`plan_state = 'completed'`, so a future activity with pre-filled attendance
used to slip past the existing guards and inflate the statistics. The
reports now also require `session_date <= CURDATE()` (an activity dated
today still counts), matching the established predicate in
`ActivitiesRepository`. Query-only; past windows show identical numbers.

# TalentTrack v4.66.0 — Attendance reports: period quick-pills + activity-type filter (#2136)

Both the team and player attendance reports now carry the same filtering
vocabulary as the activities list: retrospective period quick-pills (Last
week, This month, This season) that set the From/To window for you — with
the manual date range still overriding — and an activity-type filter
(training / game / tournament). The type filter narrows every figure
consistently: the KPI tiles, the table, the leaderboard and the at-risk
panel. Filters render through the shared FilterBar (a Filters bottom sheet
on mobile, inline on desktop) and the filter flows into the shared
`AttendanceRankingQuery` plus a new `activity_type_key` parameter on the
attendance REST endpoints, so a SaaS consumer gets the same answers.

# TalentTrack v4.66.0 — Team attendance report: expandable rows to drill into players (#2137)

Each team row on the team attendance report is now tap-to-expand: tapping
the team name opens an inline sub-table of that team's players (player ·
present %, with at-risk players marked), loaded on demand for the active
window and filters from the shared `AttendanceRankingQuery`. One team is
open at a time. The disclosure is a semantic `<button aria-expanded>` and
is keyboard-operable; without JavaScript a "View players" link beside each
team opens the player report pre-filtered to that team instead, so the
drill-down is always reachable. The per-player slice is exposed at a new
`GET /reports/attendance` REST endpoint for non-WordPress consumers.

# TalentTrack v4.66.0 — Measurements: Record-measurements roster and profile cards readable in dark mode (#2142)

The Record measurements page and the player-profile Measurements cards rendered
dark text on a dark background when the operating system or browser was in dark
mode — the stylesheet darkened the card backgrounds without lightening the text,
while no other dashboard surface offers a dark variant. Removed those two
half-implemented dark-mode overrides so the measurement surfaces stay light and
legible in both modes.

# TalentTrack v4.66.0 — Measurements: coloured status picker on the Record-measurements roster (#2144)

Recording a status-type test now offers a custom, accessible status picker per
player instead of a plain native dropdown. Both the closed control and every
option in the open list show the level's colour square next to its label, and
the control sizes to the longest label so level names are no longer clipped to
the numeric column width. The picker is fully keyboard- and touch-operable
(Enter/Space or the arrow keys to open, ↑/↓ to move, type-ahead, Escape to
close) and progressively enhances the native `<select>` — with JavaScript off
the working native dropdown remains. The chosen level still posts and saves
exactly as before. Numeric, scale and pass/fail inputs are unchanged.

# TalentTrack v4.66.0 — Test results browser: navigate every measurement result in one place (#2145)

A new **Test results** tile in the Analysis group opens a dedicated browser
(`?tt_view=test-results`) for exploring measurement results across players.
Pick a test, optionally narrow by team, age group or date range, and read
each player's latest value: status tests show the level's colour chip and
label; numeric and scale tests show the value with a ▲/▼ trend against the
previous result and a green/amber flag against the age-group target. The
grid is sortable and every player name links through to their profile, and
the per-test Excel export is one click away. Team-scoped staff only ever see
results for their own teams. The same rows are exposed at
`GET /wp-json/talenttrack/v1/measurement-results` for a future SaaS front end.

# TalentTrack v4.66.0 — My activities now shows only your own sessions (#2150)

The dedicated **My activities** page could fall through to the broader
team/club result set when the player's linked-player resolution was missing
or mismatched, leaking activities that weren't theirs. The activities REST
list now fails closed for player and parent callers: it re-derives the
scoped player id from the session (a player's own linked player, or a
verified child for a parent) instead of trusting the request, and returns an
empty list when nothing resolves — never the unscoped set. Staff lists are
unchanged.

# TalentTrack v4.66.0 — "My card" tile renamed to "My profile" (#2151)

The player overview tile, header and breadcrumb now read **My profile**
instead of "My card", and the matching parent tile reads **My child's
profile** ("Het profiel van mijn kind"). Display string only — the internal
slugs (`my_card`, `overview`) and all routes and permissions are unchanged.

# TalentTrack v4.66.0 — My development: open a goal, activity or milestone in one tap (#2152)

The goal, upcoming-activity and journey-milestone rows on the player and
parent "My development" home are now tappable. Each row title links to that
record's player-facing detail (My goals, My activities, My journey),
carrying a back hint so the detail view shows a "← Back to My development"
pill. Evaluations stay list-linked — there is no per-evaluation player
detail to open. Makes the development narrative one tap deep instead of
forcing a trip through the full list.

# TalentTrack v4.66.0 — Players can connect their own Strava (#2153)

A logged-in player can now connect their own Strava account from their
profile without hitting a "not authorized" error. The player persona was
missing the `strava_integration` matrix entity, so under matrix gating the
self-service connect flow denied the athlete even on their own record. The
authorization seed now grants the player a self-scoped Strava grant
(`rc[self]`, mirroring `my_profile`), and a re-seed migration backfills it on
existing installs. The self scope means a player can only ever manage their
own connection — never another player's. Coach and admin behaviour are
unchanged.

# TalentTrack v4.66.0 — Long position descriptions on profiles, cards and dashboards (#2155)

Player positions now read as their full description (Centre back, Striker)
instead of the short code (CB, ST) across the profile/cards/dashboards
group: the player detail hero, profile tab and archived card; the coach and
player dashboard cards; the teammate card; the overview hero meta; the
my-profile card; the rate-card hero widget; and the FIFA-style player card
(including podium cards). Lists, rosters, PDFs, CSV exports and the REST
player payload keep the short codes. The long forms are already translated,
so no new strings.

# TalentTrack v4.66.0 — My team podium links now open the teammate profile (#2156)

On the player **My team** page, tapping a podium player led to the
staff-only unified profile and returned "not authorized". Podium cards in
player-facing contexts now link to the minimal, team-scoped teammate profile
— the same authorised page the roster links already used. Staff surfaces
that render the same podium are unchanged and still open the full profile.

# TalentTrack v4.66.0 — Configurable email sender — name + address for plugin email (#2157)

Configuration → General gains an **Email sender** group: set the name and
address every TalentTrack email is sent from, instead of inheriting the
WordPress default "WordPress <wordpress@…>". The values are applied to all
plugin email — account invitations and notifications as well as Comms
messages — via the wp_mail_from / wp_mail_from_name filters. Blank or invalid
values fall back cleanly to the WordPress default, so the From header is never
broken. Stored per club in tt_config, so a future multi-tenant install keeps
each academy's sender separate.

# TalentTrack v4.66.0 — Minutes reports: harden aggregation and stop fabricating estimates (#2158)

The minutes-played reports now count only canonical recorded attendance —
`record_type = 'actual'`, non-guest — and sum each player's minutes per match
before joining, so a player with a duplicate attendance row for the same match
is counted once instead of being doubled by a JOIN fan-out. The "Player ·
Minutes played" and "Team · Minutes distribution" reports also now join
attendance on the correct `activity_id` column (the previous join used a column
renamed away years ago, which was one cause of reports showing zero minutes).

A match with no persisted minutes, no execution and no lineup now contributes
nothing — the old "credit each starter half a match" estimate is gone, so a
total never mixes recorded minutes with invented ones. Correctly-recorded past
matches show identical numbers.

# TalentTrack v4.66.0 — Manual match-minutes entry on the attendance screen (#2159)

A coach who runs a "paper match" without the sideline match-execution flow can
now record minutes per player directly on the activity's attendance screen.
The minutes land in `tt_attendance.minutes_played` as actual, non-guest rows —
the single source the minutes reports read — so they flow straight into the
Player · Minutes and Team · Minutes reports. The minutes report now also surfaces
such matches even when they have no match-prep lineup.

The orphaned "Minutes Played" field on the evaluation form is removed and the
plugin no longer writes `tt_evaluations.minutes_played` (a column no report
read). Precedence: a later match-execution recompute remains authoritative and
overwrites manually-entered minutes for the same match.

# TalentTrack v4.66.0 — Minutes audit / trace-back: report drill-down and raw rows (#2160)

Every player's minutes total in the Team · Minutes reports (both the standard
report and the Analytics minutes report) now expands to the per-match rows that
sum to it — date, match, type, source (`actual` vs recomputed) and minutes —
reusing the same hardened query so the breakdown reconciles exactly with the
total. The trace is also exposed over REST at
`/teams/{id}/players/{pid}/minutes` for a non-WordPress front end.

The raw `tt_attendance` minutes rows — `minutes_played`, `record_type`,
`is_guest`, `activity_id` — are now documented and browsable in the Data
Browser for ad-hoc verification.

# TalentTrack v4.66.0 — Data Browser search now matches column names (#2161)

The Data Browser index search now also matches column names, so typing
"minutes", "club_id" or "uuid" surfaces every table that has a matching column —
not just tables whose name or description mention it. When a table surfaces
because of a column, the result row shows which column matched. Existing
table-name / description matching and the table-page row-value search are
unchanged. Column lists are already cached per table, so there is no extra
query cost.

# TalentTrack v4.66.0 — PDP planning: remove the misplaced "Show archived" button (#2162)

The PDP/POP planning matrix no longer shows a "Show archived" button. It
implied it toggled archived rows in the matrix, but the planning view is a
live aggregate that never includes archived conversations — the button just
navigated away to the PDP manage list. Restoring archived PDPs still lives in
the PDP manage list's archived filter, which is the right place. Removing the
button also keeps the planning view within the two-affordance navigation
contract.

# TalentTrack v4.65.0 — Tests & measurements: REST CRUD for the test catalogue (#2120)

Test definitions are now fully CRUD-able over the `talenttrack/v1` REST API
at `/measurement-definitions`: list (optionally including deactivated tests),
read a single test with its per-age-group target bands, create, edit, upsert
a green/amber band for one age group, and soft-archive. A hard-delete path
is gated on the recycle-bin capability so no purge is weaker than the bin's
own. Every route is matrix-gated on the `measurement_definitions` entity
(read / change / create_delete) and delegates straight to the existing
definitions and targets repositories — no business logic in the controller —
so a future SaaS front end gets the same answers as the plugin's Configure
view.

# TalentTrack v4.65.0 — Tests & measurements: a “Manage tests” surface for the catalogue (#2121)

Academy admins and heads of development get a dedicated “Manage tests”
configuration surface for the test catalogue, reached from a new tile under
Configuration. It lists every test definition — name, category, unit,
direction and cadence — with its active state, and offers per-row Edit,
Activate / Deactivate, and Archive actions. Creating a test still runs
through the existing “+ New test” wizard; editing is a flat form (Save +
Cancel) covering the definition fields plus the per-age-group green/amber
target bands that drive coverage flagging. The view is matrix-gated on
`measurement_definitions` change and composes the same repositories the REST
catalogue contract uses, so a future SaaS front end gets identical answers.

# TalentTrack v4.65.0 — Player profile: Measurements signal in the At-a-glance panel (#2123)

A player's measurement standing now reads as part of their journey
narrative, not just a separate tab. The profile's **At a glance** panel
gains a **Measurements** signal beside Avg rating, Attendance and Goals:
the number of tests the player currently has a value for, with a hint of
how many sit below their age-group target band (or "on target" when none
do). It links straight to the Measurements tab for the full per-test
timeline. The signal is gated on `measurements:read`, so it never leaks a
player's test standing to a role that can't open the underlying results.

# TalentTrack v4.65.0 — Tests & measurements: cross-linked surfaces and consistent framing (#2124)

The three test surfaces now read as one "Tests & measurements" module and
link to each other so staff move between configuring, recording, and
reviewing without going back to the dashboard. Record measurements links to
Manage tests; Manage tests links to Record measurements and Testing coverage;
Testing coverage links to Manage tests (shown only to staff who can edit the
catalogue). Every cross-link carries a contextual back-pill on arrival. The
three dashboard tiles keep their specific names but share a common
"Tests & measurements:" lead-in so they're recognisable as one product.

# TalentTrack v4.65.0 — Strava operator console in Configuration → Integrations (#2127)

Adds a Strava integration tile to Configuration → Integrations, next to Spond,
opening an operator console (`?tt_view=strava-admin`) where an academy admin
registers the Strava app Client ID + secret, creates or deletes the club-wide
webhook subscription, and sees every player who has connected — their status,
imported-activity count, last activity and last sync. Previously these were
only reachable over the REST API with no UI.

The operator surface is now matrix-gated instead of `manage_options`: viewing
follows the new `tt_view_strava` capability and credential / webhook changes
follow `tt_edit_strava_credentials`, both bridged to the `strava_integration`
matrix entity and tunable per persona. A new `GET /strava/connections` endpoint
backs the roster and never returns tokens or the client secret. A top-up
migration seeds the entity on already-installed sites so admins and heads of
development keep access on upgrade.

# TalentTrack v4.65.0 — Tests & measurements: a status value type with coloured levels (#2138)

Tests can now use a **status** value type — a simple, manually maintained,
dated player status built on the measurement framework. The operator defines
an ordered set of coloured levels (e.g. *At risk* red, *Watch* amber, *On
track* green) from a curated palette on the test's edit screen. Recording a
status shows a level dropdown per player in the bulk team-entry grid instead
of a number field, and the player's latest level shows as a coloured chip in
the Measurements tab of their profile. Each change is a dated entry, so the
player's status history is queryable over time. The levels are exposed over
REST at `/measurement-definitions/{id}/levels` (matrix-gated read / change),
and the colour is stored as a token key — never a raw hex — so the swatch
lives in the design system. A seeded **Player status** category groups these
tests.

# TalentTrack v4.65.0 — Export a test's results to a formatted Excel workbook (#2139)

The **Manage tests** view now offers an **Export to Excel** action on every
test row and in the test's edit view. It downloads a formatted `.xlsx` for
that one test: a header block (test name, unit or *status*, date range and
club) over a frozen, bold column-header row, then one row per recorded result
with the player, team, recorded date, value, age group and recorded-by —
grouped per player so a player's series reads together.

Status-type results show the recorded level label in the value column, filled
with the level's colour to mirror the player-profile chip. The export reuses
the existing export pipeline (no new REST route) and is gated on the
`measurements` read permission.

# TalentTrack v4.64.0 — Match prep PDF: white panels + consistent player boxes (#2112)

The **Export as PDF (A4)** capture rendered the doen-per-speler and rollen
panels with a grey background and tinted the on-pitch player boxes
differently over the blue (1e) and orange (2e) halves — html2canvas can't
resolve the nested `--tt-mp-paper` custom property, so the panel fill
dropped out and the translucent pills blended with the pitch. The capture
now forces opaque white panels and player boxes (and drops the card
shadows that printed as grey halos); the on-screen view and the pitch
colours are unchanged.

# TalentTrack v4.64.0 — Measurements: restore admin & coach access on upgraded installs (#2114)

On sites upgraded from before the Measurements module shipped, academy
admins, heads of development and coaches were silently denied access to
**Record measurements** and **Testing coverage** — the dashboard tile
appeared but the screen reported "no permission". The authorization rows
for the module were added to the seed but never back-filled into existing
installs (the matrix reseed is manual and destructive). A new idempotent
migration adds the missing `measurements` / `measurement_sessions` /
`measurement_definitions` matrix rows, leaving any operator edits intact.
The two staff tiles now gate on the same matrix entity the views enforce,
so a tile can no longer appear for someone the screen will refuse.

# TalentTrack v4.64.0 — Measurements: dashboard tiles show their icon again (#2115)

The **My measurements**, **Record measurements** and **Testing coverage**
dashboard tiles rendered an empty icon chip — they referenced an `activity`
glyph that does not exist in the icon set. They now use real bundled glyphs
(`trend-up` for My measurements, `track` for the two staff tiles).

# TalentTrack v4.64.0 — Team roster: hide the player STATUS column when Player Status is off (#2118)

The team detail roster gated its STATUS column (the traffic-light dot per
player) on whether the `PlayerStatusRenderer` class existed — but that class
is always autoloaded, so the column showed even when the Player Status module
was switched off. It now checks `ModuleRegistry::isEnabled()` for the module,
matching how the VCT panel on the same page is gated. With the module off the
roster shows only Jersey # and Player, no per-player status is calculated, and
the status styles are no longer enqueued.

# TalentTrack v4.64.0 — Team detail: hide squad rating from users without evaluation-view rights (#2119)

The team detail page's **At a glance** strip showed the **Squad rating
("Selectiebeoordeling")** tile to everyone who could open a team — including
an assistant trainer with no evaluation-viewing rights. The score is an
average of the roster's evaluation ratings, so it leaked gated data. The
tile is now shown only to users who hold `tt_view_evaluations`; without it
the tile is omitted entirely (not blanked to "—"), so the strip doesn't
hint that a hidden score exists. The Upcoming and Attendance tiles are
unchanged for all roles.

# TalentTrack v4.64.0 — Analytics: switch Evaluation coverage and Cohort decision board off individually (#2128)

The two Head-of-Development analytics surfaces — **Evaluation coverage**
and **Cohort decision board** — can now be hidden independently from
**Modules → Analytics**, without disabling the whole Analytics module or
touching the shared `tt_view_analytics` permission. Each is a per-tile
feature toggle: turning one off hides its tile and blocks its
`?tt_view=` route, while the central Analytics surface, the standard
reports and the analytics engine keep working.

Note for existing installs: both toggles ship **off by default**, so the
two tiles disappear on upgrade until an admin re-enables them under
Modules → Analytics. This is a deliberate change — academies that want
the surfaces switch them back on there.

# TalentTrack v4.63.5 — Week-PDF: icon-led meta line + revised default toggles (#2108)

Each info block on a weekly-planner-PDF activity card now leads with a
small icon — a clock before the time(s) (one clock, even when a match
shows both presence and kickoff), a pin before the location, and a note
icon before the notes line. The compose dialog's defaults changed to
match how coaches actually print: Duration and Principles are now off by
default, and Notes is on. Two new line icons (`clock`, `map-pin`) were
added to the shared icon set.

# TalentTrack v4.63.4 — Match prep: 3-4-3 diamond now draws as a diamond (#2099)

The **Aanvallend 3-4-3 (ruit)** formation drew a flat midfield on the
match-prep pitch (and the live match surface, the printable sheet and the
attendance projection) because positions were keyed by the formation's
shape string — so every template sharing the `3-4-3` shape collapsed onto
one flat layout. A formation template's own geometry (its `slots_json`)
is now authoritative when it carries slot numbers, so the diamond
positions its midfield as DM / LCM / RCM / AM. Formations without custom
geometry are unchanged. A migration adds slot numbers to the seeded
diamond template.

# TalentTrack v4.63.4 — Players and parents land on the one unified profile (#2107)

Opening "My card" (and a parent opening their child's card) now lands on the
same unified, permission-aware player profile that staff use — defaulting to
the Player card tab — instead of a separate card page. A player sees their card,
profile, goals, activities and Strava; staff-only surfaces (evaluations, PDP,
trials, notes, the guardians and discovery cards, the maturation/PHV flag, and
the status-history link) stay hidden for a player or parent. The breadcrumb is
framed for the viewer ("My card" for a player, the child's name for a parent,
the Players chain for staff). The Print report action carries over to the card
tab. Bookmarks to the old card URL resolve to the unified profile.

# TalentTrack v4.63.3 — Spond sync captures venue name AND address (#2096)

A Spond event's location carries both a venue name and a street address;
the sync previously kept only the first non-empty field, so the address
was dropped whenever a venue name was present. It now keeps both on one
line — `Venue | Address`. Single-value locations are unchanged, and a
name already contained in the address isn't duplicated.

# TalentTrack v4.63.3 — Match prep: Formation KPI tile now follows the dropdown (#2098)

Changing the formation in the match-prep dropdown now updates the
**Formation** summary tile immediately. Previously the tile kept showing
the value the page loaded with while the pitch below it re-drew, so the
two could disagree. The shared KPI-tile helper gained an optional `data`
attribute map to give the tile a stable JS hook.

# TalentTrack v4.63.3 — Match prep: Export as PDF now captures the live on-screen view (#2102)

The match-prep toolbar's **Export as PDF (A4)** button now takes a picture
of the on-screen match-prep grid exactly as it appears — both formation
pitches (blue 1e / orange 2e with the white name pills), the Selection ·
minutes table, Wedstrijddoelen, Doen per speler and Roles & set pieces —
and lays it out on **portrait A4**, scaled to page width and split across
pages on overflow. Previously the export captured a separately-styled
print document, so it never matched what the coach laid out on screen.
The capture engine (html2canvas + jsPDF) stays lazy-loaded on first click.
The standalone print route and the browser print dialog remain available
as fallbacks; the team-sheet print is unchanged.

# TalentTrack v4.63.2 — Fix critical error when editing a match activity (#2097)

Opening any match in the activity edit form raised a WordPress critical
error. The match-length / participation block referenced an `$id` variable
that was never defined in that render method, so a null id was passed to
methods expecting a non-nullable integer and PHP aborted with a TypeError.
The id is now resolved from the loaded activity, and create-mode matches
(which have no id yet) skip the lookups. Editing matches works again.

# TalentTrack v4.63.1 — Player card folded into the unified profile as a tab (#1988)

The player-card showcase that used to live only on a player's own "My card" —
the skills radar, the FIFA-style player card, and the rating KPIs (Latest,
Last 5 with its momentum delta, All-time, Evaluations) — is now a "Player card"
tab on the one unified player profile. A coach, head of development or parent
viewing the player now sees that at-a-glance standing in context, without
leaving the page. Same audience as the rest of the profile; no extra permission,
and the card keeps its own coming-soon state before the first rated evaluation.

# TalentTrack v4.63.0 — Parent dashboard mirrors the player's tile grid (#2081)

A parent's dashboard now mirrors their child's own development tiles —
the same Me-group surfaces a player sees, in the same order — relabeled
to the child's first name as an Anglo possessive ("Sven's development",
"Sven's card", "Sven's evaluations"). This replaces the hardcoded
five-tile curation shipped in #1992.

Because the tiles are resolved through the normal tile registry, the
parent surface inherits module and `player_*` feature gating
automatically: switching off a player feature (e.g. `player_goals`)
removes that tile for both the player and the parent, with no
parent-specific list to maintain, and adding a new player Me-tile
surfaces for parents with no extra work. "My tasks" is included so a
parent can help remind their child of pending tasks. Account-level
tiles (settings, password) stay the parent's own — not child-scoped or
relabeled. The child anchor (name + photo), the multi-child switcher,
and `player_id`-scoped URLs (with `canViewPlayer` authorization) are
unchanged.

# TalentTrack v4.63.0 — Record-state filters render as one-tap status pills across list views (#2083)

The active/archived record-state filter on the Goals, Players, Teams, People,
Holidays, Tournaments, Evaluations and PDP-coverage lists is now the
mobile-first FilterBar status-pill control (Active / Archived / All) instead
of a dropdown — record state is the same one-tap pill on every surface. Same
query params and results as before. The PDP setup list drops its bespoke
Active/Archived links in favour of the shared control (operators who can act
on archived files still see it; the `filter[archived]` param and coverage
endpoint are unchanged). Dead CSS left behind by the FilterBar adoption
(#2082) in the prospects-overview and admin sheets was removed.

# TalentTrack v4.63.0 — Prospects status filter no longer shows "All" twice (#2093)

The Prospects overview status filter listed an explicit "All" option on top
of the FilterBar placeholder's own "All", so the dropdown showed it twice
after the FilterBar migration. The redundant option is removed; the filter
behaves identically.

# TalentTrack v4.62.0 — My PDP redesigned to a timeline-first player development view (#1990)

The player's *My PDP* surface is rebuilt around the season as a timeline. The
development conversations now sit on a horizontal rail as markers — completed,
the next planned talk, and later talks — with a progress fill up to the most
recent completed conversation. Tapping a marker expands that conversation's
detail in place (notes, agreed actions, agenda, goals discussed, saved
reflection and the acknowledgement button), so there is no long scroll.

Below the timeline the player sees their active focus goals with goal-specific
status labels, then a single self-reflection input for the one next-planned
conversation only — past and future talks never show an input, and there is
never more than one form. Any previously saved reflection appears to the right
of the input on wider screens and stacked below it on mobile. The 2-week
pre-talk window guard, the coach sign-off display, the acknowledgement flow and
the end-of-season verdict card are preserved.

The "which talk is next planned" and "is its reflection window open" decisions
live in the PDP domain layer (PdpCycleState), so the REST API and the rendered
view derive the same answer.

# TalentTrack v4.62.0 — Parents can open their child's development me-views (#1991)

A parent linked to a player but with no own player record was denied every
"Mijn …" me-view ("This section is only available for users linked to a
player record"). The dispatch gate checked "is the current user a player"
instead of "can the current user view this player".

The gate now authorizes the resolved target via
`AuthorizationService::canViewPlayer`, and the subject resolution falls back
to the parent's linked child (from `tt_player_parents`) when no explicit
`?player_id` is present: a single-child parent auto-resolves to that child;
a multi-child parent gets a child picker first and chooses. A user with no
own player and no linked child is still denied, and there is no cross-family
or cross-academy leakage (every read still passes `canViewPlayer`). The same
authority backs `GET /players/{id}`, so a non-WordPress front end gets the
same answer.

# TalentTrack v4.62.0 — Parent dashboard is now anchored on the child, not empty player-self tiles (#1992)

On the legacy tile-grid dashboard a parent saw an empty "Werk van vandaag"
column plus a "MIJN WERK" rail of player-self tiles that all denied (the
parent has no own player record). The grid had no parent-awareness.

A parent viewer (no own player, at least one linked child) now lands on a
child-scoped surface: the child's name and photo anchor the screen, a
curated parent tile subset is shown (development, player card, evaluations,
activities, development plan), each tile carries the child's `?player_id=N`
so the me-views resolve and authorize that child, and the empty
work-of-today column is hidden. A child switcher appears when the parent is
linked to more than one child. Which tiles and which child are domain
decisions (`ParentDashboardTiles` / `ParentChildResolver`), kept out of the
view.

# TalentTrack v4.62.0 — tt_player_parents is now the single source of parent → child links (#1993)

The `tt_player_parents` pivot is now the only live answer to "which children
does this parent have". The parent dashboard child switcher, the parent KPI
resolver, and the goal-thread participant graph previously matched
`tt_players.guardian_email` against the parent's WordPress email — a second,
divergent model that could disagree with the authorization layer (which
already read the pivot). They now all resolve through the new
`ParentChildResolver`, so the switcher and the me-view authorization list the
same children, club-scoped, with no email matching.

`guardian_email` is demoted to an invite/seed hint: it may still create a
pivot row when a parent is invited, imported, or seeded, but is never queried
to decide access. This is a code-only change with no migration — a parent
linked solely via `guardian_email` will surface once re-linked through the
invite/seed path or by an admin.

# TalentTrack v4.62.0 — Match executions: coach team scope no longer silently empty (#2016)

A head coach who owned teams could open Wedstrijduitvoeringen (match
executions) and still see "No teams visible to you yet", because the view
scoped coach teams via a hand-rolled JOIN on `tt_user_team_link` — a table
no migration ever creates, so the query returned nothing for every
non-admin coach. The same dead-table join silently emptied the
"Matches needing review" persona-dashboard widget. Both now resolve a
coach's teams through the canonical `QueryHelpers::get_teams_for_coach()`
(active `tt_user_role_scopes` grants plus the legacy backfill), so coaches
see their squad's match executions and pending-review reminders. A coach
with no team grants still sees the empty state. Admin / academy-wide lens
unchanged. Query-layer fix only — no schema or data changes.

# TalentTrack v4.62.0 — Recycle-bin foundation: schema, capability, retention config (#2020)

Lays the groundwork for the recycle bin (archive → trash → purge). Every
archivable record type now carries `trashed_at` / `trashed_by` columns, so a
later release can stage records for permanent deletion with a recovery window.

A new academy-admin-only capability, `tt_manage_recycle_bin`, owns permanent
deletion — it is never granted to coaches, Heads of Development, or anyone
holding only settings rights. A per-club retention window
(`tt_recycle_bin_retention_days`, default 30) is seeded for the future purge
process, and the bin gets its own `recycle_bin` authorization-matrix entity.

No user-visible behaviour changes yet — this is the substrate the bin's UI and
purge logic build on. See the new Recycle bin help page for the retention and
GDPR right-to-erasure basis.

# TalentTrack v4.62.0 — Recycle bin: archive → trash → purge lifecycle core (#2021)

Adds the recycle-bin domain core to `ArchiveRepository`: a third soft-delete
tier (active → archived → trashed → purged) layered on the existing archive.
Entities can now be moved to a recycle bin, restored back to archived, or
permanently purged through the existing fail-closed cascade. Trashed records
of minors are hidden behind the `tt_manage_recycle_bin` capability and scoped
to the club on every query, and each transition is recorded in the audit log.
Domain layer only — the bin's list view and REST endpoints land in follow-up
work; no user-visible screens change yet.

# TalentTrack v4.62.0 — Recycle bin: read-only archived/trashed detail (#2022)

Fixes Bug 1: opening an archived or trashed record showed "does not exist",
because every detail view's lookup ends in `WHERE archived_at IS NULL` and so
never received a non-active row. Detail views (players, teams, evaluations,
goals) now retry through the archive-aware visibility gate and render a
**compact read-only summary card** for archived and trashed records instead —
the record's identity plus a few key fields and a status banner, with no Edit
affordance (restore first, then edit).

An **archived** record shows an amber banner with who archived it and when,
plus **Restore** and **Move to recycle bin**. A **trashed** record shows a red
banner counting down to the purge, plus **Restore to archive** and **Delete
permanently now**, wired to two new `tt_manage_recycle_bin`-gated REST routes
(`POST recycle-bin/{entity}/{id}/restore`, `DELETE recycle-bin/{entity}/{id}`).

Privacy-critical: a trashed record is a soft-deleted minor's record. A
non-admin who opens a trashed record's link gets a clean "not found" — never a
permission-denied page that would confirm the record exists. The card lives in
a single shared `ArchivedDetailCard` renderer so the banners and actions can't
drift per entity.

# TalentTrack v4.62.0 — Recycle bin: archived-list affordances + payload audit (#2023)

Fixes a bug where archived holiday rows showed only Restore and no
destructive action: the holiday REST payload omitted `archived_at`, so the
list-table visibility check hid both archived-row actions. A new shared
`LifecycleFields` helper now emits `archived_at` plus the new `trashed_at`
on every list/detail payload that surfaces lifecycle state, so the field
can't drift per entity.

The archived-tier destructive action is relabelled from "Delete permanently"
to **"Move to recycle bin"** and re-pointed at a new reversible
`POST {entity}/{id}/trash` route (the irreversible purge stays inside the
recycle bin). Moving a record now shows a full itemized cascade preview in
the confirm dialog, and the success banner offers one-click **Undo**. The
per-entity "All" status tab is dropped — trashed records never appear in
ordinary lists, leaving Active and Archived as the only views.

# TalentTrack v4.62.0 — Recycle bin: centralized view + REST + settings entry point (#2024)

Adds the centralized recycle bin — a single admin-only screen
(`?tt_view=recycle-bin`, reachable from Configuration → System) that lists
every trashed record across all 20 archivable entity types, grouped by type
with counts. Each row shows its identity, who and when it was binned, and a
days-until-purge badge that turns red in the final week. Two inline actions:
**Restore** returns the record to the archive, and **Delete now** permanently
purges it after a cascade-preview confirm. A blocked purge surfaces the
dependency report and leaves the record in place.

The bin is academy-admin only (`tt_manage_recycle_bin`). Three new REST
routes back it: `GET /recycle-bin` (cross-entity list), `POST
/recycle-bin/{entity}/{id}/restore`, and `DELETE /recycle-bin/{entity}/{id}`.
Every mutating route verifies both the capability and that the target belongs
to the current academy before it runs, so a forged or foreign-tenant id is a
not-found, never a silent success. The `{entity}` segment is validated against
the archive's entity allowlist.

Closes the "no purge path weaker than the bin" gap: every legacy per-entity
permanent-delete endpoint (`DELETE …/permanent`) is re-gated onto
`tt_manage_recycle_bin`, so all permanent-deletion paths now require the same
capability as the bin's own purge.

# TalentTrack v4.62.0 — Recycle bin: 30-day automatic purge (#2025)

Adds the unattended purge that empties the recycle bin after the retention
window. A daily sweep finds every record trashed longer than the club's
retention window (default 30 days) and permanently deletes it — no one has to
remember to empty the bin.

The sweep runs through the same fail-closed deletion path as the manual
"Delete now": player and person records are erased across every linked table
via their cascade services, so a minor's child PII is never stranded. It runs
on the workflow engine's existing background schedule (not a separate cron),
self-throttles to once per day, and is scoped per academy so a record is only
ever purged within its own tenant. Because the job runs with no one logged in,
its audit entries are attributed to the system, so the audit log never implies
a person pressed delete.

Records the purge cannot delete — because other records still reference them —
are skipped, left safely in the bin, and surfaced in the recycle-bin view with
a banner ("N records couldn't be auto-deleted — still referenced"). A few
record types (measurement definitions and trial tracks) are templates that can
never auto-purge by design; the bin now flags those so the 30-day countdown is
never read as "these vanish at 30 days".

# TalentTrack v4.62.0 — Shared filter bar, adopted on Activities (#2026)

A new reusable filter bar replaces the bespoke filter row on the
Activiteiten list. On desktop it lays the controls out on a single line —
each under its own label, with the four control types kept visually
distinct (Team/Type selects, a Period pill-dropdown, Active/Archived/All
status pills, and a Show-cancelled switch). On a phone or tablet the bar
collapses to a **Filters** button with an active-count badge plus summary
chips; tapping it opens a bottom sheet with the same controls and an
Apply / Clear footer. Keyboard- and screen-reader-operable, with the
sheet closing on Escape, scrim tap, or the close button.

All existing Activities filtering is unchanged — Team, Type, period
quick-windows, archive status, and Show-cancelled keep the same query
params and produce the same results. The new `FilterBar` component is
data-driven and carries no Activities-specific logic, so the other list
views can adopt it in later phases of the filter-bar epic (#2017).

# TalentTrack v4.62.0 — Teams and activities can now be permanently deleted, player data preserved (#2027)

Permanently deleting a team or an activity used to be refused outright while
anything still referenced it, so a trashed team or activity could never be
purged and accumulated in the recycle bin forever. Both now have a complete,
player-centric delete plan.

Deleting a **team** removes only pure team configuration (formations, playing
styles, chemistry, blueprints, staff assignments, per-team exercise overrides
and the VCT periodization stack). The team's players, their team history, the
team's activities (with their attendance and evaluations), tournaments and
measurement sessions are all kept and re-homed to "unassigned" rather than
deleted. Open invitations, workflow tasks and staged ideas pointing at the
team simply have the link cleared.

Deleting an **activity** removes the execution data that only lives inside it
(attendance, planned exercises, principles, and the match-prep and
match-execution trees) plus its journey events, while evaluations, behaviour
ratings and tournament/VCT bindings survive with their link cleared.

A development record is never destroyed by deleting a team or activity — worst
case it is left unassigned. The deletion framework gains a "reset to
unassigned" disposition for required links that can't be emptied, and a
fail-closed completeness check guarantees a future schema change can't quietly
make teams or activities un-deletable again.

# TalentTrack v4.62.0 — Goal-detail page: goal left, conversation right two-column layout (#2029)

The standalone goal-detail pages now place the goal card on the left and the
Gesprek (conversation) on the right in a two-column grid on tablet and wider
screens (>=768px), stacking goal-then-conversation on phones. This applies to
both the coach view (`?tt_view=goals&id=N`) and the player/parent view
(`?tt_view=my-goals&id=N`), matching the existing two-column treatment on the
POP detail. Layout-only change: the grid and spacing moved out of inline
styles into the enqueued `frontend-goals.css` sheet; no data or query changes,
and the conversation pane (bubbles + compose box) is unchanged.

# TalentTrack v4.62.0 — Team detail: hide the chemistry teaser when the feature is off (#2033)

The "Team chemistry" card and its *Open the chemistry board* link on the
team detail view no longer appear when the `team_chemistry` sub-feature is
switched off, or for personas without chemistry read authority. The teaser
now uses the same access gate as the chemistry board itself, instead of
rendering whenever the module class is loaded.

# TalentTrack v4.62.0 — Branded TalentTrack 404 replaces the theme 404 and "Unknown section" (#2035)

A bad URL or an unknown `?tt_view=` slug now lands on one consistent,
branded "Offside! This page is out of play" page instead of the active
theme's 404 or a bare "Unknown section." line. The real WordPress 404 is
rendered through the same theme-free canvas chokepoint as the dashboard —
HTTP 404 status preserved, no theme chrome leaking — and offers a single
"Back to dashboard" button. The in-app fallback shows the same branded
content inside the dashboard, with the breadcrumb chain as the way back.
Operators running TalentTrack alongside other content can disable the
takeover via the club-scoped `tt_handle_wp_404` config flag or filter
(defaults on).

# TalentTrack v4.62.0 — PDP conversation breadcrumb returns to the player's file (#2038)

Opening a conversation from a PDP file and then using the breadcrumb back
affordance now returns to that player's PDP file, not the whole PDP list.
The conversation page previously reused the file-detail breadcrumb chain,
whose only clickable step was the PDP list. It now renders its own chain —
"PDP → PDP file detail → Conversation" — with the file-detail crumb as the
back-to-file step. Navigation only; no data or query changes.

# TalentTrack v4.62.0 — PDP coverage list: remove redundant "Open" button (#2039)

Rows in the PDP coverage list that already have a development plan no longer
show a separate "Open" button — the whole row is already clickable, and the
green coverage pill is a link to the same file for keyboard and assistive
tech. The "Create PDP" action on players without a plan is unchanged.

# TalentTrack v4.62.0 — PDP list: one player-centric list with Active/Archived + team gate (#2040)

The PDP tile now opens on a single player-centric list — the old Coverage /
Files tab split is gone. Archived PDP files moved into the same list behind
**Active / Archived** state pills (for operators who can unarchive or delete),
each archived row keeping its Restore / permanent-delete actions. Users who
span more than one team (or have global scope) now pick a team first ("Select
a team to see its players.") instead of facing an unscoped all-players list; a
single-team coach goes straight to their roster. The redundant per-row Open
button stays gone (#2039). The `pdp-files/coverage` REST endpoint gained an
`archived` parameter for the new view.

# TalentTrack v4.62.0 — PDP conversations: only the active conversation is fully editable (#2041)

PDP conversations now run strictly in order. Only the active conversation —
the earliest one not yet signed off — is fully editable. Later conversations
in the cycle are read-only except for their planned date, so a coach can
schedule the whole season ahead without filling in a talk out of turn. A
later conversation opens for full editing once the one before it is signed
off. Enforced both in the form and in the REST endpoint. Signed/acknowledged
conversations keep their existing end-to-end lock.

# TalentTrack v4.62.0 — PDP: coach can record player/parent acknowledgement (#2042)

When a development conversation is held in person, the coach can now record
the player's and/or parent's acknowledgement on their behalf, straight from
the conversation form — *Record player acknowledgement* / *Record parent
acknowledgement*, each behind a confirm dialog. It writes the same
acknowledgement a player or parent would record themselves, available once
the coach has signed the conversation off. The player/parent self-service
acknowledgement on *My PDP* is unchanged.

# TalentTrack v4.62.0 — PDP verdict: gated and moved next to the conversations (#2043)

The end-of-season *Record verdict* button moved out of the top action bar to
sit with the conversation list, below the cycle. It now stays disabled until
every conversation in the cycle is signed off, showing the progress on the
button itself — e.g. *Record verdict (3/5 conversations closed)* — so it's
clear why it isn't available yet rather than simply missing. Once all
conversations are closed it enables and opens the existing verdict form.

# TalentTrack v4.62.0 — Strava integration — schema foundation (#2055)

Adds the database foundation for the per-player Strava integration (epic
#2002): a `tt_player_strava_connections` table holding one encrypted-token
connection per player, and a player-scoped `tt_player_activities` table for
the personal training (runs, rides, conditioning) those connections import.
Both carry the `club_id` + `uuid` tenancy scaffold. Activities store
distance, duration, pace and elevation only — no heart-rate data, by design.
Schema-only; no behaviour change until the connect flow ships.

# TalentTrack v4.62.0 — Strava integration — OAuth connect flow (#2056)

Adds the per-player Strava account connection flow (epic #2002). Players (and
coaches/admins acting on a player) can start a one-time OAuth authorization
that links a Strava account to the player's TalentTrack record. The OAuth
callback authenticates via a signed, time-limited `state` — the one route
that can't use a WordPress nonce — exchanges the code for tokens server-side,
and stores the access + rotating refresh token encrypted at rest, per player.
Disconnecting revokes the grant at Strava and clears the stored tokens.

No activities sync yet — this slice is the connection plumbing; the token
refresh, webhook, and ingest slices follow. Access tokens are never exposed
to the browser; the Strava app client secret is write-only.

# TalentTrack v4.62.0 — Strava integration — token refresh service (#2057)

Keeps connected players' Strava access tokens fresh (epic #2002). Strava
tokens expire after six hours and the refresh token rotates on every refresh,
so a connection is kept alive two ways: a proactive sweep on the workflow
engine's hourly heartbeat refreshes any token nearing expiry, and an on-demand
refresh runs immediately before an activity sync if needed. The rotated
refresh token is always saved atomically with the new access token. If Strava
rejects a refresh (the grant was revoked), the connection is flagged so the
player can reconnect, instead of retrying a dead token forever.

# TalentTrack v4.62.0 — Strava integration — activity ingest (#2058)

Imports a connected player's Strava activities onto their TalentTrack record
(epic #2002). When an activity is recorded, it's fetched with the player's
token and saved to the player's own activity list — distance, duration, pace
and elevation only. Heart-rate and other biometric data are never read or
stored, by design, so the integration works for the academy's mostly-minor
cohort without tripping Strava's under-16 heart-rate restriction. Deleting an
activity in Strava (or disconnecting) archives it on our side. A new read
endpoint exposes a player's imported activities for the profile timeline.

# TalentTrack v4.62.0 — Strava integration — webhook sync (#2059)

Wires up live, push-based syncing for the Strava integration (epic #2002).
Instead of polling, TalentTrack registers a single academy-wide webhook
subscription with Strava and reacts to pushes: a new or edited activity is
imported within minutes, a deleted activity is archived, and when an athlete
disconnects from Strava's side their connection is revoked and their imported
activities are archived automatically. The subscription is operator-managed
(create / view / delete), and the validation handshake is answered securely
with a per-install verify token.

# TalentTrack v4.62.0 — Strava integration — consent capture + audit (#2060)

Adds the consent gate for connecting a Strava account (epic #2002, Gate 2).
Connecting now requires an explicit, audit-logged consent acknowledgement,
and the consent is recorded before any redirect to Strava — enforced on the
server, so the authorization step cannot be reached without it. The recorded
consent (and when it was given) is surfaced on the connection status.

Per the product decision of 2026-06-28, consent is captured on the player's
own profile rather than a parent's view — a deliberately simpler flow whose
minor-safeguarding trade-off is recorded for future legal review.

# TalentTrack v4.62.0 — Strava integration — connect panel on the player profile (#2061)

Adds the player-facing Strava panel (epic #2002): a mobile-first "Connect with
Strava" surface reachable at its own page (`?tt_view=strava`) and as a Strava
tab on the player profile. It shows connection status, a consent checkbox that
must be ticked before connecting, a disconnect button, and the imported
activities (distance, duration, pace — no heart-rate). Connecting sends the
player through Strava's authorization and brings them back to the profile with
a clear confirmation. Fully translated into Dutch.

# TalentTrack v4.62.0 — Player profile: hide the PHV panel when the VCT module is off (#2064)

The player profile's PHV control (the "Speler heeft een PHV-vlag" checkbox +
reason dropdown) is VCT functionality, but it rendered even when the VCT
module was switched off. The PHV hero pill, the Profile-tab panel, and the
PHV form POST handler now all gate on the VCT module being enabled, so a club
that doesn't use VCT no longer sees misleading conditioning controls on a
player's record. Behaviour is unchanged when VCT is on.

# TalentTrack v4.62.0 — Team profile: squad panel no longer shows archived or trashed players (#2065)

Archiving a player removed them from active rosters everywhere except the
team profile's squad panel (and, for trial players, the trials sub-panel),
where they kept appearing — an archived or released minor resurfacing in a
roster a coach was browsing. The three player-fetch helpers behind those
panels (`QueryHelpers::get_players()`, `QueryHelpers::get_players_for_teams()`,
and the team-detail trial loader) filtered on `status` alone, which is
orthogonal to the archive/trash lifecycle introduced with the recycle bin.
They now append the canonical active-lifecycle clause
(`ArchiveRepository::filterClause('active', 'p')`), so archived and trashed
players drop out of the squad panel, the trials sub-panel, and coach-dashboard
rosters immediately. Active players are unaffected. Query-layer fix only — no
schema or data changes.

# TalentTrack v4.62.0 — Chemistry settings: dark-mode legibility + compact number inputs (#2069)

The Chemistry settings page (`?tt_view=chemistry-config`) is now legible in
dark OS/browser modes again. The partial `prefers-color-scheme: dark` block
darkened the block background but never lightened the text, leaving dark-on-dark
legends, labels and hints — the surface has no real dark variant, so that block
is removed and the page stays on its light design system. The numeric weight
inputs also no longer blow out to full row width: the selector now wins over the
global `.tt-input { width: 100% }` rule, restoring compact ~5rem right-aligned
boxes with the label-left / input-right flex row intact. CSS only.

# TalentTrack v4.62.0 — Chemistry settings page + tile hidden when team chemistry is off (#2071)

With the Team chemistry feature switched off, the Chemistry settings view
(`?tt_view=chemistry-config`) and its dashboard tile stayed reachable while
the main formation board correctly hid. The `team_chemistry` feature now
claims the `chemistry-config` slug too, so the dispatcher renders the
standard module-disabled notice before the view loads — for administrators
as well as other roles — and the settings tile carries the feature tag so it
disappears from the dashboard when the feature is off. With the feature on,
the page and tile behave exactly as before.

# TalentTrack v4.62.0 — Goal conversation restyled to the green/gold timeline (#2072)

The conversation ("Gesprek") on a player's goal-detail page used a generic
blue chat style — a right-aligned blue self-bubble and navy author names —
that clashed with the 2026 green/gold design shown to parents and players in
the pilot presentation. It now renders as a single left-aligned timeline:
each message carries a green ring marker, a muted date above a bold green
author name, and a white bubble with a thin border, and the Send button is
green. The change is in `frontend-threads.css` only — markup, REST, polling,
and the edit/delete affordances are untouched, so both initially-rendered and
newly-posted messages share the look. Mobile-first rules are preserved
(360 px single column, 48 px Send, 16 px textarea, focus-visible rings,
reduced-motion).

# TalentTrack v4.62.0 — List filters get the mobile-first FilterBar chrome (#2082)

Every list surface built on `FrontendListTable` (players, goals, teams, people,
evaluations, holidays, tournaments, prospects, functional roles, custom fields,
my activities, PDP, …) now renders its filter row through the shared, mobile-first
FilterBar: a single inline row on wide screens that collapses to a "Filters"
button and a bottom sheet on phones. Filters are the same as before — the team /
type / status selects, the search box, and the from/to date ranges all filter
exactly as they did, with the same URL parameters, sorting, pagination and
live-filtering — they just gain a touch-friendly layout on small screens.

The list table keeps owning rows, sorting, pagination and per-page; only the
filter chrome moved. FilterBar gained free-text/search and date-range group
types and an opt-in status-pill rendering for views that want one. No view
needed changes to inherit the new chrome.

# TalentTrack v4.62.0 — Week-PDF: match cards show the typed title (#2089)

Match activities on the team-planner Week-PDF now print the title entered
on the activity form (e.g. "Candia 66 – Vv hedel 14-1") instead of
collapsing to just the team name. The card previously synthesized its
title as "Team — Opponent" and ignored the activity's own Title field;
since the form captures a required Title but no opponent, matches printed
only the team name. The card now prefers the entered title and falls back
to "Team — Opponent" (or the team name) only when no title is set. Match
location is unchanged — it already prints when the Location field is filled.

# TalentTrack v4.61.0 — Holiday rows now open an enriched read-only detail view (#1997)

Clicking a holiday row used to drop managers straight into the edit form and
left read-only viewers with inert rows. It now opens a scheduling-centric,
read-only detail page at `?tt_view=holidays&id=N` for every viewer who can see
holidays. The page shows the holiday name, the period formatted in the active
locale (e.g. "21 dec 2026 – 4 jan 2027"), the inclusive duration in days, the
note (or a dash), the colour swatch when one is set, and a one-liner reminding
the user the holiday banners across these days on every team planner. Managers
get an Edit button into the existing edit form; non-managers see the summary
only. The list-table row link points read-only viewers at the detail view, so
their rows are clickable for the first time.

A computed `day_count` (inclusive day span) is now exposed on the holiday REST
payload (`GET /holidays` and `GET /holidays/{id}`); the day-count maths lives
in `HolidaysRepository::dayCount()` so the REST API and the rendered view stay
in lockstep.

# TalentTrack v4.61.0 — Head coaches can open the Trial cases tile again (#2005)

The Trial cases list view gated entry on `tt_manage_trials`, which maps to
`trial_cases:create_delete`. Head coaches hold `trial_cases [read, change]`
at team scope in the authorization matrix but not `create_delete`, so the
tile let them in but the view returned a "no permission" page. The view now
gates entry on a matrix read check (matching the tile), scopes the list to
the players on the head coach's own teams, and keeps the "New trial case"
create action plus the create/delete write paths gated on `tt_manage_trials`.
Head coaches can now view and edit trial cases for their teams; only managers
can create or delete them. Scout, head-of-development and admin behaviour is
unchanged.

# TalentTrack v4.61.0 — Player comparison selectors now respect coach context (#2006)

The Player comparison team and player selectors no longer expose the whole
academy roster to a team-scoped coach. Both the frontend tile and the
wp-admin Player Comparison page now narrow the selectors to the coach's own
teams, exactly like the standard reports surface and the `reports/player-radar`
REST endpoint: staff with academy-wide reporting access (head of development,
academy admin, scout) still see every team and player, while a team-scoped
coach sees only their assigned teams and the players on them. The scope is
also enforced on players addressed directly by `?pN=` link, so an
out-of-context player can't be pulled into a comparison.

# TalentTrack v4.60.0 — My journey: position-change events show friendly position names (#1983)

A position-change entry on a player's journey timeline now reads the
human-friendly position names ("Centrale verdediger, Linksback") instead of
the raw codes — or, for older entries, the raw JSON array `["CB","LB"]`. The
event formatter resolves each code through the shared position-label
translator, and a one-time backfill rewrites existing position-change events
so historical entries read the same. Unknown / custom positions pass through
unchanged.

# TalentTrack v4.60.0 — Evaluations: the staff-only note field is now clearly labelled (#1984)

When writing an evaluation (both the rate-players wizard step and the flat
coach form), the free-text note field was labelled simply "Notes" — with no
sign that it is staff-internal and never shown to the player. Coaches typed
player-directed feedback there, expecting the player to read it, while the
separate "Feedback for the player" field stayed empty. The field is now
labelled "Internal notes (staff only)" with a "Not shown to the player"
placeholder, so the two audiences are unmistakable. The player-facing
feedback continues to appear on the player's My evaluations detail; the
internal note stays staff-only.

# TalentTrack v4.60.0 — Goals: the "pending" status reads "In ontwikkeling" in Dutch (#1985)

A player goal that is still pending now reads the more development-minded
Dutch label **"In ontwikkeling"** instead of "In behandeling". Goal statuses
now carry their own gettext context, so this wording is specific to goals —
the generic "Pending" label used elsewhere in the app is unchanged.

# TalentTrack v4.60.0 — My activities: full-width on desktop, all info inline (#1986)

The player's **My activities** list now uses the full dashboard width on
desktop instead of a narrow 860px column. Rows are no longer clickable — the
old row link pointed at the staff activity-detail view, which a player isn't
authorised for (it returned "niet geautoriseerd"). Everything a player may
see is now shown inline in the table, including a new **Location** column
alongside date, title, type, team and their own attendance status.

# TalentTrack v4.60.0 — Academy admin can switch off individual player dashboard tiles (#1987)

The player dashboard tiles — My journey, My team, My evaluations, My
activities, My goals and My PDP — are now per-academy features under the
Players module on the Modules &amp; features screen (`?tt_view=modules`). They
ship on; switching one off hides that tile from players *and* blocks its
`?tt_view` URL for this academy, reusing the existing feature-toggle plumbing
(per-club state, REST-managed). The player profile remains the always-on
anchor and is intentionally not toggleable.

# TalentTrack v4.60.0 — My team: next match and recent results for players (#1989)

A player's **My team** view now shows two pieces of non-sensitive team
information beyond the podium: the team's **next match** (date, opponent,
home/away, location) and a **recent results** form line — the last few match
outcomes framed from the team's perspective (win / draw / loss with the
score). No individual teammate ratings or rankings are exposed. The match
result fields are also surfaced on the activities REST payload.

# TalentTrack v4.60.0 — Academy toggle to switch off the install-on-mobile prompt (#1994)

Configuration → General gains a **Show the install-on-mobile prompt** toggle.
Players and parents get a post-login banner inviting them to install the app
on their phone; an academy admin can now switch that banner off for everyone in
the academy. It ships on, so existing installs are unchanged. The setting is
per-academy (`club_id`-scoped via `tt_config`), capability-gated, and saved
through the config REST endpoint.

# TalentTrack v4.60.0 — Per-report feature toggles for the Reports module (#1995)

The Reports module now exposes a feature toggle per report on the Modules &
features screen — the eight standard reports plus the two wp-admin reports
(10 in all) — mirroring the Export module's per-tile toggles. They ship on, so
a fresh upgrade shows every report. Switching one off hides its launcher tile
(frontend launcher + wp-admin Reports page) and rejects a direct link to that
report. The whole-module Reports toggle still works; when off, the ten
sub-toggles disappear. State is per-academy (`tt_feature_state`, `club_id`).

# TalentTrack v4.59.0 — Backups move to a frontend view, incl. restore + data migration (#1937)

The Backups surface now lives on the frontend at **Configuration → Backups**
(`?tt_view=backups`) instead of bouncing to wp-admin. The full surface ported
across: schedule / retention / destination settings (with Cancel + Save),
the stored-backups list (download, restore, delete), Run now, the destructive
database **restore** behind a typed-confirm "RESTORE" gate, and the complete
`.ttmig` data-migration flow — export, then upload → preview → dry-run →
typed-confirm "IMPORT" commit.

Every mutating action runs through a capability-gated, nonce-protected REST
endpoint (`tt_manage_backups`) on the new `BackupRestController`; the
serialization, restore engine and migration engine stay in the Backup module
services, so the frontend and the wp-admin page give identical answers. The
two destructive writes (restore + import commit) preserve the typed
confirmation, refuse to run while impersonating another user, and are written
to the audit log (`backup.restored` / `migration.imported`). Backup downloads
are returned as a URL rather than a server-relative path, so the list keeps
working unchanged if storage moves off the local filesystem.

The wp-admin Backups tab stays as the power-user fallback and still owns the
Partial restore scope-picker; the frontend list links to it.

# TalentTrack v4.59.0 — First-run Setup moves to a frontend flow (#1938)

The first-run onboarding wizard now lives on the frontend at
**Configuration → Setup** (`?tt_view=setup`) instead of bouncing to
wp-admin. The full flow ported across: a stepper through academy basics →
first team → first admin → dashboard page → done, with skip on the optional
steps, Cancel on every step, and a "Run again" / "Start over" affordance
that re-enters the flow without deleting the teams, staff, or pages you
already created. Progress is saved automatically, so you can stop and resume
from the step you left off on.

New REST endpoints back every step — `POST /onboarding/advance`,
`/onboarding/academy`, `/onboarding/first-team`, `/onboarding/first-admin`,
`/onboarding/dashboard-page`, and `/onboarding/reset` — all gated on
`tt_edit_settings`. The controller is thin: every side effect (team / staff
creation, the Club Admin grant, dashboard-page creation, state advance)
reuses the same `OnboardingHandlers` / `OnboardingState` domain layer the
wp-admin wizard uses, so the two surfaces never drift. The wp-admin Setup
wizard stays as the power-user fallback.

# TalentTrack v4.59.0 — Player-notes access no longer gated by WP role name (#1956)

The player-notes thread adapter no longer denies access based on the
player or parent WP role name. Its decision now rests solely on the
player-notes capability plus the existing team-ownership scope check —
pure players and parents, who hold no player-notes capability, stay
denied exactly as before. (A follow-up, #1982, tracks how dual-role
staff-and-parent accounts resolve that capability.)

Also removed an unused duplicate role-lookup helper from the
authorization service — pure cleanup, no behaviour change; the canonical
role-lookup chokepoint is untouched.

# TalentTrack v4.59.0 — Coach dashboard: batch the per-team podium query (#1959)

The coach "My teams" roster tab now computes every team's top-3 podium in a
single batched pass instead of running three queries per team. For a coach
with N teams this collapses the podium workload from roughly 3N queries to a
constant 3 regardless of team count. Podium output is byte-identical — same
players, same order, same rolling values — as the ranking logic is now shared
between the single-team and batched code paths. Performance only; no
behaviour change.

# TalentTrack v4.59.0 — Player dashboard: the Evaluations tab now hydrates every evaluation's ratings in a single batched query instead of one detail query per row, collapsing a 1+N database pattern into a constant two queries. Pure performance — the rendered table is byte-identical.

Player dashboard: the Evaluations tab now hydrates every evaluation's ratings in a single batched query instead of one detail query per row, collapsing a 1+N database pattern into a constant two queries. Pure performance — the rendered table is byte-identical.

# TalentTrack v4.59.0 — Blueprint editor: faster load via batched roster query (#1962)

The team-blueprint editor's "+ Add → Other team" picker built its
cross-team roster with one player query per sibling team (an N+1). It now
fetches all sibling-team players in a single batched query and groups them
in PHP. The editor also read the formation-template table twice per page
(once for the toolbar dropdown, once for the JS payload); it now fetches
those rows once and reuses them. Output is unchanged — purely fewer
queries on load.

# TalentTrack v4.59.0 — Usage detail: paginate the login and user-timeline event lists (#1963)

The usage-statistics drill-downs for **Logins** and a user's **Timeline** no
longer pull up to 500 rows into memory on every page view. Each list now
fetches a bounded 50-row window with a `COUNT(*)` for the total, and a
prev / next pager (with a "Page X of Y" indicator) lets you walk through the
full history a page at a time. The total event count shown above the table is
still the real total, not just the rows on the current page. Performance only;
no change to which events are recorded or who can see them.

# TalentTrack v4.59.0 — Faster player evaluation and attendance reads (#1964)

Added two database indexes for the hottest player-scoped read paths.
Evaluation lookups now seek on a `(player_id, club_id)` composite instead of
filtering one column as a residual, and a player's attendance history — which
matches both roster rows and linked-guest appearances — can index-merge the
two lookups rather than scanning the attendance table. Pure performance: no
behaviour, query output, or data changes. Final slice of the performance
umbrella (#1649).

# TalentTrack v4.59.0 — Evaluations view: one batched query for the coach player filter (#1971)

The evaluations list page built its player-filter dropdown by running one
player query per coached team — an N+1 that scaled with a coach's team
count. It now loads every active player across the coach's teams in a single
batched query. The rendered options are identical; this is a pure
performance change with no behaviour or output difference. Closes the last
N+1 on the perf umbrella's suspect list (#1649).

# TalentTrack v4.59.0 — Player journey now records the actual evaluation rating (#1974)

The player-journey evaluation event (`evaluation_completed`) read a
non-existent `overall_rating` column from `tt_evaluations`, so the query
errored and every evaluation was recorded on the timeline with an overall
of `0.0`. It now reads the real `rating` column, both for live saves
(`JourneyEventSubscriber`) and for the historical backfill
(`JourneyBackfillService`). Existing zeroed events are corrected the next
time the journey is rebuilt; no schema change.

# TalentTrack v4.59.0 — PDP evidence packet now includes the player's evaluations (#1976)

The PDP evidence packet's evaluations query referenced two columns that
don't exist on `tt_evaluations` — `overall_rating` (the real column is
`rating`) and `status_finalized` (no such column anywhere) — so the query
always errored and `evaluations` came back empty for every player. The
query now reads the real `rating` column and treats any non-archived
evaluation in the window as evidence (`archived_at IS NULL`), matching how
the player journey selects evaluations. No schema change.

# TalentTrack v4.59.0 — Tournament auto-balance is now a per-academy toggle (#1979)

The greedy fair-share auto-planner for tournament matches is now a toggle
on the Modules management page (**Tournament auto-balance**), on by default
so nothing changes on upgrade. Switch it off and the Auto-balance button is
removed from every match card and the `auto-plan` REST route returns 403, so
the toggle can't be bypassed by a direct call; the per-match planner grid and
manual click-to-swap planning are untouched. Closes out the last actionable
item from the #1538 FeatureRegistry tracker.

# TalentTrack v4.58.0 — VCT exercise catalogue — full 80 (#1129)

The VCT exercise catalogue now ships its full 80-exercise spread.
Migration 0181 adds 68 exercises on top of the 0177 scaffold's 12,
reaching the target per-category counts: warmup 10, technical 20,
sided_game 20, conditioning 10, finishing 10, cool_down 10. Each
exercise carries three to four coaching points in canonical English
plus native Dutch, and every intensity band respects the per-age
workload ceilings so no exercise exceeds the envelope for the youngest
age it's offered to. The seed is idempotent and forward-only.

The fr_FR / de_DE / es_ES coaching-point translations, per-exercise
diagrams, and the HoD / pilot-coach methodology review of the picks,
intensity bands, and age ranges are a deliberate follow-up — #1129
stays open until they land.

# TalentTrack v4.58.0 — Spond integration moves to a frontend view (#1936)

The Spond integration now lives on the frontend at **Configuration → Spond
integration** (`?tt_view=spond`) instead of bouncing to wp-admin. The full
surface ported across: per-team sync status with a "Refresh now" button,
the next-automatic-sync time, encrypted account credentials (save / test /
disconnect), and the collapsible API base-URL override. The Spond password
stays encrypted at rest via `CredentialsManager` and is never shown back —
a connected account displays "Connected as <email>" with a blank password
field. New REST endpoints back every action: `POST/DELETE /spond/credentials`,
`POST /spond/test`, `POST /spond/base-url` (gated on `tt_edit_spond_credentials`)
plus the existing `POST /teams/{id}/spond/sync` (gated on `tt_edit_teams`).
The wp-admin page stays as the power-user fallback.

# TalentTrack v4.58.0 — Authorization: give the exercise library a matrix entity (#1944)

The club-global exercise / drill library now has its own `exercises`
authorization-matrix entity, distinct from the `activities` session calendar. The
previously unmapped `tt_manage_exercises` write capability is bridged through
`LegacyCapMapper`, so the library's REST write paths resolve access from the matrix
once it is active instead of from raw WordPress capabilities. The seed grants
read + create + delete to head coaches, assistant coaches, the Head of Development,
and the Academy Admin — exactly reproducing today's raw cap holders, so no persona
gains or loses access. In particular, assistant coaches keep their library write
access (the `tt_coach` role backs both coach personas). A backfill migration adds
the entity to existing installs.

# TalentTrack v4.58.0 — Authorization: give the in-product mailer a matrix entity (#1945)

The in-product email composer now has its own `email_compose` authorization-matrix
action-entity. Sending an email is an act rather than a record — like impersonation
— so the previously unmapped `tt_send_email` capability is bridged through
`LegacyCapMapper` to `email_compose:create_delete`, resolving access from the matrix
once it is active instead of from raw WordPress capabilities. The seed grants
read + create + delete (academy-wide scope) to head coaches, assistant coaches, the
Head of Development, and the Academy Admin — exactly reproducing today's raw cap
holders, so no persona gains or loses access. In particular, assistant coaches keep
the composer (the `tt_coach` role backs both coach personas). A backfill migration
adds the entity to existing installs.

# TalentTrack v4.58.0 — Authorization: bridge report generation to the matrix (#1946)

The report-generation capability `tt_generate_report` (distinct from
`tt_generate_scout_report`) is now resolved from the authorization matrix once it is
active. Generating a report is a create act, so the cap is bridged through
`LegacyCapMapper` to `reports:create_delete`. Because the `reports` matrix entity
previously granted coaches and the Head of Development only read access, a naive
bridge would have revoked generation from them — so access is preserved by adding
the `create_delete` grant instead: head coaches and assistant coaches at team scope,
the Head of Development globally (the Academy Admin already held it). Both coach
personas are seeded so assistant coaches keep generation (the `tt_coach` role backs
both). Team managers, scouts, players and parents keep read-only and gain nothing.
A backfill migration adds the new grants to existing installs.

# TalentTrack v4.57.0 — MFA QR encoder — independent round-trip verification + CI gate (#1393)

Closes out the MFA-enrollment-QR bug. The payload + render fixes shipped earlier
(smaller otpauth URI, no silent truncation, larger render); the remaining risk was
that the hand-rolled QR encoder's v6–v10 paths — the only ones a real otpauth URI
ever exercises — were unverified. A new standalone check
(`scripts/qr-roundtrip-verify.php`, run in CI) encodes a representative corpus with
the production encoder, decodes each result with an independent from-spec ISO/IEC
18004 decoder, and asserts the decoded string equals the input. All versions v6–v10
round-trip cleanly, proving the encoder is correct, and the gate prevents
regressions. No user-facing change.

# TalentTrack v4.57.0 — Translations config moved to the frontend (#1935)

The auto-translation engine configuration is now a frontend view at
`?tt_view=translations` instead of bouncing to wp-admin. The Configuration
"Translations" tile opens it directly. The view covers everything the old
wp-admin tab did — enable toggle, primary/fallback engine, DeepL key and
Google service-account JSON (both kept masked with a "(set)" indicator),
site default language, monthly character cap, notify threshold, the GDPR
sub-processor confirmation, the read-only usage table, and the Clear cache
action. Settings save through a new REST surface
(`POST /translations/settings`, `POST /translations/clear-cache`) gated on
`tt_view_translations` / `tt_edit_translations`; the validation,
keep-on-blank credential handling, and GDPR opt-out cache purge all run in
the domain layer, shared with the wp-admin tab. The wp-admin tab stays as a
power-user fallback.

# TalentTrack v4.57.0 — Authorization: route remaining blueprint + player-potential caps through the matrix (#1939)

The Team-blueprint creation wizard and the blueprint comment thread now
resolve access through the `team_chemistry` matrix entity (via
`TeamChemistryAccess`) instead of the raw `tt_*_team_chemistry`
capabilities, completing the #1922 consolidation so the whole blueprint
feature answers from one source. The PlayerStatus "set potential band"
act-cap (`tt_set_player_potential`) is now bridged to the
`player_potential:change` matrix entity, closing a frontend/REST
divergence where its data-cap sibling was already matrix-aware. All three
re-points are access-preserving — the personas who could act before still
can. The behaviour-rating act-cap (`tt_rate_player_behaviour`) was left on
native capability evaluation and flagged on the issue: bridging it would
have revoked assistant-coach access, an effective-access change that needs
a product decision rather than a mechanical bridge.

# TalentTrack v4.57.0 — Authorization: bridge six act-caps to the matrix + two approved access changes (#1941)

Six legacy `tt_*` act-capabilities now resolve through the authorization
matrix instead of native WordPress capabilities, so the frontend renders
and REST endpoints that gate on each cap can no longer answer differently:
`tt_manage_teams`, `tt_manage_staff_development`, `tt_manage_modules`,
`tt_view_scout_assignments`, `tt_manage_invitations`, and
`tt_rate_player_behaviour`. Four bridges are access-preserving. Two carry
an approved effective-access change: the Head of Development now sees the
all-teams exports picker (`tt_manage_teams` → `team:create_delete`, the
HoD oversees the whole academy), and assistant coaches can no longer author
behaviour ratings (`tt_rate_player_behaviour` → `player_behaviour_ratings:change`;
the matrix treats behaviour-rating as a development judgment, not an
operational one). The stale behaviour-rating grant on the assistant-coach
role is revoked on upgrade so installs whose matrix is still dormant
converge on the same answer. Invitation management stays admin-only
(`tt_manage_invitations` bridges to the admin-level `settings` entity, not
the broad `invitations` entity that coaches and parents hold to send invites).

# TalentTrack v4.57.0 — All-teams lens now resolves from the authorization matrix (#1942)

Replaced the phantom `tt_view_all_teams` / `tt_edit_settings` capability
idiom — which gated the academy-wide ("all teams") lens across reports,
analytics, attendance, the cohort board, the team planner, match-execution
surfaces and the matches-needing-review widget — with a single
`AllTeamsScope` helper that asks the authorization matrix for global-scope
read on each surface's own entity (reports surfaces check `reports`,
analytics / attendance check `activities`, the evaluations audit override
checks `evaluations`). Frontend renders and REST permission callbacks now
resolve the all-teams question from one place, so they can no longer drift.
Head of Development and Academy Admin keep the club-wide view; scouts gain
the club-wide reports and analytics lens where the matrix already grants
them global read.

# TalentTrack v4.57.0 — Authorization: give the Tournaments planner a matrix entity (#1943)

The admin-only Tournament planner now has a `tournaments` authorization-matrix
entity. The legacy `tt_view_tournaments` / `tt_edit_tournaments` capabilities are
bridged through `LegacyCapMapper`, so the planner's frontend, REST, and add-match
surfaces resolve access from the matrix once it is active instead of from raw
WordPress capabilities. The seed grants only the Academy Admin persona full access
(read + edit + create + delete), exactly reproducing today's admin-only v1 design —
no persona gains or loses access, and WP administrators keep their override. A
backfill migration adds the entity to existing installs.

# TalentTrack v4.56.0 — Six new per-academy feature toggles (#1538)

The Modules page gains six more sub-feature switches, so academies can turn off
heavy, cost- or privacy-sensitive behaviour without disabling a whole module. All
default on, so nothing changes until you toggle one:

- **SMS channel** (Comms) — offer SMS as a messaging channel.
- **Scheduled messaging** (Comms) — the daily reminder cron.
- **Medical events on timeline** (Journey) — show medical events to permitted staff; an academy-wide privacy brake when off.
- **PDP calendar integration** (PDP) — write scheduled conversations to the calendar feed.
- **Dashboard layout editor** (Persona Dashboard) — the drag-and-drop layout builder.
- **Match prep PDF export** (Match Prep) — the A4 print / export-to-PDF actions.

(The seventh candidate, the Team planner calendar toggle, already shipped separately.)

# TalentTrack v4.55.0 — Archive lifecycle for activities (#1555)

Activities now follow the same archive lifecycle as players, teams, evaluations
and goals. Deleting an activity soft-archives it instead of removing the row, so
its attendance and history are preserved. The activities list gains an
**Active · Archived · All** status control: the **Archived** view lists archived
activities with a **Restore** button and, for admins, a **Delete permanently**
button. Permanent deletion is gated behind the *edit settings* capability and is
blocked while the activity still has attached records, so nothing is erased by
accident. New REST routes back the flow: `POST /activities/{id}/restore` and
`DELETE /activities/{id}/permanent`.

# TalentTrack v4.54.2 — Team chemistry access now follows the authorization matrix (#1922)

Team chemistry and Team blueprint access is now decided by the
authorization matrix instead of hardcoded role capabilities, with a single
shared decision (`TeamChemistryAccess`) behind both the rendered screens
and the REST API so the two can no longer disagree.

As a result, two roles that previously had access no longer do:
**assistant coaches and read-only observers no longer have access to team
chemistry** (the chemistry board and the team blueprint screens). This
matches the academy roles the matrix already grants the feature to — head
coaches, team managers, scouts, head of development, and academy admins
keep their access unchanged. The stale read capability is removed from the
read-only-observer role automatically on upgrade.

# TalentTrack v4.54.1 — Audit log: Configuration tile now opens the frontend view (#1918)

The **Audit log** tile in Configuration → System no longer bounces into
wp-admin. It now opens the read-only frontend Audit log view
(`?tt_view=audit-log`) — a paginated, filterable browser over the academy's
`tt_audit_log` trail (who changed what, when), with an All-entries tab and a
Failed-logins aggregate. The tile is cap-gated to `tt_view_audit_log`, so it
only appears for holders who can read the log. The wp-admin tab
(`?page=tt-config&tab=audit`) stays as a power-user fallback.

# TalentTrack v4.54.1 — PDP visibility: unify frontend and REST behind one matrix-aware check (#1923)

PDP-file access is now decided in a single place (`PdpAccess`), so the
rendered files tab and every REST surface answer the same question. This
closes the frontend/REST divergence (#1758) where a Head of Development who
does not personally coach a player was denied the files tab even though the
API let them through. The PDP REST endpoints that previously authorised on
"is the user logged in?" now check capabilities via the authorization
matrix, and the verdict sign-off attribution no longer relies on a role-name
string compare. Effective access is unchanged for every persona — this
removes drift and a legacy auth smell without widening or narrowing anyone.

# TalentTrack v4.54.0 — Chemistry rework — admin settings (#1017)

Phase 5 of the chemistry rework (epic #1017): a **Chemistry settings** surface (Configuration → tile) where a head of development or academy admin tunes the reworked engine — the **enable toggle** (`chemistry_engine_v2`, off by default), the **five component weights** (normalised to total 100), and the **Position Relationship Matrix** (how strongly each pair of lines interacts, 0–1). All persist via the Phase-1 contract (`tt_config` + the matrix table). Matrix-gated on `team_chemistry` change at global scope; a Save-only settings sub-form (§6 exemption); mobile-first; nl_NL strings.

# TalentTrack v4.54.0 — Chemistry rework — Unit / Lineup / Team aggregators (#1017)

Phase 4 of the chemistry rework (epic #1017): rolls the reworked pair scores up into the spec's higher-order numbers. `LineupChemistryAggregator` scores every filled-slot pair (all-pairs), weights them by the configurable Position Relationship Matrix, and returns **Lineup chemistry** (matrix-weighted average) + **Unit chemistry** per gk/def/mid/att. `TeamChemistryAggregator` writes a lineup-chemistry snapshot per blueprint save and averages recent snapshots into **Team chemistry** over a window (last 5 / 10 / season). The reworked numbers surface on the blueprint response as `chemistry_v2` (lineup + unit + windowed team + per-pair breakdown) **behind the `chemistry_engine_v2` toggle (default off)** — the legacy `blueprint_chemistry` stays the live signal until an academy opts in once attributes are populated, and any computation error degrades silently to the old behaviour.

# TalentTrack v4.54.0 — Chemistry attributes — player data entry (#1017)

Phase 7 of the chemistry rework (epic #1017, child #1913) — the load-bearing data dependency. Adds a **Chemistry attributes** editor reachable from a player's profile (⋯ menu): the attribute catalogue grouped (physical / technical / tactical / mental / behaviour / development), one 0–100 input per attribute pre-filled with the current value, saved in one nonce-protected POST. Staff who can record evaluations can edit them, matrix-scoped via `canEvaluatePlayer`. With this the reworked engine has real data to score against; un-rated attributes simply don't count (rather than scoring zero). Mobile-first, Save + Cancel, EN + nl docs.

# TalentTrack v4.54.0 — Chemistry rework — explainability panel (#1017)

Phase 6 of the chemistry rework (epic #1017) — and the last phase. Adds a **Chemistry insight** panel to the team-chemistry board (behind the `chemistry_engine_v2` toggle): the reworked Lineup + per-unit (gk/def/mid/att) + windowed Team scores, the **strongest** and **weakest partnerships** in the lineup (colour-coded by category), and plain-language **recommendations** — telling a coach which pairing to strengthen and on which component, or which players still need their attributes rated. `ChemistryExplainer` derives the strongest/weakest/recommendations from the lineup aggregate (each pair now carries its weakest component). Degrades silently if the engine throws or there isn't enough data yet. This completes the rework: define attributes → engine scores → explained on the board.

# TalentTrack v4.54.0 — Chemistry rework — pair engine orchestrator (#1017)

Phase 3 of the chemistry rework (epic #1017): the `PairChemistryEngine` that combines the five Phase-2 sub-engines into a single 0–100 pair-chemistry score using the configurable component weights, plus the `ChemistryProfileLoader` that feeds them real data — each player's attributes + age + footedness, and the pair's shared-history context (shared completed activities/games + team-tenure overlap), pre-loaded once per id set. A `PairResult` carries the score, its spec category (exceptional → poor), the per-component breakdown, and the human reasons. Exposed read-only at `GET /chemistry/pair/{a}/{b}` (gated on viewing both players) so the new engine can be tested on real pairs. It does **not** displace `BlueprintChemistryEngine` yet — the live team surface switches over only once Phase 7 has populated attributes, in Phase 4.

# TalentTrack v4.54.0 — VCT exercise catalogue — starter seed scaffold (#1129)

Ships the idempotent seed-migration scaffold for the VCT exercise catalogue
plus a small representative draft set — 12 exercises, two per category across
warmup, technical, sided_game, conditioning, finishing and cool_down — each
with three coaching points authored in all five shipped locales (canonical
English, Dutch, French, German and Spanish). Intensity bands and age ranges
respect the seeded VCT age profiles. The migration existence-checks
`(club_id, code)` before every insert, so re-running on an already-seeded club
is a no-op, and a later catalogue correction can raise `seed_revision` without
trampling operator edits. This is a clearly-marked draft subset, not the full
80-exercise catalogue: the complete catalogue, per-exercise diagrams and the
pilot-coach methodology review remain pending and are tracked on #1129.

# TalentTrack v4.54.0 — Evaluation-window coverage report for Heads of Development (#1380)

A new HoD analytics surface answers "which players have NOT been
evaluated this window, and which coach owns the gap?". Define the
season's evaluation windows (name + start/end dates) in a settings-style
editor, then read a coverage matrix: players grouped by team across each
window, every cell marked evaluated (with the evaluating coach on hover)
or a clear gap. A header strip tallies gaps per coach, per-coach chips
open the evaluations list filtered to that coach, and an
attendance-recording compliance strip shows, per team, the share of
completed activities in each window that have any attendance recorded —
so a coach who never records attendance looks different from a team with
no activity. Windows are stored in tt_config (no new entity, no
reminders) and the whole report is reachable through the REST API at
`/talenttrack/v1/eval-coverage`.

# TalentTrack v4.54.0 — Season rollover — bulk cohort promotion (#1381)

A new end-of-season tool moves whole squads up an age group in one pass and
writes a dated journey event for every affected player. The flow has three
steps — map each source team to a target team, choose which players move (and
whether each is promoted, released or graduated), then review the exact
changes before confirming.

Safety is built in: a full backup runs automatically before any record is
touched, and if the backup fails the rollover is aborted with nothing
changed. The confirm step posts through admin-post.php and redirects back
(post/redirect/get), so refreshing the result page cannot re-run the move.

Released players are deliberately **left active** — they get a dated
`released` journey event but are not archived, so the data-retention clock
never starts here. There is no season-entity creation or assignment in this
version; the rollover is purely a team move plus a journey event.

This is a bulk operation on existing records, so per the wizard-first rule it
takes wizard **exemption (b)** (bulk operations) and ships as a dedicated
multi-step view rather than a record-creation wizard. The same logic is
reachable over REST at `POST /talenttrack/v1/season-rollover/plan` (dry-run)
and `POST /talenttrack/v1/season-rollover/execute`.

# TalentTrack v4.54.0 — Cohort decision board (read-only) (#1383)

A new **Cohort decision board** under Analytics gives the Head of Development
one read-only screen for end-of-season decisions. Pick a team or age group and
see one row per active player with their status, rolling rating and trend arrow,
season attendance %, conducted-PDP-talk count, and current PDP verdict (or
"Pending"), each linking straight into the player's PDP file. Columns are
sortable (server-side, works without JavaScript) and the board exports to CSV.
Verdicts stay set in the PDP file — this board never edits them. Cap-gated on
the analytics capability; coaches see only their own teams. Backed by a new
`GET /cohort-board` REST endpoint sharing the same domain service.

# TalentTrack v4.54.0 — Configuration: Feature toggles no longer bounce into wp-admin (#1533)

The Configuration page's **Feature toggles** tile no longer sends you into wp-admin — per-module enable/disable already lives on the frontend **Modules** view (`?tt_view=modules`), which is contributed into the Configuration grid. The redundant wp-admin tile is retired, so toggling modules stays on the modern frontend surface. First port of the "wp-admin Configuration surfaces → frontend" tracker (#1533); Translations, Backups, Audit log, Setup wizard and Spond are filed as follow-up children.

# TalentTrack v4.54.0 — Team planner is now a toggleable feature (#1538)

The week-by-week **Team planner** calendar is now a `FeatureRegistry` feature an academy admin can switch off from the Modules page — for academies that work activity-by-activity and don't want the forward-looking planner. It ships **on by default**, so nothing changes on upgrade; turning it off hides the Team planner tile and gates its `?tt_view=team-planner` route (the Activities log, the backward-looking surface, stays available). First catalogued entry from the FeatureRegistry candidate tracker (#1538), wired with the standard pattern: a `FeatureRegistry::catalog()` entry plus the tile's `feature` key (route gating is automatic via the feature's `view_slugs`).

# TalentTrack v4.54.0 — Evaluation rating: find players faster on a big roster (#1642)

The **Rate players** step of the new-evaluation wizard gains a **search box** (filter the roster by name as you type) and an **Only not-yet-rated** toggle (hide everyone already rated or skipped, so you see who's left at a glance). The toggle reads the same live per-player status as the existing *"N of M players rated"* progress line, so a player drops out of the not-yet-rated view the moment you rate them. Both are instant on-device filters and never change what gets submitted — directly addressing the "players are hard to find / which still need rating" pain in #1642. (The rating control itself was already rebuilt as a 5-star input in #1641, and behaviour is already an optional collapsed step, so this slice focuses on findability; collapsing the activity-picker + attendance steps stays a separate, riskier change since attendance writes real rows.)

# TalentTrack v4.54.0 — Trial pages overhaul — redesigned case page, warmer Dutch letters, friendlier configuration (#1646)

The trial case page has been rebuilt to match the player and team profiles: a paper hero anchored by the player's photo and name, status / decision / track pills, a key-facts strip, and the content laid out in cards under tab navigation (Overview · Execution · Staff inputs, plus Decision · Letter · Parent meeting for the head of development). The old anchor-strip layout and its inline styling are gone; all styling now lives in the enqueued, mobile-first stylesheet. The post-decision summary now shows the decision's readable label instead of the raw internal code.

The shipped Dutch parent letters (admittance, decline-final, decline-with-encouragement) have been rewritten in a warm, informal "je/jullie" club voice, and a set of broken pronoun placeholders that previously printed literally in both the English and Dutch letters has been removed.

The trial tracks and letter-template configuration screens now open with plain-language guidance, label each letter by what it's for instead of an internal key, and carry per-field hints. Missing Dutch translations across the trial surfaces have been filled in so the pages read fully in Dutch.

# TalentTrack v4.54.0 — Match-day live surface: vertical positional pitch + chronological event log (#1713)

The live match-execution screen now opens with a vertical pitch showing
the first-half starting eleven laid out by position, sourced from the
match-prep line-up and the bound formation shape. Below it a new "Live
progress" feed merges the goals and substitutions already logged during
the match into one time-ordered list — each row carries the half +
minute, a type chip (icon and text, not colour alone), and a running
score chip on goals. Both surfaces are also exposed as read endpoints
(`GET /match-execution/{activity_id}/event-feed` and `/pitch-lineup`)
behind the existing `tt_edit_activities` capability.

Scope notes: the Teamchemie badge from the mockup is deferred — no
chemistry metric exists yet and the algorithm is under review (#1017).
Red and yellow cards are not modelled, so the feed is goals +
substitutions only; no schema change was added.

# TalentTrack v4.54.0 — Direct entry of per-player match minutes on match completion (#1726)

You can now log per-player match minutes without running the live match
surface. When a match-type activity is marked Completed, the attendance table
gains Starter and Minutes columns, and a Match length field appears above it
(prefilled from the match prep's two halves, or 70 minutes, and editable). The
form derives a "Subs: N on · N off" summary from the starter flags and minutes.
The minutes are written to the same place the live flow uses, so the minutes
report and the match-execution view pick them up — including for past matches
that were never live-tracked.

# TalentTrack v4.54.0 — Central per-age-category default match minutes (#1727)

You can now set a default match length per age category — minutes per half (N),
with the full match shown as 2 x N — under Configuration -> Match minutes. One
row per age group, blank inherits a global fallback of 35 minutes per half.
That central setting is now the single source of truth for match length:
new match prep and the match-completion minutes entry both prefill from the
team's age category instead of the old hardcoded 35-per-half / 70 default
(still editable per match). Accurate minutes feed each player's load and
development picture.

# TalentTrack v4.54.0 — Bulk-invite a team's players (#1770)

The **Player accounts** view gains a **Bulk invite a team** action: pick a team and generate a player invitation for every player on it who doesn't already have an account or a pending invite, in one click. The result is summarised (new invites vs. already-pending), and the daily invite limit is handled gracefully — if a large team hits the cap, the summary reports how many went out so the rest can be invited the next day. This is the deferred bulk-provisioning piece of the player↔account mapping epic; single link/unlink and per-player invites are unchanged.

# TalentTrack v4.54.0 — Dashboard tile badges for pending actions (#1846)

Dashboard navigation tiles can now carry a small **count badge** (top-right bubble) for pending actions, via a generic `badge_callback` on the tile. The **My tasks** tile uses it to show your open-task count at a glance — replacing the old `My tasks (3)` label suffix with a proper badge, so the tile label stays clean and the count reads instantly. Phase 6 of the player + parent development hub epic.

# TalentTrack v4.54.0 — Admin can create a new parent/player account directly (#1847)

The **Parent accounts** view gains a *Create a new parent account* panel: an academy admin provisions a brand-new WP account (name + email), links it to the chosen player, and the person receives a standard **"set your password"** email — the admin never sees or sets a password. For the rare no-usable-email case, a *No usable email* toggle sets a temporary password instead (share it securely). Every direct-create is audit-logged. The same `directCreate` path exists on both `ParentAccountService` and `PlayerAccountService` and is reachable over REST (`POST /players/{id}/parents` / `…/account` with `create:true`), so a future front end gets the same behaviour (§4). Inviting remains the low-friction default; direct-create is the admin-convenience path. Follow-up to the Accounts & access epic (#1815, #1770). The player-accounts-view create UI is a fast-follow — its service + REST ship here.

# TalentTrack v4.54.0 — Parents can open their child's own development views (#1849)

A parent can now open their child's **own** development surfaces — development plan, goals, card, evaluations, activities, journey — by tapping the child in the parent dashboard's child-switcher. These are the **rich player views** (the same `FrontendMy*` surfaces the player sees, e.g. the full PDP conversation cycle), not the thinner staff-profile tabs parents were previously bounced to. Access is scoped (a parent only reaches their own children, via the same per-player gate as #1725), and the development-plan view greets a parent with the child's name ("<Child>'s development plan"). Foundation for the unified development hub (epic #1846).

# TalentTrack v4.54.0 — Player + parent development home: one anchor for the My-X views (#1850)

Players (and parents, scoped to their child) get a new **My development** home — a single, scannable, mobile-first page that composes the existing rich My-X surfaces into one overview-led anchor. It opens with the player hero, then a **Today** band driven by the PDP cycle state (prepare for an upcoming talk, review a just-held talk, or the next-talk date — degrading gracefully when there's no PDP data), followed by **Your focus** (top goals), **How you're doing** (rating + momentum), **Coming up** (next activities) and **Your journey** (latest milestone). Each block links through to its deep view, carrying a back hint so the deep view shows a "← Back to …" pill. A prominent **My development** tile leads the Me group; the seven existing deep-view tiles stay as shortcuts. Parents open "&lt;Child&gt;'s development", read-only. Phase 2 of the development-hub epic (#1846).

# TalentTrack v4.54.0 — State-aware My PDP: lead with goals, flip to self-review in the window (#1851)

*My PDP* now opens with a short lead block that orients the player on **what to do now**, derived from where they are in the development-talk cycle. In a **working period** it leads with the player's focus goals and the next-talk date; in the **review window** it surfaces "prepare for your talk" and promotes the upcoming conversation so the self-reflection editor and agenda are front-and-centre; **after a talk** it points at the notes, agreed actions and acknowledgement to complete. The self-review stays optional and is never a gate — every conversation card, the reflection editor and the ack flow are unchanged, only re-ordered and highlighted by state. Parents see the same state surface for their child, read-only. State is derived by a small reusable `PdpCycleState` service from the already-seeded conversations and planning windows (migration 0043); no schedule or window data changes. Phase 3 of the development-hub epic (#1846).

# TalentTrack v4.54.0 — Self-review nudge when a PDP talk's window opens (#1852)

When a development talk's planning window opens, the player now gets a **"Prepare for your development talk"** task in *My tasks / Today's work*, due on the talk date, that opens *My PDP* at the self-reflection. It's a nudge, not a gate: saving the reflection completes it, conducting the talk auto-resolves it with no penalty even if it was skipped, and nothing is ever blocked if it's ignored. The sweep that creates these runs on the workflow engine's own scheduler (no ad-hoc cron) and is idempotent — exactly one task per conversation. On the coach side, the PDP conversation list gains a **Self-review: Done / Not yet** column per upcoming talk — visibility only, never a gate on conducting or signing off. Phase 4 of the development-hub epic (#1846).

# TalentTrack v4.54.0 — Link goals to a PDP conversation — the "combine" (#1853)

Goals and the PDP cycle are now genuinely linked, not just co-located. On the development-talk form, a coach ticks **Goals discussed in this talk** from the player's active goals; on *My PDP*, each conversation card shows a **Goals discussed** list so the player's self-review reflects on the goals that were actually covered. Built on the existing `tt_goal_links` table (a new `pdp_conversation` link type — no schema migration; the methodology-link sync is scoped so it can't clobber the conversation links), with repository methods + REST handling on the conversation PATCH (coach-only, and the goal set is validated to belong to the player). Phase 5 of the development-hub epic (#1846); supersedes the POP linkage in #1717. Turning an agreed action into a brand-new goal is a planned follow-up — this slice is the read/link connective tissue.

# TalentTrack v4.54.0 — Measurements & Testing — staff result entry (#1856)

Adds the staff-facing **Record measurements** surface for the Measurements module (epic #1854). A coach picks a team, a test, and a date, then enters one value per player and saves the whole roster in one shot — saving creates a completed testing session and one result per filled-in player against it (blank rows are skipped). The input adapts to the test's value type (numeric/scale → a numeric keypad with the unit shown; pass/fail → a dropdown). Matrix-gated on `measurements` change (a coach only reaches their own teams; head-of-development / admin see all); bulk entry is a wizard exemption under §3(b). Mobile-first, Save + Cancel, server-rendered (nonce-protected POST, no extra client JS). The "+ New test" wizard for creating the tests themselves follows.

# TalentTrack v4.54.0 — Measurements & Testing — foundation (#1856)

Stands up the data foundation for the new **Measurements & Testing** module (epic #1854): an academy can model tests (e.g. height, sprint, endurance) in editable categories with proper units of measure, a recurrence, and per-age-group target bands; schedule team testing sessions; and record one value per player. This slice ships the schema (migration 0175 — four tables, each with the `club_id` + `uuid` tenancy scaffold and an archive lifecycle), the admin-editable `measurement_category` and `measurement_unit` lookups (with Dutch labels), the repositories, and the authorization + referential-integrity-delete wiring. Visibility is matrix-scoped: a player sees only their own results, a parent only their child's, staff their team's, and head-of-development / academy admin everything. The setup wizard, result-entry screens, and the per-player trend view land in the following slices.

# TalentTrack v4.54.0 — Measurements & Testing — REST contract (#1856)

Adds the SaaS-ready REST contract for the Measurements module (epic #1854) at `talenttrack/v1`: a player's measurement profile (`GET /players/{id}/measurements` — categories → tests → latest value + green/amber/red flag + trend), result recording + editing + soft-archive, one test's trend series, the test catalogue (`/measurements/definitions`), and team testing sessions. Every endpoint is matrix-gated — player reads resolve through `canViewPlayer` (a player sees only their own, a parent only their child's, staff their team's, HoD/admin everything), writes through `canEvaluatePlayer`, and the catalogue/sessions through the `measurement_definitions` / `measurement_sessions` matrix entities — never a role-string compare. The grouping + flag logic lives in a shared `PlayerMeasurementProfile` service so the upcoming frontend renders exactly what the API returns. The frontend Metingen view, the result-entry screen, and the "+ New test" wizard follow in the next slice.

# TalentTrack v4.54.0 — Measurements & Testing — player Metingen view (#1856)

Adds the player-facing **Metingen** surface for the Measurements module (epic #1854). A player (and a parent of that player) gets a "My measurements" tile that opens a view of their tests grouped by category — each test showing its latest value, a green/amber/red flag against the age-group target, a sparkline of the trend, and the recurrence. The view is server-rendered straight from the shared `PlayerMeasurementProfile` service, so it shows exactly what the REST API returns; the sparkline is inline SVG (no extra client JS). Visibility is matrix-scoped: a player sees only their own, a parent only their child's; staff reach a player's measurements from the player profile, so the self-dashboard tile is hidden for them. Mobile-first, two nav affordances. The result-entry screen and the "+ New test" wizard follow in the next slice.

# TalentTrack v4.54.0 — Measurements & Testing — "+ New test" wizard (#1856)

Closes the Measurements epic (#1854) with the wizard-first create flow for a test definition (CLAUDE.md §3). A head of development or academy admin runs **+ New test**: pick a category and name and value type, choose a unit (from the unit list or a custom one) plus the direction and recurrence, and optionally set per-age-group green/amber target bands — then finish to create the test and its targets in one go. Registered in `WizardRegistry` (slug `measurement`, reachable from the **Record measurements** screen's "+ New test" button and `?tt_view=wizard&tt_wizard=measurement`); the standard wizard chrome supplies the Previous/Next/Cancel + progress rail. With this, the full loop is in the UI: define a test → record results for a team → players and parents see their trend.

# TalentTrack v4.54.0 — Data Browser — read-only frontend table browser (#1859)

A new **Data Browser** tile (under Administration, for administrators and Club Admins only) lets you browse the raw data behind TalentTrack, read-only. Each `tt_*` table is listed with a friendly label, description and row count; opening one shows semantic column headers with explanations, the actual stored rows (paginated and searchable), the tables it connects to, and clickable foreign keys that jump to the referenced row. Core player-centric tables get hand-written labels; the rest fall back to humanised names. Tables holding sensitive data about minors (medical, safeguarding, family) are badged, and opening one is recorded in the audit log. The same data is exposed read-only over the REST API at `/talenttrack/v1/data-browser`.

# TalentTrack v4.54.0 — Goal/season intake print no longer leaks archived evaluations (#1860)

The goal/season intake printout pulled a player's evaluation data — the
average rating and the strong/weak category breakdown — without excluding
archived evaluations, so the print could show ratings the player's own
evaluation page hides. All three intake-print evaluation reads now apply the
same `archived_at IS NULL` filter the evaluation page uses, so the printout
matches what's on screen.

# TalentTrack v4.54.0 — Match "type of match" now shows translated labels on the activity form (#1861)

The game-subtype dropdown (Friendly / League / Cup) on the frontend activity
manage form rendered the stored English labels even on a Dutch install,
because it read the lookup names without their translations. It now pulls the
full lookup rows and renders the translated label — matching the admin form
and the activity wizard. The stored value is unchanged.

# TalentTrack v4.54.0 — Cancelled activities hidden from the list by default (#1862)

Cancelled activities no longer clutter the activities list — they're hidden by
default so the schedule reads as what's actually happening. A new "Show
cancelled" filter brings them back when you need the audit trail; shown that
way they're dimmed and struck through with a Cancelled pill, in whichever date
bucket they fall. The default-hide is applied in the query (it carries through
the URL), so a shared link reflects the same view.

# TalentTrack v4.54.0 — Match end time defaults to kick-off + 105 minutes (#1863)

When you set the kick-off time on a match activity, the end time is now
prefilled to 105 minutes later (90' play + 15' half-time). It only applies to
match activities, fills in just once, never overwrites an end time you typed
yourself, and stays fully editable. Works on both the activity wizard and the
flat activity form.

# TalentTrack v4.54.0 — Match execution shows each player's logged minutes (#1864)

The match-execution screen now shows a per-player minutes chip once a match
has been ended, reading the same persisted minutes the minutes report uses, so
the two always agree. Before the match is ended there are no minutes yet and no
chip is shown. Tracked players and bench players who came on both display their
logged minutes.

# TalentTrack v4.54.0 — PDP planning is now team-scoped for coaches (#1865)

The PDP planning matrix used to show every team in the academy to anyone with
the PDP edit capability, so a team-scoped coach saw the same all-teams grid as
a head of development. It's now matrix-scoped: a HoD or administrator still sees
every team, while a coach sees only the teams they're assigned to — in the
matrix and when drilling into a block. Opening another team's block via a
hand-edited URL is refused.

# TalentTrack v4.54.0 — Branded password reset flow (#1866)

Resetting a forgotten password now stays on the academy's own branded screens
instead of dropping you onto the plain WordPress reset pages. "Lost your
password?" opens a branded request form; the emailed link lands on a branded
"Choose a new password" screen; and you're returned to the sign-in card with a
confirmation. The request step always shows the same "if that account exists,
we've sent a link" message so it can't be used to discover which emails have
accounts, and the link generation, expiry, and password storage stay on
WordPress core's secure mechanics.

# TalentTrack v4.54.0 — Players can choose which sections their parent sees (#1867)

A player (child) can now control **which sections of their record a linked parent can see** — per section, default visible. In **My settings**, a player with a linked parent gets a "What your parent can see" card with toggles for **Evaluations**, **Goals**, **Journey**, **Measurements** and **Development plan**; everything is shared by default, and turning a section off hides it from the parent across both the rendered views and the REST reads. The parent sees a calm "kept private" note rather than an error or a broken view, and the development-home previews respect the same choice. The player always sees their own record, coaches and the academy are unaffected, and safeguarding/medical stays cap-gated and outside player control. Enforced in the authorization layer (`AuthorizationService::parentCanViewSection`), not in views (§4); new `tt_player_parent_visibility` table carries `club_id`. Part of the development-hub epic (#1846) and the player/parent dignity work (CLAUDE.md §1).

# TalentTrack v4.54.0 — Match-prep print/PDF now mirrors the on-screen view (#1873)

The match-prep printout and PDF export now include everything below the
toolbar on the match-prep screen, not a reduced subset: the two formation
pitches, the **Selection · minutes** table (per-half minutes + totals), the
benches, the match goals, **Doen per speler**, and **Roles & set pieces**. The
minutes table and the roles panel were the two pieces previously missing, so a
coach printing for the dugout gets the document they laid out. The summary
tiles and the toolbar itself stay out of the printout.

# TalentTrack v4.54.0 — Team season-intake print: clean one-page-per-sheet pagination (#1875)

Printing the season-intake for a whole team produced sheets that cascaded and
overlapped — each player's pages drifted onto trailing blank pages instead of
breaking cleanly. The print stylesheet pinned each sheet to a `min-height` of a
full A4, which rounds past the printable height on some renderers and bleeds
every sheet onto the next page. Each sheet now uses an exact A4 box with
clipped overflow and an explicit page break, so a batch of N players prints
exactly 3N clean pages.

# TalentTrack v4.54.0 — Measurements insights: testing coverage — who's due / overdue (#1882)

Staff get a new **Testing coverage** screen (Performance group): pick a team and see, for every test that has a recurrence, how many of the squad are up to date versus the gap — with the players who are **overdue**, **due soon**, or have **never** been tested named so a coach can plan a session. Player-centric: it starts from the roster and surfaces exactly who still needs testing this cycle; *ad hoc* tests don't count toward coverage. Built on the #1856 foundation — a pure `MeasurementScheduleService` (frequency → due/overdue) + a `MeasurementCoverageService` composing the existing definitions/results repositories, exposed over REST (`GET /teams/{id}/measurement-coverage`, team/global matrix-scoped) so logic stays out of the view (§4). Coach sees their own teams; HoD/admin see every team. First slice of the Measurements insights work (#1854); per-definition distribution + growth/maturation curves and overdue reminders are the next increment.

# TalentTrack v4.54.0 — Measurements on the player profile (#1892)

A player's measurements now appear in context on their profile: opening a player (`?tt_view=players&id=N`) shows a **Measurements** tab beside Evaluations — the same tests-by-category view with latest value, green/amber/red flag and trend sparkline, with a badge counting how many tests the player has results for. The tab reuses the shared `PlayerMeasurementProfile` service so it renders identically to the standalone Metingen view, and is matrix-scoped (hidden for personas without `measurements` read).

# TalentTrack v4.54.0 — Evaluation wizard: one-tap "Everyone was here" on the attendance step (#1899)

The attendance step of the new-evaluation wizard gains a prominent **"Everyone was here - continue"** button at the top: for the common case where the whole squad was present, it marks the roster present and advances straight to rating in a single tap, instead of the coach scanning the roster and hitting Next. Mark any absences on the cards first if needed, then use it (or the normal Next). Attendance is still written exactly as before (real `tt_attendance` rows, present-by-default), and the standalone mark-attendance entry point is unchanged — this only adds a faster path through the existing screen. Follow-up to the evaluation-capture UX work (#1642); the deeper picker/attendance step-merge was deliberately scoped to this low-risk shortcut.

# TalentTrack v4.54.0 — My activities: 2026 chrome restyle (#1901)

The player/parent **My activities** surface now matches the 2026 look of the other Tier-2 surfaces. The **activity detail** gets the white-card chrome (card wrapper, branded meta chips + status badges, tokenised spacing) and the list's **mobile cards** are elevated to the same white-card style — scoped to this view via a `.tt-myact-list` wrapper, so the shared list component is untouched everywhere else. Presentation only; no data or behaviour change. Completes the Tier-2 visual-parity track of the go-live-readiness epic (#1723 / #1695) — all six player/parent surfaces are now on the 2026 chrome.

# TalentTrack v4.54.0 — Invitations are now emailed automatically (#1902)

When an admin creates a parent/player invitation **with an email address**, the accept link is now **emailed to the invitee automatically** — previously invitations were link-only (copy / WhatsApp share), so an admin had to hand-carry every link. The email goes out through the existing Comms module (audit-logged, in the invitee's locale, with a "set your password" call to action and the link's expiry). It's transactional — it bypasses opt-out / quiet-hours / rate-limits so an invitee is never withheld their invite — and silently no-ops when the invite has no usable email (the copy-link / WhatsApp share path still stands). New `InvitationEmailTemplate` (registered in `CommsModule`) + an `InvitationEmailNotifier` that listens on `tt_invitation_created` and dispatches via `tt_comms_dispatch`. Closes the biggest self-serve onboarding gap for the player/parent go-live (epic #1723).

# TalentTrack v4.54.0 — First-login welcome card on the development home (#1903)

A new player (or parent) opening the **development home** for the first time now sees a short, friendly **welcome card** at the top — persona-aware ("this is your development home" for a player, "this is &lt;Child&gt;'s development home — you choose what they share with you" for a parent). It's informational only; tap **Got it** to dismiss it and it won't come back (stored per viewer in user meta — no schema change). Closes the "new player/parent lands on a cold dashboard" gap from the go-live-readiness epic (#1723).

# TalentTrack v4.54.0 — Invitation accept-form polish: recovery-email hint + silent-link relationship (#1904)

Two onboarding-correctness tweaks on the invitation accept flow. The **recovery email** field now carries a short note that it's pre-filled from the invitation and only used for password recovery (and can be changed), so an invitee doesn't enter a wrong or shared address by mistake. And the **silent-link** path (a logged-in parent whose email matches) now asks for the **relationship** (parent / mother / father / guardian) just like the full form — previously it linked silently with an assumed role, so a grandparent or carer could be recorded incorrectly. The relationship is threaded through `silentLink()` into the existing linking step. Part of the go-live-readiness epic (#1723).

# TalentTrack v4.54.0 — Chemistry rework — schema foundation (#1912)

Phase 1 of the chemistry-engine rework (epic #1017): the data layer the pilot-locked spec needs, **with no engine change** — `BlueprintChemistryEngine` keeps working while later phases build on top. Adds a normalised player-attribute model — a seedable, extensible catalogue (`tt_player_attribute_defs`, 23 attributes across physical/technical/tactical/mental/behaviour/development, with Dutch labels) plus per-player values (`tt_player_attribute_values`) — the configurable Position Relationship Matrix (`tt_chemistry_position_matrix`, seeded with sensible defaults), and a lineup-chemistry time-series table (`tt_team_chemistry_snapshots`). The five component weights live in `tt_config`. Repositories and a matrix-gated REST contract (`/players/{id}/attributes`, `/chemistry/position-matrix`, `/chemistry/config`) ship so Phase 2 (sub-engines) and Phase 7 (data entry) can build against it. Every new table carries the `club_id` + `uuid` tenancy scaffold; the attribute catalogue is archive/cascade-wired.

# TalentTrack v4.54.0 — Chemistry rework — five component sub-engines (#1017)

Phase 2 of the chemistry rework (epic #1017): the five weighted component scorers the new pair-chemistry formula is built from, as standalone, independently-reviewable classes — **Compatibility** (core attribute groups + footedness), **Familiarity** (shared training + tenure), **Development** (age + potential alignment), **Behaviour** (behaviour group, team-orientation weighted), **Performance** (shared games). Each takes two player profiles + their shared-history context and returns a 0–100 score with human reasons for the explainability panel, falling back to a neutral 50 (flagged `has_data: false`) when its inputs aren't recorded yet — so an un-populated player never drags a lineup to zero. The locked spec fixes which attribute groups feed each component and the top-level weights; the internal formulas here are a documented v1, tunable per scorer. No engine integration yet (Phase 3 orchestrates them); `BlueprintChemistryEngine` is untouched.

# TalentTrack v4.53.0 — Tidy the trials list and trial-case detail page (#1646)

The trials list now uses the standard 2026 table header (dropped the legacy
sortable widget that showed broken sort glyphs). On the trial-case detail
page the in-card Assign / Extend buttons are styled as primary buttons, the
header action row wraps instead of clipping its last button off the edge, and
the duplicate in-body Archive button is gone — archiving now happens from the
single top-right action. The case execution tab's activity/evaluation/goal
queries are bounded to avoid a slow-query timeout.

# TalentTrack v4.53.0 — POP goals: per-goal progress % + evaluation evidence (#1717)

Fills in the two POP-card slots the restyle reserved but never rendered.

- **Per-goal progress %** — `tt_goals` gains a `progress_pct` (0–100) field a
  coach sets on the goal form; the POP card now shows the progress bar.
- **Evidence (Bewijslast)** — a new `tt_goal_evidence` table links specific
  evaluations to a goal. The goal form gets an evidence picker (tick the
  player's evaluations); each linked evaluation renders on the POP card as a
  scored chip — *Assessment 12 Mar · 6.5* — from its date + overall
  (average-rating) score. Stored separately from the methodology links.

Migration 0173 (additive). With #1754's collapsible cards + per-goal
conversation, the POP page now matches the deck mockup.

# TalentTrack v4.53.0 — The Accounts & access tile now shows on the admin dashboard (#1815)

Fixes the Accounts & access hub being unreachable from the dashboard: the
tile is now registered so it renders for the Academy Admin (and Head of
Development) dashboards, alongside Configuration and Invitations. The hub
groups Player accounts, Parent accounts, and Invitations.

# TalentTrack v4.52.0 — POP page: collapsible goals with a conversation per goal (#1754)

The player's POP page now renders its learning goals as **collapsible
cards** (native `<details>`, keyboard-accessible). Each card header shows the
goal title, status, due window, and a 💬 count of that goal's messages.

Expanding a goal reveals two columns: the goal's detail (description, linked
methodology, evidence) on the left and **that goal's own conversation thread
on the right** — every goal has a separate thread, so discussions don't mix.
In-progress goals open by default. Reuses the existing per-goal threads
(`thread_type='goal'`), and makes `FrontendThreadView` multi-instance-safe so
several conversations can live on one page.

Per-goal **progress %** and scored **evidence (Bewijslast)** shown in the deck
mockup are a follow-up — they need the evaluation-evidence schema in #1717.

# TalentTrack v4.52.0 — Accounts & access hub (#1815)

A new "Accounts & access" tile on the dashboard opens a hub that groups the
account-management surfaces in one place: Player accounts, Parent accounts,
and Invitations. Each card is permission-gated and links straight to its
screen. The standalone Player accounts tile is folded into the hub.

# TalentTrack v4.52.0 — Fix Unknown-column errors on the trials list and reports (#1840)

Adds a forward migration that restores the `opened_by` and `overall_rating`
columns on `tt_trial_cases`. Installs that ran the original trial-module
migration before these columns existed were missing them, causing
"Unknown column" database errors on the trials list and the trial reports
(and a blank, unstyled trials page when the failed query halted rendering).
The migration is idempotent and backfills `opened_by` from `created_by`.

# TalentTrack v4.51.1 — Parent accounts admin surface (#1815)

A new Parent accounts screen (Dashboard → Parent accounts) lets academy
admins manage guardian logins: link an existing WordPress account to a
player as a parent, see one row per parent with the players they guard, and
unlink a parent from a player in one click. Gated by the dedicated
parent-account-management permission. Inviting a parent stays available from
a player's Family tab.

# TalentTrack v4.51.0 — Restyle 14 remaining frontend surfaces to the 2026 look (#1695)

Brings the last batch of frontend view bodies onto the 2026 design system:
teammate, my-evaluations (coach view), VCT session, team chemistry,
match-executions list, team blueprints, minutes report, the data explorer,
cohort transitions, the report wizard, and the admin roles / seasons /
migrations / VCT library screens. Inline styles moved into enqueued
mobile-first stylesheets, legacy `widefat` tables replaced with the card +
`.tt-table` pattern, and raw colours swapped for design tokens. No behaviour,
data, or permission changes.

# TalentTrack v4.51.0 — Foundation for parent-account management (#1815)

Groundwork for the upcoming Parent accounts admin surface: a dedicated
`tt_manage_parent_accounts` capability (granted to administrators, Club
Admins and Heads of Development, tunable per-persona via the authorization
matrix), a `ParentAccountService` for listing parents and linking/unlinking
a parent WordPress account on a player, and REST endpoints
(`POST`/`DELETE /players/{id}/parents`). No user-facing screen yet — that
arrives with the Parent accounts view.

# TalentTrack v4.51.0 — Player/parent dashboard no longer shows the "Features" tile or a Setup section (#1836)

Follow-up to #1821. The read-only "Features" (NL "Functies") tile — which lists which parts of TalentTrack are switched on — was registered visible to every persona with no capability or matrix entity, so it appeared for players and parents as the lone tile in a "Setup & administration" section. It's now hidden from the player and parent personas, so that section no longer appears on their dashboard. (The functional-roles tile's gating from #1821 is reverted, as the active authorization matrix already gates it on its entity.)

# TalentTrack v4.51.0 — Reachable "Delete permanently" on detail/editor pages (#1784 follow-up)

The referential-integrity permanent delete now has a UI control on the
bespoke (non-list) management surfaces, not just the list views. Adds a
**Delete permanently** button to the trial-case detail page, the trial-track
editor, and each archived row in the VCT exercise library. All three reuse
the shared archive-button handler, so a blocked delete shows the same
"still referenced by …" reason on screen. Admin-gated (`tt_edit_settings`;
VCT: `tt_vct_admin_library`); built-in trial tracks stay non-deletable.

Surfaces without a management page of their own — test trainings
(create-only), custom widgets (no front-end view) and injuries (read-only
on the player timeline) — keep their delete at the REST/admin layer; a
dedicated UI for those is out of scope here.

# TalentTrack v4.50.2 — Scouting pipeline: every card opens the prospect, even with no next action (#1763)

In the onboarding pipeline, a prospect card with no pending task (and not yet promoted) used to render as a dead, unclickable tile. Now every card is clickable: when there's no "next action" it focuses the prospect on the board — `?tt_view=onboarding-pipeline&prospect_id=N` opens a panel showing who they are, their stage, and a link to their next action when one exists. This also fixes the previously no-op `prospect_id` links from the dashboards and scouting-visit detail, which now land on a real focus.

# TalentTrack v4.50.1 — Blueprint editor: a bad assignment ref no longer breaks formation + slot picking (#1619)

On an editable (draft) blueprint, the formation dropdown and slot player-picker could both be dead even though the user had the cap and the blueprint wasn't locked. Cause: an exception during the editor's setup (e.g. a malformed assignment ref) aborted the script before its wiring ran, leaving the server-rendered pitch visible but inert. The editor now runs each setup/wiring step in isolation, so one bad ref can't cascade and disable the rest — and any offender is logged to the console for diagnosis. (Defensive hardening; if a specific payload still triggers it, the console now points at the exact step.)

# TalentTrack v4.50.1 — Player dashboard: own work as tiles, no setup/functions tile (#1821)

The Speler (player) dashboard now renders the player's work (My journey, My card, My team, My evaluations, My activities, My goals, My POP) as tiles under "Today's work" instead of a separate right-hand rail. The "Functional roles" setup tile is also gated correctly: it now requires the manage capability (`tt_manage_functional_roles`), so it no longer leaks into a player's "Setup" section via the loose view-people fallback. Other personas are unchanged, and the persona switcher is respected.

# TalentTrack v4.50.0 — Finalize the safe-delete rollout — archive columns, holiday lifecycle UI + scheduled reports (#1784, #1808)

Completes the referential-integrity delete epic (#1782).

- **Migration 0172** gives every archivable entity the uniform
  `archived_at` + `archived_by` columns: adds the missing `archived_by` to
  trial tracks, test trainings, holidays, player injuries, custom widgets
  and VCT exercises, and adds both columns to scheduled reports (backfilling
  `archived_at` from the legacy `status='archived'`).
- **Scheduled reports** join the framework: an Active/Paused schedule can be
  archived, and an archived one can now be **permanently deleted** from the
  management screen (fail-closed, `tt_edit_settings`).
- **Holidays** gain the full archive lifecycle in their list — an
  Active / Archived tab with Restore and Delete-permanently actions on
  archived rows (matching the tournaments list).

With this, every record type that has an archive lifecycle has a
fail-closed, referential-integrity-checked permanent delete. Team and
activity remain block-only by design (their full player-touching cascades
wait on the PHPUnit floor, #1388).

# TalentTrack v4.50.0 — My Journey event labels no longer leak English (#1818)

The player journey timeline now shows event-type labels (Position changed,
Trial ended, Injury started, …) and the filter chips in the active
language. On Dutch installs they render in Dutch instead of English: the
view resolves each label through the lookup translator, and a migration
seeds the Dutch journey labels into the translation store.

# TalentTrack v4.49.1 — Players complete their profile when accepting an invite (#1819)

The player invitation-acceptance page now collects first name, last name,
date of birth, and preferred foot (alongside the existing jersey number),
written straight to the player record on accept. First and last name are
pre-filled from the invite so the player just confirms or corrects them.

# TalentTrack v4.49.1 — Players can't change their account display name (#1820)

Following the title-case "First Last" default, the display-name field on a
player's My settings page is now read-only — a player's name is owned by
the academy and set from their player record, so it can't be edited there
(enforced server-side as well).

# TalentTrack v4.49.1 — Player accounts: click a linked player to see which WP account it's linked to (#1823)

On the Player accounts page, a linked player's green chip is now a click-to-reveal disclosure: tapping it shows the actual WordPress account behind the link — email, username, and WP user id — so you can tell two accounts apart even when they share a display name. Read-only, inline, no wp-admin needed.

# TalentTrack v4.49.1 — Player accounts: compact rows for not-yet-connected players (#1824)

Rows for players without an account were much taller than connected rows because the link controls wrapped onto several lines. On tablet/desktop the account dropdown + Link + Invite buttons now sit on a single line, so an unconnected row is no taller than a connected one. Also fixes the "WordPress user to link" screen-reader label leaking visible under canvas mode (it relied on the theme's screen-reader-text class, which canvas isolation strips) by giving the plugin its own SR-only utility.

# TalentTrack v4.49.0 — Safe permanent delete for VCT exercises, custom widgets + injuries (#1784)

Extends the referential-integrity delete framework (#1783) to the last of
the rollout entities, plus a framework enhancement: cascade plans can now
**table-qualify** a reference column, so an ambiguous column name (e.g.
`exercise_id`, which keys both `tt_exercises` and the VCT tables) is scanned
on the right tables only.

- **VCT exercise** — cascades its coaching points; clears the exercise link
  on any session block. New `/vct/exercises/{id}/permanent` route.
- **Custom widget** — standalone; removed directly. New
  `/custom-widgets/{id}/permanent` route (uuid- or id-keyed).
- **Injury** — removes the injury and its journey-timeline events (a minor's
  medical record), so a right-to-erasure delete actually erases. New
  `/player-injuries/{id}/permanent` route.

All fail-closed, gated by `tt_edit_settings` (VCT: `can_admin`). No
migration. The `archived_by`-column migration + list-view delete
affordances for the full archive-lifecycle UI remain on #1784.

# TalentTrack v4.49.0 — Configurable dashboard tile colour scheme (#1809)

A new academy-wide **Tile colour scheme** setting recolours the dashboard tiles without changing their size or layout. Six schemes are available — Default, Brand border, Gold-topped (the new default), Soft green fill, Solid green and Left accent — and they draw entirely from the academy's brand colours, so they track your Primary/Secondary colour choices automatically. The setting sits alongside Tile size and Tile layout on the Appearance configuration surface and is stored under the `tile_style` configuration key.

# TalentTrack v4.49.0 — Team planner export buttons are now compact icon buttons (#1812)

The team planner's Export PDF / Export XLSX / Weekly PDF actions render as
icon buttons matching the height of the "Schedule activity" button, instead
of taller text buttons. On phones they collapse to icon-only circles like
the other page-header actions; each keeps an accessible label.

# TalentTrack v4.49.0 — My Journey: position changes read as a list, not raw JSON (#1818)

A "position changed" entry on a player's journey now reads e.g.
"Positie: geen → CB, LB" instead of showing the raw stored array
("[\"CB\",\"LB\"]"). New position-change events store the formatted value.

# TalentTrack v4.49.0 — Player accounts get a proper "First Last" display name (#1820)

When a player accepts their invitation, their account's display name now
defaults to their first and last name in title case (e.g. "Luuk
Nieuwenhuizen") taken from the player record, rather than an inconsistent
or lower-cased value.

# TalentTrack v4.48.2 — Security: parents can no longer open another family's child profile (#1725)

The player detail view only checked the coarse `tt_view_players` capability, never that the viewer was actually linked to *that* player — so a parent could open any child's profile by id and the "Parents · Guardians" card would expose every co-guardian's name, email, and phone (a safeguarding leak for minors). The view now enforces the canonical per-player scope (`AuthorizationService::canViewPlayer`: own record / global / player's team / parent-of-this-player), and the guardians card renders for staff only (admin/HoD or the team's coach) — never for a parent viewing their own child. Also fixes an adjacent bug where the activities REST endpoint queried `tt_player_parents` with a non-existent `wp_user_id` column (correct: `parent_user_id`), which had wrongly blocked parents from their own child's activities.

# TalentTrack v4.48.2 — PDP (and team-scoped surfaces) now visible to a player's head coach (#1758)

A head coach assigned to a team the legacy way could not see their own players' PDP files — the files tab was empty even though the coverage tab counted the PDP, while HoD/admin saw it fine. Cause: the legacy `head_coach_id` backfill (migration 0006) created the `tt_team_people` link but never the `tt_user_role_scopes` team grant that `get_teams_for_coach()` reads, so `coach_owns_player()` returned false. A new idempotent backfill (migration 0171) creates the missing team-scope grant for every team-people link, so legacy and modern assignments converge on the single matrix source of truth. Head coaches now see their team's PDPs (and every other team-scoped surface); HoD/admin visibility is unchanged.

# TalentTrack v4.48.2 — Safe permanent delete for holidays, test trainings + trial tracks (#1784)

Extends the referential-integrity delete framework (#1783) to three more
record types via new `/permanent` REST routes (gated by `tt_edit_settings`,
fail-closed). **Holidays** are removed directly; **test trainings** clear
any workflow-task link first; **custom trial tracks** block while a trial
case still uses them and built-in (seeded) tracks are refused. No migration.

The remaining archivable entities (custom widget, injury, VCT exercise) and
the list-view affordances stay tracked on #1784.

# TalentTrack v4.48.1 — CI gate: contain new inline styles (#1389)

A new **Inline-style containment** CI gate fails any pull request that
*adds* an inline `style="…"` attribute or a `<style>` block inside
`src/**/*.php`. The repo's large existing backlog is grandfathered — the
gate is diff-only, so it never trips on untouched code — but new inline
styling must now move into an enqueued stylesheet (reading the design
tokens, never raw hex), which is what keeps the spacing/colour drift from
reappearing (CLAUDE.md §2). For a genuinely dynamic value that can't live
in CSS (e.g. a computed progress-bar width), a trailing
`/* tt-inline-ok */` on the same line grandfathers it. The rule is now
documented in CLAUDE.md §2. No runtime change.

# TalentTrack v4.48.1 — Trial case page 2026 card layout + Save/Cancel on trial config forms (#1646)

The trial-case detail page now wraps each section in a token-styled 2026
card with cleaner headings, matching the teams and activity-detail surfaces;
the regenerate-letter form's inline margin moved into the enqueued sheet. The
trial-tracks editor and letter-template editor both gained a proper Cancel
button alongside Save (via the shared `FormSaveButton` helper, honouring any
`tt_back` hint), and the letter editor's monospace HTML textarea moved into a
CSS class. Visual and markup only — no data, query, or permission changes.

# TalentTrack v4.48.1 — Standardize report interfaces to the 2026 card/table/KPI pattern (#1760)

The standard-reports, report-detail and scheduled-reports surfaces now share
the same 2026 look as the attendance report: a KPI strip, card-wrapped tables
(`.tt-report-card` + `.tt-table`), and a consistent page head. The shared
primitives moved into the app-chrome sheet so every report surface inherits
one definition. No data or permission behaviour changed.

# TalentTrack v4.48.1 — Safe permanent delete for tournaments + trial cases (#1784)

Extends the referential-integrity delete framework (#1783) to two more
record types. Permanently deleting a **tournament** now cascades its
matches, squad and per-match assignments and clears a linked activity's
tournament reference; permanently deleting a **trial case** cascades its
staff assignments, staff inputs and extension history and clears any
workflow-task / prospect link. Both are fail-closed — they refuse and name
the dependents if anything undeclared still references them.

Adds the `/tournaments/{id}/permanent` (+ `/restore`) and
`/trial-cases/{id}/permanent` REST routes, and the Restore + Delete-
permanently row actions on the tournaments list. Gated by `tt_edit_settings`.
The remaining archivable entities (which need an `archived_by` column
migration) stay tracked on #1784.

# TalentTrack v4.48.1 — List filter bar: roomier controls + a search icon (#1803)

The search and filter controls on list views now have a comfortable left
text inset instead of hugging the border, and the search box shows a
magnifier icon. Both live in the shared list component, so every list
inherits them.

# TalentTrack v4.48.1 — Team planner: actions moved to the page header (#1804)

The team planner's "Schedule activity" button and the PDF / XLSX / weekly
export actions now sit in the page header, alongside the title, like the
players list — instead of crowding the filter bar. The filter toolbar now
holds only the team picker, the period selector, and the week navigation,
and the period dropdown is sized to match the team dropdown.

# TalentTrack v4.48.1 — Evaluation detail page uses the full width on desktop (#1806)

The evaluation detail page now spans the full content width on desktop,
matching the other pages, instead of rendering as a narrow centred card.
Mobile is unchanged.

# TalentTrack v4.48.0 — Referential-integrity-checked permanent delete (#1783)

Permanent delete is now fail-closed across the archive lifecycle. A new
declarative cascade framework (`CascadeRegistry` + `GenericCascadeDeleter`)
checks, before removing a record, what still references it — then cascades
the record's own children, clears references on rows that outlive it, or
refuses the delete with a message naming what still points at it. A
permanent delete can no longer silently orphan child rows.

Deleting an **evaluation** now also removes its category ratings and
evidence links; deleting a **goal** removes its links and conversation
thread and clears any spawned-goal task link. **Team** and **activity**
permanent-delete now **block** while anything still references them
(previously they deleted the row and stranded its children) — full cascades
for those two are tracked as a follow-up (#1784). Player / person / PDP
deletes are unchanged.

# TalentTrack v4.48.0 — Players list toolbar now matches the standard register card (#1791)

The players list filter/search bar now renders as the standard 2026
"register" card — white surface, soft shadow, comfortable padding, and
rounded, bordered controls — instead of the earlier soft-grey strip with
square-cornered inputs. The toolbar and the table read as two matching
cards, the same chrome every other list uses. The rounded-control fix is
in the shared list-table component, so any list that didn't already style
its own controls now gets rounded search/filter inputs too. Restyle only;
filtering, search, and sort behaviour are unchanged.

# TalentTrack v4.48.0 — Record-name links look the same regardless of the active theme (#1792)

Links to a record (player name, team name, and similar) no longer pick up
the surrounding theme's underline or link colour. The shared record-link
styling is now pinned so an aggressive theme `a` rule can't override it,
so the same install renders these links identically whatever theme is
active. Visual only — link targets and behaviour are unchanged.

# TalentTrack v4.48.0 — Activities list adopts the standard toolbar and full desktop width (#1793)

The activities list Team/Type filter bar now renders as the standard 2026
"register" card, matching the players list, and the list spans the full
content width on desktop instead of a narrow centred column. The period
quick-filter chips (All / This week / Next week / …) are unchanged and
still sit below the filter bar. Restyle only; filtering and the activity
buckets behave exactly as before.

# TalentTrack v4.48.0 — Permanently deleting an archived player no longer fails on PDP calendar links (#1794)

Permanently deleting an archived player who had a PDP with a scheduled
conversation failed with a server error and deleted nothing — the deletion
cascade tried to match PDP calendar links on a column that doesn't exist.
Calendar links are keyed by conversation, so the cascade now reaches them
through the conversation and PDP file, and the delete completes cleanly,
removing those links with the rest of the player's data. The cascade
remains all-or-nothing, so no partial deletes occur. Right-to-erasure of a
player with a full PDP history works again.

# TalentTrack v4.48.0 — Dashboard tile grid adopts the 2026 green/gold look (#1695)

The frontend dashboard renders through `FrontendTileGrid` (the tile
landing shown when no persona template takes over), which carried its own
flat, grey tile styling — it was missed by the earlier persona-landing
(#1769) and `TileGridStandard` (#1790) restyles. Its tiles now match the
2026 mockup: a green left-accent and 12px radius on each tile card, a gold
left-accent on the "Mijn werk" rail rows, green-deep section labels, and
ink/line/paper/muted design tokens throughout (with a green-tinted hover
shadow and brand-green focus rings). Everything reads from the shared
tokens, so the club-colour editor re-themes the dashboard too. Visual
only — no markup, query, or navigation change.

# TalentTrack v4.47.1 — Spond import no longer overwrites notes after the first import (#1774)

A Spond-imported activity's notes are now seeded from the event's description
on the first import only, then owned by TalentTrack — the same "set once, then
TalentTrack wins" model already used for the activity type. Previously every
hourly re-sync rewrote the notes from Spond's description, wiping any notes a
coach had added or edited in TalentTrack. Title, date, location, and the time
fields still follow Spond on every sync. Trade-off: a later edit to the
description in Spond no longer flows into an already-imported activity.

# TalentTrack v4.47.0 — Evaluations list now matches the player-file count when filtered to a player (#1755)

Opening the evaluations list filtered to a single player previously applied
coach team/author scoping, so a coach could see a non-zero "N evaluations"
badge on a player's file yet an empty or short list — evaluations authored by
another coach for a player on a team they don't coach were hidden. When the
list is filtered to one player and the viewer can open that player's file, it
now returns all of that player's non-archived evaluations (club-scoped),
matching the player-file badge count and the player-file Evaluations tab. The
unfiltered evaluations list keeps its coach team/author scoping; access is
gated on the same can-view-player check used to reach the file, so no players
become visible that weren't already.

# TalentTrack v4.47.0 — Team planner "Principles trained" bar: rebalanced label/bar/count (#1756)

The "Principles trained — last 8 weeks" coverage rows under the team planner
laid out poorly: cramped principle labels, an over-wide bar, and no room to
read the count. The row grid is rebalanced — the label column flexes wider
(and long labels wrap instead of truncating), the bar track is narrower at a
fixed width, and the activity count sits clearly to the right of the bar with
breathing space. CSS-only; selectors and markup unchanged.

# TalentTrack v4.47.0 — PDP planning grid follows the configured block count (#1759)

The PDP planning matrix used to derive its number of block columns from the
highest block sequence found across stored conversations, so a legacy or
seed conversation carrying block 4 made the grid show 4 columns even when the
season was configured for 2. The grid now follows the academy's configured
PDP block count for the season (`tt_pdp_blocks`); blocks beyond the configured
count are no longer drawn. When a season has no blocks configured, it falls
back to the previous data-derived behaviour so legacy even-divide installs are
unchanged.

# TalentTrack v4.47.0 — Academy admins can switch individual export tiles off (#1762)

Academy admins can now disable individual export tiles — for example to hide
the Audit log, the Full club-data backup, or Federation registration — from
the Modules management page, under the Export module. There's one toggle per
bulk export tile, all enabled by default, so nothing changes until one is
turned off. Disabling a tile both hides it from the Exports page (for everyone
in the academy, admins included) and rejects that export at the endpoint, so it
can't be run via a direct link either. Toggles are per-academy (club-scoped)
via FeatureRegistry and audit-logged; they only ever narrow access — a user
still needs the underlying capability to see an enabled tile.

# TalentTrack v4.47.0 — Archive a scouting visit from the UI (#1764)

The scouting-visit detail view now has an **Archive visit** action. The
archive (soft-delete) capability already existed in the REST API
(`DELETE /scouting-visits/{id}`) but nothing surfaced it, so a visit could
never be cleared from the list. The button is shown to the visit owner (or
a scope admin), confirms before firing, calls the existing endpoint with a
nonce, and returns the user to the scouting-visits list with a "Scouting
visit archived." notice. No new business logic — the REST route already
enforced the capability and row-ownership check; this only wires it into
the UI.

# TalentTrack v4.47.0 — Player accounts view — link/unlink a WP account to a player (#1771)

A new **Player accounts** view (`?tt_view=player-accounts`, academy/club
admin) lists every player with their account status — No account / Invited
/ Linked — and lets an admin directly **link** an existing WordPress user
to a player or **unlink** one, the primary account-mapping workflow.
Invitations stay the secondary self-service path (the Invite button reuses
the existing flow).

- Link is offered only for accounts not already bound to another player or
  a staff/parent record (no double-binding), and grants the player role.
- Unlink keeps the player record and removes the player role only when the
  account isn't linked elsewhere, so a coach-who-once-played keeps their
  access.
- Resource-oriented REST: `POST /players/{id}/account` (link) and
  `DELETE /players/{id}/account` (unlink), gated by `tt_manage_players`;
  the view and REST share one `PlayerAccountService` so a future
  non-WordPress front end gets the same answers.

Builds on the one-account-one-player DB guarantee from #1772, and supplies
that issue's app-layer "already linked" guard.

# TalentTrack v4.47.0 — Enforce one WP account per player (#1772)

`tt_players.wp_user_id` had no uniqueness guard and no cleanup when a WP
user was deleted, so two players could share an account and the
derived-player scope resolver could surface the wrong child's record — a
safeguarding risk for minors.

- New migration `0170` deduplicates any players sharing a `(club_id,
  wp_user_id)` (keeping the active, data-richest, newest row and
  **unlinking** — never deleting — the rest, with an audit-log entry per
  unlink), normalises "no account" from `0` to `NULL`, and adds a
  `UNIQUE (club_id, wp_user_id)` index.
- New `delete_user` cleanup nulls `tt_players` / `tt_people` account links
  and removes `tt_player_parents` rows for the deleted user, so a
  re-issued WP user id can't inherit someone else's record.
- The player/parent scope resolvers now order deterministically, and every
  write path stores `NULL` (not `0`) for an unlinked player.

No behaviour change for correctly-linked accounts; the link UI and an
app-layer "already linked" guard land with the Player accounts view (#1771).

# TalentTrack v4.46.5 — List toolbar restyled to the 2026 "register" look (#1753)

The filter/search toolbar above every list view adopts the 2026 register style (Option D): a white filter card with a soft shadow and rounded corners, and an uppercase micro-label above each control (Search, Team, Status, Sort…) matching the table header treatment. It's mobile-first — controls stack full-width at phone size and collapse to one inline register row at ≥768px, with 16px inputs (no iOS zoom) and 48px touch targets. Implemented once in the shared `FrontendListTable` + `frontend-admin.css`, so every list inherits it; the filter set stays per-list (each list still declares its own controls). Functionality is unchanged — search, filters, sort, no-JS apply, and the status line all behave as before. Stable `.tt-list-table-*` selectors preserved.

# TalentTrack v4.46.4 — Usage stats: see active users by name, not just role buckets (#1765)

The Application KPIs view gains an **Active users** panel listing the actual people active in the window — each with their role and last-seen time — below the existing role-bucket summary. Each name links through to that user's activity timeline. A new `UsageTracker::activeUsers()` method provides the data (role classification mirrors `activeByRole()`), keeping the query out of the view. The panel stays behind the same admin capability as the rest of the usage dashboard, so names never leak beyond admins. One new string ("Active users (%d days)"), Dutch added; the role-bucket table also now shows translated role labels instead of raw keys.

# TalentTrack v4.46.3 — Report cards regain the contextual back pill (#1761)

Opening a report from the Reports launcher now shows the contextual "← Back to Reports" pill, so you can return to where you came from in one tap. The destination report views already auto-render the pill from a `tt_back` URL hint, but the launcher tiles linked without one — so the pill never appeared. The launcher now stamps each tile link with the launcher page as its back-target via `BackLink::appendTo()`. Breadcrumb chain is unchanged (still ends at Dashboard); no third affordance is added (CLAUDE.md §5).

# TalentTrack v4.46.2 — App-chrome user chip: wider name box + roomier avatar circle (#1751, #1752)

Two small fixes to the signed-in user chip in the top-right app chrome. The display name no longer clips — its box widens from a 14-character cap to 20, with a touch more padding on the chip (#1751). And two-letter initials (e.g. "CN") now sit fully inside the avatar circle: it grows from 32px to 36px with a slightly smaller, properly centred glyph (#1752). CSS-only in `frontend-app-chrome.css`; selectors and the 48px touch target are unchanged.

# TalentTrack v4.46.1 — New-evaluation player picker: team-scoped dropdown instead of blank search (#1731)

The player-first new-evaluation wizard's Player step no longer hides every
player behind a type-to-search box. It now shows a team-scoped native
dropdown: pick a team, then choose the player from the list. A coach who
manages exactly one team lands with that team pre-selected and its players
already listed, so no typing is needed. The team filter repopulates the
player list on change, and Head of Development / Academy Admin keep an
"All teams" option for cross-team reach. The change is opt-in via a new
`style => 'dropdown'` arg on `PlayerSearchPickerComponent`; the ~6 other
surfaces that use the picker keep the existing search behaviour unchanged.

# TalentTrack v4.46.1 — Deep-rate step: collapsible category accordion with aligned stars (#1732)

The player-first new-evaluation Rating step is no longer a flat table of
stars with a Basic/Detailed toggle. Each main category is now a collapsible
block (collapsed by default) whose summary shows the category name, a
read-only star mirror, and the average word — so a coach can scan what's
rated without expanding anything. Expanding reveals the editable
category-level stars and the sub-skill rows; rating sub-skills still sets the
category to the rounded average of the non-zero subs, and the summary
reflects it live. The #1643 training default still surfaces the Mental
category first and opens it. All inline styles moved to a stylesheet; the
star column lines up across categories and sub-rows. Ratings submit and
restore exactly as before — no data-shape change.

# TalentTrack v4.46.1 — Dutch eval-category labels no longer leak English (#1733)

The New-evaluation rating screen (and anywhere eval categories render) leaked
English labels — "Tactical", "Physical", "Short pass", "Dribbling", "Offensive
positioning" — alongside the few that already showed Dutch. The category
vocabulary is seeded in `tt_eval_categories` and resolved through
`tt_translations`, but only a handful of Dutch rows existed, so the rest fell
back to the raw English label on nl_NL installs.

A new idempotent migration seeds the authoritative Dutch label for every
default eval-category and sub-skill straight into `tt_translations`, keyed by
the stable `category_key`. It only seeds a category whose label is still the
seeded English default, so an academy that renamed a category keeps its own
wording; re-running is a no-op. No `.po` or code change — `displayLabel()`
already prefers `tt_translations`.

# TalentTrack v4.45.25 — Spond import maps start → kickoff time and meet-up → presence time, and stops dropping the time of day (#1741)

Activities imported from Spond now keep their **time of day**. Previously the sync stored only the date and discarded the start time, so every imported activity came in time-less. The import now reads Spond's start/end timestamps — converting them from UTC to the site timezone (which also fixes a possible off-by-one calendar day for late-evening events) — and stores them as the activity's start/end time. For **match** types (game, tournament), the Spond start becomes the **kickoff time** and Spond's meet-up time (its "meet X minutes before start" setting, read from `meetupTimestamp` or `meetupPrior`) becomes the **presence time** ("Aanwezig", added in #1729) — both then print on the weekly planner PDF. Times are treated as schedule fields, so a re-sync overwrites them from Spond (consistent with title/date/location); a coach-changed activity type is still preserved. No schema change, no new strings.

# TalentTrack v4.45.24 — Weekly planner PDF: ISO week number in the badge instead of academy initials (#1730)

When no academy logo is configured, the Team Planner weekly PDF's top-left badge previously fell back to the academy/team initials (e.g. "J") — a meaningless orphan, since the week number already sits in the title. The badge now shows the ISO week number (digits only, e.g. "26") instead. A configured academy logo still wins and is shown unchanged. PDF-only cosmetic change; the now-unused `initials()` helper was removed.

# TalentTrack v4.45.23 — Match presence time + fix match start-time not printing in the weekly planner (#1729)

Match-type activities (game, tournament, and any operator-added match/friendly types) can now capture a **presence time** — the arrival/"be present by" time families act on — via a new optional field on the activity form, shown only for match types. It round-trips through the REST activities endpoint and prints in the Team Planner weekly plan PDF as `Present HH:MM` ahead of kickoff. This ship also fixes a latent bug: a match never printed any time in the weekly PDF. The activity form only ever writes `start_time` (kickoff_time stays null), but the weekly-PDF match branch read kickoff_time alone, so a match with a start time showed nothing — it now falls back to start_time and prints `Kickoff HH:MM`. New nullable `time_of_presence` column on `tt_activities` (migration 0168); the wp-admin activities form is unchanged (it captures no time fields, so a lone presence field there would be orphaned). New strings "Present %s" and "Presence time (optional)", Dutch added.

# TalentTrack v4.45.21 — Team planner restyled to the 2026 look (#1683)

The team-planner view body adopts the 2026 chrome: day cells become white cards with rounded corners and a soft shadow, the current day is marked by a gold "today" ring instead of the old blue outline, and activity cards pick up the brand green for their titles with a subtle lift on hover/focus. The "principles trained — last 8 weeks" coverage list is reworked from wrapped chips into a vertical list of proportional gold bars, each scaled against the most-trained principle in the window (the bar is hidden below 520px, leaving the chip + count). This is CSS plus a small markup tweak only — no data, query, or REST changes.

# TalentTrack v4.45.21 — Shared frontend app chrome: top bar + persona chip + KPI tile (#1690)

The global dashboard header — rendered once for every `?tt_view=` route by `DashboardShortcode::renderHeader()` — adopts the 2026 design: a dark-green top bar with a gold brand mark (the academy's initials when no logo is configured) and a **persona chip** showing the signed-in user's initials avatar, name, and resolved persona label (Head of Development, Coach, Speler, Ouder, …). The chip *is* the existing user-menu trigger, so no new navigation affordance is introduced (CLAUDE.md §5) and the dropdown, persona switcher, and docs drawer are untouched — the change is additive (nothing moved). A new `FrontendAppChrome` component (`src/Shared/Frontend/Components/FrontendAppChrome.php`) carries the chip, a brand-initials helper, and a reusable `kpiTile()` for views to call; styling is a new mobile-first `assets/css/frontend-app-chrome.css` reading the existing `--tt-primary` / `--tt-secondary` tokens (no new palette). Persona labels resolve through the SaaS-portable `PersonaResolver`, not role-string checks. Below 560px the chip collapses to the avatar alone. This is the foundation for the per-view visual-parity work (#1680); one new string ("Observer"), Dutch added.

# TalentTrack v4.28.0 — Pixel-faithful image-capture PDF for match-prep + team-sheet print (#1475)

The match-prep print sheet and the match-day team sheet now produce a PDF that visually matches the live page instead of a separately-styled DomPDF rebuild that drifted from what the coach laid out. Both surfaces open a clean, chrome-free print page in a new tab; an **Export as PDF (A4 landscape)** action there captures the visible page with html2canvas and assembles an A4-landscape PDF (jsPDF), scaled to width and split across multiple pages when the content overflows. The capture libraries are vendored locally under `assets/js/vendor/` and lazy-loaded only when the user clicks Export, so nothing extra weighs on the always-loaded front end. The browser's own **Print → Save as PDF** stays on the same page as a text-based fallback, and the server-side DomPDF team-sheet exporter remains registered as a fallback path. The print routes stay cookie-authenticated and capability-gated (`tt_edit_activities` for match prep, `tt_view_activities` for the team sheet). Trade-off accepted for fidelity: captured text in the image PDF is not selectable.

# TalentTrack v4.21.36 — Fix activities table typo that broke saving on fresh installs (#1511)

The plugin activator created the activities table as `tt_activitys` (a misspelling from the #0035 sessions→activities rename) while the entire codebase reads `tt_activities`. Installs that upgraded from `tt_sessions` were fine, but any install created fresh after #0035 got the wrong-named, half-built table and could not save activities ("De activiteit kon niet worden opgeslagen") — and the activities feature was broken throughout. The activator typo is fixed for new installs, and a new idempotent repair migration (0159) adopts an orphaned `tt_activitys` under the correct name and backfills the missing columns. It's a no-op on correctly-built installs.

# TalentTrack v4.21.33 — Group the Reports launcher by purpose (#1503)

The Reports launcher was a flat grid of a dozen tiles under a single "Pick a report." line. It now reads under five purpose-based sections — **Development & performance**, **Playing time**, **Recruitment**, **Staff & quality**, and **Season overview** — so the right report is easy to find. The existing scope filter is unchanged: academy-admin-only reports still hide for regular coaches, and a section with no visible tiles (e.g. Recruitment, Season overview for a coach) renders no header. No new reports, no data or query changes — purely how the existing tiles are laid out.

# TalentTrack v4.21.32 — Fix wizard 404 on subdirectory installs (#1491)

On a subdirectory WordPress install (e.g. `http://host/wordpress`), starting a wizard 404'd after the first step, with the subdirectory doubled in the URL (`/wordpress/wordpress/?tt_view=wizard&…`). `FrontendWizardView::wizardStepUrl()` built the wizard's step/return URL by passing the full `REQUEST_URI` path — which already includes the subdirectory — into `home_url()`, which prepends the subdirectory a second time. The same latent bug sat in `RecordLink::dashboardUrl()`'s last-resort fallback. Both now combine the canonical scheme+host with the request path (no re-prepended home path), mirroring the `currentDashboardUrl()` fix from #1455. Root and subdomain installs were unaffected and stay unchanged.

# TalentTrack v4.21.18 — Surface planned attendance on the activity page + match prep (#1453)

Planned attendance was already captured at activity creation (the roster step writes `record_type='expected'` rows) but never shown back. Two surfaces now read it:

- **Activity detail page** gains an **Expected attendance** panel listing the planned players (guests tagged) with the count, so a coach knows who to expect before the session. It shows nothing when the activity was saved with "Set attendance later".
- **Match prep — Availability step** now seeds its defaults from the planned roster instead of marking everyone Present: planned players default to Present, and team players the coach left out of the plan are pre-marked **Absent** with the reason "not in planned roster". Activities without a planned roster keep the all-Present default.

No new table — this reads the existing `tt_attendance` expected rows. A shared `ActivitiesRepository::plannedRosterForActivity()` backs both surfaces and a new read endpoint, `GET /wp-json/talenttrack/v1/activities/{id}/planned-attendance`, so a non-WordPress front end gets the same data.

# TalentTrack v4.21.17 — wp-admin menu: grouped headings for the modern menu (#1449)

When the legacy entity menus are off, the wp-admin TalentTrack submenu was a flat jumble of operator/utility pages. It now reads under separator headings: **Configuration** (Dashboard layouts, Custom widgets), **Data & demo** (Demo data, Demo data review, Seed review), **Help** (Help & Docs), **Advanced** (Impersonate user), and **Developer** (Module completeness, WP_DEBUG only). Dashboard and Account stay at the top. Each heading auto-hides when its group has no visible row (so module-disabled or cap-gated groups don't leave an orphan heading).

Two pages that registered their own raw `add_submenu_page` — Impersonate user and Module completeness — now register through `AdminMenuRegistry` like every other page, so they group, order, and gate consistently. Ordering is driven by a new `sort` weight on the registry, applied only in the modern menu; the legacy menu's layout is unchanged. (The earlier #1449 ship, v4.21.12, removed the stray Eval Type Categories item and translated "Demo data review".)

# TalentTrack v4.21.16 — PHPStan baseline loads via `includes`, CI actually analyses (#1437)

`phpstan.neon` declared the baseline under `parameters.baseline`, which PHPStan 1.12 rejects (`Unexpected item 'parameters › baseline'`). Because the release workflow runs PHPStan with `|| true`, the config error was swallowed and the job went green without analysing anything — static analysis had been a silent no-op gate. The baseline is now loaded the supported way, via a top-level `includes:` entry, so `vendor/bin/phpstan analyse` parses its config and runs. The grandfathered baseline (`phpstan-baseline.neon`) is still honoured. Making the job actually gate (dropping `|| true`) is a separate follow-up.

# TalentTrack v4.21.15 — Frontend Modules toggle (#1451)

Module enable/disable is now reachable from the frontend admin surface at `?tt_view=modules` (and a Modules tile under Configuration), not only `wp-admin/admin.php?page=tt-modules`. It's gated by a new `tt_manage_modules` capability (administrator + academy admin by default) and exposed over REST (`GET`/`POST /wp-json/talenttrack/v1/modules`) so a non-WordPress front end can read/toggle modules — per the SaaS-readiness principle. Disabling a module prompts a confirm + reload reminder. The wp-admin page stays as the power-user fallback.

# TalentTrack v4.21.14 — Data migration: export for moving data between installs (#1464, phase 1)

First phase of install-to-install migration. The Backups page gains a **Data migration** section: pick which data sets to include (players, teams, staff & roles, evaluations, activities & attendance, goals, lookups & configuration) and download a portable `.ttmig` archive (gzipped JSON, same envelope as a backup, stamped `kind: migration`). Export is read-only and data-only — WordPress users and media aren't included.

The import side (upload + entity/record selection + interactive conflict resolution + user mapping + ID remapping) lands in follow-up phases of #1464.

# TalentTrack v4.21.13 — Dashboard links self-heal off a stale/trashed page (#1462)

Internal dashboard links could point at a trashed page when `dashboard_page_id` config pointed at a page that was later trashed/deleted (e.g. a duplicate dashboard page). Both link resolvers (`RecordLink::dashboardUrl()` + `FrontendAccessControl::dashboardUrl()`) now only trust the configured page when it's published; otherwise they fall through — RecordLink rediscovers the live dashboard page and re-caches its id, FrontendAccessControl falls back to the front page. The setup wizard also now pins `dashboard_page_id` when it creates the dashboard page, so the link-builder and homepage can't drift.

# TalentTrack v4.21.12 — Admin menu cleanup: Dutch labels, no stray Eval Type Categories (#1449)

The wp-admin TalentTrack menu is tidier: **Eval Type Categories** is removed from the menu (it's a low-level evaluation setting — the page stays reachable by URL via a null parent), and the last English-leaking label, **"Demo data review"**, is now translated ("Demogegevens beoordelen"). The remaining items already had Dutch labels, so the menu now reads consistently in the site language.

# TalentTrack v4.21.11 — Dashboard page renders full-width on block themes (#1457)

The dashboard looked narrow because block themes constrain post content (e.g. theme.json `contentSize` ~645px) and the dashboard page held a bare `[talenttrack_dashboard]` shortcode. The setup wizard now creates the dashboard page with the shortcode wrapped in an `alignfull` group block, so it breaks out of the content constraint; the plugin CSS then caps it at 1600px on desktop (#1457's cap). Existing dashboard pages can be updated the same way (wrap the shortcode in a full-width group, or set the page to a full-width template).

# TalentTrack v4.21.10 — Wizards no longer 404 on subdirectory installs (#1455)

Pressing Next in any wizard (activity, team-blueprint, …) 404'd when WordPress is installed in a subdirectory: `WizardEntryPoint::currentDashboardUrl()` rebuilt the URL with `home_url($path)` where `$path` (from REQUEST_URI) already contained the subdir, doubling it (`/wordpress/wordpress/…`). It now combines the site's scheme+host with the request path, so the subdir appears once. Domain-root installs are unaffected.

# TalentTrack v4.21.9 — Dashboard uses desktop width (#1457)

The dashboard was capped at 1100px on every screen. It now widens from the 1024px breakpoint up (to `min(94vw, 1600px)`), so desktops use far more of the viewport while phone/tablet keep the comfortable reading width. If a block theme constrains page-content width below this, a full-width page template is the follow-up.

# TalentTrack v4.21.8 — Version moved into the dashboard header row (#1452)

The operator version indicator now sits in the dashboard header actions row, next to the help button, instead of a footer at the bottom of the page. Still operator-only.

# TalentTrack v4.21.7 — Running version shown on the dashboard (#1452)

Operators now see the running plugin version (`v<x.y.z>`) as a subtle footer at the bottom of the frontend dashboard, so they can confirm what's deployed without opening wp-admin. Gated to operators (`tt_edit_settings`) so player and parent dashboards stay clean.

# TalentTrack v4.21.6 — Installed-version stamp advances after auto-migration (#1448)

After a plugin update via PUC (which doesn't re-fire the activation hook), the kernel ran the migration runner on every request because `tt_installed_version` was only ever set on activation. The kernel now stamps the version once migrations apply cleanly (zero failures), so the runner stops re-firing post-update. A failed migration intentionally leaves the stamp behind so the SchemaStatus retry path still engages.

# TalentTrack v4.21.5 — Plugin boots on init, ending the textdomain notice (#1438)

The kernel now boots on the `init` hook (early priority) instead of `plugins_loaded`. Several modules translate strings (`__()`) during `boot()`; doing that before `init` tripped WP 6.7's `_load_textdomain_just_in_time` "called incorrectly" notice on every request. Booting on `init` means translations resolve cleanly. Module-registered `init` callbacks (default priority) still fire, REST routes, admin menus, and the frontend shortcode are unaffected — verified on a live install (0 notices, 174 REST routes, dashboard renders).

# TalentTrack v4.21.4 — Setup wizard creates the dashboard page and sets it as the homepage (#1441)

The setup wizard gains a dedicated **Dashboard page** step (now six steps). It creates a WordPress page holding the `[talenttrack_dashboard]` shortcode — reusing an existing one if present, never duplicating — and sets it as the site homepage (`show_on_front` / `page_on_front`), so signing in lands straight on the dashboard. The final **Go to dashboard** button now opens that frontend page rather than the wp-admin dashboard. The step can be skipped, and the homepage is changeable later under Settings → Reading.

# TalentTrack v4.21.3 — Lookup values ship translated in all 5 languages (#1442)

Seed lookup vocabularies now carry curated nl_NL / fr_FR / de_DE / es_ES display labels, so dropdowns and status badges render in the site language out of the box instead of falling back to English. A new `LookupTranslationSeeds` map covers the player/coach/parent-facing types — foot, age group (Senior), eval categories + types, activity types + statuses, competition types, game subtypes, goal statuses + priorities + approval decisions, attendance statuses, journey events, player values, behaviour ratings, potential bands, audience types, tournament formats, VCT theme statuses, and the generic certificate types. Migration 0151 seeds them into `tt_translations` with `INSERT IGNORE`, so existing operator edits and earlier backfills are preserved. Locale-invariant codes (age-group U-codes, position codes, UEFA grades) are intentionally left untranslated.

# TalentTrack v4.21.2 — All 17 canonical age groups seeded (#1439)

Installs seeded before the canonical age-group list grew only had 7 options (U8, U10, U12, U14, U16, U19, Senior). The odd-numbered groups (U7, U9, U11, U13, U15, U17, U18, U20, U21, U23) are now present. Migration 0150 tops up existing installs (idempotent, per club) and normalises the display order to age order; the Activator seeds the full set on fresh installs. Custom age groups are preserved.

# TalentTrack v4.21.1 — Setup wizard age-group dropdown shows the site language (#1440)

The setup wizard's "First team" age-group dropdown rendered the raw canonical English value (e.g. `Senior`) regardless of site language. It now uses `QueryHelpers::get_lookup_label_pairs()`, so the visible label honours the site language (e.g. `Senioren` on `nl_NL`) while the submitted value stays the canonical English name — no change to what's persisted for existing teams.

# TalentTrack v4.21.0 — Player motivational layer (#1385)

The player dashboard now feels *for* the player instead of just *about* them. All seven player/parent KPIs — previously permanent "—" stubs — are wired to real data and surfaced on the player landing as progress cards:

- **My rating trend** (rolling average + since-last-month delta), **My activities attended %** (rolling 4-week), **My evaluations received**, **My goals completed**, **My PDP conversations done**, and **My next milestone** (nearest-due goal).
- **My team podium position** is wired too but, per #1384, only appears when the academy has enabled the player-visible rank toggle — so the default landing never shows a permanent dash for it.

The **"A note from your coach"** card is now live: it surfaces the most recent of the player-facing evaluation feedback (#1386) or a comment on one of the player's goals, and hides itself when there's nothing new (no more permanent "No new notes" stub). A **My check-ins** tile anchors the weekly self-evaluation — the one place the academy asks something *of* the player.

KPI business logic lives in the repository layer (`EvaluationsRepository`, `GoalsRepository`, `PdpFilesRepository`, `TeamStatsService`, `ThreadMessagesRepository`); the per-player rating trend is additionally exposed at `GET /players/{id}/rating-trend`. No schema change.

Completes the player-login launch gate (#1384/#1385/#1386).

# TalentTrack v4.20.131 — Player rank is now opt-in, with a growth trend (#1384)

The player-visible "#N of M" team rank on **My team** is now **opt-in per academy** and **off by default**. By default a player sees a growth-framed **personal trend chip** instead: how their rolling rating moved since last month (up / down / level) and the skill category they're improving most. Academies that want the numeric standing can enable it under **Configuration → Rating scale → "Show each player their team rank"**, and it then shows alongside the trend. No other teammate's rank is ever exposed; staff surfaces are unchanged.

The trend is computed in `EvaluationsRepository::personalTrendForPlayer` (two adjacent rating windows + top-improving main category) and is also reachable at `GET /wp-json/talenttrack/v1/players/{id}/rating-trend`, gated per-player by `AuthorizationService::canViewPlayer`. No schema change.

Second slice of the player-login launch gate (#1384/#1385/#1386).

# TalentTrack v4.20.130 — Player-visible evaluation feedback (#1386)

Coaches can now add an optional **Feedback for the player** field when recording an evaluation — a growth-framed message shown to the player (and their parents) on their My evaluations screen, alongside the scores. It is deliberately separate from the existing **Notes** field, which stays staff-only and is never surfaced to player or parent personas. The field is available on both the evaluation wizard (per-player, with interruption-buffer support) and the flat evaluation form, and rides the existing player/parent read surface so no new capability grant is required. **Schema**: one forward-only migration (0156) — additive `player_feedback` column on `tt_evaluations`, no operator action required.

First slice of the player-login launch gate (#1384/#1385/#1386).

# TalentTrack v4.20.95 — Demo→production conversion, PDP archive/delete, pilot-feedback drains, auto-release pipeline

Cumulative release covering every ship since v4.20.51 (2026-06-04). Forty-four patches: two feature epics shipped in slices (demo→production conversion, PDP archive + hard delete), two pilot-feedback drains (2026-06-10 + 2026-06-11), the i18n stabilisation arc, and the release-pipeline automation that makes PUC auto-update on pilot sites work without manual tagging. **Schema changes**: 8 forward-only migrations (0144–0152) — additive columns + backfills, no operator action required on upgrade.

## Demo→production conversion (#1272, v4.20.60–.62 + .75)

Operators who seeded demo data and then started entering real records can now convert in place instead of reinstalling. Admin **Demo Review** page ships a read-only inventory of every demo-tagged row (v4.20.60), a per-batch convert form driven by `DemoConversionService` — promote (strip demo tags) or delete per entity batch (v4.20.61), a terminal lock-out state + audit-log entry once conversion runs (v4.20.62), and per-record overrides on top of the per-batch toggle for the rare row that turned real mid-demo (v4.20.75).

## PDP archive + hard delete (#1274, #1293, #1294, v4.20.63–.65 + .73–.74)

PDP files gain a full lifecycle end: soft archive (schema + repo + REST + cap, v4.20.63), player-archive cascade (v4.20.64), hard delete with a five-table cascade behind the new `tt_delete_pdp` cap (v4.20.65), inline Archive/Restore buttons + show-archived toggle on the PDP list (v4.20.73), and a typed-name destructive-confirm surface with pre-delete CSV export to `wp-content/uploads/tt-pdp-deletes/` (v4.20.74).

## Pilot-feedback drain 2026-06-10 (v4.20.79–.84)

- Player profile Activities tab sorts chronologically with the recent-25 window preserved (#1316).
- Attendance Status select no longer collapses to `Aa▾` on Dutch installs (#1311).
- Goal-intake print gains a 7-block picker — snapshot / doelen 1-3 / afsluiting / handtekeningen / reminder — with a Print-alles escape hatch; team batch shares one selection (#1313).
- **Head-coach persona bug**: coaches assigned via the Staff section landed on the assistant_coach dashboard because no write path ever set `tt_team_people.is_head_coach`. Fixed at both canonical insert sites + backfill migration 0149 (#1314), then the dead `tt_teams.head_coach_id` column was retired outright — all four read sites moved to the modern path, column dropped in migration 0150 (#1315).
- Activities cap checks route through `AuthorizationService::userCanOrMatrix` so Functional-Role-only operators see the same UI the REST API already allowed (#1319).

## Pilot-feedback drain 2026-06-11 (v4.20.85–.92)

- Blueprint assignment refs repair migration for the silently-failed 0129 dbDelta (#1331); save-as-blueprint loud-fail + redirect to editor (#1328); open-saved-blueprint into the chemistry board (#1325); Delete affordance on blueprint list + editor (#1329).
- Goal detail page gains a Print doelenintake action (#1332).
- **Match-day team-sheet PDF now mirrors match-prep** — the exporter reads `tt_match_prep_lineup` + availability instead of never-populated `tt_attendance` columns, match-prep saves write through to `tt_attendance.lineup_role`/`position_played` as a projection, and the match-prep toolbar gains a Print-team-sheet button (#1194).
- **Activities ↔ Tournaments link**: tournament-typed activities carry a `tournament_id` FK (migration 0152), detail view shows the linked tournament with a cap-gated planner deep-link, edit form gains a team-scoped picker; create-new CTA stays admin-only (#1324).

## i18n stabilisation arc (v4.20.72 + .77–.78 + .93 + gates)

The audit-4 translator bundle landed 672 Dutch msgstrs across 11 surface batches (#1279 + 10 siblings, v4.20.77), with a msgctxt hotfix for the demo/PDP `Promote` collision (v4.20.78). The weekly drift report + PR-time drift gate shipped as v4.20.72 (#1223). When `i18n-sync.yml` kept failing post-merge on duplicate-msgid landmines, the PR gate learned to surface msgmerge fatal errors + run msguniq (#1338), and the landmines were cleared — 7 Dutch-literal msgids converted to English, 29 Dutch→Dutch obsolete pairs purged (#1339, v4.20.93). v4.20.95 itself repairs two regressions from that arc (a stderr line interleaved into the .po by the #1339 sweep, and a duplicate `Tournament` msgid that raced the gate).

## Release pipeline — PUC auto-update fixed (#1376, #1318)

PUC on pilot sites checks the latest GitHub **release**, but releases required manual tag pushes and lagged main by dozens of versions — auto-update was structurally broken. New `auto-release.yml` publishes a release (tag created via the release API) on every version bump that lands on main; idempotent against existing releases; the manual tag path stays. Supporting fixes: the legacy-sessions CI gate stopped tripping on every rename-away migration (blanket migrations-dir exclude, #1318), and audit-1's phantom-entity/cap-without-entity CI harness shipped as v4.20.71 (#1191).

## Activities repository extraction (#1320, v4.20.91 + .94)

Option-B per-surface extraction under way: `listForPlayer` (player profile Activities tab) and `listRecentCompletedForPlayer` (hero popovers + status capture) moved into `ActivitiesRepository`; remaining slices tracked on the issue.

## Other

New-activity wizard gains an AttendanceRosterStep with guest disclosure (#1297, v4.20.76). Team planner redirect snaps to the saved activity's week (#1271). Player profile date helpers guard the zero-date sentinel (#1281). `preferred_foot` lookup-type slug consolidation across six callsites (#1278). Audit-11 player-picker pattern coverage doc (#1296).

---

# TalentTrack v4.20.51 — Architectural audit drain, REST security hardening, scope-filter consistency

Cumulative release covering every ship since v4.20.21 (2026-06-03). Thirty patches across the **architectural-audit drain** (10 audits filed, ~47 follow-up issues, 28 fixes shipped) plus four follow-ups to the v4.20.21 pilot-feedback batch. No new feature epics — this release is consolidation: cross-cutting bug families surfaced by the audits and the REST-security class flagged by audit 2. **No operator-breaking changes** — no schema migrations, no capability matrix mutations, no API contract changes.

## Architectural audit infrastructure (#1175 - #1184)

Ten audits filed against the v4.20.21 codebase, each producing a `docs/audits/2026-06-audit-N-<slug>.md` findings doc and a slate of `ready-for-dev` follow-up issues. Audit numbering:

1. Authorization matrix entity catalogue completeness (#1175)
2. REST controller cross-club rewrite class (#1176) — flagged 5 critical CVEs
3. Standard reports scope-filter parity (#1177)
4. i18n hardcoded English literals (#1178)
5. Wizard reactivity (#1179)
6. Persona-dashboard KPI deep-link parity (#1180)
7. Entity scope-filter consistency across reads (#1181) — 7 follow-ups
8. Cross-entity picker privacy (#1182)
9. Form save/cancel + redirect-shape polish (#1183)
10. Documentation surface drift (#1184)

The findings docs ship in `docs/audits/` for future reference. The audit drain ran autonomously overnight with a cron-triggered queue executor.

## Audit 2 — REST security: cross-club rewrite class closed

Five REST controllers accepted attacker-controllable `player_id` / `team_id` / `tournament_id` without scope checks. Single-tenant pilot blunts impact today (`CurrentClub::id()` resolves to 1), but the SaaS-readiness contract (CLAUDE.md §4) requires these closed pre-emptively:

- **#1197 / v4.20.37** — `EvaluationsRestController::update_eval` + `delete_eval` skipped `club_id` in WHERE; `update_eval` never re-ran the `coach_owns_player` gate that `create_eval` enforces. A coach in club A who knew an eval id from club B could rewrite or soft-archive it.
- **#1198 / v4.20.38** — `GoalsRestController::create_goal` accepted any `player_id`; no club lookup, no coach roster gate for non-admins.
- **#1199 / v4.20.39** — `TournamentsRestController::update_assignments` inserted `tt_tournament_assignments` rows with `player_id` straight from the payload; off-squad player_ids now silently drop.
- **#1200 / v4.20.40** — `TeamsRestController::add_player_to_team` accepted any `team_id` from the URL path; cross-club reassign now 404s with `team_not_found`.
- **#1201 / v4.20.41** — `FrontendTrialsManageView::handlePost` accepted `player_id` from POST without club validation; trial cascade now starts from a verified-in-club player_id.

Each fix adds `QueryHelpers::get_*` lookup (which is club-scoped) before mutating; non-admin writers get the existing `coach_owns_player` gate. Error responses (403 `forbidden_player`, 404 `team_not_found`) stay backwards-compatible with create-side shapes so the JS handler doesn't need updates.

## Audit 7 — Entity scope-filter consistency across reads (8 fixes)

Eight reads across coach, admin, and parent surfaces silently mixed archived rows, guest call-ups, and (post #788 ship 2) planned-vs-actual attendance into operational queries. The canonical reference is `TeamRosterTableWidget.php:229-243` — every other read now mirrors that scope:

- **#1222 / v4.20.44** — 4 `tt_activities` reads (KPI snapshot exporter, season-summary KPI strip, per-team match_count column, PDP activities-timeline) add `archived_at IS NULL`.
- **#1224 / v4.20.45** — `CommsScheduledCron` attendance-flag + goal-nudge detection get `att.is_guest = 0`, `att.record_type = 'actual'`, `a.archived_at IS NULL`, plus cross-tenant `pl.club_id = ...` join condition.
- **#1225 / v4.20.46** — `PlayerDashboardView` tabs (evals, goals, attendance) add `archived_at IS NULL` filters; attendance adds `record_type = 'actual'`.
- **#1226 / v4.20.47** — `PeopleRepository::list()` default-hides archived rows; mirrors `PeopleRestController::list_people`. Fixes the parent-link picker and functional-roles surface offering archived parents.
- **#1227 / v4.20.48** — 3 `tt_attendance` reads (player profile KPI tile, activity edit form's per-player attendance map, admin Activities page roster) add `record_type = 'actual'` for stability through #788 ship 2.
- **#1228 / v4.20.49** — `FrontendPdpManageView::renderActivitiesTimeline` adds `is_guest = 0` + `record_type = 'actual'`.
- **#1230 / v4.20.50** — `Wizards\TeamBlueprint\SetupStep` team picker adds `archived_at IS NULL`.
- **#1232 / v4.20.51** — `ReportsPage::runLegacy` "Top 10 players" fallback adds `pl.archived_at IS NULL`; `FrontendComparisonView` misleading pre-#0038 comment rewritten.

Per-helper `club_id` WHERE clauses were deliberately NOT added across this slice. Per #1188 below, tenancy is enforced at the request layer in SaaS, not by individual repository helpers.

## Audit 6 — Persona-dashboard KPI deep-link parity (6 fixes)

#1207 surfaced the foundation bug: `KpiCardWidget` never honoured `linkUrl()` overrides — every per-KPI deep-link fix landed since v3.50.x silently no-op'd in the dominant placement. Fix routes through a new `AbstractWidget::kpiHrefFor()` helper that prefers `KpiDataSource::linkUrl()` over `linkView()`. The five downstream fixes (#1209-#1213) re-enable filter parity between dashboard tiles and their destination views:

- **#1207 / v4.20.22** — `KpiCardWidget::kpiHrefFor()` helper introduced; 11 KPIs migrated to use it.
- **#1209 / v4.20.23** — `ActivePlayersTotal` carries `filter[status]=active`.
- **#1210 / v4.20.24** — 5 academy KPIs (EvaluationsThisMonth, NewEvaluationsThisWeek, AttendancePctRolling, RecentAcademyEvents, GoalsByPrincipleKpi) ship `linkUrl()` overrides with date-window deep-links matching #771's pattern.
- **#1211 / v4.20.25** — `OpenTrialCases` carries `status=open,extended`.
- **#1212 / v4.20.26** — `MyTeamAttendancePct` + `MyTeamAvgRating` pass `filter[team_id]` to destination.
- **#1213 / v4.20.27** — `MyEvaluationsThisWeek` aligns 7d window with destination.

## Audit 3 — Standard reports AC scope leak

Two of the analytics module's reports leaked academy-wide data to the assistant-coach persona (same family as #1147 closed in v4.20.4):

- **#1187 / v4.20.29** — `FrontendStandardReportsView` 6 slug handlers + launcher gain a `scope()` helper that narrows via `get_teams_for_coach` for non-admins. AC-only team/player pickers replace the academy-wide pickers.
- **#1193 / v4.20.34** — `FrontendMinutesTeamReportView` `listTeams()` + URL-tamper guard close the same shape on the minutes-team report (shipped slightly later via #1034, missed by v4.20.4's pass).

## #1188 / v4.20.30 — SaaS-readiness direction-setter

`QueryHelpers::get_player()` historically required a strict `club_id = CurrentClub::id()` match, drifting from the on-screen player loader which doesn't. The drift surfaced as #1149 (Print doelenintake "Player not found" despite player profile rendering) and a family of follow-up scope-mismatch bugs. **Fix** drops the strict club_id clause from `get_player`. **Implication beyond the fix**: this set the direction for every subsequent audit-7 follow-up — per-helper `club_id` filtering is being phased out in favour of request-layer enforcement, which is the right tenancy model for SaaS (CLAUDE.md §4). Inline `What this is NOT` notes throughout the audit drain cite #1188 so subsequent edits don't reflex-revert.

## Audit 1 — Authorization matrix entity catalogue

The matrix admin UI's "no tile uses this entity" warning fired on 17 false-orphan entries because their consumer pages are wp-admin surfaces using either a WordPress cap (`administrator`, `manage_options`, `read`) or a `tt_*` cap that maps via `LegacyCapMapper` to a different entity (e.g. Spond admin uses `tt_edit_teams`).

- **#1189 / v4.20.31** — `CoreSurfaceRegistration` exports tile entity aligned to `reports`. Closes the non-admin-denial half of the bug class.
- **#1192 / v4.20.33** — `MatrixEntityCatalog::ADMIN_ONLY_ENTITIES` widened with 17 entries (`roles`, `authorization_matrix`, `matrix_preview_apply`, `backup`, `demo_data`, `custom_css`, `impersonation_action`, `usage_stats_details`, `documentation`, `persona_templates`, `rating_scale`, `translations`, `translations_config`, `custom_widgets`, `football_actions`, `spond_integration`, `thread_messages`), each with an inline comment naming the consumer surface.

## Audit 9 — Form save/cancel + redirect-shape polish

- **#1195 / v4.20.35** — `FrontendTestTrainingsView` post-save redirect `dashboard` → `list` (same bug class as #795). The `dashboard` shape was unparsed by `public.js` so saves succeeded but the operator saw a blank form.
- **#1196 / v4.20.36** — 3 Cancel buttons (tournament create/edit, VCT defaults card, PHV flag panel) now honour `tt_back` per CLAUDE.md §6 point 5.

## Audit 8 — Cross-entity picker privacy

- **#1202 / v4.20.42** — `FrontendTeamBlueprintsView` "Other team" picker narrows to coach scope. Head-coach editing their own blueprint could browse the entire academy roster across every other team — a privacy leak under CLAUDE.md §1 (minors).

## Audit 4 — i18n

- **#1220 / v4.20.43** — 38 `wp_die()` English literals across Development + Invitations handlers (`IdeaPromoteHandler`, `IdeaRefineHandler`, `IdeaRejectHandler`, `IdeaSubmitHandler`, `TrackDeleteHandler`, `TrackSaveHandler`, `InvitationAcceptHandler`, `InvitationCreateHandler`, `InvitationRevokeHandler`, `MessageSaveHandler`) wrapped in `__()` + 2 misc (`BaseController` field-required sprintf, `BackupSettingsPage` unknown-error fallback). 5 new msgids ship with Dutch translations.

## Audit 5 — Wizard reactivity

- **#1186 / v4.20.28** — `tournament-wizard.js` `rebuildChipHidden` dispatches a change event on the hidden CSV input so autosave fires.

## Architecture — ActivitiesRepository extraction

- **#1190 / v4.20.32** — New `Activities\Repositories\ActivitiesRepository` (`findById`, `listRosterAttendance`, `attendanceMapByPlayer`) shared between `FrontendActivitiesManageView` and `ActivityBriefPdfExporter`. Closes the data-source divergence that produced subtle differences between the edit form and the brief PDF.

## What's not in this release

- **i18n batches #1204-#1219** — Translator-quality work for 10 follow-up batches the audit-4 drain queued. Skip-flagged: needs human review for Dutch nuance, not autonomous patching.
- **#1191 / #1223** — Workflow file edits flagged by audits 1 + 7. Blocked by the release.yml self-modification guardrail.
- **#1194** — Multi-day UI build flagged by audit 9. Out-of-scope for a patch-level audit drain.
- **#1017 / #1129** — Chemistry algorithm (design call needed) + VCT-8 catalogue seed (content-heavy, pilot-coach review gated).
- **#1221** — Direction ambiguous post-#1188's loosened `get_player()`; skip-flagged with three possible directions documented on the issue.

## Upgrade notes

No schema migrations. No matrix seed changes. No new caps. No new tiles. Drop the new zip in place; PUC handles the rest.

---

# TalentTrack v4.19.9 — VCT Phase 2, standard reports, pilot polish

Cumulative release covering every ship since v4.17.2 (2026-05-31). Twenty-two patches across three feature epics — the **VCT module Phase 2 UI**, the **standard-reports module** (12 reports across 2 PRs), and the **2026-06-03 pilot-feedback batch** — plus three rounds of authorization-scope refinement and the foundation rewrites that unblocked them (touch-friendly rating input, lookup-translation completeness, match-prep print rebuild).

The plugin version on disk advanced one minor (4.18.x = VCT Phase 2 UI) and a second minor (4.19.x = standard reports) since v4.17.2; this release rolls both up to a single tag. There are **no operator-breaking changes** — three new schema migrations (0140 PHV extension, 0141 + 0142 lookup-translation backfill, 0143 AC seed trim, 0144 activity time fields) all ship as additive + idempotent.

## VCT module — Phase 2 UI complete (#905)

The Voetbal Conditionele Training module's safety-critical core (schema, rules engine, REST, workflow task) shipped in Phase 1 before v4.17.2. This release closes the Phase 2 UI epic across **eight child PRs**:

- **VCT-12 — Configuration tiles** (#1087, v4.18.1). Two new HoD-gated tiles on the Configuration grid linking into the existing `?tt_view=vct-config` sub-tabs (macro-blocks + age-profiles), with live counts and a NEW pill matching the `.local-mockups/vct-config-tiles/` design-of-record.
- **VCT-13 — Team-defaults panel** (#1088, v4.18.2). Inline panel on team detail with weekday chips + default start time + duration, driving the new-VCT-session wizard's basis-step prefill. Cap-gated on `tt_vct_admin_library`.
- **VCT-14 — PHV per-player panel + hero pill** (#1089, v4.18.3). Schema migration 0140 adds `reason_key` + `intensity_ceiling` columns; Profile-tab panel with reason picker + ceiling dropdown + notes; orange `PHV` pill on the hero when active. Privacy gating per CLAUDE.md §1 (other parents see nothing, AC-also-parent sees own kid via parent persona only).
- **VCT-11 — Exercise library inline edit + search + intensity edge** (#1086, v4.18.4). Each library row gets an inline edit form, a client-side search input, and a 4px intensity-band coloured edge keyed to the mockup's intensity ramp.
- **VCT-9 — New-VCT-session wizard step 1 start time** (#1084 first slice, v4.18.5). Step 1 picks up an optional start-time field; prefills from the team's VCT defaults (#1088). Persists through `VctTrainingComposer` to `tt_vct_sessions.start_time`.
- **VCT-10 — Sideline PHV exclusion banner** (#1085 first slice, v4.18.6). Coach-view banner lists actively-flagged players on the team roster so the sideline reads the same data `WorkloadCapRule` enforces.
- **VCT epic closeout — docs + spec move** (#905, v4.19.3). New `docs/vct.md` (en + nl) with the per-surface URL map, capability matrix, shipped-feature index, parked follow-ups, and the inter-surface data-flow narrative; spec moved to `specs/shipped/0095-feat-vct-module.md`.
- **VCT-8 catalogue seed spun out** as #1129 (content-heavy, gated on pilot-coach review). The engine functions correctly with operator-added exercises today; the catalogue seed is an accelerator, not a blocker.

## Standard reports module — 12 reports across 2 PRs

The standard-reports mockup batch (#1063) shipped its implementation half:

- **6 explorer-bound presets** (#1119, v4.19.0) covering `evaluations_received`, `goal_progress`, `activity_volume`, `evaluation_coverage`, `attendance_vs_squad`, `prospects_logged_per_scout`. Each preset registers a new KPI keyed against the mockup vocabulary and adds an "Explorer →" button on the relevant entity surface (player Goals/Evaluations tabs, team detail, activity detail, Reports launcher). Central URL builder `\TT\Modules\Analytics\Domain\ExplorerUrl::build()` keeps every preset call site to two lines.
- **6 curated per-persona reports** (#1120, v4.19.1) — Player Minutes played, Team Minutes distribution, Team Squad evaluation summary, Season summary, Season Trial funnel, Scout report card. Slug-dispatched on `?tt_view=standard-report&slug=<key>` with shared chrome (KPI strip, empty state, entity pickers when player_id/team_id is absent). Every curated view's "Explorer →" action lands on the matching preset KPI from v4.19.0 with the same entity filter pre-applied.

Each report inherits the host surface's cap gate; the explorer re-checks `tt_view_reports` + the KPI's `context` (COACH / ACADEMY / PLAYER_PARENT). No new permission surfaces.

## 2026-06-03 pilot-feedback batch — 7 issues, 4 PRs

Pilot triage on 2026-06-03 raised seven issues around the activity surfaces, teamplanner, and methodology principles; all closed across four PRs:

- **Planner bugs** (#1133, v4.19.6) closes #1121 (`LabelTranslator::activityType()` routed through `LookupTranslator::byTypeAndName` so operator-added activity-type rows render their Dutch label), #1124 (planner team list scope-filters via `QueryHelpers::get_teams_for_coach()` for non-admins; admins unchanged — was leaking sibling teams to AC users after #1060), and #1127 (planner activity query gains `archived_at IS NULL` so archived activities stop rendering as cards).
- **Principles render polish** (#1134, v4.19.7) closes #1123 (activity detail's linked principles move from an inline `<dt>/<dd>` to a dedicated "Gekoppelde spelprincipes" section with linked-pill palette keyed off the code's first letter) and #1125 (planner card chips show the bare code + bucket colour, up to 4 per card with `+N` overflow).
- **Two-level principle picker** (#1135, v4.19.8) closes #1122. Both `PrinciplesStep` (new-activity wizard) and `FrontendActivitiesManageView::renderForm` (edit form) replace the hold-Ctrl flat multiselect over 18 principles with a stack of `<details>` sections — one per team function — with a small Dutch-cap label per team-task sub-bucket and one checkbox per principle inside. 44px minimum row height so each principle is a real tap target on phones.
- **Activity start/end time fields** (#1136, v4.19.9) closes #1126. Migration 0144 adds `start_time` + `end_time` (both nullable TIME columns) after `session_date`. Wizard step + edit form gain optional time inputs; activity detail, team detail Aankomende activiteiten, planner card, and the REST payload all render the time window when set. Empty fields render nothing — no placeholder.

## Authorization-scope refinements

Three rounds of AC scope-creep audit followed up on #1060's foundational tightening:

- **#1105 / v4.17.3** removed `podium_panel` from the AC default seed — the Podium tile linked to an evaluations-derived leaderboard AC could no longer read.
- **#1106 / v4.19.5** completed the per-entity audit confirming `rate_cards` + `compare` REMOVE (both aggregate development-judgment data) while `reports` / `people` / `vct` KEEP (operational, gated at the next layer or shared with HC by spec). Migration 0143 mirrors #1105's idempotent + `is_default = 1`-only DELETE pattern.
- **#1107 / v4.17.5** locked down the player-detail view's Evaluations / PDP / Trials tabs + avg-rating KPI so they cap-check at render time, not just at the tab-set generator. Defense in depth.
- **#1104 / v4.17.4** added `ORDER BY id DESC` to `AuthorizationService::getPersonIdByUserId` (deterministic resolution when a WP user has multiple active `tt_people` rows) + migration 0139 dedupes existing rows. Closes the AC-dashboard-empty silent disagreement between resolver and admin Persoon edit page that took three hours to diagnose during pilot.
- **#1102 / v4.17.6** added a green-check / amber-warning hint to the persona dashboard editor when a widget's cap is invisible to the persona's default WP role. Editor-time signal so admins don't ship layouts AC users can't see.

## Lookup-translation completeness (#902)

`tt_translations` had three distinct gaps the operator-facing Lookups admin exposed:

- **Gap 1 — positions.** Migrations 0086/0106/0109 called `__('GK')`, but `.po` files only have msgids for the long forms ("Goalkeeper"); the gettext-equal-source guard skipped every INSERT. Migration 0141 drives the long form through gettext via `LabelTranslator::positionLongForm()`.
- **Gap 2 — player values.** The 8 values seeded by migration 0031 (`Commitment`, `Coachability`, etc.) were never wrapped in `__()`. Migration 0142 ships hardcoded translations across nl_NL / fr_FR / de_DE / es_ES; new `LabelTranslator::playerValueLabel()` anchors them for future extractor coverage.
- **Gap 3 — fr/de/es position translator content.** 10 of 11 positions had empty `msgstr` in fr/de/es `.po` files. Filled with standard football vocabulary (Défenseur central / Innenverteidiger / Defensa central, etc.).

## Match prep print rebuild (#1059)

PR #1041's "align browser print to the legacy `MatchPrepPdfExporter` template" decision had both outputs consistent but consistently wrong vs. the on-screen view. New `MatchPrepPrintableRenderer` (v4.19.4) is the single source of truth — formation pitches per half (reusing `FrontendMatchPrepView::defaultSlotLayouts()`), Dutch labels (Algemeen / Aanvallen / Verdedigen / Spelhervattingen ×2), one row per available player on the "Doen per speler" column. `MatchPrepPdfExporter` delegates to the same renderer so print + PDF stay in lockstep going forward.

## Touch-friendly rating input (#1067 / v4.18.0)

Replaces typed-number rating inputs with a chip-grid + inline-slider component wherever a coach captures a rating. New `\TT\Shared\Frontend\Components\RatingInputComponent` ships two render methods: `renderSingle()` emits an 11-chip grid for a single overall rating (no keyboard, one-tap commits a final value); `renderListRow()` emits a label + range slider + tabular value-readout row that fits a 360px viewport with all four canonical category names. Slider rows track an empty state (`data-tt-rating-empty="1"`) so unrated values don't post. Dropped into `PostGameEvaluationForm`, `PlayerSelfEvaluationForm`, `RateActorsStep`, and `HybridDeepRateStep`. Server-side validators upgraded to floats + snap-to-0.5; `EvaluationInserter` mirrors the float+snap before writing.

## Other ships within this release

- **v4.17.0** — printable season-start goal-setting intake + selectable methodology reference card (#1064). Per-player A4 portrait (snapshot + 3 goals + reflection) and team-batch concatenation.
- **v4.17.1** — per-eval-type category allowlist (#819). New `tt_eval_type_categories` join table + admin matrix; wizard filters the category list per eval type.
- **v4.17.2** — `LookupTranslator` into Evaluations repository (#806 first slice). `EvaluationsRepository::recentForCoach()` now pulls + localises lookup-backed fields at the repository boundary so view code that does `echo $row->type_name_localised` gets the localised string by construction. (Architectural worked example; four follow-up tickets file the same pattern in Goals / Activities / Players / PDP repos.)

## What's not in this release

- **VCT-8 — 80-exercise per-club catalogue seed.** Content-heavy, gated on pilot-coach methodology review. Tracked as #1129.
- **Phase 2 mockup-fidelity polish** items the VCT child PRs documented as deferred — wizard MD-context chip-bar visualization, bottom-sheet exercise picker, current-block teal highlight, live timer on the coach view, A4/A6 print polish. Each ships when pilot reports the friction.
- **Per-report cap audit** inside the Reports launcher. Some legacy reports may not check the per-entity cap at the next layer; tracked as a follow-up if pilot finds a leak.

## Upgrade notes

Four schema migrations land in this release. All additive + idempotent; no operator action required:

- **0140** — `tt_player_phv_flags.reason_key VARCHAR(64)` + `intensity_ceiling TINYINT` (VCT-14).
- **0141 + 0142** — backfill `tt_translations` for positions + player values across nl_NL / fr_FR / de_DE / es_ES.
- **0143** — DELETE `(persona='assistant_coach', entity IN ('rate_cards','compare'), is_default=1)` rows from `tt_authorization_matrix`. Operator overrides (`is_default=0`) survive.
- **0144** — `tt_activities.start_time TIME` + `end_time TIME`, both NULL.

`MatrixRepository::clearCache()` fires at the end of 0143 so in-flight AC sessions pick up the change on their next request.

---

# TalentTrack v4.16.0 — Assistant Coach scope tightened to operational-only (closes #1060)

Default authorization matrix defaults change. **AC is operational, HC is development.**

The assistant coach persona inherited too much of the head coach's read access — evaluations, PDP files, behaviour ratings, team chemistry sandbox. The pilot raised this in the context of an AC who is also a parent of a player on the same / sibling team: the kid's evaluations are HC professional-judgment data + safeguarding territory, and shouldn't be visible to the AC even if (or especially when) they're a parent. The fix is broader than that single case — AC's job is operational (run trainings, manage attendance, prep matches, take VCT sessions), HC's job is development (rate, plan PDP, set per-player goals).

## What ships

**Matrix seed change** (`config/authorization_seed.php`) — the AC persona block loses these entities and tile-visibility panels:

- `evaluations` — HC's per-player ratings.
- `pdp_file`, `pdp_verdict`, `pdp_conversations` — Personal development planning + verdicts (safeguarding territory).
- `team_chemistry` — chemistry sandbox + blueprint reads.
- `dev_ideas` — development authoring (AC was previously able to create ideas).
- `player_behaviour_ratings` — behaviour data is dev signal.
- `evaluations_panel`, `team_chemistry_panel`, `pdp_panel` — tile-visibility entities that would render empty tiles without the data caps above.

AC keeps every operational entity (team, players-identity, people, activities, goals, attendance, methodology, reports, rate_cards, compare, documentation, workflow, my-evaluations self-scoped, player_status, trial_inputs, player_timeline, invitations, player_notes, vct, every staff-development entity) plus `pdp_calendar_export` at `self` scope (AC exports own calendar slots).

**Backfill migration** (`database/migrations/0136_assistant_coach_scope_tightening.php`) — DELETEs the 10 removed AC rows from `tt_authorization_matrix` on existing installs, **scoped to `is_default = 1`** so any row an operator explicitly customised via the Authorization admin stays. Flushes the matrix read cache via `MatrixRepository::clearCache()` so AC sessions pick up the change on the next request. Idempotent; forward-only (reverting would re-grant AC access to development data, which is the safeguarding regression this closes).

**No per-surface code changes** — every gated view already routes through `current_user_can()` or `MatrixGate::can()`. Removing matrix rows automatically blocks:

- The Evaluations tab on the player profile (gated on `tt_view_evaluations`).
- The PDP tab + PDP file uploads (gated on the `pdp_file` matrix entity).
- Behaviour ratings card / rate-actor wizard (gated on `tt_rate_player_behaviour` + the `evaluations` matrix entity respectively).
- Team chemistry sandbox + per-blueprint editor (gated on `tt_manage_team_chemistry`).
- The `dev_ideas` authoring surface.

Match-prep and match-execution surfaces are unchanged — those gate on `match_prep` / `match_execution` entities (kept for AC), so HC's per-player notes still flow through to AC inside those operational windows. The AC-with-kid case is handled by the existing parent role: as parent of their own kid the AC sees that kid's evaluations/PDP via the `'parent'` persona block, independent of the AC matrix.

## Verification

Use `wp-admin/admin.php?page=tt-auth-chain-debug`, pick an AC user from the dropdown, confirm these caps return false post-migration:

- `tt_view_evaluations`
- `tt_edit_evaluations`
- `tt_view_player_behaviour`
- `tt_view_pdp`
- `tt_edit_pdp`
- `tt_manage_team_chemistry`

And confirm these stay true (operational entities):

- `tt_view_activities`, `tt_edit_activities`, `tt_mark_attendance`
- `tt_edit_match_prep`, `tt_edit_match_execution`
- VCT caps
- `tt_view_player_notes`

## Out of scope (follow-up issues if pilot raises)

- **Per-tab visibility on the player profile** when AC reaches a player's page. The Goals tab still renders since `goals` matrix entity stays operational (match-prep flow needs it). If pilot reports the tab feeling out of place, file a follow-up that introduces per-tab gating distinct from data-entity gating.
- **`tt_drill_analytics` cap** for the explorer view per §3 of #1060. Current behaviour: explorer view gates on the `analytics` matrix entity (kept HC-only). The optional belt-and-braces cap is a follow-up.
- **Aggressive migration**: today's migration preserves operator customisations. An aggressive variant that flips every AC row regardless of `is_default` is available if a future install needs the harder reset (e.g. SaaS multi-tenant onboarding).

## Pilot impact

AC users see the same operational surfaces they always did (activities, attendance, match prep/execution, methodology library, VCT, their own calendar + staff development). They no longer see other players' evaluations, PDP files, behaviour ratings, or the team chemistry sandbox. The AC-also-parent case sees their own kid's development data through the parent persona, unchanged from before.

---

# TalentTrack v4.13.0 — Team chemistry page rework, single-tier blueprint port (closes #1002, supersedes #1007)

Full surface rework of `?tt_view=team-chemistry`. Ports the design-of-record mockup at `.local-mockups/team-chemistry/index.html` onto the live surface: three-column shell with a roster sidebar on the left, the pitch in the centre, and a stacked KPI scoreboard plus coach-marked pairings panel on the right. The chemistry surface is single-tier — the chemistry engine scores primary cells only, so the secondary / tertiary tier stack the blueprint editor exposes is irrelevant here. Each pitch position renders one slot card.

## What ships

**PHP — view rebuild**

- `src/Modules/TeamDevelopment/Frontend/FrontendTeamChemistryView.php` (rewrite) — replaces the v1 single-column inline-styled layout with a mockup-driven three-column grid. New methods: `renderToolbar()` (formation picker + style summary + Suggested / Try-a-lineup segmented toggle + Save-as-blueprint), `renderRosterSidebar()` (sorted by best team-fit score, searchable), `renderPitchCard()` (hands off to `PitchSvg` with the legend chrome), `renderRightColumn()` + `renderScoreboard()` (the headline link-chemistry card plus composite / formation / style / depth / coverage sub-cards from the mockup) + `renderPairingsCard()` (inline coach-pairings list with a collapsible add form). The depth-chart table is dropped — the data still flows to the picker via the localised payload, but the standalone three-column "1st / 2nd / 3rd choice" table is gone in favour of the per-slot picker.
- Asset enqueue moved out of the cap-gated sandbox path. The chemistry CSS now enqueues on every entry to the view (team picker + board + error states) so styling is consistent everywhere; the JS still cap-gates on `tt_manage_team_chemistry`.

**JS — selector retarget + new wiring**

- `assets/js/frontend-team-chemistry.js` (rewrite) — selectors retargeted from `.tt-chem-sandbox*` to `.tt-tc-sandbox*`. New `wireSegmentedToggle()` replaces the v1 single-button `wireToggle()` and binds both segments (Suggested / Try a lineup) instead of toggling one. New `wireRosterFilter()` does live substring filtering on the sidebar (case-insensitive, name + position). New `wireFormationAutosubmit()` replaces the v1 inline `onchange="this.form.submit()"` so CSP-strict installs work. Sandbox + bottom-sheet picker + save-as-blueprint modal behaviour is unchanged from v3.110.174 / v3.110.184; only the surface they bind to has moved.

**CSS — mockup port**

- `assets/css/frontend-team-chemistry.css` (rewrite) — full token system from the mockup (`--tt-tc-bg`, `--tt-tc-panel`, `--tt-tc-line`, `--tt-tc-accent`, `--tt-tc-accent-2`, `--tt-tc-strong`, `--tt-tc-weak`, etc.). Mobile-first base CSS for ~360px; tablet at 768px (two-col with right column going full-width below); desktop at 1180px (three columns, right column sticky). Touch targets ≥ 44px on every interactive surface (toolbar select / segmented buttons / pairing form inputs / pairing-x remove). 16px input font-size for iOS no-zoom on focus. `prefers-reduced-motion` honoured on the picker + sandbox-on slot animations. The bottom-sheet picker + save-as-blueprint modal styles are preserved from v3.110.174 / v3.110.184 with the new token names.

## Bugs caught + fixed from #1007 (supersedes)

The `?tt_view=team-chemistry` v1 surface had four investigation-grade defects the rework folds in alongside the layout port:

1. **Inline `onchange="this.form.submit()"` on the formation dropdown.** Breaks under CSP `script-src 'self'` and produces a console warning on stricter dashboard themes. v4.13.0 replaces it with a `data-tt-tc-autosubmit` attribute handled by the chemistry JS.
2. **Team picker had no stylesheet enqueued.** The CSS file was only loaded inside `enqueueChemistrySandboxAssets()`, which was cap-gated on `tt_manage_team_chemistry`. Read-only viewers landing on the team picker saw unstyled `<a>` cards. v4.13.0 enqueues `frontend-team-chemistry.css` from the top of `render()` so every code path picks it up.
3. **No empty-state for installs without `tt_formation_templates` rows.** v1 emitted a one-line `tt-notice` and returned, with no styling. v4.13.0 renders a `.tt-tc-emptystate` card with a clear "Configure one in Settings → Team development" pointer.
4. **Help link button "How does this work?" stacked above the board.** Pushed the toolbar + pitch down 60px on phones for no benefit. v4.13.0 moves the help row below the board so the chemistry score is the first thing on screen.

## Out of scope

- Chemistry algorithm: unchanged (`BlueprintChemistryEngine::computeForLineup()` / `computeForSuggested()`, `ChemistryAggregator::teamChemistry()`).
- REST contracts: unchanged. `GET /teams/{id}/chemistry`, `POST /teams/{id}/chemistry/preview`, pairings CRUD, blueprint create + assignments PUT — all hit unchanged.
- Schema: no migration.
- Caps: same — `tt_view_team_chemistry` for read (dispatcher-gated), `tt_manage_team_chemistry` for sandbox + pairings CRUD.
- Multi-team chemistry comparison: separate ship if asked.
- Per-player chemistry detail drilldown: separate surface.
- The reasoning panel in the mockup ("Why?" with Default / Slot / Link states) is shape-only and deferred to a follow-up — the mockup's `body[data-sel]` switch is a JS state machine that needs server-side explanation strings the engine doesn't currently emit. Tracked separately.

## Why minor bump

Meaningful surface rework + restored functionality on a previously-broken page (#1007). Patch bump would understate the visual + interaction change.

# TalentTrack v4.12.15 — Match prep print polish + short player names (closes #1023)

Two scopes ship in one PR because they share files (the match-prep view + CSS) and the on-screen short-name change is what the print CSS inherits.

## A. Print polish (six items)

1. **Hide the dashboard brand banner / DEMO strip / breadcrumbs on print.** The `@media print` block now adds `display: none !important;` to `.tt-dash-header`, `.tt-dash-brand`, `.tt-dash-actions`, `.tt-dash-demo-pill`, `.tt-dash-help`, `.tt-user-menu`, `.tt-back-pill` on top of the existing `.tt-breadcrumbs` / `.tt-back-link-wrap` / `.tt-mp-toolbar` rules. The shared dashboard chrome (rendered by `DashboardShortcode::render()`) was leaking the JG4IT brand row + tagline + DEMO pill onto every printed page.
2. **Page title is now the first line on paper.** Source string changed from `Match prep — %1$s · %2$s` to `Match preparation — %1$s · %2$s` so the Dutch translation `Wedstrijdvoorbereiding — …` lands as the first visible printed line at 12pt bold (≈16px), no top margin. CSS rule `.tt-match-prep-title { font-size: 12pt !important; margin: 0 0 3mm !important; }` inside the print block.
3. **Player-name labels visible on both pitches.** The on-screen `.tt-mp-slot .tt-mp-slot-name` uses translucent backgrounds and inherited colours; the print block now forces `color: var(--tt-mp-ink) !important; background: #fff !important;` plus `-webkit-print-color-adjust: exact` so the slot number circle AND the player-name label both render on paper.
4. **Restore `!` (red) and camera (green) icon colours on print.** `.tt-mp-dps tbody td.tt-mp-col-spec.tt-mp-on` forces `color: var(--tt-mp-danger)` and `.tt-mp-dps tbody td.tt-mp-col-cam.tt-mp-on` forces `color: var(--tt-mp-success)`, both with `print-color-adjust: exact;` so they survive the "Background graphics off" default in print dialogs.
5. **Compact Wedstrijddoelen so it fits one landscape-A4 page.** Goal-box font 9pt → 7.5–8pt; row padding halved (`0.25mm 1mm`); section heading padding halved (`0.5mm 2mm`); `.tt-mp-goals-row` forced to two columns so attacking + defending sit side by side; grid columns tightened from `50mm / 1fr / 70mm` to `48mm / 1fr / 64mm`; body font 10pt → 9pt; line-height 1.25 → 1.2. The whole spreadsheet now fits on one landscape-A4 sheet at 100% print scale on Chrome / Edge / Firefox.
6. **Empty goal lines print blank, no placeholder dots.** Placeholder text (`…`, `Goal 1…` etc.) is now `color: transparent !important; opacity: 0 !important;` on every print-time goal-line input via `::placeholder` / `::-webkit-input-placeholder` / `::-moz-placeholder`. The horizontal underline rule remains visible — coaches see a clean line to write into.

## B. Short player names (whole match-prep surface)

New helper `TT\Shared\Util\PlayerShortName` resolves a list of players into a `[ player_id => short_name ]` map:

- Default: first name only (`Daan`, `Senna`, `Javi`).
- Disambiguation: when two players in the input set share a first name, both render as `<firstName> <lastInitial>` (`Daan P`, `Daan A`). The disambiguation scope is the input set, not the whole club.
- Graceful fallback for players with missing first or last names (returns the available part, or `—`).
- v1 assumes Western "first last" order — East-Asian "last first" conventions deferred.

`FrontendMatchPrepView::render()` computes the short-name map once from the team roster and threads it into every render site:

- Roster column (Selectie · Minuten).
- Doen per speler column (Player focus).
- Rollen & standaardsituaties column (Roles & set pieces).
- Pitch slot labels — the bootstrap payload's `players[].name` is the short form, so the JS `renderPitches()` / `renderRoster()` / `renderDps()` / `renderRoles()` paths pick it up without code changes.
- Availability drawer (`renderDrawer()`) — same `state.players[].name` source, same short form, same vocabulary across every sub-surface.

The full name is still passed on the bootstrap as `players[].full` so a future view variant can show the long form if needed; current renderers only consume `name`.

## Files

- `src/Shared/Util/PlayerShortName.php` (new) — the short-name resolver.
- `src/Modules/MatchPrep/Frontend/FrontendMatchPrepView.php` — title string change, short-name map, threaded into roster / Doen / Rollen / bootstrap.
- `assets/css/frontend-match-prep.css` — print-block rewrite for items 1-6.
- `.local-mockups/match-preparation/index.html` — mockup parallel-tracked so the design-of-record stays current.
- `talenttrack.php`, `readme.txt` — version 4.12.12, changelog stanza.
- `CHANGES.md` — this stanza.

## Out of scope

- Other surfaces (Player profile, Activities list, Team detail) still use full names. The short-name helper is `Shared\Util` so future call sites can adopt it, but this PR is match-prep only per the spec.
- Per-locale name ordering (East-Asian "last first") — v1 assumes Western order.
- Retrofit of any other view's print-CSS — same six items might apply to VCT-print / match-execution-print, deferred.

## DoD

- [x] Print one A4-landscape page fits everything (CSS-spec'd via 8pt body / halved padding / 48mm-1fr-64mm columns / two-up goal grid).
- [x] No dashboard brand chrome on print (`.tt-dash-header`, `.tt-user-menu`, breadcrumbs, back-pill all hidden).
- [x] Page title is first visible line at 12pt bold (Wedstrijdvoorbereiding — … via Dutch translation of the new source string).
- [x] Player names appear on both pitches in print (forced `color` + opaque `background` + `print-color-adjust: exact`).
- [x] `!` red, camera green on print (`tt-mp-on` rules in print block).
- [x] Empty goal lines print as clean rules with no placeholder text.
- [x] On-screen + print: every player label uses the short form (resolver threaded into PHP renders + JS bootstrap).
- [x] `.local-mockups/match-preparation/index.html` mirrors the changes.
- [x] Patch bump v4.12.12.

(closes #1023)

---

# TalentTrack v4.12.10 — PHPStan rule enforcing vocabulary constants (PR-set 8 of 8 — closes #988 umbrella)

Final PR-set in the #988 umbrella migration. Lands the custom PHPStan rule that flags raw string-literal comparisons against any value already enumerated under `TT\Domain\Vocabularies\Lookups\*` or `TT\Domain\Vocabularies\Enums\*` — the regression gate that prevents PR-sets 1-7's work from silently un-doing itself as new code lands.

## What ships

**PHP - PHPStan rule**

- `tests/PhpStanRules/VocabularyConstantsRule.php` (new) — implements `PHPStan\Rules\Rule`. On first node visit, scans `src/Domain/Vocabularies/{Lookups,Enums}/*.php` via reflection and builds a flat index of `string value -> [Class::CONST, ...]` suggestions. Walks four AST node families on every analyse run:
    - `BinaryOp\Identical` (`===`) and `BinaryOp\NotIdentical` (`!==`).
    - `BinaryOp\Equal` (`==`) and `BinaryOp\NotEqual` (`!=`).
    - `FuncCall` to `in_array($needle, [ 'literal_1', 'literal_2' ], $strict)` — the most common allowlist shape in the codebase.
  For each `String_` operand whose value matches a known vocabulary value, emits one error per literal: `String literal 'present' matches a TalentTrack vocabulary value. Use the typed constant AttendanceStatus::PRESENT instead (umbrella issue #988).` Identifier `talenttrack.vocabularyConstants`. Tip text directs the reader to `src/Domain/Vocabularies/{Lookups,Enums}/` and acknowledges the deliberately-out-of-scope contexts (SQL string literals, array keys, migration seeds — those may be locally suppressed when the rule lands a false-positive).

  Out of scope by design:
    - `switch ( $value ) { case 'present': ... }` arms — walking `Stmt\Case_` nodes is straightforward but reserved for a v2 iteration once the rule has burned in.
    - SQL string literals inside `$wpdb->prepare()` arguments — DB is the canonical source of truth; the literal there IS the canonical value.
    - Array keys like `[ 'present' => __( 'Aanwezig', 'talenttrack' ) ]` — the key IS the canonical value; rewriting it to `AttendanceStatus::PRESENT => ...` is correct but is a separate sweep.
    - Default-parameter literals (`function ( string $status = 'manual' )`). Reachable later via a `Param` walk; out of scope for v1.

- `tests/PhpStanRules/vocabulary-constants-rule.neon` (new) — opt-in PHPStan overlay. Registers the rule via `services:` with the `phpstan.rules.rule` tag. NOT included from `phpstan.neon` by default — operators wire it on by including this overlay from their own local config (`includes:` array in `phpstan.local.neon`). The header comment in the .neon file documents the wire-up.

**PHP - autoload wiring**

- `composer.json` — gains an `autoload-dev` PSR-4 mapping for `TT\Tests\PhpStanRules\` -> `tests/PhpStanRules/` so PHPStan can resolve the rule class via the composer autoloader. The mapping is in `autoload-dev`, not `autoload`, so the runtime plugin classmap stays unchanged. `composer dump-autoload` is required locally to pick up the new map; CI's `composer install` step covers this automatically.

**Default-disabled rationale**

Per #988's locked decisions (2026-05-28), PR-set 8 ships as infrastructure but with the rule **disabled by default**. The backwards-compat allowlist documented in `docs/rest-api.md` keeps raw string-literal comparisons legal until the one-release deprecation window closes — flipping the rule into the default `phpstan analyse` run today would flood the build with errors on the same call sites the allowlist deliberately tolerates (REST endpoints accept BOTH the raw literal AND the typed constant for one release). The wire-up is one `includes:` line away when the allowlist sunsets in the next minor.

**Rule severity**

The rule emits PHPStan-native `error`-level diagnostics — there is no `info` / `warning` tier in PHPStan core. "Disabled by default" is the equivalent of `info` for this rule until enabled.

## Why patch

PR-set 8 of 8 in a refactor umbrella. No new feature, no behaviour change, no schema migration, no REST change, no UI change. The 29 existing vocabulary classes under `src/Domain/Vocabularies/` are unchanged. The plugin runtime is byte-equivalent — only `composer.json` autoload-dev + two new files under `tests/` (which are not included in the plugin's runtime classmap).

## Test plan

- `composer install --dev` resolves the `autoload-dev` map; `vendor/composer/autoload_psr4.php` lists the `TT\Tests\PhpStanRules\` namespace.
- `vendor/bin/phpstan analyse -c phpstan.neon` runs unchanged — the rule overlay is NOT included; the analyse output is byte-equivalent to v4.12.9.
- Create a local `phpstan.local.neon` with the documented two-line `includes:` overlay. `vendor/bin/phpstan analyse -c phpstan.local.neon` emits at least one error of identifier `talenttrack.vocabularyConstants` on each existing `=== 'present'` / `=== 'completed'` / etc. site in `src/`.
- The rule does NOT flag SQL-prepare string literals (e.g. `'WHERE status = %s'` is a single literal, no equality operator near a vocabulary value).
- The rule does NOT flag literals inside `src/Domain/Vocabularies/` itself (the constants there ARE the canonical values; the equality check `'present' === self::PRESENT` would otherwise self-report).
- The rule's index is populated at first node visit, not per-node; analyse run time is negligibly affected (one-time `scandir` + 29 `ReflectionClass` constructions).

## Closes

The #988 umbrella issue. Each of PR-sets 1-7 closed its corresponding `partial #988` slice; this PR-set is `closes #988` since it is the final infrastructure piece (the PHPStan rule the umbrella's checklist named explicitly as PR-set 8). The rule itself is disabled by default per the locked decisions; flipping it on is a separate, single-line config edit in a future minor when the backwards-compat allowlist sunsets.

---

# TalentTrack v4.12.9 — Vocabulary constants for auth + ideas + invitations + behaviour (PR-set 7 of #988)

Seventh of eight PR-sets in the umbrella migration of #988 (~131 hardcoded vocabulary string literals -> typed constants under `TT\Domain\Vocabularies\*`). PR-set 1 (attendance + activity) shipped in v4.11.1; PR-set 2 (goals + tasks) in v4.12.3; PR-set 5 (reports + journey + scouting) in v4.12.5; PR-set 6 (tournament + match) in v4.12.6; PR-set 3 (PDP + trial) in v4.12.7; PR-set 4 (player + team) in v4.12.8; this ship — landing as v4.12.9 — covers the auth + ideas + invitations + behaviour vocabularies. Same architectural pattern, same backward-compat allowlist, same patch-bump rhythm.

## What ships

**PHP - new vocabulary classes**

- `src/Domain/Vocabularies/Lookups/IdeaStatus.php` (new) — nine constants for the values stored on `tt_dev_ideas.status`: `SUBMITTED`, `REFINING`, `READY_FOR_APPROVAL`, `REJECTED`, `PROMOTING`, `PROMOTED`, `PROMOTION_FAILED`, `IN_PROGRESS`, `DONE`. Mirrors the PR-set 1 / 2 / 3 / 4 / 5 / 6 file shape (`const ALL` + static `isValid()`). The nine values are the canonical lifecycle set per the `IdeaRepository::transition()` chokepoint, the `GitHubPromoter` start / failure paths, the kanban board's `boardColumns()` filter, and the `AuthorNotifier` notification arms.
- `src/Domain/Vocabularies/Lookups/IdeaType.php` (new) — four constants for `tt_dev_ideas.type`: `FEAT`, `BUG`, `EPIC`, `NEEDS_TRIAGE`. Maps directly to the type marker that goes into the promoted GitHub file (`<!-- type: feat -->` etc.) and the `<type>` segment of the assigned filename.
- `src/Domain/Vocabularies/Lookups/InvitationStatus.php` (new) — four constants for `tt_invitations.status`: `PENDING`, `ACCEPTED`, `EXPIRED`, `REVOKED`. Backs the `invitation_status` lookup seeded by migration 0108 with display labels for en_US / nl_NL / fr_FR / de_DE / es_ES.
- `src/Domain/Vocabularies/Lookups/InvitationKind.php` (new) — three constants for `tt_invitations.kind`: `PLAYER`, `PARENT`, `STAFF`. Drives the role resolver that maps a `kind` to a WP role (`tt_player` / `tt_parent` / staff functional role) on acceptance.
- `src/Domain/Vocabularies/Lookups/BehaviourRating.php` (new) — five constants for the 1..5 scale captured on `tt_player_behaviour_ratings.rating`: `CONCERNING` ('1'), `BELOW_EXPECTATIONS` ('2'), `ACCEPTABLE` ('3'), `STRONG` ('4'), `EXEMPLARY` ('5'). The column is DECIMAL so non-integer values (e.g. 3.5) are accepted when a coach captures a between-tier judgement; the five constants below are the canonical anchor points each `behaviour_rating_label` row maps to. Documentation-only addition this PR-set — no PHP-side `'1'..'5'` comparison literals surfaced; the class documents the seeded anchor set for future PHPStan rule consumption (PR-set 8).
- `src/Domain/Vocabularies/Lookups/PotentialBand.php` (new) — five constants for `tt_player_potential.potential_band`: `FIRST_TEAM`, `PROFESSIONAL_ELSEWHERE`, `SEMI_PRO`, `TOP_AMATEUR`, `RECREATIONAL`. Backs the `potential_band` lookup seeded by migration 0042 with display labels in en_US / nl_NL; consumed by `PlayerStatusCalculator::POTENTIAL_BAND_SCORES` (100 / 80 / 60 / 40 / 20 weights) and the trainer-facing potential-capture surface.
- `src/Domain/Vocabularies/Enums/ImpersonationEndReason.php` (new) — two constants for `tt_impersonation_log.end_reason`: `MANUAL`, `EXPIRED`. Code-only enum (not operator-editable), lives under `Vocabularies\Enums\*` per #988's locked sub-namespace split. `MANUAL` is the actor's "Switch back" click + the `ImpersonationService::end()` default-parameter case; `EXPIRED` is the daily orphan-cleanup cron closing a session older than 24h whose `ended_at` was still NULL.

**PHP - legacy classes converted to deprecated aliases**

- `src/Modules/Development/IdeaStatus.php` — the nine `public const *` declarations now delegate to `TT\Domain\Vocabularies\Lookups\IdeaStatus::*` via `use … as CanonicalIdeaStatus`. Each constant carries a `@deprecated since v4.12.9 — removed in next minor` docblock. The module-local `label()` / `authorFacingLabel()` / `boardColumns()` / `all()` helpers stay in place — they encode rendering rules that aren't part of the vocabulary contract.
- `src/Modules/Development/IdeaType.php` — same pattern: four `public const *` declarations delegate to the canonical `Vocabularies\Lookups\IdeaType::*` values; `label()` / `isValid()` / `all()` helpers stay.
- `src/Modules/Invitations/InvitationStatus.php` — same pattern: four `public const *` declarations delegate to `Vocabularies\Lookups\InvitationStatus::*`; `label()` helper stays.
- `src/Modules/Invitations/InvitationKind.php` — same pattern: three `public const *` declarations delegate to `Vocabularies\Lookups\InvitationKind::*`; `label()` / `isValid()` / `all()` helpers stay.

**PHP - literal -> constant replacements**

- `src/Infrastructure/PlayerStatus/PlayerStatusCalculator.php` — the `POTENTIAL_BAND_SCORES` map's five string keys (`'first_team'` ... `'recreational'`) swap to `PotentialBand::FIRST_TEAM` ... `RECREATIONAL` constants. Use statement added.
- `src/Infrastructure/REST/PlayerStatusRestController.php` — `setPotential()`'s allowlist literal-array `[ 'first_team', 'professional_elsewhere', ... ]` → `PotentialBand::ALL`. Use statement added.
- `src/Shared/Frontend/FrontendPlayerStatusCaptureView.php` — the form-handler's allowlist literal-array → `PotentialBand::ALL`; the `<select>` option-label map's five string keys → `PotentialBand::*` constants. Use statement added.
- `src/Shared/Frontend/FrontendPlayerDetailView.php` — the potential-popover `$bands` map's five `key` literals → `PotentialBand::*` constants. Use statement added (alongside the existing `PlayerStatus` import from PR-set 4).
- `src/Modules/Authorization/Impersonation/ImpersonationService.php` — `end()` method's `string $end_reason = 'manual'` default-parameter literal → `ImpersonationEndReason::MANUAL`. Use statement added.
- `src/Modules/Authorization/Impersonation/ImpersonationAdminPost.php` — `end()` handler's `ImpersonationService::end( 'manual' )` call-site literal → `ImpersonationEndReason::MANUAL`. Use statement added.
- `src/Modules/PersonaDashboard/Widgets/SystemHealthStripWidget.php` — `countPendingInvitations()`'s defensive `class_exists()` fallback literal `'pending'` → `InvitationStatus::PENDING` (canonical). Use statement swap: `TT\Modules\Invitations\InvitationStatus` → `TT\Domain\Vocabularies\Lookups\InvitationStatus`.

**Out of scope for this PR-set**

- `CertificationType` — empirical grep on the codebase surfaced zero PHP-side string-literal comparisons against the six `cert_type` lookup keys (`uefa_a`, `uefa_b`, `uefa_c`, `first_aid`, `gdpr`, `child_safeguarding`) seeded by migration 0048; the values live in `tt_lookups` and are read-only on the operator-facing surface (the cert-type lookup-id is the FK in `tt_staff_certifications.cert_type_lookup_id`, not a string-key comparison). A constants class would document them without making any literal-to-constant swap. Deferred to a future PR-set if call sites surface — same shape as PR-set 4's `PlayerValue` / `AgeGroup` / `Position` deferral.
- `BehaviourRating` is **declared-only** in this PR-set — the column is DECIMAL so the canonical 1..5 anchor values are stored numerically; PHP-side comparison literals against the five anchor keys don't surface in the call sites. The class documents the seeded anchor set for future PHPStan rule consumption (PR-set 8).
- Other auth-related state machines — MFA enrollment-state (timestamps + counters, no discrete vocabulary), audit log payloads (free-form), comms log status (separate cleanup task) — out of scope; the auth surface in PR-set 7's title refers specifically to the impersonation `end_reason` code-only enum.
- SQL string literals (`SET end_reason = 'expired'` in `ImpersonationService::cleanupOrphans()`'s UPDATE statement), `tt_lookups` seed values in `LookupCanonicalSeeds.php`, migrations 0024 / 0025 / 0042 / 0048 / 0108 / 0115 default values, .po / .pot files, test fixtures, and JavaScript stay as literals per the umbrella's locked plan.

## Why patch

PR-set 7 of 8 in a refactor umbrella. No new feature, no behaviour change, no schema migration. The constants are byte-equivalent to the literals they replace; the REST endpoints continue to accept BOTH the raw literal AND the new constant for one release (per #988's backward-compat allowlist) so external integrations do not break. The PHPStan rule (#988 PR-set 8) that will forbid raw literals is deferred until the allowlist drops in a subsequent minor.

## Test plan

- Admin submits a new dev idea via the `?tt_view=ideas-submit` surface: stored with `type=needs-triage`, `status=submitted`. Idea board renders the new card in the Submitted column.
- Admin refines the idea (Type → `feat`, Status → `ready-for-approval`): stored. `refined_at` / `refined_by` populated by `IdeaRepository::transition()`. Idea moves into the Ready-for-approval column on the board.
- Admin promotes the idea: `GitHubPromoter::promote()` flips status to `promoting`, then `promoted` on success or `promotion-failed` on API failure. Author notification fires on each transition arm.
- Admin invites a player via the `?tt_view=configuration&config_sub=invitations` surface: row inserted with `kind=player`, `status=pending`.
- Invitee opens the acceptance URL: `AcceptanceView` renders the player-details step; accept POST flips status to `accepted`.
- Admin revokes a pending invitation: row's status flips to `revoked`.
- A pending invitation past `expires_at` is opened: `InvitationService` lazy-flips it to `expired` before rendering the "this link has expired" page.
- System health strip widget on the admin dashboard reports the count of `pending` invitations.
- Coach records a behaviour rating of 3 via the player status capture: row inserted with `rating=3.0` against the seeded `behaviour_rating_label` 1..5 vocabulary.
- Coach sets a player's potential to `semi_pro`: row inserted in `tt_player_potential` with `potential_band=semi_pro`. `PlayerStatusCalculator` scores the band at 60 (vs 100 for `first_team`, 20 for `recreational`).
- Frontend player detail view's potential-popover renders the five bands with the canonical English labels (First-team / Professional elsewhere / Semi-pro / Top amateur / Recreational).
- REST `POST /players/{id}/potential` with `potential_band=first_team`: 200. With `potential_band=top_pro` (typo): 400 `bad_input` with `allowed` array listing the five canonical bands.
- Admin starts an impersonation session, then clicks "Switch back": `tt_impersonation_log.end_reason` carries `manual`.
- The daily `ImpersonationCron` runs against an orphan session > 24h old: `end_reason` carries `expired`. Both are equality-comparable against `ImpersonationEndReason::MANUAL` / `EXPIRED`.

---

# TalentTrack v4.12.8 — Vocabulary constants for player + team (PR-set 4 of #988)

Fourth of eight PR-sets in the umbrella migration of #988 (~131 hardcoded vocabulary string literals -> typed constants under `TT\Domain\Vocabularies\*`). PR-set 1 (attendance + activity) shipped in v4.11.1; PR-set 2 (goals + tasks) in v4.12.3; PR-set 5 (reports + journey + scouting) in v4.12.5; PR-set 6 (tournament + match) in v4.12.6; PR-set 3 (PDP + trial) in v4.12.7; this ship — landing as v4.12.8 — covers the player-side roster vocabularies. Same architectural pattern, same backward-compat allowlist, same patch-bump rhythm.

## What ships

**PHP - new vocabulary classes**

- `src/Domain/Vocabularies/Lookups/PlayerStatus.php` (new) — five constants for the lifecycle values stored in `tt_players.status`: `ACTIVE`, `TRIAL`, `INACTIVE`, `RELEASED`, `GRADUATED`. Mirrors the PR-set 1 / 2 / 5 file shape (`const ALL` + static `isValid()`). The five values are the canonical set per `JourneyEventSubscriber::emitStatusTransition()`, `LabelTranslator::playerStatus()`, the `PlayersPage` status dropdown, and the trials / workflow forms that write the column. Lifecycle vs archive: the `archived_at` column from migration 0010 is the soft-delete / bulk-archive marker (NULL vs timestamp); `status` is the orthogonal lifecycle marker, so archived players still carry one of the five values. Migration 0061 already back-filled legacy `status='deleted'` rows from v3.89.1-and-earlier delete paths back to `'active'` (with `archived_at` populated), so the five-value vocabulary is the only stored set on every install. `GRADUATED` is intentionally part of `ALL` even though `PlayersPage`'s status dropdown currently exposes only four of the five values — the `JourneyEventSubscriber` already emits a `graduated` journey event when the column flips to that value, so the vocabulary documents the canonical five-state set; surfacing the fifth dropdown option is a separate UX task.
- `src/Domain/Vocabularies/Lookups/PreferredFoot.php` (new) — three lowercase constants for `tt_players.preferred_foot`: `LEFT`, `RIGHT`, `BOTH`. Backs the `foot_option` lookup (operator-editable, seeded by migration 0001 with TitleCase display labels), but the stored player-record value is the lowercase key per `RosterDetailsStep::validate()`'s `sanitize_key()` + allowlist. The empty-string sentinel ("not specified") is intentionally not part of `ALL` — it represents the absence of one of the three options. Chemistry / compatibility engines that compare against `'left'` / `'right'` slot sides are NOT consumers of this vocabulary — those are `position_side_preference` / `slot_side` comparisons (a different left / right / center vocabulary) and stay out of scope for this PR-set.

**PHP - literal -> constant replacements**

- `src/Modules/Players/Admin/PlayersPage.php` — replaces the four literals in the `$status_options` map (`'active'` / `'inactive'` / `'trial'` / `'released'`), the `selected( $player->status ?? 'active', ... )` default, the `handle_save` `$_POST` fallback, and the `stub` row creation with `PlayerStatus::ACTIVE / INACTIVE / TRIAL / RELEASED` constants. SQL string literal `WHERE pl.status='active'` in `render_list()` is kept as a literal per the spec (DB is the source of truth).
- `src/Modules/Players/PlayerCsvImporter.php` — `status` default on row sanitisation: `'active'` → `PlayerStatus::ACTIVE`.
- `src/Shared/Frontend/FrontendPlayerDetailView.php` — trial-player gate on the trials tab empty state: `(string) $player->status === 'trial'` → `=== PlayerStatus::TRIAL`.
- `src/Shared/Frontend/FrontendTrialsManageView.php` — inline player-create on the trial-case create form + the status flip on the existing player: both `'trial'` literals → `PlayerStatus::TRIAL`.
- `src/Infrastructure/Journey/JourneyEventSubscriber.php` — the three-arm `emitStatusTransition()` match — status comparisons swap to `PlayerStatus::*` constants. Pairs cleanly with PR-set 5's `JourneyEventType::*` swap on the `EventEmitter::emit()` emit-arg side: this PR-set replaces the `$new === 'released'` LHS comparisons; PR-set 5 already replaced the `'released'` second-positional emit arg with `JourneyEventType::RELEASED`. Result is a fully-typed branch with no raw literals on either side of the assignment.
- `src/Infrastructure/Query/LabelTranslator.php` — `playerStatus()` switch cases swap to `PlayerStatus::*` constants. Adds a `case PlayerStatus::GRADUATED` arm for symmetry (missing previously). The legacy `case 'deleted'` arm is preserved as a literal — it's a historical-display safety net for migration-0061-pre installs that may still surface a value not in the canonical five-state set.
- `src/Modules/Tournaments/Wizard/SquadStep.php` — trial-badge gate on the squad picker: `$pl->status === 'trial'` → `=== PlayerStatus::TRIAL`.
- `src/Modules/Wizards/Player/ReviewStep.php` — status assignment on wizard submit: `$path === 'trial' ? 'trial' : 'active'` → `? PlayerStatus::TRIAL : PlayerStatus::ACTIVE`.
- `src/Modules/Wizards/Player/RosterDetailsStep.php` — preferred-foot allowlist in `validate()`: `[ '', 'left', 'right', 'both' ]` → `[ '', PreferredFoot::LEFT, PreferredFoot::RIGHT, PreferredFoot::BOTH ]`.
- `src/Modules/Workflow/Forms/RecordTestTrainingOutcomeForm.php` — the new-player insert on prospect-admission: `'status' => 'trial'` → `PlayerStatus::TRIAL`.
- `src/Modules/Workflow/Forms/AwaitTeamOfferDecisionForm.php` — the accepted-offer update: `[ 'status' => 'active' ]` → `[ 'status' => PlayerStatus::ACTIVE ]`.
- `src/Modules/DemoData/Generators/PlayerGenerator.php` — the seeded player insert + the `tt_player_created` hook payload: both `'status' => 'active'` → `PlayerStatus::ACTIVE`.

**Out of scope for this PR-set**

- `PlayerValue` / `AgeGroup` / `Position` — empirical grep on the codebase surfaced zero PHP-side string-literal comparisons against the eight player-value keys (the 0031 PDP-cycle seed), the U7-U23 / Senior age-group codes (the 0001 + 0051 seeds), or the 11 position abbreviations (the 0001 seed). The values live in `tt_lookups` and are read-only on the operator-facing surface; a constants class would document them without making any literal-to-constant swap. Deferred to a future PR-set if call sites surface — the issue's "every value" rule is satisfied at the call-site replacement layer, not by ahead-of-need declaration.
- `TeamLevel` / `AgeGroupCode` — `tt_teams` has no level / tier column (squad tier sits on `tt_team_blueprint_assignments.tier` per migration 0072, scoped for PR-set 7's `BlueprintTier` enum); the `age_group` column on `tt_teams` is VARCHAR but no equality comparisons surfaced in code.
- `PlayerOnePagerPdfExporter::statusLabel()` — has a defensive 6-value map (`active` / `archived` / `trial` / `released` / `contracted` / `inactive`) for display fallback against historical / drifted values; left as literals because the map intentionally accepts values outside the canonical five-state set and acts as a defensive translation surface, not a vocabulary contract.
- SQL string literals, `tt_lookups` seed values, .po / .pot files, test fixtures, and JavaScript stay as literals per the umbrella's locked plan.

## Why patch

PR-set 4 of 8 in a refactor umbrella. No new feature, no behaviour change, no schema migration. The constants are byte-equivalent to the literals they replace; the REST endpoints continue to accept BOTH the raw literal AND the new constant for one release (per #988's backward-compat allowlist) so external integrations do not break. The PHPStan rule (#988 PR-set 8) that will forbid raw literals is deferred until the allowlist drops in a subsequent minor.

## Test plan

- Coach creates a new player via the admin form: stored with `status=active`. Status dropdown lists Active / Inactive / Trial / Released — unchanged from previous behaviour.
- Coach edits an existing trial player to `status=active` (signing flow): `JourneyEventSubscriber::emitStatusTransition()` writes a `signed` journey event via `EventEmitter::emit()` exactly as before.
- Coach edits a player to `status=released` or `status=graduated`: corresponding journey events fire.
- Player-create wizard, roster path: `status=active`. Trial path: `status=trial`. Preferred-foot dropdown accepts `left` / `right` / `both` and persists the lowercase key.
- CSV bulk import without a `status` column: defaults to `active`.
- Frontend trial-case create with inline new-player: new `tt_players` row carries `status=trial`; the trial case ties to it. Existing-player promotion flips the row to `trial`.
- Tournament wizard squad step: trial players surface with the Trial badge, unchecked by default.
- Workflow form "Record test-training outcome" (prospect admitted): new `tt_players` row carries `status=trial`.
- Workflow form "Await team offer decision" (accepted): existing player row flips to `status=active`.
- Demo-data seed run: every generated player carries `status=active` and the `tt_player_created` hook payload reflects the same.
- LabelTranslator round-trip: `playerStatus('graduated')` returns "Graduated" (previously fell through to `humanise()`); other arms unchanged.

---

# TalentTrack v4.12.7 — Vocabulary constants for PDP + trial (PR-set 3 of #988)

Third of eight PR-sets in the umbrella migration of #988 (~131 hardcoded vocabulary string literals -> typed constants under `TT\Domain\Vocabularies\*`). PR-set 1 (attendance + activity) shipped in v4.11.1; PR-set 2 (goals + tasks) shipped in v4.12.3; this ship covers the PDP-cycle and trial-case vocabularies. Same architectural pattern, same backward-compat allowlist, same patch-bump rhythm.

## What ships

**PHP - new vocabulary classes**

- `src/Domain/Vocabularies/Lookups/PdpStatus.php` (new) — three lowercase constants for `tt_pdp_files.status`: `OPEN`, `COMPLETED`, `ARCHIVED`. Mirrors the PR-set 1 / 2 file shape (`const ALL` + static `isValid()`). The column is VARCHAR(20) with `DEFAULT 'open'` per migration 0031; `PdpFilesRepository::setStatus()` is the gate that rejects any value outside the three.
- `src/Domain/Vocabularies/Lookups/PdpVerdictDecision.php` (new) — four constants for `tt_pdp_verdicts.decision`: `PROMOTE`, `RETAIN`, `RELEASE`, `TRANSFER`. Backs the `pdp_verdict_decision` lookup seeded by migration 0112 with per-locale translations through `tt_translations`. `PdpVerdictsRepository::upsertForFile()` is the gate.
- `src/Domain/Vocabularies/Lookups/TrialCaseStatus.php` (new) — four constants for `tt_trial_cases.status`: `OPEN`, `EXTENDED`, `DECIDED`, `ARCHIVED`. Backs the `trial_case_status` lookup seeded by migration 0116.
- `src/Domain/Vocabularies/Lookups/TrialCaseDecision.php` (new) — six constants for `tt_trial_cases.decision`: `ADMIT`, `DENY_FINAL`, `DENY_ENCOURAGEMENT`, `OFFERED_TEAM_POSITION`, `DECLINED_OFFERED_POSITION`, `CONTINUE_IN_TRIAL_GROUP`. Backs the `trial_case_decision` lookup seeded by migration 0116. The three rolling-membership decisions (#0081 child 4) sit alongside the classic admit / decline triad — single vocabulary, one canonical list.

**PHP - literal -> constant replacements**

- `src/Modules/Pdp/Repositories/PdpFilesRepository.php` — insert default for new files moves from `'open'` to `PdpStatus::OPEN`; the `setStatus()` allowlist `in_array( $status, [ 'open', 'completed', 'archived' ], true )` becomes `PdpStatus::isValid( $status )`.
- `src/Modules/Pdp/Repositories/PdpVerdictsRepository.php` — drops the private `ALLOWED_DECISIONS` literal array; the `upsertForFile()` gate switches to `PdpVerdictDecision::isValid()`. The `label()` switch cases reference `PdpVerdictDecision::*` constants.
- `src/Modules/Pdp/Rest/PdpVerdictsRestController.php` — drops the private `ALLOWED_DECISIONS` literal array; the PUT-handler validation switches to `PdpVerdictDecision::isValid()`; the error payload's `allowed` key uses `PdpVerdictDecision::ALL`.
- `src/Modules/Pdp/Frontend/FrontendPdpManageView.php` — the list-filter `$status_options` keys, the verdict-form `$decisions` keys, and the private `statusLabel()` switch cases all reference the new constants.
- `src/Modules/Pdp/Frontend/FrontendMyPdpView.php` — the read-only verdict `decisionLabel()` switch cases reference `PdpVerdictDecision::*`.
- `src/Modules/Trials/Repositories/TrialCasesRepository.php` — the `STATUS_*` and `DECISION_*` class constants now alias `TrialCaseStatus::*` and `TrialCaseDecision::*` rather than carrying duplicate raw strings. Backward compatible: every existing internal caller compiles and produces the same stored value. The `recordDecision()` allowlist switches from the self-constant triad to the `TrialCaseDecision::ADMIT|DENY_FINAL|DENY_ENCOURAGEMENT` triad; the status / decision label switches reference the new constants directly.
- `src/Infrastructure/Journey/JourneyEventSubscriber.php` — the post-trial-decision branches (signed / released journey events) switch from `'admit'` / `'deny_final'` literals to `TrialCaseDecision::ADMIT` / `TrialCaseDecision::DENY_FINAL`.
- `src/Modules/Trials/TrialGroupTeam.php` — the two `wpdb->prepare()` bindings for the trial-group active-member queries switch from the `'continue_in_trial_group'` literal to `TrialCaseDecision::CONTINUE_IN_TRIAL_GROUP`.
- `src/Modules/PersonaDashboard/Kpis/TrialGroupActiveCount.php` — the KPI's active-trial-group-member query binding switches to `TrialCaseDecision::CONTINUE_IN_TRIAL_GROUP`.
- `src/Modules/Workflow/Templates/ReviewTrialGroupMembershipTemplate.php` — the chain-step gate for the `continue_in_trial_group` branch switches to `TrialCaseDecision::CONTINUE_IN_TRIAL_GROUP`.

**Out of scope for this PR-set**

- SQL string literals (`status IN ('open','extended')` in `TrialCasesRepository::findOpenForPlayer` and `listEndingBetween`, `status NOT IN ('completed','archived')` in `SeasonCarryover::copyOpenGoals`) stay as literals — DB is the source of truth, not the PHP layer.
- Form-internal radio-button values in `ReviewTrialGroupMembershipForm` (`offer_team_position`, `decline_final`) stay as form-input literals — they're transient HTML radio values mapped to canonical `TrialCaseDecision::*` values inside `serializeResponse()`, not themselves stored. Replacing them would conflate two vocabularies.
- The local `pdpFileStatusLabel()` switch in `PdpPrintRouter` translates an `'open'`/`'closed'` enum that is separate from the `tt_pdp_files.status` vocabulary — kept local per the existing comment.
- `LookupCanonicalSeeds.php` has stale / drift-prone entries for `pdp_verdict_decision` and `trial_case_status` ("On track / Behind / Ahead / At risk / Released" and "Open / In progress / Decision pending / Accepted / Rejected") that don't match the canonical pools. That's a #987 cleanup item, out of scope for #988.
- Migrations, `tt_lookups` seed values, .po / .pot files, test fixtures, and JavaScript stay as literals per the umbrella's locked plan.

## Why patch

PR-set 3 of 8 in a refactor umbrella. No new feature, no behaviour change, no schema migration. The constants are byte-equivalent to the literals they replace; the REST endpoints continue to accept BOTH the raw literal AND the new constant for one release (per #988's backward-compat allowlist) so external integrations do not break. The PHPStan rule (#988 PR-set 8) that will forbid raw literals is deferred until the allowlist drops in a subsequent minor.

## Test plan

- Coach opens the PDP manage list at `?tt_view=pdp`: the status filter dropdown still shows Open / Completed / Archived; selecting one filters the file list as before.
- Coach opens a PDP file: the verdict-form dropdown still offers the four `promote` / `retain` / `release` / `transfer` decisions with the academy-progression labels; submitting still upserts the verdict.
- Coach records a trial decision via `TrialCasesRepository::recordDecision()` with `admit` / `deny_final` / `deny_encouragement`: stored as before; the journey subscriber emits the signed / released events on `admit` / `deny_final`.
- HoD landing's "Players in trial group" KPI counts trial cases with `decision = 'continue_in_trial_group'` (byte-identical to prior).
- ReviewTrialGroupMembershipTemplate chain-step gates the re-spawn on `decision === 'continue_in_trial_group'` (byte-identical to prior).
- Player / parent opens the read-only PDP at `?tt_view=my-pdp`: the verdict-decision label resolves through `PdpVerdictDecision::*` or the operator-edited `tt_translations` value, identical to prior behaviour.

---

# TalentTrack v4.12.4 — Match prep widen + landscape A4 print + save-indicator + in-place print button (closes #998)

Four bundled UX defects on the head-coach match-preparation surface (`?tt_view=match-prep&activity_id=<id>`), shipping together as one patch because they sit on the same three files.

## What ships

**(1) Widen on-screen** — `.tt-dashboard:has(.tt-match-prep)` lifts the wrapper max-width from 1100px to 1320px on the match-prep route only; every other dashboard view stays at 1100px. Desktop grid columns widen from `12.5rem | 1fr | 20rem` to `14rem | 1fr | 22rem`. Mobile and tablet breakpoints untouched.

**(2) Landscape A4 print CSS** — new `@page { size: A4 landscape; margin: 8mm }` plus an `@media print` block that drops the dashboard chrome (`.tt-breadcrumbs`, `.tt-back-link-wrap`, page-head actions, `.tt-mp-toolbar`) and every overlay (`.tt-mp-picker(-backdrop)?`, `.tt-mp-drawer(-backdrop)?`) so only the spreadsheet renders on paper. Selectors verified against the live markup rather than guessed. Forces the 3-column grid on regardless of print viewport width. Pitch tints, panel-head shading, and "on pitch" green cells preserved via `print-color-adjust: exact`. `break-inside: avoid` on each player row, goal box, and set-piece row prevents page-break splits.

**(3) Save-indicator layout shift** — `.tt-mp-save-state` gains `min-height: 1.4em`, `min-width: 12ch`, `display: inline-flex` so its bounding box stays stable while the textContent toggles between dirty / saving / saved / empty. Pure CSS defence; the JS textContent flip is unchanged.

**(4) Print button** — replaces the toolbar's `<a href="?tt_view=exports&exporter=match_prep_pdf&...">PDF (landscape A4)</a>` with a `<button type="button" data-tt-mp-print>Print (landscape A4)</button>` plus a one-line `window.print()` handler in `frontend-match-prep.js`. The `$pdf_url = add_query_arg([...])` block in `FrontendMatchPrepView::render()` is removed. The browser's "Save as PDF" within the print dialog handles file-output for free. The exports page's match-prep PDF exporter route stays available for direct visits to `?tt_view=exports`. Dutch string `Afdrukken (liggend A4)`.

## Files touched

- `assets/css/frontend-match-prep.css` — wrapper widening, grid column widths, save-state stability, print block.
- `assets/js/frontend-match-prep.js` — `data-tt-mp-print` click handler.
- `src/Modules/MatchPrep/Frontend/FrontendMatchPrepView.php` — PDF anchor → Print button; drop unused `$pdf_url`.
- `.local-mockups/match-preparation/index.html` — mirror the changes (mockup is design-of-record).
- `languages/talenttrack-nl_NL.po` — add `Print (landscape A4)` → `Afdrukken (liggend A4)`.
- `languages/talenttrack.pot` — add the same `msgid`.
- `docs/match-prep.md` + `docs/nl_NL/match-prep.md` — rewrite "Print to PDF" section to describe browser-print flow.
- `talenttrack.php` + `readme.txt` — version bump to 4.12.4, changelog stanza.

No schema, no REST, no behavioural change beyond the four items above.

---

# TalentTrack v4.12.3 — Vocabulary constants for goals + tasks (PR-set 2 of #988)

Second of eight PR-sets in the umbrella migration of #988 (~131 hardcoded vocabulary string literals -> typed constants under `TT\Domain\Vocabularies\*`). PR-set 1 (attendance + activity) shipped in v4.11.1; this ship covers the goal-side workflow vocabularies. Same architectural pattern, same backward-compat allowlist, same patch-bump rhythm.

## What ships

**PHP - new vocabulary classes**

- `src/Domain/Vocabularies/Lookups/GoalStatus.php` (new) — six lowercase snake_case constants for `tt_goals.status`: `PENDING`, `PENDING_APPROVAL`, `IN_PROGRESS`, `COMPLETED`, `ON_HOLD`, `CANCELLED`. Mirrors the PR-set 1 file shape (`const ALL` + static `isValid()`). The lowercase snake_case form is the canonical stored value per `LabelTranslator::goalStatus()` and the REST controller's defaults; the `goal_status` lookup row `name` column carries the TitleCase display label, but the table is the operator-facing surface and unaffected here.
- `src/Domain/Vocabularies/Lookups/GoalPriority.php` (new) — three lowercase constants for `tt_goals.priority`: `LOW`, `MEDIUM`, `HIGH`.
- `src/Domain/Vocabularies/Lookups/GoalApprovalDecision.php` (new) — three constants for the approval-form decisions stored in `tt_workflow_tasks.response_json`: `APPROVE`, `AMEND`, `REJECT`. Backs the `goal_approval_decision` lookup seeded by migration 0111.

**PHP - literal -> constant replacements**

- `src/Infrastructure/REST/GoalsRestController.php` — replaces the five raw `'pending_approval'` / `'pending'` literals (default status on create, force-approve gate for player-self-create, status update authorization check) and the `'medium'` priority default with the new `GoalStatus::*` / `GoalPriority::*` constants. REST endpoint payload-side behaviour is unchanged; the stored values are byte-identical to the previous release.
- `src/Modules/Goals/Admin/GoalsPage.php` — replaces the `'pending'` and `'medium'` form-default literals (status / priority dropdown `selected()` calls + the `handle_save` `$_POST` fallback) with the new constants.
- `src/Modules/Development/Notifications/GoalSpawner.php` — the idea-promotion goal materialisation hands `'pending'` / `'medium'` to `wpdb::insert(tt_goals)`; switched to the constants.
- `src/Modules/Workflow/Forms/GoalApprovalForm.php` — `DECISION_APPROVE` / `DECISION_AMEND` / `DECISION_REJECT` class constants now alias `GoalApprovalDecision::APPROVE` / `::AMEND` / `::REJECT` rather than carrying duplicate raw strings. Backward compatible: every existing internal caller continues to compile and produce the same stored decision value. The aliases stay one release before the umbrella's PR-set 8 PHPStan rule lands.

**Out of scope for this PR-set**

- `TT\Modules\Workflow\TaskStatus` already follows the constants-shaped pattern from the original v3.x ship; it carries the canonical six values (`open`, `in_progress`, `completed`, `overdue`, `skipped`, `cancelled`) plus helpers `isActionable()` and `label()`. Consolidating it into `Vocabularies\Lookups\TaskStatus` is a mechanical lift but pulls in two more touch points (`TasksRepository`, `FrontendMyTasksView`, `FrontendTaskDetailView`); deferred to keep this PR-set focused on the *new* constants classes. The existing class continues to be the source of truth for the task-status vocabulary in the meantime.
- SQL string literals, `tt_lookups` seed values, .po / .pot files, test fixtures, and JavaScript stay as literals per the umbrella's locked plan.

## Why patch

PR-set 2 of 8 in a refactor umbrella. No new feature, no behaviour change, no schema migration. The constants are byte-equivalent to the literals they replace; the REST endpoints continue to accept BOTH the raw literal AND the new constant for one release (per #988's backward-compat allowlist) so external integrations do not break. The PHPStan rule (#988 PR-set 8) that will forbid raw literals is deferred until the allowlist drops in a subsequent minor.

## Test plan

- Coach creates a goal via the goals admin: defaults to `priority=medium`, `status=pending`. (Both stored as the lowercase form, unchanged from previous behaviour.)
- Player creates a goal via the player-self-create flow: stored with `status=pending_approval` regardless of payload override.
- Coach approves a pending-approval goal via the inline status dropdown: head-coach-only gate fires; status moves to `pending`.
- Coach uses the workflow goal-approval form: each `approve` / `amend` / `reject` decision serializes to the same byte value as before.
- Idea promoted to in-progress: spawns a `tt_goals` row with `status=pending`, `priority=medium`.


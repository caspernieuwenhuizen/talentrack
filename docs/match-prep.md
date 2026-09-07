---
title: Match preparation
group: match-day
summary: 'Plan a match: squad, formation, roles and the printed team sheet.'
audience: [user]
views: [match-prep]
module: TT\Modules\MatchPrep\MatchPrepModule
order: 10
---

# Match preparation

The **Match preparation** surface lets a head coach plan a match in one
spreadsheet-style screen: who is available, who starts each half on
which pitch position, what the tactical goals are per phase, what each
player should pay attention to, and who takes the captain band plus
each set piece.

Open it from a match-type activity's detail page; the URL is
`?tt_view=match-prep&activity_id=<id>`. The first time you open it for
a given match the **Availability wizard** runs first so the roster gets
the Present / Absent / Injured chips it needs.

## Layout

The page is laid out as three columns matching the pilot's working
spreadsheet:

- **Left** — the available roster with three minute counters per row
 (`min 1e`, `min 2e`, `tot`) and totals at the foot. Minutes auto-
 derive from `half_length × (on pitch ? 1 : 0)`; editing the
 **Half length** input at the top updates every row live.
- **Middle** — two half-pitches side by side with a `→` copy button
 between. Below them, the **Principles** panel (see below), and under
 that the **Match goals** panel — one full-width
 *General* box on top, then a 2×2 grid of *Attacking / Defending* and
 *Set pieces (attack) / Set pieces (defend)*. Each box has four short
 single-line inputs (bullet-style) — written for the pilot's habit of
 short rules rather than long paragraphs.
- **Right** — two stacked panels. **Player focus** carries a per-player
 attention text field, a `!` flag for "this is a specific goal for
 this player", and a camera icon for "I've appointed a video analyst
 for this player." **Roles & set pieces** carries six rows — Captain,
 Corner left / right, Free kick left / right (cross), Penalty.

## Principles — what this match is working on

Above the goal boxes sits a read-only **Principles** panel: the methodology
principles the activity is linked to, in the same O / A / V colour pills the
activity page uses. It is there so you write the match goals against the
principle the team is actually working on, rather than from memory.

**You attach principles on the activity, not here.** One place to answer
"which principle is this match about" is the point of reading it rather than
picking it again — two pickers would eventually give two answers. When a match
has none linked, the panel says so and links straight to the activity's edit
form (only if you can edit activities). If your academy runs without the
Methodology module the panel does not appear at all.

The principles print. They appear on the paper team sheet and in the PDF
export, above the goals, so an assistant working from the sheet sees the same
framing you did.

## Assigning players to slots

A slot on a pitch is a position circle. Click any slot — empty or
filled — to open a **player picker** anchored next to the slot. Type
to filter; tap a name to assign. Picking a player who is already on
this half displaces them automatically (one player per half).

You can also **drag a player from the left roster** directly onto a
slot. The drag is the desktop enhancement; the click-to-pick is the
primary path and works the same on every device.

The `→` button between the two pitches **copies the first half's
lineup to the second half** in one click, no confirmation. Adjust the
second half from there (typically one or two subs).

## Captain and set-piece takers

The right-side **Roles & set pieces** pane works the same as a pitch
slot. Click any row — Captain, Corner left, Corner right, Free kick
left, Free kick right, Penalty — to open the same player picker. The
× pill on a filled row clears the assignment. Marking a player Absent
in the drawer pulls them out of role assignments AND lineup slots
automatically, so the role pane never points at an unavailable player.

## Availability drawer

Click **Availability** in the toolbar to slide in the drawer with
three chips per player: **Present**, **Absent (excused)**, **Injured**.
Add an optional reason for absences. **Mark all present** is the
shortcut for "the whole roster is here today." Closing the drawer
saves; marking anyone Absent pulls them out of every lineup slot and
role row.

When the match activity has a **planned roster** (the expected players
you picked when creating it), the availability step starts from that
plan instead of marking everyone Present: planned players default to
Present, and team players you left out of the plan are pre-marked
Absent with the reason "not in planned roster." Adjust any chip — the
defaults are just a head start. Activities created without a planned
roster still default everyone to Present.

## Formation

The **Formation** dropdown lists every entry from
`tt_formation_templates`. The default is **4-2-3-1** — the pilot's
most common shape. Changing the formation reshapes the slot positions
on the pitches; player assignments transfer to the slots that survive
the rename, the rest fall back to the bench.

The descriptive name reads in your language (the shape numbers stay the
same in every language) — e.g. **Neutraal 4-3-3**, **Balbezit 4-3-3**.
An **Aanvallend 3-4-3 (ruit)** option (a 3-4-3 with a midfield diamond:
DM, two central mids and an attacking mid behind a front three) ships
alongside the standard 3-4-3, and draws its true diamond on the pitch —
a template carries its own slot positions, so two formations that share a
shape number (the flat 3-4-3 and the diamond) no longer collapse onto the
same layout.

## Player names — short form

Every player label on the match-prep surface — roster column, pitch
slot labels, Doen per speler column, Rollen pane, availability drawer
— renders as the player's **first name only** (`Daan`, `Senna`,
`Javi`). The full name is reserved for the player's own profile and
the team roster pages.

When two players on the same team share a first name, both render
as `<firstName> <lastInitial>` (`Daan P`, `Daan A`) so the coach can
tell them apart at a glance. The disambiguation triggers per team —
a third `Daan` on a different team has no effect on this team's
labels. The same player's short form is identical across every
sub-surface of the match-prep page.

If a player has no first name on file, the surface falls back to the
last name; if neither is on file, it shows `—` until the record is
fixed.

## Save behaviour

This screen is one of the ones that saves itself. [How saving works](save-model.md)
sets out the three models TalentTrack uses and which screens are in each; this
section is the detail for this one.

Every edit live-saves over REST — there is no Save button to press.
The toolbar's right side shows the current save state ("All changes
saved", "Unsaved changes…", "Saving…", "Save failed — retry").
If a save fails, retry the edit; the network may have hiccuped.

### Undoing the last change

Because there is no Save button, a mis-tap is stored the moment it
settles. An **Undo** button appears next to *All changes saved* and
takes back the last change that was saved — the lineup slot you just
filled, the half length you just typed, the focus note you just wrote.

Three things are worth knowing:

- **It is one step, not a history.** Undo takes back the last saved
  change. Once you undo, the button goes away; make the edit again if
  you want it back.
- **The undo is itself saved.** Reload the page and the change stays
  undone — you are not looking at a screen-only revert.
- **It only shows on a settled screen.** While the toolbar says
  *Saving…* or *Unsaved changes…* the button is hidden, so it can
  never fight with a save that is still on its way.

Captain and set-piece takers save on their own, so picking one clears
the offer: the button reappears with your next ordinary edit.

### Going back to how it was when you opened the screen

Undo answers the mis-tap you spot straight away. For the one you spot
ten minutes later, a **Revert changes** button sits beside it. It puts
the whole screen back to the state it was in when you opened it —
lineup, half length, formation, tactical goals and per-player notes, all
at once.

It asks first. The confirm says how many fields it will restore and
warns that the restore cannot itself be undone; press Escape or
**Cancel** to back out.

What it reaches, and what it does not:

- **It survives a reload.** Refresh the page, or close the tab by
  accident and come back, and the button is still there offering the
  same starting point.
- **It is tied to this device and this sitting.** The starting point is
  kept in your browser, not on the server. Open the same match on
  another device — or come back the next morning — and you get the saved
  plan with no revert offered. The sitting ended.
- **A private window, a cleared browser or a browser that blocks storage
  means no revert.** Everything else on the screen works exactly as
  before, including autosave and Undo; only this one offer is missing.
- **Captain and set-piece picks end it.** They save on their own
  endpoint, so a revert could not put them back — rather than restore
  part of the screen and quietly leave those standing, the button
  retires for the rest of the sitting.
- **It only shows when there is something to revert**, and only on a
  settled screen, for the same reason Undo does.

## Print to paper (or PDF)

The **Export as PDF (A4)** button in the toolbar takes a picture of the
match-prep grid exactly as it appears on screen — both formation
pitches, the **Selection · minutes** table (per-half minutes + totals),
the **Wedstrijddoelen**, **Doen per speler**, and the **Roles & set
pieces** — and lays it out on **landscape A4**. The slot numbers, player
names, the `!` icon (red) and the camera icon (green) all keep their
on-screen colours. The PDF downloads straight to your device. Because it
captures the page as an image, the result is pixel-faithful to what you
see — the trade-off is that the text in the PDF is not selectable.

The sheet **names the match**. A header band across the top carries the
fixture, the date, the kick-off time, home or away and the venue, plus
the formation, the half length and how many of the squad are available.
Whatever your academy has recorded appears; what it hasn't is left out
rather than printed empty. None of this is on screen — it is above the
grid there already — so you only see it on the exported sheet.

The export no longer depends on the device you trigger it from. It is
composed at the page's own width, so the same match exported from a
phone and from a desktop produces the same document. The sheet is fitted
to the page and centred; where it is taller than one page it is printed
at full size across two rather than shrunk to something you can't read.

### The printed sheet is laid out for paper

The PDF is **one landscape page**, arranged for the hand rather than for
the screen:

- **Left** — Selection · minutes, with Roles & set pieces underneath it.
  Both are the same squad read the same way, a name per line, so they
  share the narrow column.
- **Middle** — the two half-pitches, then the match goals.
- **Right** — Doen per speler, which always reserves **fifteen rows**:
  the players who already carry a note, then ruled lines to the count.
  It is the block you write in during the warm-up, so the room is there
  whether or not you filled it in beforehand.

Empty goal boxes and unused player rows print as **ruled writing lines**,
not as gaps or placeholder text. A full 22-player squad still fits the
one page.

The **Print match sheet** route (`?tt_match_prep_print=1`) prints the same
layout, so the page you print without JavaScript and the PDF you export
are the same document.

Two things are deliberately left off the paper. **Empty fields print
blank**: a goal line or a player note you haven't filled in comes out as
an empty ruled line, not as its grey hint text ("Doelstelling 2…", "…"),
and a set-piece role with nobody assigned prints blank rather than
"— Pick player —". And **the buttons don't print**: the `×` that clears
a set-piece player, and the `→` that copies the first half's line-up to
the second, are on-screen controls with nothing to do on a printed
sheet.

The capture engine loads on demand the first time you click Export (it
isn't downloaded until then), so nothing extra weighs on the match-prep
page itself. If the capture can't run on your device a short notice
appears; fall back to your browser's own print dialog (**Ctrl/Cmd+P →
Save as PDF**), which prints the page directly.

The **Print team sheet** button next to it opens the pitch-side team
sheet (Starting XI / Bench / Squad with signature lines) on a clean
print page in a new tab, with its own **Export as PDF (A4 landscape)** /
**Print** choice. The central `?tt_view=exports` page still carries a
server-side match-prep / team-sheet PDF exporter as a fallback for
anyone who prefers to drive the export from there.

### Two sheets, two switches

They are two documents for two readers, and your academy switches them
on and off separately (**Configuration → Modules**, under Match prep):

| Feature | What it controls | Who reads it |
| - | - | - |
| **Match prep PDF export** | The **Export as PDF** button, and the print page behind it | The coach — it carries the plan: minutes, per-player instructions, goals |
| **Match day team sheet** | The **Print team sheet** button, its print page, and the server-side exporter on `?tt_view=exports` | The referee and the opposition — it carries identity and eligibility |

Both ship on. Switch the team sheet off if your competition files match
forms digitally: the button leaves the toolbar and the print URL refuses,
while the coach's own export is untouched. Turning either off leaves the
match-prep screen itself fully usable.

## Share the plan with staff who have no account

**Share plan** produces a link you can send to an assistant coach, an analyst or
a keeper coach. They open it and read the plan — the lineup, the per-phase goals,
the per-player notes — without logging in and without an account on this install.

It is read-only. Nobody who opens the link can change anything, and the page is
kept out of search engines.

Three things worth knowing before you send one:

- **The link is the key.** Anyone who has it can read the plan, so treat it the
  way you would treat the printed sheet: give it to the people who need it, and
  not to a group chat you do not control.
- **It names players and says who is expected to start.** That is the point of
  sending it, and it is also the reason not to forward it outside the staff.
- **You can revoke it.** *Replace link* issues a new one and stops the old one
  working immediately — for everyone who has it. Use it if a link went to the
  wrong group, or when someone leaves the staff.

The link keeps working as you edit: whoever opens it sees the plan as it stands,
not as it was when you sent it. That is the difference between this and emailing
a PDF, which is out of date the moment you swap a starter.

Sharing can be switched off entirely for the academy under **Modules and
features**, separately from match-analysis sharing. Writing and printing the plan
are unaffected.

## Who can open it

The plan belongs to the team playing the match. You can open — and
change — match preparation for matches of teams you coach. Opening the
URL of a match belonging to a team you do not coach answers *You do not
coach this activity's team.* Academy Admin and Head of Development see
every team, as they do everywhere else.

The same rule holds for the share link and the role assignments: both
are decided by the plan's own team, not by holding a coaching role
somewhere in the academy.

## What you can't do here

- Edit the roster (add / remove players from the team) — that's the
 **Teams** page.
- Run the match itself — that's **Match Execution**, the live phone
 app for the assistant coach. The **Start match** button only becomes
 active on match day itself — before then it's shown but disabled, with
 a tooltip naming the date it unlocks ("Available on match day (14
 Jun)"). This keeps a match from being started early by accident.
- Review the match afterwards — that's **Match analysis**, which opens from
 the same activity once the match has been played. It shows what you
 planned here next to each phase, and lists the players you flagged with
 their attention note, so the review answers what you asked for. The camera
 flag still only marks who was appointed an analyst; what the analyst saw
 goes in the analysis.

## On your plan

Match preparation is a **Pro** feature. On Standard a plan the club already made stays on screen and printable; starting a new one, or saving a change to an existing one, is what the plan buys. See [Licence and account](license-and-account.md).

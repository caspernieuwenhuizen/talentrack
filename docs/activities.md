---
title: Activities
group: performance
summary: Games, trainings, and other activities — typing, attendance, and post-game evaluations.
audience: [user]
views: [activities, my-sessions]
module: TT\Modules\Activities\ActivitiesModule
capability: tt_view_activities
order: 30
---

# Activities and attendance

An **activity** is anything on the calendar — a training, a game, or any other event (team-building day, club meeting, …). Each activity records who attended.

## The activities list

The **Activities** tile opens a date-bucketed card list. The buckets read top to bottom:

- **⚠ Past — still open** — past activities that are still marked Planned. They never got flipped to Completed or Cancelled, so the coach lost track of them. This section renders **by default** at the very top of the list — above the collapsed Past toggle — in its own tinted, orange-accented block so past-but-unclosed activities can't be missed.
- **Today** — what's on for today.
- **This week** — the rest of this calendar week (up to and including Sunday).
- **Next week** — Monday → Sunday of the upcoming week.
- **Later this month** — anything beyond next week, up to the end of the current month.
- **Later** — anything beyond the end of the month.

Empty buckets don't render their header at all — if there's nothing on for next week, the "Next week" header simply doesn't appear.

Each row is a card: a date badge on the left (month + day, painted blue for today and orange for past-still-open rows), the activity title in the middle with a colour-coded type pill (Training blue, Match red, Friendly yellow, Other grey), and a chevron on the right. Activities imported from Spond carry a small blue **Spond** chip on the card so you can tell at a glance which ones came from the integration; manually-created and generated activities show none. Tap anywhere on the card to open the activity detail page.

### Past activities

Past activities (Completed or Cancelled) are pinned to the **top** of the list as a single button — `N past activities hidden · Show ▼`. Tap to expand; tap again to collapse. The state is preserved in the URL as `?include_past=1`, so a shared link reflects the same view the sender saw.

Past **planned** activities (not closed off) are NOT in this collapsed bucket — they appear in the **Past — still open** section at the top of the list (above the Past toggle and Today), shown by default, since they are signals that the coach still needs to act on.

### Filters

A single **filter bar** sits above the list. On a desktop screen it shows everything on one line; on a phone or tablet it collapses to a **Filters** button (with a badge counting how many filters are active) and a row of summary chips — tap **Filters** to open a bottom sheet holding the same controls, then **Apply** or **Clear**.

The bar holds five controls, each under its own label:

- **Team** — narrow to one team. Defaults to all teams the coach has access to.
- **Type** — narrow to one activity type (Training / Game / Friendly / Other / any custom type your academy added). Each type carries its own icon — a football for a match, a trophy for a tournament, a clipboard for a meeting, a cone for training — drawn in the same outline style as the rest of the product, so they read the same on screen and in a print.
- **Period** — a date window: **All · This week · Next week · This month · Next month · This season**. Picking one scopes the list without typing dates — weeks run Monday–Sunday, months are calendar months, and **This season** uses your configured current season.
- **Status** — an **Active · Archived · All** control. **Active** is the default — the timeline you normally see. **Archived** replaces the timeline with a flat list of the activities you've archived, each with a **Restore** button and (for admins) a **Delete permanently** button. **All** shows the active timeline with the archived list appended below it.
- **Cancelled** — a **Show** switch, off by default. Cancelled activities are hidden so the schedule stays clean; flip it on to bring them back, dimmed and struck through with a Cancelled pill in whichever date bucket they fall.

Every choice survives in the URL (`?team_id=N&activity_type_key=match&period=this_week&archived=archived&show_cancelled=1`), so deep-links from the dashboard land on the same scoped view, and the controls combine freely.

## List or calendar view (v4.x+)

A **Calendar view** button in the header switches the activities page from the chronological list to a **week-grid calendar** — the same read-only grid the Team Planner uses, with the days as columns and one row per team. Click **List view** to switch back. Your choice is remembered per user, so the page opens the way you left it. The calendar shows the teams you can see (the same scope as the list); arriving with a `?team_id=N` filter narrows it to that one team. It's a read-only glance — creating and editing activities still happens from the list and the activity form, and the full editable planner stays on its own **Team planner** page.

**The calendar keeps your filters (v4.x+).** The period you picked (or a manual From/To range) and the activity **Type** carry across, so switching to the calendar shows the same activities over the same dates instead of resetting to a default window. Two details worth knowing: the grid paints whole weeks, so a window starting mid-week is shown from that week's first day — never less than you asked for; and with the period set to **All** there is no bounded range to draw, so the calendar falls back to its default forward window. Either way the dates being shown are stated above the grid.

## The activity detail page

Tapping a card opens the activity's detail page. The whole detail body sits inside one **grouping panel** — a bounded, softly-tinted container that holds the hero, a compact key-numbers strip and the section cards, so they read as a single, deliberate record rather than loose cards floating on the page (even when only a couple of sections apply). It adapts between a **training** and a **match day**:

- **Hero** — a type-coloured icon chip, the title, and a sub-line reading `date · time · team · location`. For a match day with both teams known the title reads `Your team vs Opponent` and the sub-line shows the kick-off time and whether it's home or away. Pills below the title show the type (plus the game subtype or the Other label) and the status. Edit, Mark attendance and the other actions stay in the page header above.
- **Stat strip** — a compact row of the key numbers under the hero. A match shows **Present** (turned up / roster) · **Substitutes** · **Match length**; a training shows **Present** · **Duration**. Numbers with no value are left out. **Present** appears only once the activity is **completed**, matching the Attendance card below it: on a session still marked *Planned* the number would state a turnout that has not happened yet. It counts recorded attendance only — the planned roster on the **Expected attendance** card is a separate list and is never added in.
- **Facts strip** — four quick facts. A training shows Date · Time · Type · Status; a match day shows Opponent · Home/Away · Kick-off · Formation. Facts with no value are left out.
- **Cards** — each with a titled header, only the cards that have something to show appear, so the page stays uncluttered:
 - **Linked principles** — the practiced principles as colour-coded O/A/V pills, each linking into the methodology browser.
 - **Notes** — the activity's free-text notes.
 - **Line-up** (match day) — the Starting XI and the Bench, each player shown with jersey number and the position played (falling back to their preferred position).
 - **Expected attendance** — the planned roster (see below).
 - **Attendance** (completed activities) — a breakdown bar and legend across Present / Absent / Late / Excused / Injured (plus any custom statuses), with the headline `X / Y present (Z%)` linking to the attendance edit form. A note warns when roster players still have no attendance row. A collapsible **Show roster** list underneath names each registered player with their status pill (guests tagged), so you can see who had which status without opening the edit form.
 - **Tournament** — for tournament-typed activities, the linked tournament with its dates and match count.
- **Audit footer** — who created and last changed the activity. For an activity imported from Spond it also shows **Team last synced from Spond: <time>** — the team's most recent Spond sync (the timestamp is team-level, not per activity, and the label says so), so you can judge how fresh the imported data is.

If that sync looks stale — the event moved in Spond, or the roster changed — a **Sync team from Spond** button sits in the page header of any Spond-imported activity. It pulls the team's calendar again straight away, so you don't have to wait for the scheduled sync or ask an academy admin. It is a *team-wide* refresh (Spond has no way to re-fetch a single event), so the confirmation says so, and the change you were looking for may land on a different activity in the list. The button appears only for someone who may manage that team's Spond connection — an academy admin for any team, a head coach for their own — and only while the activity is active, not archived.

The page reads cleanly on a phone: the cards stack in a single column and widen to two columns on a tablet or desktop.

## Creating an activity

1. Open the **Activities** tile.
2. Pick the **type** from the dropdown. Five types ship by default — Training, Game, Tournament, Meeting and Other — and your academy can rename or add new ones.
3. If you picked **Game**, optionally pick the subtype (Friendly, Cup, League).
4. If you picked **Other**, give it a short label.
5. Pick the team, set the date, and optionally add a location, a start/end time, and notes. For a match, entering the kick-off time prefills the end time to 105 minutes later (90' play + 15' half-time); you can still change it.
6. For a **match** type (Game, Tournament, or a custom match/friendly type) an optional **Presence time** field appears — the arrival time families should be there by. It prints on the weekly planner PDF as `Present HH:MM`.
7. Save. New activities start **Planned**.

The edit form does not change status — **status is changed with explicit buttons** (see [Completing an activity](#completing-an-activity)). Attendance is normally recorded in the guided completion flow, but once a **training** (non-match) activity is **completed** its edit form also shows an **editable attendance table** — one row per player with a status dropdown and a note — so you can correct a missed or wrong attendance right there and hit **Update activity**. This is also the fallback when the guided wizards are switched off. Match-type activities keep their minutes-aware completion flow, so their attendance stays there (the edit form links you to it).

### Completing an activity

You don't set status with a dropdown anymore. A **planned** activity shows a **Complete activity** button — both on its **list card** (so most activities complete in one click without opening the detail page) and on its **detail view**. The button is type-aware:

- **Training** → opens the evaluation wizard on the activity: mark attendance, optionally rate, done.
- **Match with no live-tracked execution** ("paper match") → the same wizard, but attendance also collects per-player **minutes**.
- **Match with a live match-execution** → routes to the match's **Resume / Finalize** flow instead; minutes come from finalize, so the wizard doesn't re-ask.

The activity flips to **Completed** only when the flow finishes (the wizard's final save, or the match finalize). Abandoning the flow leaves it **Planned** — re-launching resumes without duplicating attendance.

The detail view also carries **Cancel activity** (on a planned activity) and **Reopen** (on a completed or cancelled one) — direct, confirmed status changes.

**The header actions follow the status.** A planned activity is the only one you can change, so it is the only one that offers **Edit** and the run actions. Once an activity is completed or cancelled the header switches to reading what happened:

- **Edit** disappears. **Reopen** is the way back to an editable record — it returns the activity to *Planned* and Edit comes back with it.
- **Match prep** reads **View match prep** and opens the line-up that was prepared. On a planned match the label already tells you whether a prep exists: **Plan match prep** when there is none yet, **Match prep** once there is.
- The match-day button no longer offers **Start match** or **Resume match** on a finished activity. **View match** is the only execution label left, and only when the match was actually tracked.
- A training reads **View this training** and opens the plan that was run. A finished training with no plan attached shows no run button at all — there is nothing to look at, and the button would otherwise invite you to start one.
- The rating button says what is left to do: **Rate players** when nobody has been rated, **Continue rating** when some have, and it disappears once every attending player carries a rating. Completing an activity does not rate anyone by itself — the rate step is skippable and a match finalize writes minutes only — so the button stays available after completion until the work is genuinely done. The **Ratings grid** button next to it stays put either way, for reviewing or correcting ratings that are already in.

An **archived** activity is stricter still: everything mutating is hidden and only **Restore** remains.

### Four buttons, and a ⋯ for the rest

The header shows what you came to do and folds everything else behind a **⋯** button on the same row. Which actions sit on the row depends on the activity's status:

| Status | On the row | Behind ⋯ |
| --- | --- | --- |
| Planned | Edit · Match prep (or Run this training) · Complete activity · Cancel activity | Start match, the three grids, Sync from Spond, Archive |
| Completed | Match analysis · Archive | Reopen, View match prep, rating, the three grids, Sync from Spond |
| Cancelled | Archive | Reopen |

Nothing new becomes reachable by being folded away: every action keeps the permission, feature-flag and archived-record rules it already had, and one you could not see before is still not there. If every folded action is hidden for you, the ⋯ button does not appear at all.

The menu opens on click, on Enter and on Space, closes on Escape or a click outside, and returns you to the ⋯ button when it closes. On a phone it spans the width of the header rather than hanging off the right edge.

**The three grids and Sync from Spond are icons.** *Attendance grid*, *Minutes + statistics* and *Ratings grid* are shortcuts into the same bulk surface rather than three separate decisions, and re-pulling a Spond calendar is maintenance. In the header they would be four text buttons competing with Complete and Cancel; inside the menu they get their words back. Every one of them still names itself to a screen reader and on hover.

**Match analysis waits for completion.** *Write the match analysis* appears once you complete the activity, not when the date arrives — there is nothing to review while the match is still being played.

**With the guided wizards switched off** the button reads **Mark attendance** instead and opens the [attendance grid](attendance-grid.md) on that activity's own column — same place on the list card and the detail view, and the dashboard's **Mark attendance** hero goes there too. A match that *is* live-tracked still routes to Resume / Finalize. Because a single grid save can cover weeks of sessions, recording attendance there deliberately doesn't complete anything: a planned activity gains a **Mark completed** button for that, next to Cancel. Record attendance first, then mark it completed — **Reopen** undoes it.

### Match minutes (paper matches)

When you complete a **match**-type activity that was never live-tracked, the wizard's attendance step gains **Starter** and **Minutes** columns so you can log how long each player actually played. The minutes feed the minutes report and the player's load picture. For a match you *did* live-track, the minutes come from the match execution's finalize instead.

### Tournament minutes

**Tournaments record minutes too** — they're a minutes-bearing type just like a match. How you record them depends on the tournament:

- **Single-game tournament** — plan a **match prep** line-up and run the **live match** surface exactly as you would for a match; minutes come from the finalize. The **Plan match prep** and match-day CTAs appear on a tournament's detail view just like a match's.
- **Multi-game-day tournament** — record minutes with the by-hand **Minutes** entry on the attendance screen (the same Starter/Minutes columns as a paper match), logging how many minutes each player played across the whole day.

Both paths write the recorded minutes to the attendance row, so the minutes reports pick them up. For a multi-game day the line-up-derived **starts (basisplaatsen)** are approximate — one line-up covers several games — so the recorded *minutes* are the meaningful figure, not the start count. A tournament with no recorded minutes still shows 0, exactly like a match.

The activity list shows the type as a colour-coded pill so trainings, games, tournaments, meetings and other activities are easy to scan at a glance.

## Expected attendance

When you create an activity you pick which players are expected — the roster step defaults to the whole team, and you untick anyone you already know is away. Those picks are the activity's **planned roster**.

Open an activity's detail page and you'll see an **Expected attendance** panel listing those players (guests are tagged), with the count in the heading, so you know who to expect before the session. When some players are marked away the panel shows a summary such as *"2 not coming · 1 maybe"* and tags each affected player. It shows nothing if you chose "Set attendance later" at creation. Marking who actually turned up happens in the guided completion flow (**Complete activity** / **Continue rating**) — the planned roster is what you expected, the marked attendance is what happened. The detail view keeps a read-only attendance summary on completed activities.

### Adjusting the plan

The plan is not frozen at creation. Edit an activity that has **not yet been completed** and you'll find a **Planned attendance** section: one row per planned player with a status you can set — **Expected**, **Not coming**, or **Maybe** — plus a free-text **note** (e.g. "texted, injured"). Save and the plan updates. The detail panel's **Edit plan** link jumps you straight there.

If the activity was created with "Set attendance later" (so it has no planned roster yet), the section seeds itself from the current team roster with everyone set to **Expected**, so you can start managing the plan from scratch.

Marking a player **Not coming** early feeds the later attendance defaults: when you eventually complete the activity, the match-prep availability step already knows who you didn't expect, so your early note isn't lost. Adjusting the plan never touches recorded (completed) attendance — the two are kept separate, so the attendance reports are unaffected.

If you create an activity **already marked Completed** (it happened in the past) and don't enter attendance, the full active roster is recorded as **present** automatically so the activity is immediately rateable — adjust any absences in the guided completion flow afterward.

## Why the type matters

Each activity type can be linked to a workflow template that fires when you save an activity of that type. By default:

- **Game** spawns a post-game evaluation task per player on the team.
- **Training** and **Other** don't spawn anything.

Your academy admin can change which template fires for each type — or add a new type and pick its workflow template — under **Configuration → Activity Types**. The seeded types can't be deleted because the post-game evaluation rule depends on the **Game** type existing.

## Which week of the cycle a training is in

When the team has a [VCT cycle](configuration-vct.md), each training
records the week of that cycle it was planned as, and the detail page
shows it: **Week 3**, or **Neutral — game this week**.

A neutral week always says why. "Neutral" on its own reads as a bug, and
nobody who thinks the planner is buggy keeps using it.

The week is written when the training is saved, and it is a **record of
what the training was**, not a live calculation. Add a game in October
and every training after it shifts back a week — but the trainings
*before* it keep the week they were planned as. That is deliberate: a
player's training history from three months ago should still say what it
said at the time, rather than quietly rewriting itself every time the
season is rearranged.

### Changing it for one training

The edit form's **Cycle week** field takes one of three answers:

- **Follow the cycle** — the default. The week is worked out for you and
  kept up to date.
- **Neutral** — this one training runs at neutral intensity.
- **A specific week** — pin it to a week of the cycle you choose.

Choosing anything other than "Follow the cycle" fixes the training's week
for good: it stops shifting when fixtures move. Setting it back to
"Follow the cycle" hands it straight back and recalculates it on the spot.

This changes **one training**. To move the whole team's week — and
everything after it — use the cycle calendar under VCT configuration
instead. The two are easy to confuse, and only one of them is what you
want when a squad's whole rhythm is off.

## Status and source

Every activity carries two extra fields beyond the headline type:

- **Status** — where the activity is in its lifecycle. **Planned** is the default for newly-created activities. It flips to **Completed** when you finish the guided completion flow (see [Completing an activity](#completing-an-activity)), and to **Cancelled** via the detail view's **Cancel activity** button; **Reopen** returns it to Planned. Status values are admin-extensible under **Configuration → Lookups** (lookup type `activity_status`).
- **Source** — who or what created the activity. **Manual** for activities created in the app, **Generated** for ones produced by the demo-data generator, and **Spond** for activities synced from a Spond calendar (when the integration is enabled). Source is set automatically; you don't pick it on the form. Like status, the list of sources is admin-extensible.

The Head of Development's 90-day quarterly rollup also uses these types: it shows one row per type in use, so renaming or adding types reflects there automatically.

## Who created and changed it

The activity detail page shows a small line at the bottom of the detail panel: **Created by** whoever added the activity, on the date they did, and **Last changed by** whoever most recently edited it. This is recorded automatically from now on — activities created before this was added show nothing there (there's no history to fill in), and the line only appears once an author is known.

## Guests

You can add players from outside the squad to an activity — for example a player borrowed from another team for a friendly, or a trial player.

There are two kinds of guest:

- **Linked guest** — an existing player from another team. Search for their name and pick them. Any evaluation you write attaches to their profile.
- **Anonymous guest** — name only, no record yet. Useful for one-off trial players. You can promote them to a real player later via **Add as player**.

To add a guest, open the activity, scroll to the **Guests** section, click **+ Add guest**, fill in the fields and click **Add**.

Guests don't count toward team statistics — attendance percentages and the team podium use the squad only.

A guest appearance **does** count toward the guesting player's own load. On a player's profile — their attendance KPI and the status engine's load input — a session they played as a guest for another team is counted alongside their own-team sessions, so a heavily played-up player's load reads accurately. The split is deliberate: *player load = everything the player did anywhere; team statistics = own-roster only.*

## Cleaning up

You can **archive** an activity to clean up old seasons without losing its history. The **Archive** button on the activity detail page hides the activity from the active timeline but keeps the row — and its attendance — intact.

Archiving is a soft delete, so the **Archive** and **Restore** buttons need the activities *create/delete* capability — the same permission as creating an activity. A coach who can only edit activities (for example an assistant coach) can still change an activity but won't see Archive or Restore.

Archived activities live under the **Archived** status tab on the activities list (see [Filters](#filters)). From there you can:

- **Restore** an activity — it returns to the active timeline exactly as it was. Opening an archived activity's own detail page now shows a **Restore** button in its header too (in place of Archive), so you can bring it back with one click without hunting through the list. An archived activity is read-only until restored — the Edit and match actions stay hidden until it is active again.
- **Delete permanently** — an admin-only action (requires the *edit settings* capability) that removes the activity for good. This cannot be undone. If the activity still has attached records (attendance, exercises, match data), the delete is blocked and the activity stays archived — restore it or clear those records first. Archiving is the safe default; permanent deletion is the rare exception.

## Principles practiced

Each activity can be tagged with one or more methodology principles so reports can ask "how often did we work on principle X this period?" The Principles practiced multiselect appears on both the public Activity edit page and the wp-admin form — pick from the principles configured under Methodology. The link is optional.

## Admin guest panel

The wp-admin Activity edit page now shows a read-only list of guest attendees recorded against the activity. Add or remove guests from the public Activity page; the admin panel stays in sync.

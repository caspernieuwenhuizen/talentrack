---
title: Player Development Plan (PDP)
group: performance
summary: Per-season development files, conversation cadence, end-of-season verdict.
audience: [user]
views: [pdp, my-pdp]
module: TT\Modules\Pdp\PdpModule
capability: tt_view_pdp
order: 60
---

# Player Development Plan (PDP)

A **PDP file** is a per-season development plan for a single player. It pulls together what would otherwise be scattered across evaluations, goals, and ad-hoc notes — and gives the academy a structured, repeatable rhythm: configurable conversations across the season, polymorphic goal links into the methodology vocabulary, and a deliberate end-of-season verdict signed off by the head of academy.

## Who sees what

- **Coaches** — full edit on PDP files for players on their own teams. Tile: **Performance → PDP**.
- **Head of academy** — global edit on every file plus exclusive write access to the end-of-season verdict.
- **Players** — read-only of their own file, presented as a season timeline, plus an editable self-reflection for the single next-planned conversation. Tile: **Me → My PDP**.
- **Parents / guardians** — read-only of their child's file (after sign-off) plus a per-conversation acknowledgement button.
- **Read-only observer** — read-only across all files; no edit, no acknowledgement.

## PDP setup: who has a PDP this season

The **PDP** tile opens on a single **player-centric list** for the current season instead of a bare list of files. It starts from the player (CLAUDE.md §1): every player you coach is shown once, with a clear indicator of whether their PDP **for this season** exists yet.

- If you span **more than one team** (or have global scope), you first pick a team — *"Select a team to see its players."* — so you start scoped rather than facing every player at once. A coach with a single team skips straight to their roster.
- A summary line at the top reads, for example, *"14 of 18 players have a PDP for the current season (2025/26)."*
- Each row shows the **player** (linked to their record), their **team**, and a **PDP this season** status:
 - **Created** — a green *PDP ✓* pill, with conversation progress where available (e.g. *PDP ✓ 1/3*), linking straight to the file.
 - **Not started** — a grey *Not started* pill plus a **Create PDP** button that opens the create flow pre-filled for that player and team.
- **Filters** — team dropdown + player search, scoped the same way as the rest of the app: coaches see only their own teams' players; admins see everyone.
- **Only players without a PDP** — a one-click toggle to hide everyone who already has a file, so you can work straight through the gaps.
- The **⋯** button at the end of the filter row (for operators who can unarchive or delete) switches the list to the players whose PDP for the season is **archived**, with per-row **Restore** / permanent-delete. This replaces the old separate Files tab — archived files now live in the same list.
- Clicking a covered row opens the player's PDP file; clicking a missing row jumps into the create flow.

The coverage data is also available over REST at `GET /wp-json/talenttrack/v1/pdp-files/coverage` (`season_id`, `filter[team_id]`, `search`, `only_missing`, `archived`), so a future front end gets the same answer.

## The flow

### 1. Open the file

From the **PDP** tile, click *Create PDP* on a player's row (or *Open new PDP file*), pick a player and click *Open new PDP file*. The file is created with one row per conversation in the cycle (2, 3, or 4 — configurable per club, overridable per team) with each `scheduled_at` distributed evenly across the season's start and end dates.

A native calendar entry is written for every conversation.

### 2. Run the conversations

Each conversation has two halves:

- **Pre-meeting** — agenda + the player's self-reflection.
- **Post-meeting** — notes, agreed actions, and a sign-off.

The form's **evidence sidebar** lists every evaluation, activity, and goal change for that player since the previous conversation — read-only, just there to anchor the discussion.

The conversations run in order: only the **active** conversation — the earliest one not yet signed off — is fully editable. Later conversations in the cycle are read-only except for their **planned date**, so a coach can schedule the whole season ahead without filling in a talk out of turn. A later conversation opens for full editing once the one before it is signed off.

The player can fill in their self-reflection any time before the coach signs off. Once the coach signs off, the field locks.

### The conversation form saves itself

There is no **Save conversation** button. The form saves as you write, and a status line where the button used to be says where it got to — *Unsaved changes…*, *Saving…*, *All changes saved*. **Undo** and **Revert changes** sit beside it, exactly as on every other autosaving screen; both are described in full in [how saving works](save-model.md).

The player's **self-reflection** works the same way, while its window is open. Outside the window the field is disabled, as before, and nothing saves.

**Sign-off is now its own button, not a checkbox on the form.** It used to be a tick you saved along with everything else. On a form that saves itself that would be one accidental tap away from locking the conversation for everyone, permanently — so it is a separate **Sign off** button below the form, behind a confirm. Everything above it is already saved by the time you press it; signing off is what closes the conversation for editing.

A **content-locked** conversation — a later talk in the cycle — still autosaves its planned date, which is the only field it lets you change. A **signed-off** conversation autosaves nothing, because there is nothing left to write.

### 3. Acknowledgement

After sign-off the conversation appears on the player's *My PDP* view (and the parent's, if they're linked). Both can click *Acknowledge* — a lightweight "I've seen this" timestamp.

When the talk happens in person, the coach can also record those acknowledgements on the conversation form — *Record player acknowledgement* / *Record parent acknowledgement*, each behind a confirm dialog. It writes the same acknowledgement as if the player or parent had clicked it themselves. Confirm only when they actually acknowledged the conversation with you.

### What the player sees: a timeline-first My PDP

*My PDP* is the player's **preparation and self-review** space, built around the season as a timeline.

- **Season timeline on top.** The season's development conversations sit on a horizontal rail as markers, each in one of three states: **completed** (a green ✓), **planned** (the gold next-up talk), and **later** (muted). A progress fill runs along the rail up to the most recent completed talk. Tapping a marker **expands that conversation's detail in place** — notes, agreed actions, agenda, the goals discussed, any saved reflection, and the acknowledgement button — so there is no long scroll. The markers are keyboard-operable (Tab to focus, Enter/Space to open, Escape to close).
- **Active goals below the timeline.** The player's current focus goals (not the full archive), each with its goal-specific status label (e.g. *In ontwikkeling*) and target date.
- **One self-reflection input.** Only the **single next-planned** conversation can take a reflection — past and future talks never show an input. The input appears once its 2-week pre-talk window opens; before that, a guard line explains when it will appear. Any previously-saved reflection shows **to the right** of the input on wider screens and **stacked below** it on mobile. The self-review is helpful, never required — nothing is blocked if the player skips it.
- **End-of-season verdict** closes the page once recorded.

Parents see the same timeline for their child, read-only and possessive ("&lt;Child&gt;'s development plan"): the saved reflection is shown but there is no editable input, and they acknowledge via their own button. The timeline state is derived from the seeded conversations and their planning windows — no schedule or window data changes.

### 4. End-of-season verdict

When the cycle's last conversation is signed off, the head of academy (or head coach in some configurations) records a verdict: **promote**, **retain**, **release**, or **transfer**. The verdict is its own row, signed off independently from the conversation rows.

The *Record verdict* button sits with the conversation list, below the cycle. It stays **disabled until every conversation in the cycle is signed off**, showing the progress on the button itself — e.g. *Record verdict (3/5 conversations closed)* — so it's clear why it isn't available yet rather than missing.

## Carryover

Setting a new season as current triggers a one-shot job: every open PDP file in the previous season's roster is replicated for the new season — fresh conversations, fresh `created_at`, but the player's open goals (anything not `completed` or `archived`) carry forward.

Conversation prose does **not** carry over. Each season starts with a clean slate; the previous season's history stays where it is.

## Goal links

A goal can now link to one or more methodology entities:

- a **principle** (e.g. *playing out from the back*)
- a **football action** (e.g. *passing under pressure*)
- a **position** (e.g. *number 8*)
- a **player value** (commitment, coachability, leadership, resilience, communication, work ethic, fair play, ambition — editable from Configuration → Lookups)

The links are surfaced on the player profile and in the print template; they let you query "all goals tied to *resilience* across the academy" or "every player working on *playing out from the back*".

### Goals ↔ development talks

A goal can also link to a **development talk**. On the conversation form, the coach ticks **Goals discussed in this talk** from the player's active goals; those links are saved alongside the talk. On *My PDP*, expanding a conversation marker shows a **Goals discussed** list, so the player's self-review reflects on the goals that were actually covered — PDP and goals become genuinely combined rather than sitting side by side.

## Print

The detail view's **Print / PDF** button opens a clean A4 layout: photo, season label, current goals + status, agreed actions per conversation, and signature lines for coach / player / parent. Toggle *Re-render with evidence page* to add a second A4 with recent evaluations and activities.

## Configuration

- **Configuration → Lookups → Player values** — edit the value vocabulary.
- **Top-level menu → Seasons** — list, add, set current. Setting a new season as current triggers carryover.
- **Configuration → System** — the *PDP cycle default* (2 / 3 / 4) and *Print: include evidence by default* toggle.
- **Per-team override** — on the team edit page, set *PDP cycle size* to override the club default.
- **Configuration → PDP preparation questions** — what a coach is asked to think about before each conversation, with a separate set per conversation in the cycle. Every academy starts with a shipped set and can add, reword, reorder or remove questions.

### Preparation questions

The start of a season asks different things than the end, so each conversation in the cycle carries its own set of prompts. Answers are **private to the coach and the head of academy** — never shown to the player or their parents, on any surface.

Rewording a question that has already been answered does not change what those answers were given against: the old wording is kept on the preps that hold them, and the new wording applies from the next conversation onward. So a prep written last season still reads the way it was written.

## Workflow nudges

Three task templates are registered:

- `pdp_conversation_due` — reminds the owning coach as a conversation's `scheduled_at` approaches.
- `pdp_verdict_due` — reminds the head of academy at season-end.
- `pdp_self_review` — nudges the **player** to prepare for an upcoming talk.

All go through the workflow & tasks engine — the same inbox, the same nudge cadence configurable from Configuration → Workflow.

### Self-review nudge

When a conversation's planning window opens, the player gets a **"Prepare for your development talk"** task in *My tasks / Today's work*, due on the talk date. Tapping it opens *My PDP* at the self-reflection. The sweep that creates these runs on the workflow engine's own scheduler and is idempotent — exactly one task per conversation, no duplicates.

It is a **nudge, not a gate**:

- Saving the reflection **completes** the task.
- Conducting the talk **auto-resolves** the task with no penalty, even if the reflection was never filled in.
- Nothing is ever blocked if the player ignores it.

On the coach side, the conversation list gains a **Self-review** column showing **Done / Not yet** per upcoming talk — visibility only, never a gate on conducting or signing off.

## Cycle progress and acknowledgement visibility

The PDP file detail page shows cycle progress as **(X of N signed off)** next to the cycle size, so you see at a glance how much of the cycle is complete. Each conversation row carries:

- a derived **status** badge (Scheduled / Held / Signed off) instead of three separate date+yes columns
- an **Acks** column with parent (👤) and player (⚽) acknowledgement icons — `✓` once acked, `·` while pending

The summary card has a context-sensitive help button that opens the PDP topic in the docs drawer.

## Archive vs. permanently delete

PDP files have **two** removal paths so destructive cleanup never costs an accidental click on the wrong row.

- **Archive** — soft-delete. The file disappears from the default list, but every row stays in the database. Coaches with edit reach can archive an active file (`Archive` button in the actions column). Admins can flip the *Show archived* toggle on the PDP list and click *Restore* to bring it back. This is the right answer when a player leaves mid-season or the cycle was opened by mistake.
- **Permanently delete** — irreversible hard-delete. Available only to operators holding the `tt_delete_pdp` capability (admin only by seed). Two entry points: the *Permanently delete PDP* button on the PDP file detail page, **and** a per-row *Delete permanently* action on archived files (flip the *Show archived* toggle on the PDP list — operators with `tt_delete_pdp` see the toggle even without restore reach). Both open the same confirm subview that:
 - shows a **Cascade summary** — how many conversations / verdicts / calendar links / PDP blocks / goal-link rows will vanish.
 - requires the operator to **type the player's name** verbatim before the *Permanently delete PDP* button enables (case-insensitive, trim-tolerant).
 - writes a **pre-delete CSV snapshot** to `wp-content/uploads/tt-pdp-deletes/pdp-<file-id>-<timestamp>.csv` before the cascade runs. The CSV's absolute path is recorded in the `pdp.deleted_with_cascade` audit-log entry alongside the per-table row counts.
 - runs the five-table cascade inside a single transaction. Any failure rolls the whole thing back; partial state on failure is impossible.

Use *Archive* by default. Reach for *Permanently delete* only for GDPR-erasure, parental-request, or other legitimate data-retention cases. The CSV snapshot is your audit trail — keep it.

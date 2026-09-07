---
title: How saving works
group: basics
summary: Which screens save themselves, which need Save, and how far Undo reaches.
audience: [user, admin, dev]
order: 30
---

# How saving works

TalentTrack has three ways of committing your work, and every screen uses
exactly one of them. Which one it uses is not an accident — it follows from
what the screen is for.

A coach should never have to guess whether their work is safe, so each model
says on its face which one it is.

## 1. The screen saves itself

**What you see.** No Save button. A status line instead, in the same words
everywhere: *Unsaved changes…* while you type, *Saving…* while it is on its
way, *All changes saved* when it is done, and *Save failed — retry* when it is
not. Beside it, **Undo** and **Revert changes**.

**Where.**

| Screen | Notes |
| --- | --- |
| [Match preparation](match-prep.md) | The whole screen |
| [Match analysis](match-analysis.md) | Draft only; **Mark as final** publishes it |
| [Evaluations](evaluations.md) | Editing an existing one |
| [Player goals](goals.md) | Editing an existing one |
| [PDP conversation](pdp-cycle.md) | Until it is signed off |
| [PDP preparation](pdp-cycle.md) | The coach's answers, until the conversation is signed off |
| [PDP self-reflection](pdp-cycle.md) | While the reflection window is open |

**Why these.** They are all places where you *compose* — you write sentences
about a player, over minutes, often on a phone, often standing somewhere
inconvenient. The thing most worth protecting there is the paragraph you were
halfway through, and a Save button you have to remember is exactly the wrong
protection for it.

**There is no Cancel**, because there is nothing uncommitted to walk away
from. Undo and Revert are what "I did not mean that" looks like here, and they
reach further than abandoning a form ever did.

### How far Undo reaches

Two ranges, because they answer two different mistakes.

- **Undo** — the last change that was saved. One step, not a history. It
  disappears once you use it; make the edit again if you want it back. The undo
  is itself saved, so reloading does not bring the change back.
- **Revert changes** — the whole screen, back to how it was when you opened
  it. It asks first, names how many fields it will restore, and warns that the
  restore cannot itself be undone.

Both are offered only on a settled screen — while the status line says
*Saving…* or *Unsaved changes…* they are hidden, so neither can fight with a
request already on its way.

**Revert belongs to this device and this sitting.** The starting point is kept
in your browser, not on the server. That is what makes it survive a reload or
an accidental tab close. It also means opening the same record on another
device, or coming back the next morning, gives you the saved document with no
revert offered — the sitting ended. A private window, a cleared browser, or a
browser that refuses storage means no revert is offered at all; everything else
on the screen still works exactly the same.

Neither is version history, and neither is trying to be. There are no
server-side snapshots, no per-field history, and no restoring to a date.

### Publishing is not saving

Two screens have one deliberate button left, and neither of them is a Save:

- **Mark as final** on a match analysis. Until you press it the staff share
  link says the analysis is not finished yet; after it, the link shows the
  document. Editing a final analysis afterwards leaves it final.
- **Sign off** on a PDP conversation. Everything above it is already saved;
  signing off closes the conversation for editing, for everyone.

Both are irreversible in practice, which is why neither is a checkbox on a
screen that saves itself.

## 2. Save, with a real Cancel

**What you see.** A **Save** button and a **Cancel** button beside it, Cancel
first in tab order and Save on the right where the thumb finds it.

**Where.**

- The three data-entry grids: attendance, minutes, ratings.
- Short record forms: player, team, person, activity, and the rest.
- Creating an evaluation or a goal — as opposed to editing one.
- Configuration screens and lookup lists.
- The [VCT cycle calendar](configuration-vct.md), where weeks are set to
  neutral or made to run anyway.

**Why the cycle calendar.** Changing one week shifts every week after it for
the rest of the season. Saving each toggle as it was tapped would apply a run
of half-considered shifts one at a time, and there would be no single point to
undo them from — one commit for the set is what makes the knock-on legible.

**Why these — the grids first, because they are the interesting case.** The
grids are not waiting for autosave to reach them. Explicit Save is the right
model for a coach rating a whole squad pitchside on a flaky connection: they
get **one commit point**, so the record is either the session they entered or
the session before it, never a half-finished mixture of the two. And Cancel
means cancel. A half-finished commit is worse than a lost one when the thing
being committed is a set of judgements that only make sense together.

**Why the short record forms.** The fields are a small known set, and Save is
a useful pause — the beat where you check the date is right before you commit
it. Autosaving them would remove that pause and buy nothing.

**Why creating is different from editing.** Autosave writes *to a record*, and
while you are creating one there is nothing to write to. A create form that
autosaved would leave an empty evaluation or an empty goal on a player's file
behind everyone who opened it and thought better of it.

## 3. Draft, then submit

**What you see.** A wizard: Previous / Next / Cancel, and one commit on the
final step.

**Where.** Every wizard — new player, new evaluation, new goal, new match
analysis, install and import.

**Why.** A wizard already keeps its own draft between steps, so nothing is
lost if you stop halfway and come back. What it does not do is write into the
live record until the last step, which is what lets you abandon it cleanly.

## Posting is not saving

One more thing that looks like a save and is not: the **conversation** boxes —
player notes, and the conversation on a goal.

A note is a *post*, not a field. It is sent when you press **Send**, and not
before: nothing half-written reaches the staff, and no notification goes out
for a sentence you were still choosing the words for. A posted note can be
edited for five minutes afterwards, and after that it stands as written.

What the box does do is keep your draft. Close the tab, come back later on the
same device, and the sentence you were in the middle of is still there. It is
cleared the moment the note is actually sent, and it never leaves your own
browser.

## For developers: choosing a model for a new surface

Ask what the surface is, not what is convenient:

1. **Does somebody compose here?** Sentences, judgements, something written
   over minutes rather than filled in. → **Autosave.** Use
   `\TT\Shared\Frontend\Components\FormAutosave` and
   `\TT\Shared\Frontend\Components\SaveState`; the save state, undo and revert
   come with them. Do not hand-roll a debounce.
2. **Is a half-finished commit worse than a lost one?** A set of values that
   only mean something together, entered in one pass, possibly on a bad
   connection. → **Explicit Save**, with a real Cancel per `CLAUDE.md` §6.
3. **Is this creating a record?** → **Explicit Save or a wizard**, never
   autosave. There is no record to write to yet.

Three rules that hold whichever model you pick:

- **The endpoint must accept partial updates before autosave points at it.**
  An endpoint that rebuilds the whole row from the request plus a debounce is a
  data-loss bug, and it will not announce itself — it looks like a coach's
  write-up disappearing when they edit a different field. Add a test that a
  write omitting a field leaves it alone; `tests/php/AutosaveWriteContractTest.php`
  is the pattern.
- **A commit that cannot be taken back is never a field on an autosaving
  form.** Publishing, signing off, submitting: separate control, separate
  confirm, outside the form.
- **Say which model the surface is using.** A screen that saves itself shows
  the status line; a screen that does not shows a Save button. Silence is the
  failure this rule exists to remove.

`CLAUDE.md` carries the short version as an always-on principle, alongside the
Save + Cancel rule it qualifies.

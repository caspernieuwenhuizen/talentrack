# PDP evidence: the Evidence tab shows what the record actually says (#3303)

Bump: minor

The Evidence tab on a PDP conversation listed evaluation **dates** — no
rating, no assessor, no notes — ten activities and ten goals, as bulleted
lists inside a hardcoded grey panel that ignored every theme the plugin
ships. It ran its own three queries, so its numbers could disagree with the
printed file's for the same player on the same day.

It now renders the one evidence packet through a shared component, and
carries the whole picture: evaluations with their rating, assessor, notes
and per-category scores; attendance with the present / absent / excused
split alongside matches played, minutes and the per-match breakdown; goals
with whether each one has moved since the last talk; the player's own
self-reflection; staff notes, injuries and journey events; and the
potential and behaviour ratings set in the window.

Records link through to their own pages, with a back-pill home. Tables
reflow to labelled cards on a phone. **A section with nothing in it says
so** rather than disappearing — a coach needs to see that there is no
evidence, not be shown a shorter page.

The Conversation | Evidence tab strip is now the shared record-spine strip
rather than a hand-rolled one, so it gains arrow-key navigation and matches
every other tab strip in the plugin; its styling, and the conversation
form's signature-lock banner and self-reflection card, move out of inline
attributes into an enqueued stylesheet that reads the design tokens.

# PDP evidence: one packet, extended to what a conversation actually needs (#3302)

Bump: minor

`EvidencePacket` now carries the full picture a PDP conversation is meant
to be held against: evaluations with their assessor, notes and per-category
scores rather than a bare date; minutes played with the per-match
breakdown; injuries and return-to-play; the player's staff notes; goals
with what moved since the last talk; and, on a conversation, the player's
own self-reflection. Attendance keeps its present / absent / excused split
and now carries the rate.

A second entry point sits beside `forFile()`: `forConversation()` narrows
the same assembly to the window since the previous talk in the cycle. The
first conversation of a season has no predecessor, so it falls back to the
season — a coach preparing the opening talk wants the season, not an empty
page.

Every group is club-scoped and excludes archived and trashed rows. That is
the point of the slice: three surfaces used to assemble their own evidence
and filter it differently, so the same player on the same day could show a
coach one set of numbers and the head of academy another. The rendering
surfaces move onto this packet next; nothing a user sees changes yet.

The thread visibility gate moved out of `ThreadsRestController` into
`ThreadAccess`, so the packet answers "may this reader see this note" the
same way the notes tab does rather than deciding it a second time.

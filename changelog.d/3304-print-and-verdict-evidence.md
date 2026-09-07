# PDP evidence: the print and the verdict screen read the same packet (#3304)

Bump: minor

Two surfaces still assembled their own evidence. The printed file ran a
fourth variant — five evaluations and ten activities, scoped by neither
`club_id` nor the activity archive flag, printing raw ISO dates — so on a
multi-team install its numbers could legitimately disagree with the Evidence
tab a coach had open for the same player on the same day. The verdict
screen showed no evidence at all: the packet existed, its only caller was a
REST endpoint nothing consumed, and the head of academy read the numbers
somewhere else or from memory.

Both now render the shared evidence panel over
`EvidencePacket::forFile()`. The printed page has no links, since paper has
nowhere to click to, and forces the table layout rather than the phone card
stack, because the PDF exporter renders through DomPDF, which resolves no
viewport and ignores `@media print` outright. The `?include_evidence=1`
toggle still works exactly as it did — a coach printing a one-page summary
for a parent can leave the evidence page off.

On the verdict screen the panel sits above the decision as **Evidence for
this season**, collapsed: a head of academy who already knows the player
should not have to scroll a season's record to reach the four buttons, and
one who does not is a tap away from all of it.

The print router now carries no evidence SQL, and a test asserts the two
surfaces show the same rows for a seeded player — including that a row from
another club and a row from before the season reach neither of them, which
is exactly what the old print got wrong.

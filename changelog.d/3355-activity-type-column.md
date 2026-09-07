# VCT match-day context works again, and the fixture peeks fill in (#3355)

Four queries filtered on a `tt_activities` column that does not exist, using a
pattern the activity vocabulary has never contained. Each returned nothing and
each caller quietly took its "found nothing" branch.

The visible effects: every VCT training resolved a match-day context of NONE
regardless of when the team actually played, so the MD-specific training shapes
were never selected and the per-age "match-day logic" setting did nothing.
Publishing a VCT training wrote no bound activity at all. A player's "my team"
next-fixture and recent-form lines were permanently empty.

Match detection now runs off the canonical activity type, counts a tournament as
the multi-game day it is, and skips called-off fixtures. A regression test goes
through the production reader against real rows rather than the in-memory fake
the existing rule tests inject — which is why none of them caught this.

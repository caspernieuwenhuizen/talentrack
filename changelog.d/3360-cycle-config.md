# Give a team a training cycle of three, four or six weeks (#3360)

Bump: minor

Each team's row on VCT configuration → Team schedules now carries a cycle: how
many weeks it runs, the week it starts, and the weekly shape it repeats. Six
weeks is the default. A line under the fields names each week, so you can read
the shape without opening anything else.

Picking a weekly shape that does not have one row per week of the cycle is
refused rather than quietly accepted — a cycle whose last weeks come out flat
for no visible reason is not something anyone traces back to this screen.

Removing a cycle sends the team back to planning from the season's macro-blocks,
and takes any hand-set neutral weeks with it. Trainings already planned keep the
week they were given.

Setting a cycle needs the VCT configuration permission, which the head of
development holds.

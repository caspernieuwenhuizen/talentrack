# Analytics reports name the activity again instead of showing a bare id (#3356)

The activity dimension read a column that does not exist on the activities
table, so the lookup failed and every activity in a grouped report or CSV export
fell back to its raw id. The report still rendered — it was just wrong, which is
the hardest kind of reporting bug to notice.

Grouping by activity now shows "Training — 2026-02-17" as intended.

# The attendance and minutes reports filter without reloading (#3338)

Changing a team, a period or an activity type on a report reloaded the whole
page — and on a season-wide minutes audit that is a long wait staring at
nothing, because the slow part is the server working out the numbers.

The five Analytics reports now update in place: the tiles, the tables and the
empty states all change together, the filter bar dims while it works and stops
taking input, and screen readers are told what came back. Narrowing to a window
with no data now replaces the tables with "nothing recorded", instead of
leaving the previous window's numbers on screen.

Without JavaScript nothing changes — the filters still work by loading the
page.

# One period control on the reports and grids, not two (#3331)

Eight screens asked the same question twice: a period dropdown (*Last week*,
*This month*, *This season*) and, separately, a From/To date range. Two
controls for one window, and it was never obvious which of them the numbers
came from.

They are one control now. Picking a preset sets the window; **Custom range…**
opens the dates inside the same dropdown, and typing there wins. Whatever the
control says is the window the report actually used — pick a preset and it
names the preset, type dates and it names the dates.

Existing links, bookmarks and saved views keep working: the dates are the same
`from` and `to` they always were.

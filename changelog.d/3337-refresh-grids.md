# The attendance and minutes grids filter without reloading — and without losing your work (#3337)

Both grids now update in place when you change a team, a period or a type,
like the reports do.

The part that mattered most: if you have entered attendance or minutes and not
yet saved, changing a filter **asks first**. Say no and the grid and the filter
both stay exactly as they were. Nothing is saved on your behalf, and nothing is
thrown away without being asked — a filter is still a way of looking, not a way
of committing.

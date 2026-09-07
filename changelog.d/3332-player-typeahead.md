# Filtering by player means typing a name, not scrolling the academy (#3332)

The player filter on goals and evaluations was a dropdown listing every player
you could see — the whole academy for an admin, in whatever order the database
returned, with no way to type toward a name. On a phone it was a native picker
the length of the squad list.

Both now use the same type-to-filter player picker the rest of the app uses:
start typing a name and pick it. A coach still sees only their own teams'
players, exactly as before.

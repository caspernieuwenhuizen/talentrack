# Taking one filter off a list works, and the comparison view can be cleared (#3333)

The ✕ on a filter chip is built from the name of the control that set the
filter, and on every list screen those names are nested — which the code that
built the link could not remove. It was not yet reachable on most screens, but
it was one change away from being so.

The comparison screen also had no Clear at all: a date range and a dropdown,
neither with a "none" option, and no way back to the unfiltered view. It has
one now.

# Filtering the audit log no longer reloads the page (#3336)

Changing a filter on the audit log used to reload the whole screen: a white
flash, no sign anything was happening, and nothing stopping you picking a
second filter into a request that had already gone. On the widest date ranges
that is a long wait staring at a blank page.

The results now update in place. While they load, the filter bar dims and
stops taking input, and screen-reader users are told how many results came
back. Back and forward still work, and the address bar still carries your
filters, so a filtered view is still something you can bookmark or send to
someone.

Without JavaScript nothing changes — the filters still work by loading the
page, exactly as before.

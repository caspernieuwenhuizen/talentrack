# The message log and alerts inbox filter without reloading (#3339)

Changing a filter on either screen reloaded the whole page. They now update in
place, like the audit log: the bar dims while the results load, a second
filter cannot be queued into a request that has already gone, and screen
readers are told what came back.

Filtering an alerts inbox down to nothing now replaces the list with "nothing
needs your attention" rather than leaving the previous alerts on screen.

Without JavaScript nothing changes — the filters still work by loading the
page.

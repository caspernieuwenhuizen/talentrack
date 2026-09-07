# A training says which week of the cycle it is (#3362)

Bump: minor

Where a team has a cycle, each training now records the week of it they were
planned as, and the activity shows it: "Week 3", or "Neutral — game this week".
A neutral week always says why; on its own it just looks broken.

The week is a record of what the training was, not a live calculation. Add a
game in October and every training after it shifts back a week — the ones before
it keep what they said. A player's training history from three months ago should
still read the way it read at the time.

The edit form's **Cycle week** field changes one training: follow the cycle, run
it neutral, or pin it to a week you choose. Anything but "follow the cycle" fixes
that training's week for good, so it stops moving when fixtures do; setting it
back hands it straight to the cycle again. To move a whole team's week, the cycle
calendar under VCT configuration is still the place.

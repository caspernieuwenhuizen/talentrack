# See the cycle week by week, and correct any week (#3361)

Bump: minor

A new **Cycle** tab on VCT configuration lays out a team's season one week at a
time: which week of the cycle it is, the phase, theme and intensity, and why the
week is what it is. A neutral week says either "there is a game this week" or who
set it by hand and when — without that, a neutral week just looks like a bug.

The list is also the only place the pause rule is visible: cycle week 3 turns up
*after* a neutral week rather than being spent on it.

Each week offers three choices rather than a checkbox — automatic, force
neutral, or run anyway. The third is the one that comes up in practice: a
friendly you never meant to interrupt anything. Setting a week back to automatic
removes the correction rather than remembering it.

One Save commits the lot, and the list redraws so you can see how the later weeks
shifted.

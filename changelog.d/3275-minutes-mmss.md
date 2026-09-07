# A test measured in minutes now offers mm:ss without being asked (#3275)

Setting up a test in minutes and opening the entry grid gave you a decimal
number box: `11:30` was refused, and `1130` was accepted without complaint and
stored as nearly nineteen hours. The mm:ss option existed, but it sat below
"Custom unit" where nobody looking at the unit picker would find it.

Tests measured in minutes or hours now use mm:ss by default — seconds and
milliseconds stay decimal, because a sprint is `2.05 s` and not `0:02.05`. The
toggle moved directly under the unit picker and still overrides in either
direction, and a single reading is capped at four hours so a typo like `1130`
is refused instead of stored.

The entry row also stops printing the unit twice.

# PDP preparation: configurable question sets per conversation (#3305)

Bump: minor

A coach preparing a PDP conversation had one free-text box labelled
"Agenda". Nothing differed between the start-of-season talk and the
end-of-season one, and nothing made one coach's preparation comparable
with another's or with their own from three months earlier.

**Configuration → PDP preparation questions** replaces it with a question
set per conversation in the cycle. Every academy starts with a shipped set —
"Where is this player now?", "Which goals have moved, and which have not?",
"What will you recommend to the head of academy?" — and can add, reword,
reorder or remove questions per conversation.

Rewording a question that has already been answered does not rewrite
history. The old wording stays on the preps that hold those answers and
the new wording applies from the next conversation onward, so a prep
written last season still reads the way it was written.

The answers are private to the coach and the head of academy, enforced at
the repository rather than per screen, so a surface added later cannot
forget to ask. A player or parent token gets nothing back for their own
conversation.

The form that puts these questions in front of a coach ships next; this
release adds the schema, the REST endpoints and the settings screen.

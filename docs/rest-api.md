---
title: REST API reference
group: developer
summary: Plugin REST endpoints, payload shapes and capability scopes.
audience: [dev]
order: 10
---

# REST API reference

Plugin namespace: `talenttrack/v1` (full base: `/wp-json/talenttrack/v1`).

Every endpoint authenticates via the standard WordPress REST flow — pass the `X-WP-Nonce` header carrying a `wp_rest` nonce on logged-in browser requests, or use application passwords / OAuth for external integrations. Capability checks happen in each controller's `permission_callback`; a user without the required cap gets a 401/403 from WP itself before the handler runs.

The canonical machine-readable contract lives in [`docs/openapi.yaml`](openapi.yaml). This document is the human-readable narrative; if the two disagree, treat the OpenAPI spec as authoritative and open an issue. A self-contained contract test ships at [`bin/contract-test.php`](../bin/contract-test.php) — run it with `wp eval-file bin/contract-test.php` (or `WP_LOAD=/path/to/wp-load.php php bin/contract-test.php`) to verify every read endpoint returns the standard envelope shape.

## v1 → v2 migration policy (#0052 PR-C)

Breaking changes to a `talenttrack/v1` endpoint shape bump the namespace to `talenttrack/v2`. The v1 namespace is supported for at least one release after v2 ships, with `Deprecation: true` headers on the v1 responses. Additive changes (new optional field, new endpoint) **don't** trigger a bump — they go into v1 as before.

This policy is **codified but not yet exercised** — every change to v1 so far has been backwards-compatible.

## Vocabulary constants — backward-compat allowlist (#988)

From v4.10.1 the activities + attendance vocabularies have typed PHP constants in `TT\Domain\Vocabularies\Lookups\*` (`AttendanceStatus`, `ActivityTypeKey`, `ActivityStatusKey`, `GameSubtype`). The REST endpoints that read these fields — `POST/PUT /sessions`, `POST/PATCH /sessions/{id}/guests`, `PATCH /attendance/{id}`, `POST /tournaments/{id}/matches` — **continue to accept the raw string literals AND the new typed constants** for one release.

From v4.12.1 the same pattern extends to the goal-side vocabularies: `GoalStatus`, `GoalPriority`, `GoalApprovalDecision`. The `POST /goals`, `PATCH /goals/{id}/status` endpoints continue to accept BOTH the raw literal (e.g. `'pending'`, `'pending_approval'`, `'medium'`) AND the corresponding typed constant for one release.

From v4.12.5 the same pattern extends to the tournament-side lookups (`TournamentFormation`, `TournamentOpponentLevel`) and the first code-only enum (`MatchExecutionState`). The `POST/PUT /tournaments`, `POST/PUT /tournaments/{id}/matches`, and `POST /match-execution/{activity_id}/{start-half|end-half|finish}` endpoints continue to accept BOTH the raw literal AND the corresponding typed constant for one release. Additionally, per the locked decisions on #988, `TT\Modules\MatchExecution\Repositories\MatchExecutionRepository::STATE_*` constants are now deprecated aliases that point at `TT\Domain\Vocabularies\Enums\MatchExecutionState::*` — the aliases stay for one release and are removed in the next minor.

From v4.12.7 the same pattern extends to the PDP-cycle and trial-case vocabularies: `PdpStatus`, `PdpVerdictDecision`, `TrialCaseStatus`, `TrialCaseDecision`. The `PATCH /pdp-files/{id}` (status field), `PUT /pdp-files/{id}/verdict` (decision field), and the trial-cases endpoints under `/trial-cases/*` (status + decision fields) continue to accept BOTH the raw literal (e.g. `'open'`, `'promote'`, `'extended'`, `'admit'`, `'continue_in_trial_group'`) AND the corresponding typed constant for one release.

From v4.12.8 the same pattern extends to the player-side roster vocabularies: `PlayerStatus`, `PreferredFoot`. The `POST/PUT /players`, `PATCH /players/{id}` endpoints continue to accept BOTH the raw literal (e.g. `'active'`, `'trial'`, `'released'`, `'graduated'`, `'inactive'`, `'left'`, `'right'`, `'both'`) AND the corresponding typed constant for one release.

From v4.12.9 the same pattern extends to the auth, ideas, invitations, and behaviour vocabularies: `IdeaStatus`, `IdeaType`, `InvitationStatus`, `InvitationKind`, `BehaviourRating`, `PotentialBand`, plus the first code-only enum on the auth side (`ImpersonationEndReason`). The `POST /players/{id}/potential` (potential_band field), the dev-ideas write surface, and the invitations REST endpoints continue to accept BOTH the raw literal (e.g. `'first_team'`, `'submitted'`, `'feat'`, `'pending'`, `'player'`) AND the corresponding typed constant for one release. Additionally, per the locked decisions on #988, `TT\Modules\Development\IdeaStatus::*`, `TT\Modules\Development\IdeaType::*`, `TT\Modules\Invitations\InvitationStatus::*`, and `TT\Modules\Invitations\InvitationKind::*` constants are now deprecated aliases that point at the corresponding `TT\Domain\Vocabularies\Lookups\*` values — the aliases stay for one release and are removed in the next minor.

Per the same shape as the v4.3.21 #953 blueprint-assignment deprecation and the #903 sunset, the allowlist drops in the next minor (v4.11.0 for PR-set 1; future minor for PR-sets 2 + 3 + 4 + 5 + 6 + 7): payloads carrying literals that don't match any value in the corresponding `::ALL` array will return `400 bad_value` instead of silently falling back to the seeded default. The matching PHPStan rule (issue #988 PR-set 8) lands at the same time.

PR-set 8 (the PHPStan rule that gates all literal -> constant migration enforcement) is the only remaining #988 PR-set after this ship.

## Resources

| Resource         | Routes                                                                                        | Source                                                  |
| ---              | ---                                                                                           | ---                                                     |
| Sessions         | `GET/POST /sessions`, `PUT/DELETE /sessions/{id}` (DELETE soft-archives, #1555), `POST /sessions/{id}/restore`, `DELETE /sessions/{id}/permanent` (gated on `tt_edit_settings`) | `src/Infrastructure/REST/ActivitiesRestController.php`    |
| Attendance (#0026) | `POST /sessions/{id}/guests`, `PATCH /attendance/{id}`, `DELETE /attendance/{id}`            | same controller                                         |
| Entry grids (#2382, #2386, #2414 — epic #2381) | `POST /attendance/bulk` (attendance grid), `POST /minutes/bulk` (minutes), `PUT /activities/{id}/contributions` (#3094 — goals + assists, body `{ players: [ { player_id, goals, assists } ] }`. Counts in, goal events out: the reconciliation lives in `MatchExecutionRepository::setContributions()` so the grid and a future front end write the same rows. A count up inserts manual events carrying no `execution_id`, `half` or `minute_in_half` — a coach filling this in afterwards does not know the minute and one is never invented. A count down sets `reversed_at` rather than deleting, typed rows before live ones. An assist attaches to an existing goal with a free `assist_player_id`, and only creates a scorerless goal when none is free, because an assist is a column on a goal and inserting a row per assist would inflate the score. `tt_activities.home_score` is never written), `POST /activities/{id}/ratings/bulk` (ratings grid — body `{ changes: [ { player_id, category_id, rating } ] }`; a null/blank `rating` means *not rated* and is skipped rather than written as a zero, so an untouched cell never clears an existing score). Each gates on `tt_edit_activities` **plus** its own feature toggle (`attendance_grid` / `minutes_grid` / `ratings_grid`), and enforces the caller's team scope per activity. The ratings write goes through `EvaluationInserter::upsertForActivity()` — the same writer the evaluation wizard uses — so re-saving updates the player's existing evaluation for that activity instead of appending a second one, and grid and wizard cannot diverge. Values outside the configured `rating_min`..`rating_max` / `rating_step` scale are refused server-side, not only by the input attributes. `GET/PUT /me/preferences/minutes-grid` (#3094) carries which statistic columns the caller wants shown — a per-user display preference in user meta, gated on being logged in because it reads and writes nothing but the caller's own row. | `ActivitiesRestController.php` |
| Players          | `GET/POST /players`, `PUT/DELETE /players/{id}`, `POST /players/import`                       | `PlayersRestController.php`                             |
| Player status (#0057, #3226) | `POST /players/{id}/behaviour-ratings`, `POST /players/{id}/potential` (set the band), `GET /players/{id}/potential` (the **trajectory** — every dated entry oldest-first, each carrying `direction` (`first` / `up` / `down` / `same`) and `steps`, plus `current` for the latest; `tt_player_potential` is append-only so nothing is ever overwritten), `GET /players/{id}/status` (the verdict), `GET /teams/{id}/player-statuses` (the squad board). Direction is computed by position in the `PotentialBand` vocabulary, which is ordered best-first — so a move toward `first_team` reads as `up` regardless of the underlying string. A band retired from the vocabulary after it was recorded still renders and simply carries no direction. Reads gate on `tt_view_player_status` **plus** `canViewPlayer`, which is what lets a parent read their own child and nobody else's; the potential write gates on `tt_set_player_potential`. The potential write also refuses `409 potential_below_age_floor` for a player under `PlayerStatusModule::POTENTIAL_MIN_AGE` (13) — the bands describe a professional ceiling, and a rule that only exists in the capture view is a rule every other client ignores; a player with no `date_of_birth` on record is not blocked (#3265). | `src/Infrastructure/REST/PlayerStatusRestController.php` |
| Trials (#0017, #3223) | `GET/POST /trial-cases`, `GET/PUT /trial-cases/{id}`, `POST /trial-cases/{id}/extend`, `POST /trial-cases/{id}/decision`, `GET/POST /trial-cases/{id}/staff`, `POST /trial-cases/{id}/inputs` (+ `/release`), `GET /trial-tracks`, `POST /trial-reminders/run`. **Letters (#3223)**: `GET /trial-cases/{id}/letters` lists what has been generated (audience, timestamps, `is_active`) and `POST /trial-cases/{id}/letters` generates one from a `audience` in `AudienceType::trialLetters()`, with optional `strengths_summary` / `growth_areas`. Generating **supersedes** — the service revokes the prior active letter, so a case has one letter that counts plus the history of what it replaced; two live letters saying different things to the same family is the failure this prevents. Both letter verbs gate on `tt_manage_trials`, matching the manager-only Letter tab. The list deliberately returns no body text: it answers "what has been sent", and the document itself is fetched through the reports surface that owns delivery and revocation. Per-case reads and input writes gate through `TrialCaseAccessPolicy`, which requires assignment for a non-manager. **Input writes also freeze at the decision (#3238)**: `POST /trial-cases/{id}/inputs` returns `409 case_closed_to_input` once the case leaves `open` / `extended`, which is the rule the Staff inputs tab has always applied and the endpoint did not — an assigned coach could otherwise rewrite through the API what they had said about a child after the academy decided on the strength of it, with no earlier version kept. Entry to the case is deliberately **not** frozen with it (`canOpenCase()` reads `isInputAuthor()`, not `canSubmitInput()`), so the coach who wrote an input can still open the decided case and read it. | `src/Modules/Trials/Rest/TrialsRestController.php` |
| Teams            | `GET/POST /teams`, `PUT/DELETE /teams/{id}`, roster ops at `/teams/{id}/players/{player_id}`. **Football form (#3044)**: a team row carries `football_form` (its own override, empty when it has none) and `football_form_resolved` (what it actually plays, after the age-group default). `football_form` is only written when the field is present in the request, so a caller that does not manage it leaves the column alone; blank clears the override and a value outside the `football_form` vocabulary is refused rather than stored. | `TeamsRestController.php`                               |
| Evaluations      | `GET/POST /evaluations`, `PUT/DELETE /evaluations/{id}`                                       | `EvaluationsRestController.php`                         |
| Goals            | `GET/POST /goals`, `PUT/DELETE /goals/{id}`, `PATCH /goals/{id}/status`                       | `GoalsRestController.php`                               |
| People (staff)   | `GET/POST /people`, `PUT/DELETE /people/{id}`                                                 | `PeopleRestController.php`                              |
| Custom fields    | `/custom-fields` CRUD + `/custom-fields/{id}/move`                                            | `CustomFieldsRestController.php`                        |
| Eval categories  | `/eval-categories` CRUD + `/eval-categories/{id}/move`                                        | `EvalCategoriesRestController.php`                      |
| Functional roles | `/functional-roles` CRUD + assignments at `GET/POST /functional-roles/assignments`, `PUT/DELETE /functional-roles/assignments/{assignment_id}`. **`PUT` (#2608)** changes an assignment's `functional_role_id`, `start_date` and `end_date` in place — team and person are not accepted, since moving either is a different assignment. Writing the role also rewrites `role_in_team` and `is_head_coach`, so an assistant→head-coach change moves the coach to the head-coach persona dashboard. `tt_edit_people` for every write; 404 when the row is not in the caller's club, 409 when the person already holds the target role on that team. | `FunctionalRolesRestController.php`                     |
| Media (#2592, epic #2589) | `GET /media?entity_type=&entity_id=` (also `kind`, `include_archived`), `POST /media` (multipart `file` + optional `poster`, or JSON `external_url`; `entity_type` + `entity_id` required — the item is attached on create), `GET/PATCH/DELETE /media/{uuid}` (DELETE archives; `?hard=1` erases the row **and its bytes**), `POST /media/{uuid}/links`, `DELETE /media/{uuid}/links/{link_id}`, `GET /media/{uuid}/file`, `GET /media/{uuid}/thumb`, `GET /players/{id}/media`. Items are addressed by **uuid**, never the autoincrement id — the id is a detail of this database, and a uuid cannot be walked to enumerate an academy's photographs. Two-stage authorization: the `permission_callback` asks `MediaVisibilityService` for media authority *anywhere*, then the callback asks about the actual record. **An item the caller may not see answers 404, not 403** — a 403 would confirm the uuid names a real item here. The two byte routes bypass the envelope and stream (`Range`/206 supported; `nosniff`; `private, no-store`; non-inline-safe types download rather than render). Payloads carry REST URLs only — never a filesystem path or an uploads URL. | `MediaRestController.php` |
| Media retention (#2666, epic #2589) | `GET /media/retention` (queue + held list + configured period), `DELETE /media/retention/{link_id}` (remove one expired attachment — returns `media_deleted` so the caller can say whether the file itself went), `POST /media/retention/{link_id}` (body `{decision: 'keep', reason}` to hold, or `{decision: 'release'}` to put a held one back; a `keep` without a reason is 400 — an unexplained exception is indistinguishable from nobody having looked). Gated on **global** `media:create_delete`, not the any-scope check the other media writes use: curating your own squad is a coach's act, deciding what leaves the academy's records permanently is an admin's. Retention expires an **attachment**, never an item — the file is deleted only when nothing links it any more. | `MediaRestController.php` |
| Prospects (#0081, #2838) | `POST /prospects/log` (dispatches the LogProspect chain), `GET /prospects` (paginated list), `GET /prospects/{id}`, `PATCH /prospects/{id}`. **The PATCH body is deliberately four fields** — `parent_name`, `parent_email`, `parent_phone`, `consent_given_at` — not a general record editor: correcting how the academy reaches a family is a different act from correcting who the player is, and one payload that could do both invites the second by accident. Reads gate on `tt_view_prospects`, writes on `tt_edit_prospects`. Fields are matched with `array_key_exists`, so an explicit `null` or `""` **clears** a field rather than reading as "not supplied" — that is what makes withdrawing consent expressible at all. A `consent_given_at` that is neither empty nor `YYYY-MM-DD` is refused with 400 rather than stored as null; silently dropping a consent date somebody typed is the failure this endpoint exists to end. Every consent transition writes a `prospect.consent_changed` audit entry carrying the old and new value; contact edits write `prospect.updated`. | `src/Modules/Prospects/Rest/ProspectsRestController.php` |
| Configuration    | `GET/PUT /config`                                                                             | `ConfigRestController.php`                              |
| Modules + features (#1451, #1485, #2387, #2409) | `GET /modules` (each row: `class`, `enabled`, `always_on`, `under_development`), `POST /modules` (body `class` plus `enabled` and/or `under_development` — supply at least one, 400 otherwise; disabling an `always_on` module is refused with 400, but flagging one under development is allowed because the flag gates nothing), `GET /features` (each row adds `key`, `label`, `description`, `module_class`, `under_development`), `POST /features` (body `key` plus `enabled` and/or `under_development`). Both gate on `tt_manage_modules`. `under_development` is **cosmetic**: it drives the informational pill on the surfaces the module/feature owns and the badge on their dashboard tiles, and never enables, disables or hides anything. | `src/Shared/Frontend/FrontendModulesView.php` |
| Feature status + catalog (#1486, #2878) | `GET /feature-status` — every user-facing module with `label`, `enabled`, `always_on`, `provides` and its `features`; the complete unfiltered audit shape, for a caller checking state. `GET /feature-catalog` — the same modules shaped as a reader-facing catalog: grouped by `category`, split into `in_use` / `available`, each entry carrying the written `label`, `description` and `icon` from `ModuleMetadata`. The catalog omits always-on core, the Advanced / developer category, and anything flagged `under_development` that is not also enabled; `/feature-status` omits none of those. Both are read-only and gate on `is_user_logged_in()`. | `src/Shared/Frontend/FrontendFeaturesView.php`, `src/Core/FeatureStatusService.php` |
| Authorization matrix (#2654) | `GET /authorization/matrix` (personas + entities with their label/module/group, the `grid`, the `activities` and `scopes` vocabularies, and `editable` — whether the caller is unrestricted and, if not, which entities and personas are locked for them), `PUT /authorization/matrix` (body `{ cells, scopes }`, returns `{ grants, revokes, scope_changes, rejected }`), `POST /authorization/matrix/reset`. `GET`/`PUT` gate on `tt_manage_authorization` (administrator + Club Admin); **reset gates on `manage_options`** — it discards every edit anybody ever made, including the ones the caller was not allowed to make. **`scopes` is the coverage declaration, not just a value map:** its keys say which `persona|entity` pairs the payload speaks for, so a client changing one cell sends one pair and cannot revoke everything it forgot to mention; a pair absent from `scopes` is untouched. Within a covered pair a missing **or falsy** `cells` entry means revoked. The protected-cell guardrail is enforced in `MatrixEditService`, not in the controller, so REST, the frontend view and the wp-admin page cannot disagree — a rejected cell increments `rejected` and writes neither a matrix row nor a changelog entry. | `src/Modules/Authorization/Rest/MatrixRestController.php` |
| Player journey (#0053) | `GET /players/{id}/timeline`, `GET /players/{id}/transitions`, `POST /players/{id}/events`, `PUT /player-events/{id}`, `GET /journey/event-types`, `GET /journey/cohort-transitions`, `GET/POST /players/{id}/injuries`, `PUT/DELETE /player-injuries/{id}` | `PlayerJourneyRestController.php` |
| Team development — chemistry (#0018, #0068) | `GET/PUT /teams/{id}/formation`, `GET/PUT /teams/{id}/style`, `GET /formation-templates` (optional `?team_id=N` narrows the catalogue to the shapes that team can field, per its football form — #3044; each row carries `football_form`), `GET /teams/{id}/chemistry` (returns `composite` / `formation_fit` / `style_fit` / `depth_score` / `data_coverage` / `blueprint_chemistry` with `team_score` + colored `links`), `GET/POST /teams/{id}/pairings`, `DELETE /pairings/{id}`, `GET /players/{id}/team-fit` | `TeamDevelopmentRestController.php` |
| Team development — blueprints (#0068 Phase 1 + 2, #953 Phase 3) | `GET/POST /teams/{id}/blueprints`, `GET/PUT/DELETE /blueprints/{id}`, `PUT /blueprints/{id}/assignment` (single slot — body `{ slot_label, tier?, ref: { kind: 'player'|'guest'|'custom', ... } }`; returns recomputed `blueprint_chemistry`), `PUT /blueprints/{id}/assignments` (bulk — accepts slot → ref-object map, or per-tier maps), `PUT /blueprints/{id}/status` (body `{status: draft|shared|locked}`). Locked blueprints reject every write with HTTP 409. **Legacy flat `{ player_id: N }` payload deprecated v4.3.21 (#953); the boundary shim continues to translate it to `{ ref: { kind: 'player', player_id: N } }` for external API consumers until removal in v5.0.0.** | `TeamDevelopmentRestController.php` |
| Tournaments (#0093) | `GET/POST /tournaments`, `GET/PUT/DELETE /tournaments/{id}`, `GET /tournaments/{id}/totals`, `POST /tournaments/{id}/matches`, `PATCH/DELETE /tournaments/{id}/matches/{match_id}`, `POST /tournaments/{id}/matches/{match_id}/kickoff`, `POST /tournaments/{id}/matches/{match_id}/complete`, `POST /tournaments/{id}/matches/{match_id}/auto-plan`, `GET /tournaments/{id}/matches/{match_id}/planner` (planner bundle), `PATCH /tournaments/{id}/matches/{match_id}/assignments` (bulk replace; pass `force=1` to override the post-complete lock), `PATCH /tournaments/{id}/squad` (bulk replace), `PATCH/DELETE /tournaments/{id}/squad/{player_id}`. v1 admin-only: all routes gate on `tt_view_tournaments` / `tt_edit_tournaments`, mapped to `administrator` + `tt_club_admin` only. | `TournamentsRestController.php` |
| System — error log (#1360) | `GET /system/errors` (filters: `level` = `error`\|`warning`, `date_from`, `date_to` as `Y-m-d`; paginated via `X-WP-Total` / `X-WP-TotalPages`). Read-only; gated on `tt_view_audit_log`. Returns the bounded `tt_error_log` buffer (newest 500 Logger error/warning entries). | `ErrorLogRestController.php` |
| Reports — coach evaluation quality (#1367) | `GET /reports/coach-evaluation-quality` (filters: `team_id`, `date_from`, `date_to` as `Y-m-d`). Read-only; gated on `tt_view_reports` PLUS academy-wide scope (`tt_view_all_teams` or the settings roll-up) — coaches cannot read each other's stats. Returns per-coach rows (eval/rating counts, mean, stddev, modal value + share, last eval date, low-variance flag) plus the flag thresholds. | `ReportsRestController.php` |
| Reports — player radar (#1369) | `GET /reports/player-radar` (`mode` = `progress`\|`comparison`\|`team_avg`, `player_ids` as comma list). Read-only; gated on `tt_view_reports`; non-scope-admin callers are narrowed to their own teams' players/teams. Returns radar labels + datasets (progress mode: per-player blocks) + `rating_max`. | same controller |
| Reports — minutes audit (#2368) | `GET /reports/minutes-audit` (required `team_id`; optional `from` / `to` as `Y-m-d`, defaulting to the current-season window; optional `type` = `League`\|`Cup`\|`Friendly`, else all match types). Read-only; gated on `tt_view_analytics` **plus** the `report_minutes_audit` feature toggle; the caller's team scope is enforced (a coach passing a team they don't coach gets an empty matrix). Returns the games × players auditability matrix: `games[]` (each with `activity_id`, `session_date`, `title`, `type_key`, per-player `minutes` + `on_squad` maps, `total_minutes`, `recorded_count`, `squad_count`, completeness `status` = `complete`\|`partial`\|`none`), `players[]` (id, name, jersey), `column_totals`, `grand_total`, and a `summary` (`total_games`, `complete`, `partial`, `none`). Reads the same recorded, actual, non-guest minutes as the minutes report, so the two reconcile exactly; the squad is resolved from attendance, not the player's team assignment. | same controller |
| Reports — minutes-audit per-match editor (#2367) | `GET /reports/minutes-audit/{activity_id}/editor` — the read model for the per-match minutes editor. Gated on **`tt_edit_activities`** (the SAME capability the two minutes write paths enforce) plus the `report_minutes_audit` feature toggle; the caller's team scope is enforced on the activity's own team (a coach who deep-links to a match on a team they don't coach gets a 403). Returns `{ activity: { id, team_id, title, session_date, type_key }, owned_by_execution: bool, half_length: int, players[] }`, each player carrying `player_id`, name, `jersey_number`, `attendance_id`, and effective / `minutes_derived` / `minutes_override` minutes. The client uses `owned_by_execution` to route each per-player write — **owned** → `PATCH /match-execution/{activity_id}/minutes` `{ player_id, minutes\|null }` (the override, survives recompute); **not owned** → `PATCH /attendance/{attendance_id}` `{ minutes_played }` (manual entry, #2159). This endpoint is read-only; the writes reuse the existing arbiter endpoints, so no new write route is introduced. | `ReportsRestController.php` |
| Measurements (#1856) | `GET /players/{player_id}/measurements` (a player's profile — categories → tests → latest value + green/amber/red flag + trend), `POST /players/{player_id}/measurements` (record a result), `GET /players/{player_id}/measurements/{definition_id}/series` (one test's trend), `PUT/DELETE /measurements/results/{id}` (edit / soft-archive), `GET/POST /measurements/definitions`, `PUT /measurements/definitions/{id}` (the test catalogue), `GET /teams/{team_id}/measurement-sessions`, `POST /measurements/sessions`. **Matrix-gated, not cap-gated**: player reads use `AuthorizationService::canViewPlayer` (self/parent-child/team/global); writes use `canEvaluatePlayer`; the catalogue + sessions gate on the `measurement_definitions` / `measurement_sessions` matrix entities. | `MeasurementsRestController.php` |
| Measurement results browse (#2145) | `GET /measurement-results?definition_id={id}` (the **Test results** browser read model — one row per player carrying their latest in-window value for the test, with the status level's colour token + label, or the green/amber/red `flag` against the player's age-group band and a direction-aware `trend` of `up`/`down`/`flat` versus their previous value). Optional filters: `team_id`, `age_group`, `from` / `to` (inclusive `YYYY-MM-DD` recorded-date window). `definition_id` is **required** (400 otherwise). **Matrix-gated** on the `measurements` entity (`read`). Thin controller — delegates to `MeasurementResultsBrowse`, the same service `FrontendTestResultsView` renders, so a SaaS front end gets identical rows. | `MeasurementsRestController.php` |
| Measurement definitions (#2120) | `GET /measurement-definitions` (the test catalogue — `?include_inactive=1` to include deactivated tests), `GET /measurement-definitions/{id}` (one test with its per-age-group target bands), `POST /measurement-definitions` (create — same field surface the "+ New test" wizard persists: `category_id`, `name`, `value_type` numeric\|scale\|passfail, `unit`, `scale_min`/`scale_max`, `frequency`, `direction` higher\|lower\|neutral, `is_active`, `sort_order`), `PUT /measurement-definitions/{id}` (edit — partial; only the keys present are written), `POST /measurement-definitions/{id}/targets` (upsert one age-group band — body `{ age_group, green_min, green_max, amber_min, amber_max }`, unique per (definition, age_group)), `DELETE /measurement-definitions/{id}` (soft-archive — stamps `archived_at`/`archived_by`), `DELETE /measurement-definitions/{id}/permanent` (hard-delete, **gated on `tt_manage_recycle_bin`** so no purge path is weaker than the bin's own). The CRUD routes are **matrix-gated** on the `measurement_definitions` entity: `read` for GET, `change` for PUT + target upsert, `create_delete` for POST-create + soft-archive. Thin controller — every read/write delegates to `MeasurementDefinitionsRepository` / `MeasurementTargetsRepository`; soft-archive + purge run through `ArchiveRepository`. | `src/Modules/Measurements/Rest/MeasurementDefinitionsRestController.php` |
| Spond integration (#0031, extended #1936, #2286, #2388, #2399) | `POST /teams/{id}/spond/sync` (sync one team), `POST /spond/credentials` (save email + password; blank password keeps the stored one), `DELETE /spond/credentials` (disconnect / clear), `POST /spond/test` (live login check via `SpondClient`), `POST /spond/base-url` (override / revert the Spond API base URL). Per-team account override (#2286): `POST/DELETE /teams/{id}/spond/credentials`, `POST /teams/{id}/spond/test`. Per-team group selection (#2399): `GET /teams/{id}/spond/group` (groups the team's effective account can see — each carrying `used_by`, the other team already linked to it, `''` when none; pass `refresh=1` to bypass the 5-minute cache) and `POST /teams/{id}/spond/group` (`{ group_id }`; `''` unlinks; the response echoes `used_by` so the client can warn). Both gate on `TeamSpondAccess::canManage()`, so a head coach finishes their own team's setup without `tt_edit_teams`; a shared group is warned about, never blocked. The four club credential / base-url routes gate on `tt_edit_spond_credentials`. **The per-team credential, test and sync/preview routes gate on `TeamSpondAccess::canManage()` (#2388)** — change authority on the `spond_integration` matrix entity for that exact team (an academy admin globally, a head coach for their own team), so a head coach can connect their own team but not another's (`tt_edit_teams` still admits admins to the per-team sync/preview). The password is accepted on save/test and stored encrypted via `CredentialsManager`, but is **never** returned in any response; test only reports ok / a non-sensitive error message. | `SpondRestController.php`, `TeamSpondAccess.php` |
| Strava integration (#2056, epic #2002) | `POST /players/{id}/strava/connect` (mint a signed authorize URL the browser navigates to — returns `{ authorize_url }`, does not store it), `DELETE /players/{id}/strava/connect` (disconnect — revoke at Strava + clear the encrypted tokens), `GET /players/{id}/strava/status` (connection status — `connected` / `status` / `last_sync_at`, **no tokens**), `GET /players/{id}/strava/activities` (the player's imported activities — distance/duration/pace/elevation, **no HR**; gated on `canViewPlayer`), `GET /strava/callback` (the OAuth redirect target — **public** `permission_callback`, authenticates by verifying the signed `state`, exchanges the `code` for tokens server-side, then redirects the browser back to the player profile), `POST /strava/app` (operator — register the Strava developer-app `client_id` + `client_secret`; the secret is write-only), `GET /strava/connections` (operator — the console roster: every club connection with player name, status, imported-activity count, last activity + last sync; **no tokens, no secret**), `GET /strava/webhook` (**public** — Strava's subscription-validation handshake; echoes `hub.challenge` as raw JSON after the verify token matches), `POST /strava/webhook` (**public** — the event push feed; resolves the athlete to a connection, pins its club, then routes activity create/update → ingest, delete → archive, athlete deauthorize → disconnect + archive), `GET/POST/DELETE /strava/webhook/subscription` (operator — view / create / delete the single club-wide push subscription; `GET` reconciles the stored id against Strava's real state via Strava's `GET push_subscriptions`, and `POST` adopts an existing subscription rather than failing on the one-per-app rule). The three per-player routes gate on **self-or-edit**: the player on their own record, or a caller with `canEditPlayer`. The operator routes (`app`, `connections`, `webhook/subscription`) are **matrix-gated, not `manage_options`** (#2127): reads (`GET connections` + `GET subscription`) on `tt_view_strava` (→ `strava_integration:read`), writes (`POST app`, `POST/DELETE subscription`) on `tt_edit_strava_credentials` (→ `…:change`). Per-player access + rotating refresh tokens are stored encrypted via `ConnectionRepository` and **never** returned. Activities are imported with non-HR metrics only (Gate 1). | `StravaRestController.php` |
| Onboarding / Setup flow (#1938) | `POST /onboarding/advance` (leave the welcome step for academy), `POST /onboarding/academy` (save academy basics, advance), `POST /onboarding/first-team` (create the first team — or pass `skip:1` to skip — advance), `POST /onboarding/first-admin` (create the first-admin staff record + optional Club Admin grant, advance), `POST /onboarding/messaging` (#3140 — record which message templates the academy sends, or `skip:1` to send nothing; delegates to `OnboardingHandlers::applyMessaging()` / `skipMessaging()`, which invert against the registered switchable set rather than the request body so a template cannot be enabled by omission), `POST /onboarding/profile` (#3259 — apply an install profile named by `profile`, or `skip:1` for the full academy; delegates to `OnboardingHandlers::applyProfile()` / `skipProfile()`, and returns 409 `profile_not_applicable` when the slug is unknown or the install has already been configured by hand), `POST /onboarding/import` (#3260 — the only multipart route here: send the workbook as `roster_file`, with `commit:1` to write and without it to report only, or `skip:1`; delegates to `OnboardingHandlers::applyImport()`, which calls the same `ImportService` the wp-admin step does, so there is exactly one importer. A preview deliberately does not advance the step, and a workbook with blockers never reaches the commit pass. Always 200 with the step payload — the refusals are things about the *workbook*, which the step renders as a report rather than a failed request), `POST /onboarding/dashboard-page` (create / reuse the frontend dashboard page + set homepage — or `skip:1` — finish), `POST /onboarding/reset` (reset state, re-enter at welcome). Every route gates its `permission_callback` on `tt_edit_settings` (matches `OnboardingPage::CAP`). Thin controller — every persistence, team / staff creation, role grant, page creation, and state advance lives in `OnboardingHandlers` / `OnboardingState`, the same domain layer the wp-admin wizard uses. Each response reports the post-mutation `{ step, completed, payload }` so the frontend re-renders the right step. | `OnboardingRestController.php` |
| Backups + data migration (#1937) | `GET /backups` (list stored local backups — each row carries a `download_url`, a full URL not a server-relative path per SaaS §4), `POST /backups/settings` (save preset / custom tables / schedule / retention / local + email destinations, reconcile the cron), `POST /backups/run` (run a backup now), `DELETE /backups/{id}` (delete a stored backup file), `GET /backups/{id}/preview` (restore preview — per-table row counts + source metadata), `POST /backups/{id}/restore` (**DESTRUCTIVE** full restore — requires `confirm_text:RESTORE`), `POST /backups/migration/preview` (multipart `.ttmig` upload — read-only validation + summary + stable-key conflict analysis; stages the archive, size-guarded to `MigrationImporter::MAX_UPLOAD_BYTES`), `POST /backups/migration/dry-run` (dry-run the staged import, no writes), `POST /backups/migration/commit` (**DESTRUCTIVE** import write — requires `confirm_text:IMPORT`). Every route gates its `permission_callback` on `tt_manage_backups` (matches `BackupSettingsPage::CAP`) — restore + import gate identically. The two destructive writes additionally refuse to run while impersonating (`ImpersonationContext::denyIfImpersonating`), preserve the typed-confirmation gate, and are audit-logged (`backup.restored` / `migration.imported`). Binary downloads + the `.ttmig` export are not JSON routes — they stream from the wp-admin admin-post handler; the list response returns a download URL pointing at that stream (object-storage-ready). Thin controller — serialization, the restore engine, and the migration engine live in the Backup module services. | `BackupRestController.php` |

| Player attributes / chemistry config (#1912) | `GET/PUT /players/{player_id}/attributes` (the player's chemistry attribute set, grouped physical/technical/tactical/mental/behaviour/development; PUT body `{ values: { <def_id>: <0–100|null> } }`), `GET/PUT /chemistry/position-matrix` (the configurable Position Relationship Matrix), `GET/PUT /chemistry/config` (the five component weights). **Matrix-gated**: attribute reads use `canViewPlayer`, writes `canEvaluatePlayer`; the matrix + weights gate on the `team_chemistry` entity at global scope. Phase 1 of the chemistry rework (epic #1017) — schema/contract only, no engine change. Phase 3 adds `GET /chemistry/pair/{a}/{b}` — the reworked pair-chemistry score (0–100 + category + per-component breakdown + reasons), gated on viewing both players. | `PlayerAttributesRestController.php` |

| Training plans (#2496, epic #2493) | `GET/POST /training/plans` (list returns `rows` + `total` + `page` + `per_page` inside the standard envelope — the shape `FrontendListTable`'s hydrator reads — and accepts both a direct-API vocabulary and the list-table's `page` / `per_page` / `search` / `orderby` / `order` / `filter[status|team_id|is_template]`; sorting is allowlisted to title / duration / created / updated / theme so an arbitrary column never reaches SQL; filters `team_id`, `is_template`, `theme_key`, `include_archived`, `limit`, `offset` — a `team_id` filter returns that team's plans **plus** the club-wide ones it can draw on), `GET/PATCH/DELETE /training/plans/{id}` (DELETE soft-archives; the plan's runs are deliberately untouched), `POST /training/plans/{id}/duplicate` (body `title`, `team_id`, `as_template` — an `as_template` copy drops the team, because a club template belongs to no single one), `GET/PUT /training/plans/{id}/blocks` (PUT bulk-replaces the whole ordered set; the caller hands over the desired state rather than a diff, which is what makes the builder's save and the generator's output the same operation), `POST /training/plans/generate` (#2497 — composes a plan from `team_id`, `session_date`, `tactical_theme`, `requested_duration_minutes`, `roster_player_ids`; `preview=1` composes **without saving**, which is what the wizard's proposal step renders. A blocking warning returns `plan_id: null` and the reasons rather than a partial plan — an age group with no VCT profile has no age-safe intensity ceiling to plan inside, and guessing one for a twelve-year-old is not a fallback), `GET /training/plans/suggest?team_id=N` (#2497 — the squad size to expect, from recent attendance rather than the roster, with `squad_size_source` naming which it used so the UI can say where the number came from), `GET /training/plans/{id}/coverage` (#2498 — which players' open goals this plan touches and which it misses, **by name**; the builder's side panel re-reads it after every save), `GET /training/plans/{id}/exercise-options?search=` (#2498 — the picker's list, sorted by how many of the plan team's open player goals each exercise would serve, with that count on every row as `players_served`: a ranking the user cannot see is one they cannot trust). **Publishing (#3220)**: `POST /training/plans/{id}/publish` stamps `published_at` and fires `tt_training_plan_published`, which is what sends the `methodology_delivered` message to the head coaches the plan is for; `DELETE` on the same route clears the stamp and sends nothing. Its own verb rather than a field on PATCH, because publishing mails people and an announcement should not ride along on a record update (CLAUDE.md §6). **Edge-triggered and idempotent** — publishing an already-published plan returns success, moves no timestamp and announces nothing, so a typo fix cannot re-notify. A template is refused with 400: it is library material and there are no coaches to tell. Plans stay mutable after publication, as migration 0213 designed them; the plan payload carries `published_at` and a `published` boolean so a consumer can render a badge without parsing a timestamp. Gates on `tt_training_plan` throughout — reads and writes share the cap because a plan carries no player data, so the boundary that matters is which teams' plans a coach can reach, not whether they may edit. | `TrainingPlansRestController.php` |
| Training runs (#2496, epic #2493) | `POST /training/runs` (attach a plan to an activity — 201 on a fresh run, **200 with the existing run** when the activity already has one, since re-attaching must never silently replace a snapshot), `GET/PATCH/DELETE /training/runs/{id}` (PATCH takes `status`: `planned` / `running` / `completed` / `abandoned`, stamping `started_at` and `completed_at` on the transitions that own them), `PATCH /training/runs/{id}/blocks/{block}` (`actual_duration_minutes`, `was_skipped`, `notes` — refuses a block belonging to another run), `GET /activities/{id}/training-plan` (returns `{ run: null }` rather than a 404 when nothing is attached, because most activities have no plan). `GET/POST /training/runs/{id}/observations` and `DELETE /training/observations/{id}` (#2500 — a coach's note about one player during one training; `rating` is optional and a note-only observation is the common case, while an observation carrying neither is a 400. A rating outside the install's configured scale is **dropped rather than clamped** — storing a rounded 9 would put a number on a child's record nobody chose). The run's `snapshot` is **read-only** on every route: it is written once at attach time and is the only history the session has. A `PATCH` to `status: completed` also recomputes exposure for the players who were present, so the player file is right immediately (#2500 D17). | `TrainingRunsRestController.php` |
| Training exposure (#2500, epic #2493) | `GET /players/{id}/training-exposure` (minutes and trainings per principle, plus a summary; **every principle is returned including those never trained** — the empty row is the finding, so a consumer filtering them out is making a choice rather than never being offered one), `GET /players/{id}/observations`, `GET /training/coverage` (principle × team, academy-wide). Gated on the `training_exposure` matrix entity, **not** on `tt_training_plan`: reading a player's training history and planning a training are different rights (D16). The per-player routes additionally apply `AuthorizationService::canViewPlayer()` **and** `parentCanViewSection( …, 'training' )`, so a parent reads only their own child and only if the child has not switched the section off. `/training/coverage` requires the entity at **global** scope, so a team-scoped coach is refused. | `TrainingExposureRestController.php` |

| Exercise scenes (#2501, epic #2493) | `GET/POST /exercises/{id}/scenes` (the exercise's animated diagrams; the first one created becomes its primary automatically, because a scene nobody flagged would otherwise leave every read surface still showing nothing), `GET/PUT/DELETE /exercise-scenes/{id}`, `POST /exercise-scenes/{id}/primary` (move the flag — the write clears the others in the same statement pair, so two can never both be primary). **Every write returns the stored, normalised scene rather than an acknowledgement.** The repository clamps coordinates to the 0–100 pitch space, sorts and dedupes keyframes, restricts actor and link kinds to the known vocabularies, and drops a link whose endpoint is not an actor in the scene; returning the result means an editor adopts the server's version instead of keeping a hopeful copy that disagrees on the next reload, and a coordinate that was clamped is visible in the same request. Re-saving a scene exactly as returned is a no-op, which is what makes the editor's save-fetch-save cycle stable. The single-scene routes take `{id}` and **not** `{scene}`: the body field carrying the payload is called `scene`, and a URL placeholder sharing a name with a body field is silently overwritten by it. Gates on `tt_manage_exercises` for writes and `tt_view_activities` for reads — a scene is a field of an exercise (D6), not a record with an audience of its own. | `ExerciseScenesRestController.php` |

| Match analysis (#2705, epic #2704) | `GET /activities/{id}/analysis` (the whole document plus everything it pre-fills from: the match-prep goal per section, the roster of who played with their minutes, and each player's match-plan attention note. Returns a well-formed empty analysis rather than a 404 when nothing has been written, and never creates the record — reading a match must not write one), `PUT /activities/{id}/analysis` (the whole document; accepts any subset of `summary`, `status`, `sections`, `players`, so a client that only knows about sections cannot wipe the player items by omission — this is what the on-screen form posts), `PUT /activities/{id}/analysis/sections/{section_key}` (`section_key` is a `MatchAnalysisEnums` section — the four methodology team functions plus `set_pieces` / `general`; anything else is a 400 rather than a silently-stored row. Since #3091 `notes` is a list of `{body, valence}` where `valence` is `plus`, `minus` or empty; a flat list of strings and a single newline-joined blob are both still accepted and read as unmarked notes, and an unrecognised valence is stored as neutral rather than as itself), `PUT/DELETE /activities/{id}/analysis/players/{player_id}` (one player item; an item with neither a marker nor a note is deleted rather than stored, because "not mentioned" is the resting state of every player on the roster. `notes` carries up to two `{body, valence}` entries (#3091) and the server truncates a longer list rather than trusting it; the older single `note` key is still accepted), `POST /activities/{id}/analysis/share` (#2749 — mints the share seed and returns the link. Idempotent: asking twice returns the same URL rather than quietly invalidating the one already sent. Sharing is an explicit act, which is why rendering the surface no longer does it as a side effect), `POST /activities/{id}/analysis/share/rotate` (replaces the seed, shutting every previously issued URL, and returns the new link), `GET /activities/{id}/analysis/share-views` (#3096 — `{unique, opens, last_seen_at}` for the staff share link: how many browsers opened it and when the last one did. Reads through the same `ShareViewQuery` the rendered share block calls, so the page and the API cannot disagree. Zeroes rather than a 404 for an analysis nobody has opened — "not read yet" is an answer. Gated on `tt_view_activities`, the cap that gates the analysis itself). Writes gate on `tt_edit_activities` and reads on `tt_view_activities` — the caps match prep and match execution already use. Every write refuses an activity that is not a match. The read payload also carries `goals` (#2860) — the fixture's non-reversed goal events in chronological order across both halves, each with an absolute `minute`, `half`, `team`, `is_own_goal`, `scorer`, `assist` and `has_scorer`. Read-only: goals are written on the match-execution resource, so there is no matching write here. Player-item writes keep the player's `match_observed` journey entry in step: written on first save, rewritten in place on edit, removed when the item is cleared. | `MatchAnalysisRestController.php` |
| Goal contributions (#2859, epic #2855) | `GET /players/{id}/goal-contributions` (optional `from` / `to` as `Y-m-d`; a partial range is ignored rather than half-applied, and no range means the player's whole record). Returns `{ player_id, from, to, goals, assists, own_goals, contributions, matches[] }`, each match carrying `activity_id`, `session_date`, `goals`, `assists`. Reads the same `GoalContributionQuery` the player profile's *Goals scored* tile and the Team · Minutes report's columns read, so a non-WordPress front end gets the rendered pages' numbers rather than its own arithmetic over the raw goal log. The counting rules live there and are the substance of the endpoint: a goal with no scorer recorded (`player_id = 0`) counts toward the **score** but toward **no player**; an **own goal** is recorded against the player but never added to their goal tally; a **reversed** goal counts for nobody; assists are credited from `assist_player_id` independently of who scored. Gated on `tt_view_players` via `AuthorizationService::userCanOrMatrix`. | `GoalContributionsRestController.php` |
| Match execution — goals (#2856, epic #2855) | `POST /match-execution/{activity_id}/goal-event` `{event_uuid, team, half, minute, player_id, assist_player_id, is_own_goal}`, `PATCH /match-execution/{activity_id}/goal-event/{event_uuid}`, `DELETE /match-execution/{activity_id}/goal-event/{event_uuid}`. `team` is `home` (ours) or `away` (theirs). Attribution is optional on both: `player_id` **0** means “no scorer recorded”, which is what the live goal sheet writes when the coach could not see the final touch — refusing the goal instead only pushed them onto a score control that recorded no event at all. `assist_player_id` is one of our players or omitted, and may not equal `player_id`. `is_own_goal` marks a goal put in by the side it counts against. A named `player_id` / `assist_player_id` must belong to the match squad (the prep's availability rows plus its lineup), otherwise `player_not_in_squad`. The **PATCH is partial in two independent halves**: a payload carrying only `half` + `minute` leaves the attribution untouched, and one carrying only attribution keys leaves the timing untouched — so correcting a minute cannot silently drop a scorer. Sending `assist_player_id: 0` clears the assist. Every write is refused once the match is finalized (re-open first). Gated on `tt_edit_activities`. | `MatchExecutionRestController.php` |

| Messages (#2605, epic #2600) | `GET /comms/messages` (the send log, filterable by `player_id`, `user_id`, `template_key`, `message_type`, `status`, `channel`, `date_from`, `date_to`; paginated through `X-WP-Total` / `X-WP-TotalPages`, and the payload also carries `statuses_in_use` so a filter offers the statuses that actually occurred rather than every one the vocabulary defines), `GET /players/{id}/messages` (the same log scoped to one player — the player-centric alias, and the URL segment **wins** over a conflicting `player_id` parameter, because on this data answering for the wrong child is the worst available bug). **The body is never returned, and neither is its hash**: the log stores a SHA-256 of the rendered message and nothing else, so a reader can see who was told what kind of thing and when, and cannot read a coach's words about a child out of the audit trail. Gated on `tt_view_audit_log` — the same read-only operator-log audience the audit log and the error log use, and deliberately **not** `tt_send_email`: being allowed to send is not being allowed to read what everyone else sent. | `src/Modules/Comms/Rest/CommsRestController.php` |
| In-app inbox (#2605, epic #2600) | `GET /comms/inbox` (the caller's own in-app messages, `unread_only` + paging, with `unread_count` in the payload), `PATCH /comms/inbox/{id}` (body `{ read: true|false }`). Logged-in only and scoped to `recipient_user_id = me` **in SQL** — there is no route here capable of reading another person's inbox, which is how the no-cross-family guarantee is structural rather than a capability check. A message that is not yours answers **404, not 403**: a 403 would confirm it exists, which is itself a fact about another family. Marking read is idempotent — the first stamp stands, so opening on a second device does not rewrite when the message was first read. | same controller |
| Message templates + preferences (#2605, epic #2600) | `GET /comms/templates` (every registered template with its label, channels, editability and whether the academy-wide switch has it on), `PATCH /comms/templates/{key}` (body `{ enabled }`; an unknown key is a 404 rather than a stored value, so a typo cannot switch off a template that does not exist) — both gated on `tt_edit_settings`, since turning a template off is a configuration change for the whole academy. `GET /comms/preferences` and `PUT /comms/preferences` (body `{ opted_out: [...] }`) read and replace the **caller's own** per-message-type opt-outs; the PUT states the complete list, so a type left out is one the user wants to hear about again. Operational types are never offered and never stored — safeguarding email is not something a recipient can mute, and rendering a switch that silently does nothing would be worse than rendering none. | same controller |

The list is generated by walking `register_rest_route()` calls in the REST controllers. When you add a new route, add a row here.

## Recycle bin (#2021 / #2024, epic #2018)

The recycle bin adds a second soft-delete tier on top of the existing archive: **active → archived → trashed (bin) → purged (gone)**. The domain core lives in `ArchiveRepository` (`src/Infrastructure/Archive/ArchiveRepository.php`); the REST surface that exposes it lives in `RecycleBinRestController` (`src/Infrastructure/REST/RecycleBinRestController.php`).

- **Routes:**
  - `GET /recycle-bin` — cross-entity aggregation. Returns `{ groups: [ { entity, label, count, rows: [ { id, identity, trashed_at, trashed_by, trashed_by_name, days_until_purge } ] } ], total, retention_days }`. Club-scoped per entity. Cap: `tt_manage_recycle_bin`.
  - `GET /recycle-bin/preview/{entity}/{id}` — itemized cascade preview a later purge would apply (removals / nullifications / zeroings / blockers). Cap: `tt_manage_recycle_bin` (**#2413** — was `tt_edit_settings`, a cap orthogonal to bin access that could both deny a legitimate bin manager the impact statement and admit someone who cannot use the bin); ownership-checked.
  - `POST /recycle-bin/{entity}/{id}/restore` — bin → archived (not active). Cap: `tt_manage_recycle_bin` + ownership.
  - `DELETE /recycle-bin/{entity}/{id}` — purge — the single owner of permanent deletion. Cap: `tt_manage_recycle_bin` + ownership. A fail-closed `DeleteBlockedException` returns **409** with `errors[0].details.report` (per-table blocking counts); the row stays in the bin.
  Resource-oriented, no RPC verbs.
- **Legacy `/permanent` routes re-gated (#2024 security #6):** every per-entity `DELETE …/permanent` endpoint now requires `tt_manage_recycle_bin` (previously `tt_edit_settings` / module admin caps), so no permanent-deletion path is weaker than the bin's purge.
- **Lifecycle methods to call (never re-implement in the controller):**
  - `ArchiveRepository::trash($entity, $ids, $userId)` — archived → bin. Rejects rows that aren't archived yet. Caller gate: `tt_edit_settings`.
  - `ArchiveRepository::restoreFromTrash($entity, $ids, $userId)` — bin → **archived** (not active). Caller gate: `tt_manage_recycle_bin`.
  - `ArchiveRepository::purge($entity, $ids, $userId)` — bin → gone, via the existing fail-closed cascade. A `DeleteBlockedException` propagates unchanged → the controller surfaces the dependency report. Caller gate: `tt_manage_recycle_bin`.
  - `ArchiveRepository::trashedAcrossEntities()` / `trashedRowsFor($entity)` — bin listings, club-scoped per entity, each row carrying `trashed_by`, `trashed_by_name`, and computed `days_until_purge` (retention from `tt_config` key `tt_recycle_bin_retention_days`, default 30).
- **Permission-callback backstop (IDOR):** every `{id}` route's `permission_callback` calls `ArchiveRepository::ownedByCurrentClub($entity, $id)`; a 0-row result is a 404, never a pass. The cap check and the ownership check are both required.
- **Visibility gate for detail loads:** detail views call `ArchiveRepository::findIncludingArchived($entity, $id)`, which returns `null` for a trashed row unless the caller holds `tt_manage_recycle_bin`. A `null` result renders a 404 — never a permission-denied page that would confirm a trashed minor's record exists.
- **`?tt_view` / view vocabulary:** the 3-state filter is `active | archived | trashed | all`, where `all` = active + archived and **never** trashed. Per-entity list views never surface `trashed`; only the bin view does, gated on `tt_manage_recycle_bin`.

## Spreadsheet import (#2956, epic #2954)

Brings a club's teams, players and staff in from an Excel workbook. The domain core is `ImportService` (`src/Modules/Import/ImportService.php`) over `ExcelImporter`; the REST surface is `ImportRestController` (`src/Infrastructure/REST/ImportRestController.php`). The PHP surfaces call the same service, so a non-WordPress front end gets identical validation.

- **Routes:**
  - `POST /imports` — multipart upload, file field `file`. Returns `{ ok, committed, batch_key, imported: { <entity>: <count> }, warnings, sheets }`. **Writes nothing unless `commit` is true** — the default is a dry run that validates the workbook and reports what it *would* create, which is what lets a wizard show the report before anyone commits. A workbook that fails validation returns HTTP 200 with `{ ok: false, blockers, warnings }`: a bad spreadsheet is a normal outcome, not a server fault, and the blockers are the useful payload. Cap: `manage_options`.
  - `GET /imports` — the club's real import batches, most recent first: `{ batches: [ { id, uuid, batch_key, source_filename, counts, created_at, created_by } ] }`. Cap: `manage_options`.
- **Real imports never touch `tt_demo_tags`.** Rows created by a real import are recorded in `tt_import_batches` / `tt_import_tags` (migration 0238) via `ImportBatchRegistry`, not in DemoData's tag table. This is a safety property, not tidiness: `DemoDataCleaner::wipeData( null, null )` resolves what to delete from `tt_demo_tags` with no batch filter, so a club's real squad recorded there would be deleted by a routine "wipe demo data". Separate tables mean there is no filter for a future query to forget.
- **Which sink is used is the caller's choice, not the importer's.** `ExcelImporter` takes an `ImportTagSink` factory (#2955). `DemoBatchRegistry` satisfies it for demo workbooks, `ImportBatchRegistry` for real ones; parsing, validation and foreign keys are identical either way.
- **Capability note:** `manage_options` matches the existing import surface (`DemoDataPage::CAP`) rather than inventing a looser gate. A dedicated `tt_manage_import` capability belongs with the import-history surface (#2959), where there is a screen for the matrix to point at.

## Impersonation log (#2861)

The read side of `tt_impersonation_log`, which has been written since migration 0056 with nothing able to query it back. `ImpersonationLogRepository` is the domain core; `ImpersonationRestController` (`src/Infrastructure/REST/`) is the REST surface, and the **Audit log → Impersonation** tab reads the same repository.

- **Route:** `GET /impersonation/log` — `{ sessions: [ { id, actor_user_id, actor_name, target_user_id, target_name, started_at, ended_at, end_reason, actor_ip, actor_user_agent, reason, is_active } ], total }`. Filters: `actor_user_id`, `target_user_id`, `date_from`, `date_to` (both `YYYY-MM-DD`; anything else is ignored rather than a 400), `active_only`, `limit` (capped at 200), `offset`. Club-scoped.
- **Cap:** the `impersonation_log` matrix entity, via `MatrixGate::canAnyScope( …, 'impersonation_log', 'read' )` — Academy Admin RCD, Head of Development R. The entity already existed in `MatrixEntityCatalog` gating a surface that had never been built, so this is a read over an existing entity rather than new authorization work. Deliberately **not** the audit-log page's own cap: seeing who opened a minor's record is a narrower question than seeing who edited what.
- **A deleted account does not erase attribution.** `actor_name` / `target_name` fall back to `Deleted user #<id>`, so a session cannot decay into "someone impersonated someone" once the account is gone. This is an audit trail; that property is the point of it.

## Saved views — personal filter presets (#2385 / #2448)

Named filter combinations a user re-applies with one click, for any surface that renders the shared `FilterBar`. The domain lives in `SavedViewsRepository` (`src/Infrastructure/Filters/SavedViewsRepository.php`); the REST surface is `SavedViewsRestController` (`src/Infrastructure/REST/SavedViewsRestController.php`), registered from `Kernel` rather than a module, since the surfaces span more than analytics.

- **Routes:**
  - `GET /filter-presets?view_key=<key>` — the caller's own views for one surface. Returns `{ views: [ { id, name, filters, is_default } ] }`.
  - `POST /filter-presets` — `{ view_key, name, filters }`. Returns the stored view.
  - `PATCH /filter-presets/{id}` — rename and/or overwrite (#2451). `{ name }` renames, `{ filters }` overwrites, both together does both; an omitted field is left untouched, which is what makes "update this view with my current filters" one call that keeps the name. An empty body is a no-op 200, not an error. A name already used by the same user on the same surface returns **409** `duplicate_name` (the same guard applies to `POST`) — the same name on a *different* surface, or used by a different user, is allowed.
  - `PATCH` also accepts `is_default` (#2450), applied after any name/filters change so one call can rename a view and make it the default. Setting it clears any previous default for the same user + surface — MySQL cannot express "at most one default per group" as a partial unique index, so `SavedViewsRepository::setDefault()` enforces it inside a transaction.
  - `DELETE /filter-presets/{id}` — deletes one of the caller's own views.
  - `/reports/filter-presets` (+ `/{id}`) remain registered as **aliases** of all three for one release, and still accept the retired `report_key` param, so a page loaded just before a deploy keeps working. Remove once shipped.
- **Capability — per surface, from the registry.** `permission_callback` resolves the capability via `\TT\Infrastructure\Filters\SavedViewsRegistry::currentUserCan( $view_key )`, so a players-list view is gated on the players capability rather than a single fixed one. An unregistered `view_key` is **refused**, never allowed through a permissive default. `DELETE` carries no `view_key`, so it resolves the row first (proving ownership) and then gates on that row's surface.
- **Ownership** is enforced in the repository, not only the permission callback: every query is scoped to `user_id` + `club_id`, so a user cannot read or mutate another user's views even within the same club. Views are personal — there is no sharing tier.
- **The `filters` payload is opaque.** #2385 whitelisted six report params here; that cannot scale to every FilterBar surface, and on a surface the list doesn't know about it silently stores nothing. The controller now applies structural limits only — key matches `^[a-z0-9_]+(\[[a-z0-9_]+\])?$` (flat params plus `FrontendListTable`'s `filter[<key>]` shape), values are scalar and `sanitize_text_field`'d, max 20 keys, max 200 chars each. The consuming view sanitises its own `$_GET` when the preset is re-applied, which is the layer that knows what each param means.
- **Storage:** `tt_saved_filters`, club- and user-scoped with a `uuid`. Migration 0211 renamed `report_key` → `view_key` and added `is_default` (column only; the auto-apply behaviour is #2450).

## Install profiles (#3035 / #3036)

A named shape for a whole install — which modules and features a club runs — so the choice can be made once instead of fifty times. The domain lives in `ProfileRegistry` + `ProfileService` (`src/Shared/Modules/`); the REST surface is `ProfilesRestController` (`src/Infrastructure/REST/ProfilesRestController.php`), registered from `ConfigurationModule` because Configuration is always-on and the routes that reshape an install must not themselves be switchable off.

Deliberately **not** added to `FrontendModulesView`, where `/modules` and `/features` currently live. A view file registering REST routes is the coupling §4 asks new code not to extend. Moving those two is worth doing and is a separate change.

- **Cap:** all three routes gate on `tt_manage_modules` — the same capability the modules and features endpoints use.
- **Routes:**
  - `GET /profiles` — `{ profiles: [ { slug, label, description, is_current } ], current, divergence }`. `current` is `null` for an install that predates profiles or was never put on one, and `divergence` is `null` with it. That is a neutral state, not an error.
  - `GET /profiles/{slug}` — the profile plus its full diff against live state: `{ slug, label, description, is_current, divergence, changes: [ { id, kind, label, from, to, skipped_reason } ] }`. **This is the preview.** There is no separate preview route: a diff is a pure read, so `GET` is the honest verb, and one route means the screen and the API cannot drift.
  - `POST /profiles/{slug}/apply` — body `{ "exclude": ["<row id>", …] }`. Returns `{ profile, applied: [ … ], skipped: [ { …, reason } ], divergence }`.
- **Row ids** are `module:<FQCN>` or `feature:<key>` — a diff mixes both kinds, so the id has to carry which. `kind` repeats it as a first-class field so a consumer never has to parse the id.
- **`changes` holds only rows that would move.** A module or feature already in the profile's shape is not a row. `divergence` counts the rows that would actually be written, so it is the number a UI shows, not `count(changes)`.
- **`skipped_reason` travels with the row** (`null` or `tier`) rather than being dropped, so a consumer can explain the gap instead of silently under-applying. `apply` echoes the same information back as `reason`, adding `excluded` for a row the caller asked to hold.
- **An unknown slug is 404, not 400.** The slug names a resource; asking for one that does not exist is a missing resource, not a malformed request.
- **Applying with every row excluded is a 200 no-op** with an empty `applied` list. The caller asked for nothing to happen and got it.
- **`GET /profiles/{slug}` writes nothing**, and the smoke suite asserts it against a snapshot of live module and feature state rather than by inspection — the preview being read-only is the property the whole "nothing is written without a human seeing the diff" decision rests on.
- **No WP-isms in the payload.** The response exposes what a caller may do via the capability gate, never a role name.

## VCT team cycles (#3360)

The per-team repeating cycle — how many weeks it runs, the Monday week 1 starts
on, and which reference phase profile it repeats.
`VctTeamCyclesRestController` (`src/Modules/Vct/Rest/VctTeamCyclesRestController.php`).

- **Caps:** `tt_vct_admin_config` on every verb, the same gate the VCT
  configuration tile uses. A cycle decides how hard a squad is worked week by
  week, so it sits with the head of development rather than with general
  administration.
- **Routes:** `GET /vct/team-cycles?season_id=N`,
  `PUT /vct/team-cycles?season_id=N&team_id=M`,
  `DELETE /vct/team-cycles?season_id=N&team_id=M`.
- **`PUT` requires `cycle_weeks` and `anchor_date`.** `cycle_weeks` must be 3,
  4 or 6 — anything else is a 400 rather than a coerced value, because a
  silently corrected cycle length would plan a season nobody chose. The anchor
  is stored as the Monday of whatever week it names; the whole feature counts
  in whole weeks.
- **`template_id` must match the cycle's length.** A four-week shape on a
  six-week cycle is refused: accepting it produces a cycle whose last two weeks
  are flat for no reason the coach can see. Omit it (or send 0) to use the
  seeded profile of that length.
- **`DELETE` also clears the team's week overrides.** An override only ever
  means "this week of the cycle is an exception", so one left behind would
  silently re-apply to a cycle set up months later.
- **Absence of a cycle is a normal answer, not an error.** A team with no row
  is simply planned from the season's macro-blocks, as it was before cycles
  existed.

## VCT age profiles (#2601)

The per-age workload envelope the training generator plans inside: maximum
session length, intensity ceiling, weekly load envelope, recovery gap, PHV
reduction. `VctAgeProfilesRestController`
(`src/Modules/Vct/Rest/VctAgeProfilesRestController.php`).

- **Caps:** read on `tt_vct_plan` — a coach needs to know the ceiling they are
  planning under. Write on `tt_vct_admin_config`, which is head-of-development,
  not general administration: these numbers govern how hard minors are worked.
- **Routes:** `GET /vct/age-profiles`, `POST /vct/age-profiles`,
  `PATCH /vct/age-profiles/{id}`, `DELETE /vct/age-profiles/{id}`.
- **`POST` requires `age_group`, `session_minutes_max` and
  `intensity_band_max`.** The two ceilings carry the age safety, and nothing
  here defaults them — an endpoint that invented load limits for children would
  be the bug. The rest fall back to the seeded conventions (48h recovery, 20%
  PHV reduction, 7.0 match multiplier). Duplicate age group → 400 with a message
  naming it; `uniq_club_age` makes a second row impossible anyway.
- **`POST` also copies session templates** from the nearest age group that has
  them, and reports `templates_copied`. A profile clears the age rule (pass 1);
  the template clears the composition rule (pass 3). Creating only the profile
  would move the block rather than remove it, and there is no operator surface
  for templates. Ties break downwards — a new U15 takes U14's shape, the more
  conservative neighbour.
- **`DELETE` is 409 while a live team is in that age group.** Those teams would
  quietly stop getting drafted trainings with nothing connecting the effect to
  the cause. Saved plans are never affected: a plan carries its own blocks, and
  the profile is only read while drafting.
- **Deleting leaves the session templates in place.** They are inert without a
  profile, and keeping them means re-adding the profile restores the academy's
  own blueprint rather than silently re-copying a neighbour's.
- The create/delete decisions live in `AgeProfileAdminService`, not in the
  controller, so this route and `FrontendVctConfigView` cannot answer
  differently.

## Common conventions

### Response envelope

Successful responses return `{ data: <payload>, ... }` via `RestResponse::success()`. Errors return:

```json
{ "code": "<error_code>", "message": "<localized message>", "data": { "status": <http>, ... } }
```

Common codes: `bad_id`, `missing_fields`, `not_found`, `db_error`, `partial_save`, `invariant`.

### Plan refusals are 402, permission refusals are 403 (#3104)

Two different refusals exist and they never share a status:

| Status | Code | Meaning |
| - | - | - |
| `403` | whatever the controller's `permission_callback` emits | The capability model said no. This user may not do this on any plan. |
| `402` | `license_required` | The **plan** said no. This user may do it; the install is not entitled to the feature. `details` carries `feature` and `required_tier`. |
| `402` | `license_cap_teams` / `license_cap_players` | A usage cap, not a feature. `details` carries `cap_type`. |

Build both through `LicenseGate` rather than by hand — `enforceFeatureRest( $feature )` for a surface that locks whole, `enforceWriteRest( $feature, $request )` for one that keeps its reads. Both return `null` when allowed, so the caller pattern is:

```php
$blocked = LicenseGate::enforceWriteRest( 'match_analysis', $request );
if ( $blocked ) return $blocked;
```

**A read of an existing record survives its feature leaving the plan.** A club that drops from Pro keeps `GET`-ing and exporting the records it wrote while it was on Pro; only `POST` / `PUT` / `PATCH` / `DELETE` are refused. That asymmetry lives in `enforceWriteRest()`, so a controller never re-derives it. Features with no stored records — the dimension explorer, the bulk-entry grids — have nothing to keep readable and use `enforceFeatureRest()` on every verb.

### Pagination + filters

List endpoints follow the Sprint 2 contract used by `FrontendListTable`:

- `?page=<int>&per_page=10|25|50|100` (defaults: `1`, `25`).
- `?orderby=<col>&order=asc|desc` — `<col>` is whitelisted per controller in an `ORDERBY_WHITELIST` constant.
- `?filter[<key>]=<value>` for list filters.
- `?search=<text>` for free-text search.
- `?include_archived=1` when the resource supports soft archive.

**Archive state is always `filter[archived]`** (#2625). Values: `active`
(the default when the param is absent), `archived`, and — where the endpoint
already supported them — `all` / `trashed`. Holidays, tournaments, exercises
and training plans used to spell this `filter[status]`; that key is accepted
as a **deprecated alias for one release** on those four endpoints only, and
`filter[archived]` wins when both are present.

Do not generalise that alias. `filter[status]` is a genuine domain filter
elsewhere and must keep its own meaning: on players it selects on
`tt_players.status` (`active` / `trial` / `released` / `inactive`), and on
goals it selects the Active / Achieved / Missed bucket. Those two are
unaffected by the rename and are covered by regression tests in
`tests/php/ArchiveFilterParamTest.php`.

Coach-scoping for non-admins (`! current_user_can('tt_edit_settings')`) usually limits list reads to teams returned by `QueryHelpers::get_teams_for_coach( get_current_user_id() )`.

### Capabilities

Each controller exposes `can_view()` and `can_edit()` permission callbacks that map onto the granular cap pairs introduced in v3.0.0:

| Resource     | View cap                | Edit cap          |
| ---          | ---                     | ---               |
| Sessions     | `tt_view_activities`      | `tt_edit_activities` |
| Players      | `tt_view_players`       | `tt_edit_players`  |
| Teams        | `tt_view_teams`         | `tt_edit_teams`    |
| Evaluations  | `tt_view_evaluations`   | `tt_edit_evaluations` |
| Goals        | `tt_view_goals`         | `tt_edit_goals`    |
| People       | `tt_view_people`        | `tt_edit_people`   |
| Reports      | `tt_view_reports`       | (read-only)        |

Settings-level edits (custom fields, eval categories, functional roles, config) require `tt_edit_settings`.

## Sessions — payload shapes

### `POST /sessions`

```json
{
  "title": "Tuesday training",
  "session_date": "2026-04-29",
  "team_id": 12,
  "location": "South pitch",
  "notes": "Possession focus.",
  "attendance": {
    "<player_id>": { "status": "Present", "notes": "" },
    "<player_id>": { "status": "Absent",  "notes": "Sick" }
  }
}
```

`attendance` is also accepted as `att` (legacy form-encoded shape). Roster rows are written with `is_guest = 0`.

### `PUT /sessions/{id}`

Same body shape. The handler **only wipes `is_guest = 0` rows** before re-inserting roster attendance — guest rows survive a session edit.

#2248 — the PUT also accepts an optional `planned_attendance` sub-resource (form-encoded as `planned[<player_id>][status|note]`) that records **expected** attendance for a not-yet-completed activity:

```json
{
  "planned": {
    "<player_id>": { "status": "expected",   "note": "" },
    "<player_id>": { "status": "not_coming",  "note": "texted, injured" },
    "<player_id>": { "status": "maybe",       "note": "exam that afternoon" }
  }
}
```

The plan-status keys map to a stored `attendance_status`: `expected` → `present`, `not_coming` → `absent`, `maybe` → `excused` (`excused` is reused so no lookup seed/migration is needed). These rows are written with `record_type = 'expected'` and are wiped/re-inserted **independently** of the `record_type = 'actual'` roster rows above, so recorded attendance and the attendance reports are never touched. `GET /activities/{id}/planned-attendance` returns each expected row's `status`, `plan_status`, and `notes`. Gated on `tt_edit_activities`.

### `GET /activities/{id}/principles` (#2831)

The methodology principles an activity is linked to, read through the same
domain service the activity detail card, the match-prep screen and the printed
team sheet compose from — so a non-WordPress front end draws the identical row.

```json
{
  "activity_id": 412,
  "count": 2,
  "principles": [
    { "id": 7,  "code": "O3", "title": "Opbouwen van achteruit", "bucket": "O", "label": "O3 · Opbouwen van achteruit" },
    { "id": 21, "code": "V1", "title": "Druk zetten op de bal",  "bucket": "V", "label": "V1 · Druk zetten op de bal" }
  ]
}
```

`bucket` is the O / A / V colour class the pill uses, derived from the first
letter of `code`; a consumer that wants its own palette has the `code` it comes
from. Read-only by design — principles are attached through the
`activity_principle_ids[]` field on `PUT /activities/{id}`, so this route
reports what a match is working on rather than offering a second place to
decide it. Gated on `tt_view_activities`. Returns an empty list (not a 404) for
an activity with no principles, and for an install running without the
Methodology module.

### `GET /teams/{id}/minutes-share` (#2835)

What share of the minutes the team actually played did each player get. Reads
the domain service the Minutes share report composes from, so the rendered page
and a non-WordPress front end cannot disagree.

```json
{
  "team_id": 12, "from": "2025-08-25", "to": "2026-08-25",
  "matches": 10, "available_minutes": 700, "target_pct": 30,
  "players": [
    { "player_id": 41, "name": "…", "jersey_number": 9, "minutes": 140, "share_pct": 20.0, "below_target": true },
    { "player_id": 38, "name": "…", "jersey_number": 4, "minutes": 350, "share_pct": 50.0, "below_target": false }
  ]
}
```

`available_minutes` is the sum of every **played** match's own length (the
match-prep half length doubled, else the age-group default, else 35 a half) —
the same "played" predicate the two other minutes reports use, so a fixture
kicking off tonight is not yet in the denominator. `share_pct` is `null` when
the team has played nothing: a share of no minutes is undefined, not zero.
Rows come back lowest share first. `from` / `to` (`YYYY-MM-DD`) narrow the
window; both default to the rolling twelve months, and anything unparseable
falls back to that default rather than 400'ing.

`GET /teams/{id}/minutes-share/{player_id}` returns one player's row out of the
same answer (`minutes`, `share_pct`, `below_target`, plus the team's
`available_minutes`, `matches` and `target_pct`), so a player-facing client
need not fetch and filter the whole squad. A player with no recorded minutes on
that team in the window is a 404 rather than a zero row — they were not in the
squad, which is different from having played none of it.

Both routes, and `GET /teams/{id}` beside them, gate on the caller's **team
scope** and not on `tt_view_teams` alone (#3152). `tt_view_teams` is club-wide
on `tt_coach`, so before that a coach could read any squad's roster and minutes
by changing one number in the URL. The predicate is
`AllTeamsScope::canReadTeam()`: a global `team` read sees every squad, everyone
else sees the teams they are assigned to — the same narrowing `GET /teams`
already applied when deciding which rows to list. A team outside scope is a
**403**, not a 402: this is the capability model, not the plan.

Both gate on a `reports` read: global scope sees any team, a team-scoped grant
only its own, and anything else is a 403 rather than an empty list — an empty
list would read as "this team played nothing".

### `POST /sessions/{id}/guests` (#0026)

```json
{ "guest_player_id": 42 }                               // linked
{ "guest_name": "Sam", "guest_age": 13, "guest_position": "RW" }   // anonymous
```

Application invariant: linked XOR anonymous. Returns the inserted attendance row decorated with `player_name` + `home_team` for linked guests.

### `PATCH /attendance/{id}` (#0026)

Partial update. Accepts any subset of `status`, `notes`, `guest_notes`, `guest_name`, `guest_age`, `guest_position`, `minutes_played`. Used by the inline "anonymous guest notes" save-on-blur path and (#2224) by the match-execution "correct recorded minutes" action on a finalized match. `minutes_played` is clamped to 0–200; an empty value clears it. Gated on `can_edit` (`tt_edit_activities`).

### `DELETE /attendance/{id}` (#0026)

Removes a single attendance row. Used by the guest UI's Remove affordance.

### `POST /activities/{id}/status` (#2245, #2407)

Direct, confirmed status transition for the detail view's buttons. Body `{ status: 'cancelled' | 'planned' | 'completed' }`; gated on `can_edit` (`tt_edit_activities`).

`completed` is **conditionally** accepted (#2407). While the `new-evaluation` wizard is available to the caller (`tt_wizards_enabled`, plus the wizard's own cap), completion belongs to that flow — which records attendance and then flips the status at its final save — so `completed` is rejected with a 400 and a second, attendance-free path can't open. When the wizard is switched off there is no such final save (the grid bulk endpoints write attendance and minutes but never status), so this endpoint becomes the only route to `completed` and accepts it. `cancelled` / `planned` are always accepted.

Writes `activity_status_key` **and** the derived `plan_state` (`planned` → `scheduled`, otherwise the same value), then fires `tt_activity_status_changed`.

## Search + peek (#2458)

Backs the command palette and the peek panel in the app shell. Both exist as REST
endpoints per CLAUDE.md §4 — the feature is reachable by a non-WordPress front
end, not only via rendered HTML.

### `GET /search`

`?q=<string>&types=view,player,team,activity`

Permission: any logged-in user. **The gate is per row, not per route.**

```json
{ "results": [
  { "type": "player", "id": 42, "label": "Sem de Vries", "sublabel": "JO15-1",
    "url": "https://…/?tt_view=players&id=42" }
] }
```

| Type | Source | Filtering |
| --- | --- | --- |
| `view` | `TileRegistry::tilesForUserGrouped()` | Inherited — capability, per-persona labels, `__hidden__`, module/feature gating |
| `player` | `tt_players` prefilter | Every row through `AuthorizationService::canViewPlayer()` |
| `team` | `tt_teams` | `tt_view_teams`, then narrowed in SQL to the caller's team scope — the same narrowing `GET /teams` applies (#3159) |
| `activity` | `tt_activities` | `tt_view_activities` |

Two constraints that are load-bearing rather than cosmetic:

- **Player rows are filtered per record**, using the same authorization the
  detail view uses. A capability check on the route would let someone with
  `tt_view_players` enumerate players outside their scope. These are minors
  (§1); the search box is the easiest place in a product to leak one.
- **Team rows are narrowed in SQL**, not post-filtered, so the cap is filled
  with squads the caller may actually open. A caller with no teams gets an
  empty list — a genuine empty result, not an unfiltered one. Team names encode
  age groups, so enumerating them maps the academy's cohorts: not player data
  itself, but the index to it.
- **Results are hard-capped at 8** and record types need ≥2 characters. An
  uncapped search is both a performance problem and an enumeration surface.
- **`activity` rows are still capability-only.** A title and a date are not a
  child's identity, so this is recorded rather than narrowed — but it is the
  one row in the table above where "per row, through the same authorization
  service" is not yet literally true.

### `GET /players/{id}/summary`, `/teams/{id}/summary`, `/activities/{id}/summary`

Read-only summaries behind the peek panel. Permission is the same per-record
check the detail view uses — `canViewPlayer()` for players,
`AllTeamsScope::canReadTeam()` for teams — so a record you cannot open is a
record you cannot peek.

The team peek was the exception until #3152: it gated on `tt_view_teams` alone,
which is club-wide on `tt_coach`, so it returned name, age group, season and
roster count for any team id. It now asks the same question `GET /teams/{id}`
and `GET /teams` ask. **The activity peek still gates on `tt_view_activities`
alone** and is the remaining route where the sentence above is aspirational.

```json
{ "type": "player", "id": 42, "title": "Sem de Vries", "subtitle": "JO15-1",
  "url": "https://…/?tt_view=players&id=42",
  "facts": [ { "label": "Status", "value": "Signed" } ] }
```

One envelope for all three so the panel renders one way. Facts with an empty
value are dropped server-side rather than rendered blank. **Read-only in v1** —
editing inside a panel means a second save path and a stale-parent problem, on a
surface whose job is orientation rather than data entry.

## PDP evidence packet (#3302, epic #3301)

### `GET /pdp-files/{id}/evidence-packet`

Everything the academy already knows about the player on this PDP file,
assembled once. Read-only. Gated on `tt_view_pdp` / `tt_edit_pdp` /
`tt_edit_pdp_verdict` plus the per-file scope check.

The same assembly backs three surfaces — the Evidence tab on a
conversation, the printed file, and the verdict screen — so they cannot
show three different answers for the same player on the same day. Every
group is club-scoped and excludes archived and trashed rows.

```json
{
  "file_id": 12,
  "player_id": 340,
  "conversation_id": 0,
  "season": { "id": 3, "name": "2026/27", "start_date": "2026-07-01", "end_date": "2027-06-30" },
  "window": { "from": "2026-07-01", "to": "2027-06-30", "scope": "season" },
  "status": { },
  "behaviour": [ ],
  "potential": [ ],
  "evaluations": [
    {
      "id": 88, "eval_date": "2026-09-01", "rating": 7.2,
      "notes": "Reads the game two passes ahead.",
      "assessor_id": 9, "assessor_name": "Ada Kuipers", "eval_type_id": 2,
      "categories": [ { "category_id": 4, "label": "Passing", "is_main": true, "rating": 7.5 } ]
    }
  ],
  "attendance": { "activities": 24, "present": 21, "absent": 2, "excused": 1, "rate": 88 },
  "minutes": { "apps": 14, "minutes": 812, "breakdown": [ ] },
  "goals": [ { "id": 5, "title": "Win more headers", "status": "in_progress", "changed_in_window": true, "is_closed": false } ],
  "injuries": [ { "id": 2, "started_on": "2026-10-05", "actual_return": "2026-11-20", "is_open": false } ],
  "notes": [ { "id": 31, "created_at": "2026-09-15 09:00:00", "author_name": "Ada Kuipers", "visibility": "public", "body": "…" } ],
  "self_reflection": "",
  "recent_journey": [ ]
}
```

`notes` is assembled for the **reader**, not for the record: a caller who
cannot see this player's staff notes on the player file gets an empty
array here, and a private-to-coach note stays private. `rate` is null when
the window holds no activities.

`attendance.activities` was `attendance.sessions` before #3302 — the
entity was renamed under #0035 and the packet was still carrying the old
word. The endpoint had no consumers, so the key is corrected rather than
kept.

`minutes.breakdown` comes from `MinutesQuery::matchBreakdownForPlayer()`,
which is team-scoped — a player with no team gets the totals and an empty
breakdown rather than a wrong one.

`EvidencePacket::forConversation( $conversation_id )` is the same shape
narrowed to the window since the previous conversation in the cycle, with
`window.scope` set to `conversation`, `conversation_id` populated, and
`self_reflection` carrying what the player wrote for that talk. The first
conversation of a season has no predecessor, so its window opens at the
season start.

## Adding a new resource

1. Add a controller under `src/Infrastructure/REST/` (or per-module `Rest/` directory) following the existing pattern: `init()` adds the `rest_api_init` action, `register()` registers the routes, `can_view()` / `can_edit()` return capability checks, handlers extract via `\WP_REST_Request`, validate, write via `$wpdb`, return `RestResponse::success()` / `RestResponse::error()`.
2. Wire the controller's `init()` into the module's `boot()` (or via `Kernel::registerRestControllers()` if the project uses that path).
3. Update this doc with the new routes + payload shape.
4. If the resource is consumed by `FrontendListTable`, document the orderby whitelist and any computed columns (e.g. `attendance_count`).

See `ActivitiesRestController.php` as the canonical reference.

<?php
namespace TT\Shared\Configuration;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Core\ModuleRegistry;
use TT\Shared\Modules\ModuleMetadata;
use TT\Shared\Tiles\TileRegistry;

/**
 * What Configuration offers, grouped by the module it belongs to (#3046).
 *
 * Configuration used to build six hardcoded sections — appearance,
 * dashboard, data, methodology, integrations, system — and append every
 * entry to one of them by hand. Those are technical groupings rather than
 * the modules an operator thinks in, so a module's settings scattered
 * across three of them depending on which felt closest the day it was
 * added, and an operator looking for "how are trials organised" had to
 * already know where each piece was filed.
 *
 * Two facts already recorded elsewhere carry the whole grouping:
 *
 *   - `TileRegistry`'s `kind` — `work` or `setup`. A `setup` surface is a
 *     Configuration entry; a `work` surface is a dashboard tile.
 *   - `module_class` on every tile, plus `ModuleMetadata`'s label, icon
 *     and category per module.
 *
 * So a module that registers a `kind: setup` tile gets a Configuration
 * group for free, and switching that module off takes the group with it —
 * neither needs an edit here. What remains hand-listed is the set of
 * entries that are NOT tiles: the in-page `?config_sub=` forms, which have
 * no route of their own and therefore no tile to carry the module.
 *
 * Settings that genuinely belong to the whole install rather than to any
 * one module — locale, date notation, backups, the audit log, wp-admin
 * menus, the configuration export — group under "Academy-wide" instead of
 * being indistinguishable from module settings filed under "System".
 */
final class ConfigurationSurfaces {

    /** The Configuration tile itself — the door, not a room inside it. */
    private const SELF_SLUG = 'configuration';

    /**
     * Configuration's entries, grouped and ordered for rendering.
     *
     * @param int    $user_id   Viewer, for capability and persona gating.
     * @param string $sub_base  Base URL for `?config_sub=` in-page forms.
     * @param string $view_base Base URL for `?tt_view=` destinations.
     * @return list<array{label:string, tiles:list<array<string,mixed>>}>
     */
    public static function groups( int $user_id, string $sub_base, string $view_base ): array {
        /** @var array<string,bool> $seen URLs already placed, so a surface appears once. */
        $seen = [];

        /** @var array<string,array{label:string,category:int,tiles:list<array<string,mixed>>}> $by_module */
        $by_module = [];

        foreach ( self::formEntries( $user_id, $sub_base, $view_base ) as [ $module, $tile ] ) {
            self::place( $module, $tile, $by_module, $seen );
        }
        foreach ( self::setupTiles( $user_id, $view_base ) as [ $module, $tile ] ) {
            self::place( $module, $tile, $by_module, $seen );
        }
        foreach ( self::contributedTiles() as $tile ) {
            self::place( null, $tile, $by_module, $seen );
        }

        // Academy-wide first (category -1), then modules by their
        // ModuleMetadata category and label, so the Administration modules
        // cluster rather than interleaving with Player data.
        uasort( $by_module, static function ( array $a, array $b ): int {
            $c = $a['category'] <=> $b['category'];
            return $c !== 0 ? $c : strcmp( $a['label'], $b['label'] );
        } );

        $out = [];
        foreach ( $by_module as $group ) {
            if ( $group['tiles'] === [] ) continue;
            $out[] = [ 'label' => $group['label'], 'tiles' => $group['tiles'] ];
        }
        return $out;
    }

    /**
     * File one entry under its module's group, skipping a URL already placed
     * so a surface reachable from two sources appears once.
     *
     * @param array<string,mixed> $tile
     * @param array<string,array{label:string,category:int,tiles:list<array<string,mixed>>}> $by_module
     * @param array<string,bool> $seen
     */
    private static function place( ?string $module, array $tile, array &$by_module, array &$seen ): void {
        $url = (string) ( $tile['url'] ?? '' );
        if ( $url === '' || isset( $seen[ $url ] ) ) return;
        $seen[ $url ] = true;

        $key = $module === null || $module === '' ? '' : ltrim( $module, '\\' );
        if ( ! isset( $by_module[ $key ] ) ) {
            if ( $key === '' ) {
                $by_module[ $key ] = [ 'label' => __( 'Academy-wide', 'talenttrack' ), 'category' => -1, 'tiles' => [] ];
            } else {
                $meta = ModuleMetadata::for( $key );
                $by_module[ $key ] = [
                    'label'    => (string) $meta['label'],
                    'category' => self::categoryRank( (string) $meta['category'] ),
                    'tiles'    => [],
                ];
            }
        }

        // Mark wp-admin destinations so the context switch is expected.
        $tile['external'] = strpos( $url, '/wp-admin/' ) !== false;
        $by_module[ $key ]['tiles'][] = $tile;
    }

    /**
     * Position of a `ModuleMetadata` category in its canonical order.
     *
     * Falls to the end for anything unrecognised, so a new category shows
     * up last rather than silently ahead of Academy-wide.
     */
    private static function categoryRank( string $category ): int {
        $keys = array_keys( ModuleMetadata::categories() );
        $pos  = array_search( $category, $keys, true );
        return $pos === false ? count( $keys ) : (int) $pos;
    }

    /**
     * The in-page `?config_sub=` forms and the `?tt_view=` settings screens
     * that have no `kind: setup` tile of their own.
     *
     * Each is `[ module class or null, tile ]`. A tile with a `kind: setup`
     * registration does NOT belong here — it arrives through
     * {@see setupTiles()} and would be deduped away.
     *
     * @return list<array{0:string|null,1:array<string,mixed>}>
     */
    private static function formEntries( int $user_id, string $sub_base, string $view_base ): array {
        $sub  = static fn ( string $s ): string => (string) add_query_arg( [ 'config_sub' => $s ], $sub_base );
        $view = static fn ( string $s ): string => (string) add_query_arg( [ 'tt_view' => $s ], $view_base );

        $out = [];

        $out[] = [ null, [
            'title' => __( 'Appearance', 'talenttrack' ),
            'desc'  => __( 'Academy name, logo, all brand colours, fonts and theme inheritance — in one place.', 'talenttrack' ),
            'url'   => $sub( 'appearance' ),
            'icon'  => 'rate-card',
        ] ];
        $out[] = [ null, [
            'title' => __( 'General', 'talenttrack' ),
            'desc'  => __( 'Date notation, first day of the week, timezone and locale for the whole academy.', 'talenttrack' ),
            'url'   => $sub( 'general' ),
            'icon'  => 'settings',
        ] ];
        $out[] = [ null, [
            'title' => __( 'Lookups', 'talenttrack' ),
            'desc'  => __( 'Activity types, positions, age groups, goal statuses, evaluation types — every dropdown vocabulary in one place.', 'talenttrack' ),
            'url'   => $sub( 'lookups' ),
            'icon'  => 'categories',
        ] ];
        $out[] = [ null, [
            'title' => __( 'Seasons', 'talenttrack' ),
            'desc'  => __( 'Create, edit, delete and set the current academy season. PDP files and the carryover job are scoped to the current season.', 'talenttrack' ),
            'url'   => $view( 'seasons' ),
            'icon'  => 'calendar',
        ] ];
        $out[] = [ null, [
            'title' => __( 'wp-admin menus', 'talenttrack' ),
            'desc'  => __( 'Show or hide the legacy wp-admin menu entries.', 'talenttrack' ),
            'url'   => $sub( 'menus' ),
            'icon'  => 'gear',
        ] ];

        // #2569 — the drift tile links into lookup normalisation, a curation
        // surface behind tt_edit_lookups. Gate the tile on the same cap so it
        // never advertises a screen the viewer cannot open.
        $pending = current_user_can( 'tt_edit_lookups' ) ? self::pendingLookupDriftCount() : 0;
        if ( $pending > 0 ) {
            $out[] = [ null, [
                'title' => __( 'Lookup canonical-language review', 'talenttrack' ),
                'desc'  => sprintf(
                    /* translators: %d is the number of lookup rows pending canonical-language review. */
                    _n( '%d lookup row drifted from its canonical English internal key. Review the suggestion and accept the rewrite, or skip.', '%d lookup rows drifted from their canonical English internal key. Review each suggestion and accept the rewrite, or skip.', $pending, 'talenttrack' ),
                    $pending
                ),
                'url'  => $view( 'lookup-normalisation' ),
                'icon' => 'docs',
            ] ];
        }

        // #1937 — the frontend Backups view; the wp-admin tab stays as the
        // power-user fallback and still owns the partial-restore picker.
        if ( current_user_can( 'tt_manage_backups' ) ) {
            $out[] = [ null, [
                'title' => __( 'Backups', 'talenttrack' ),
                'desc'  => __( 'Scheduled and on-demand database backups: download, restore, and the .ttmig data migration export and import flow.', 'talenttrack' ),
                'url'   => $view( 'backups' ),
                'icon'  => 'migrations',
            ] ];
        }
        // #2540 — configuration snapshot as JSON. Also gated on the Export
        // module: it owns the `admin_post_tt_export` handler this posts to,
        // so with the module off the tile would dead-end on a 400.
        if ( current_user_can( 'tt_edit_settings' ) && ModuleRegistry::isEnabled( 'TT\\Modules\\Export\\ExportModule' ) ) {
            $out[] = [ null, [
                'title' => __( 'Export configuration', 'talenttrack' ),
                'desc'  => __( 'Download every setting plus which modules and features are on or off, as a JSON file. Credentials are redacted; no player data.', 'talenttrack' ),
                'url'   => $sub( 'export' ),
                'icon'  => 'share',
            ] ];
        }
        // #2024 — centralized recycle bin. Configuration entry point rather
        // than a dashboard tile: an operator surface, reached deliberately.
        if ( current_user_can( 'tt_manage_recycle_bin' ) ) {
            $out[] = [ null, [
                'title' => __( 'Recycle bin', 'talenttrack' ),
                'desc'  => __( 'Records staged for permanent deletion. Restore them to the archive, or delete them now. Purged after the retention window.', 'talenttrack' ),
                'url'   => $view( 'recycle-bin' ),
                'icon'  => 'migrations',
            ] ];
        }

        // ── Per-module settings that have no tile of their own ──

        $out[] = [ 'TT\\Modules\\CustomCss\\CustomCssModule', [
            'title' => __( 'Custom CSS', 'talenttrack' ),
            'desc'  => __( 'Per-club custom styling: visual + code editor, file upload, starter templates, revertable history.', 'talenttrack' ),
            'url'   => $view( 'custom-css' ),
            'icon'  => 'edit',
        ] ];
        $out[] = [ 'TT\\Modules\\PersonaDashboard\\PersonaDashboardModule', [
            'title' => __( 'Default dashboard', 'talenttrack' ),
            'desc'  => __( 'Choose what every user sees on the dashboard root: the persona dashboard or the classic tile grid.', 'talenttrack' ),
            'url'   => $sub( 'dashboard' ),
            'icon'  => 'dashboard',
        ] ];
        $out[] = [ 'TT\\Modules\\Evaluations\\EvaluationsModule', [
            'title' => __( 'Rating scale', 'talenttrack' ),
            'desc'  => __( 'Min, max and step for evaluation ratings.', 'talenttrack' ),
            'url'   => $sub( 'rating' ),
            'icon'  => 'weights',
        ] ];
        // #2207 — which cards the player-profile "Profile" tab shows academy-wide.
        $out[] = [ 'TT\\Modules\\Players\\PlayersModule', [
            'title' => __( 'Profile cards', 'talenttrack' ),
            'desc'  => __( 'Choose which cards the player-profile Profile tab shows academy-wide. Hide the ones you do not use (e.g. Discovery). Identity always stays.', 'talenttrack' ),
            'url'   => $sub( 'profile-cards' ),
            'icon'  => 'players',
        ] ];
        if ( current_user_can( 'tt_edit_players' ) ) {
            $out[] = [ 'TT\\Modules\\Players\\PlayersModule', [
                'title' => __( 'Players CSV import', 'talenttrack' ),
                'desc'  => __( 'Bulk-import players from a spreadsheet. Map columns, choose duplicate-handling, preview before commit.', 'talenttrack' ),
                'url'   => $view( 'players-import' ),
                'icon'  => 'import',
            ] ];
        }
        if ( current_user_can( 'tt_edit_settings' ) ) {
            // #1548 — player traffic-light weights and thresholds.
            $out[] = [ 'TT\\Modules\\Players\\PlayerStatusModule', [
                'title' => __( 'Player status methodology', 'talenttrack' ),
                'desc'  => __( 'Weights and thresholds for the player traffic-light status, per age group.', 'talenttrack' ),
                'url'   => $view( 'player-status-methodology' ),
                'icon'  => 'settings',
            ] ];
        }
        $out[] = [ 'TT\\Modules\\Pdp\\PdpModule', [
            'title' => __( 'PDP cycle blocks', 'talenttrack' ),
            'desc'  => __( 'Date ranges for each block in a PDP cycle, per season. Configure 2, 3 or 4 blocks with date pairs validated against the season window.', 'talenttrack' ),
            'url'   => $sub( 'pdp-blocks' ),
            'icon'  => 'calendar',
        ] ];
        // #3305 (epic #3301) — the prompts a coach prepares each PDP
        // conversation against, per conversation template.
        $out[] = [ 'TT\\Modules\\Pdp\\PdpModule', [
            'title' => __( 'PDP preparation questions', 'talenttrack' ),
            'desc'  => __( 'What a coach thinks about before each conversation in the cycle. A different set per conversation, private to the coach and the head of academy.', 'talenttrack' ),
            'url'   => $sub( 'pdp-prep-questions' ),
            'icon'  => 'note',
        ] ];
        // #3044 — how many a side each age category plays. A Teams setting:
        // it pre-fills the team record's own football form, which is where a
        // club that runs one team differently says so.
        $out[] = [ 'TT\\Modules\\Teams\\TeamsModule', [
            'title' => __( 'Football form', 'talenttrack' ),
            'desc'  => __( 'How many a side each age category plays — 6v6, 8v8, 11v11. Pre-fills a new team and decides which formations its blueprint offers.', 'talenttrack' ),
            'url'   => $sub( 'football-form' ),
            'icon'  => 'teams',
        ] ];
        // #1727 — central per-age-category default match minutes. Owned by
        // Activities rather than Match prep: it still governs the
        // match-completion minutes entry on an install with prep switched off.
        $out[] = [ 'TT\\Modules\\Activities\\ActivitiesModule', [
            'title' => __( 'Match minutes', 'talenttrack' ),
            'desc'  => __( 'Default match length per age category (minutes per half, total 2 x N). Prefills match prep and the match-completion minutes entry.', 'talenttrack' ),
            'url'   => $sub( 'match-minutes' ),
            'icon'  => 'hourglass',
        ] ];
        // #2603 — per-template on/off for outgoing messages. Gated on
        // tt_edit_feature_toggles, the sub-cap the `comms_templates_disabled`
        // config key resolves to, so the entry never offers a screen whose
        // save the REST layer would refuse.
        if ( current_user_can( 'tt_edit_feature_toggles' ) ) {
            $out[] = [ 'TT\\Modules\\Comms\\CommsModule', [
                'title' => __( 'Messages', 'talenttrack' ),
                'desc'  => __( 'Choose which messages your academy sends — cancellations, nudges, reminders and letters. Switching one off stops it for everyone; the message log still records that it would have gone out.', 'talenttrack' ),
                'url'   => $sub( 'messages' ),
                'icon'  => 'docs',
            ] ];
        }
        // #1935 — the frontend Translations view; the wp-admin tab stays as
        // the power-user fallback.
        if ( current_user_can( 'tt_view_translations' ) ) {
            $out[] = [ 'TT\\Modules\\Translations\\TranslationsModule', [
                'title' => __( 'Translations', 'talenttrack' ),
                'desc'  => __( 'Auto-translation engine (DeepL / Google), monthly usage, and cache.', 'talenttrack' ),
                'url'   => $view( 'translations' ),
                'icon'  => 'docs',
            ] ];
        }
        // #1938 — the frontend Setup flow; the wp-admin wizard stays as the
        // power-user fallback.
        if ( current_user_can( 'tt_edit_settings' ) ) {
            $out[] = [ 'TT\\Modules\\Onboarding\\OnboardingModule', [
                'title' => __( 'Setup', 'talenttrack' ),
                'desc'  => __( 'Run or re-run first-time setup: academy basics, your first team, your admin profile, and the dashboard page.', 'talenttrack' ),
                'url'   => $view( 'setup' ),
                'icon'  => 'lightbulb',
            ] ];
        }
        // #2880 — the authorization matrix, editable by an academy admin
        // without a WordPress account. A settings entry rather than a
        // dashboard tile: reached deliberately, not met on a landing page.
        if ( current_user_can( 'tt_manage_authorization' ) ) {
            $out[] = [ 'TT\\Modules\\Authorization\\AuthorizationModule', [
                'title' => __( 'Access control matrix', 'talenttrack' ),
                'desc'  => __( 'Who may read and change each kind of record, per persona. The grants behind player evaluations, notes and medical fields.', 'talenttrack' ),
                'url'   => $view( 'matrix' ),
                'icon'  => 'roles',
            ] ];
        }
        // #1936 — the frontend Spond view. Cap-gated to tt_edit_teams, the
        // view's own gate.
        if ( current_user_can( 'tt_edit_teams' ) ) {
            $out[] = [ 'TT\\Modules\\Spond\\SpondModule', [
                'title' => __( 'Spond integration', 'talenttrack' ),
                'desc'  => __( 'Per-team calendar sync status, "Refresh now", encrypted account credentials, and the API endpoint override.', 'talenttrack' ),
                'url'   => $view( 'spond' ),
                'icon'  => 'sessions',
            ] ];
        }
        // #2127 — Strava operator console. Matrix-gated on tt_view_strava,
        // the view's own read gate.
        if ( current_user_can( 'tt_view_strava' ) ) {
            $out[] = [ 'TT\\Modules\\Strava\\StravaModule', [
                'title' => __( 'Strava integration', 'talenttrack' ),
                'desc'  => __( 'Register the Strava app credentials, manage the webhook subscription, and see which players have connected their accounts.', 'talenttrack' ),
                'url'   => $view( 'strava-admin' ),
                'icon'  => 'sessions',
            ] ];
        }

        foreach ( self::vctEntry( $user_id, $view_base ) as $tile ) {
            $out[] = [ 'TT\\Modules\\Vct\\VctModule', $tile ];
        }

        // A module that is switched off takes its entries with it, the way
        // its tiles already disappear. Academy-wide entries have no module
        // to switch off.
        return array_values( array_filter(
            $out,
            static fn ( array $row ): bool => $row[0] === null || ModuleRegistry::isEnabled( $row[0] )
        ) );
    }

    /**
     * `kind: setup` tiles, which are Configuration entries by definition.
     *
     * `TileRegistry::tilesForUser()` has already applied module state,
     * feature toggles, the matrix, capabilities and persona hiding, so
     * whatever comes back is a surface this viewer can open.
     *
     * @return list<array{0:string|null,1:array<string,mixed>}>
     */
    private static function setupTiles( int $user_id, string $view_base ): array {
        $out = [];
        foreach ( TileRegistry::tilesForUser( $user_id )['setup'] as $tile ) {
            $slug = (string) ( $tile['view_slug'] ?? '' );
            if ( $slug === self::SELF_SLUG ) continue;

            $url = (string) ( $tile['url'] ?? '' );
            if ( $url === '' && isset( $tile['url_callback'] ) && is_callable( $tile['url_callback'] ) ) {
                $url = (string) ( $tile['url_callback'] )( $user_id );
            }
            if ( $url === '' && $slug !== '' ) {
                $url = (string) add_query_arg( [ 'tt_view' => $slug ], $view_base );
            }
            if ( $url === '' ) continue;

            $module = $tile['module_class'] ?? null;
            $out[]  = [
                is_string( $module ) && $module !== '' ? $module : null,
                [
                    'title' => (string) ( $tile['label'] ?? '' ),
                    'desc'  => (string) ( $tile['description'] ?? '' ),
                    'url'   => $url,
                    'icon'  => (string) ( $tile['icon'] ?? '' ),
                ],
            ];
        }
        return $out;
    }

    /**
     * #1539 — entries contributed through the `tt_config_tile_groups`
     * filter (Modules, Dashboard layouts, Custom widgets, the matrix).
     * Contributors use an emoji icon and carry no module, so they group
     * under Academy-wide.
     *
     * @return list<array<string,mixed>>
     */
    private static function contributedTiles(): array {
        $out    = [];
        $groups = (array) apply_filters( 'tt_config_tile_groups', [] );
        foreach ( $groups as $group ) {
            $tiles = is_array( $group['tiles'] ?? null ) ? $group['tiles'] : [];
            foreach ( $tiles as $tile ) {
                $cap = (string) ( $tile['cap'] ?? 'tt_view_settings' );
                if ( ! current_user_can( $cap ) ) continue;
                $url = (string) ( $tile['url'] ?? '' );
                if ( $url === '' ) continue;
                $out[] = [
                    'title' => (string) ( $tile['label'] ?? '' ),
                    'desc'  => (string) ( $tile['description'] ?? '' ),
                    'url'   => $url,
                    'icon'  => (string) ( $tile['icon'] ?? '' ),
                    'emoji' => true, // contributors use an emoji icon, not a slug
                ];
            }
        }
        return $out;
    }

    /**
     * #1546 — one "VCT configuration" entry opening `?tt_view=vct-config`
     * at its default tab; the destination's own tab bar handles
     * sub-navigation. The count line summarises the macro-block templates
     * and age bands configured.
     *
     * @return list<array<string,mixed>>
     */
    private static function vctEntry( int $user_id, string $view_base ): array {
        if ( ! \TT\Infrastructure\Security\AuthorizationService::userCanOrMatrix( $user_id, 'tt_vct_admin_config' ) ) {
            return [];
        }

        $blocks_count = count( ( new \TT\Modules\Vct\Repositories\VctMacroBlocksRepository() )->listReferenceTemplates() );
        $ages_count   = count( ( new \TT\Modules\Vct\Repositories\VctAgeProfilesRepository() )->listAll() );

        return [
            [
                'url'   => (string) add_query_arg( [ 'tt_view' => 'vct-config' ], $view_base ),
                'title' => __( 'VCT configuration', 'talenttrack' ),
                'desc'  => __( 'Macro-block calendar, per-age workload envelopes and per-team training days for the Variabel Coachen-template planner — all on one screen.', 'talenttrack' ),
                'icon'  => 'methodology',
                'vct'   => true,
                'count' => sprintf(
                    /* translators: 1: number of macro-block templates, 2: number of age bands. */
                    __( '%1$d block templates · %2$d age bands', 'talenttrack' ),
                    $blocks_count,
                    $ages_count
                ),
            ],
        ];
    }

    /**
     * #987 — `tt_audit_log` rows flagged for canonical-language review that
     * have not been resolved (accepted or skipped).
     */
    private static function pendingLookupDriftCount(): int {
        global $wpdb;
        $table = $wpdb->prefix . 'tt_audit_log';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) return 0;
        $club_id = \TT\Infrastructure\Tenancy\CurrentClub::id();
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} n
              WHERE n.action     = %s
                AND n.entity_type = 'lookup'
                AND n.club_id     = %d
                AND NOT EXISTS (
                    SELECT 1 FROM {$table} r
                     WHERE r.entity_type = 'lookup'
                       AND r.entity_id   = n.entity_id
                       AND r.club_id     = n.club_id
                       AND r.action IN ('lookup.normalisation.applied', 'lookup.normalisation.skipped')
                )",
            'lookup.needs_review', $club_id
        ) );
    }
}

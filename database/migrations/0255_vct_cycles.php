<?php
/**
 * Migration 0255 — per-team repeating VCT cycles (#3357, epic #3354).
 *
 * A macro-block is a dated season phase. A cycle is the repeating
 * 3/4/6-week rhythm inside it, and until now had no representation:
 * ProgressionRule walked weeks straight out from a block's start date
 * and never wrapped, so a six-week profile over a twelve-week block
 * planned six real weeks and then silently fell back to a neutral
 * multiplier.
 *
 * Two tables and four columns:
 *
 *   - tt_vct_team_cycles — one row per (team, season) holding the cycle
 *     length, the Monday cycle week 1 starts on, and which reference
 *     template supplies the per-week shape. No row means no cycle, and
 *     the existing macro-block behaviour applies unchanged.
 *
 *   - tt_vct_cycle_weeks — manual week overrides, and ONLY those. It is
 *     deliberately sparse: most weeks have no row. The resolver computes
 *     the full week list on demand from anchor + length + fixture dates,
 *     reading this table for exceptions. A materialised week-per-row
 *     ledger would need invalidating on every fixture move, and a
 *     NEUTRAL week shifts every week after it, so that invalidation is
 *     the whole season every time. Computing it is cheaper than keeping
 *     it correct.
 *
 *   - tt_activities gains vct_cycle_id / vct_cycle_week /
 *     vct_cycle_state / vct_cycle_manual so a training records the week
 *     it was planned as. `vct_cycle_manual` marks a human's override and
 *     exempts the row from every re-stamp pass.
 *
 * Plus a 3-week reference phase profile, the one length the seeded set
 * was missing (0126 seeded 4-week and 6-week; 0203 added the 5-week
 * speelwijze cycle).
 *
 * Both new tables carry `club_id INT UNSIGNED NOT NULL DEFAULT 1`
 * (CLAUDE.md §4 tenancy scaffold). `tt_vct_team_cycles` is a
 * user-facing root entity and carries `uuid CHAR(36) UNIQUE`; the
 * overrides table is a child of it and does not.
 *
 * No DB-level FOREIGN KEY constraints — app-level integrity per the
 * codebase convention.
 *
 * `cycle_weeks` is a TINYINT validated in the repository against
 * {3, 4, 6} rather than a DB ENUM: the seeded set already contains a
 * five-week speelwijze profile, and widening an ENUM costs another
 * migration where widening a validator costs a line.
 *
 * Idempotent — SHOW TABLES / SHOW COLUMNS guards throughout, and the
 * seed checks for its row before inserting.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Database\Migration;

return new class extends Migration {

    public function getName(): string {
        return '0255_vct_cycles';
    }

    public function up(): void {
        $this->createTables();
        $this->addActivityStampColumns();
        $this->seedThreeWeekReference();
    }

    private function createTables(): void {
        global $wpdb;
        $p       = $wpdb->prefix;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = [];

        // tt_vct_team_cycles — the per-team cycle configuration.
        $t = "{$p}tt_vct_team_cycles";
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) !== $t ) {
            $tables[] = "CREATE TABLE {$t} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                club_id INT UNSIGNED NOT NULL DEFAULT 1,
                uuid CHAR(36) NOT NULL,
                team_id BIGINT UNSIGNED NOT NULL,
                season_id BIGINT UNSIGNED NOT NULL,
                cycle_weeks TINYINT UNSIGNED NOT NULL DEFAULT 6,
                anchor_date DATE NOT NULL,
                template_id BIGINT UNSIGNED NULL,
                archived_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_uuid (uuid),
                UNIQUE KEY uniq_club_team_season (club_id, team_id, season_id),
                KEY idx_club_season (club_id, season_id)
            ) {$charset};";
        }

        // tt_vct_cycle_weeks — manual overrides only. Sparse by design.
        $t = "{$p}tt_vct_cycle_weeks";
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) !== $t ) {
            $tables[] = "CREATE TABLE {$t} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                club_id INT UNSIGNED NOT NULL DEFAULT 1,
                team_id BIGINT UNSIGNED NOT NULL,
                season_id BIGINT UNSIGNED NOT NULL,
                week_starts_on DATE NOT NULL,
                state VARCHAR(16) NOT NULL,
                note VARCHAR(255) NULL,
                set_by BIGINT UNSIGNED NULL,
                set_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_club_team_week (club_id, team_id, week_starts_on),
                KEY idx_club_team_season (club_id, team_id, season_id)
            ) {$charset};";
        }

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }

    /**
     * The stamp a training carries. Nullable throughout: an activity with
     * no cycle is the normal state for a team that has not configured one,
     * not an error, and every reader handles it.
     */
    private function addActivityStampColumns(): void {
        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $activities ) ) !== $activities ) return;

        self::addColumn( $activities, 'vct_cycle_id',     'BIGINT UNSIGNED DEFAULT NULL' );
        self::addColumn( $activities, 'vct_cycle_week',   'TINYINT UNSIGNED DEFAULT NULL' );
        self::addColumn( $activities, 'vct_cycle_state',  'VARCHAR(16) DEFAULT NULL' );
        self::addColumn( $activities, 'vct_cycle_manual', 'TINYINT UNSIGNED NOT NULL DEFAULT 0' );

        $index = $wpdb->get_var( $wpdb->prepare(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = 'idx_vct_cycle'",
            $activities
        ) );
        if ( $index === null ) {
            $wpdb->query( "ALTER TABLE {$activities} ADD KEY idx_vct_cycle (club_id, vct_cycle_id, vct_cycle_week)" );
        }
    }

    /**
     * The 3-week reference phase profile, seeded the way 0126 seeds its
     * two: at the season_id = 0 / team_id = 0 sentinel, with year-2000
     * placeholder dates that never match a real season query. Sequence 4
     * discriminates it from the 4-week (1), 6-week (2) and speelwijze
     * (3) references within the UNIQUE index.
     *
     * Three weeks leaves no room for a `piek` week, so the shape is
     * introduce → load → unload rather than a truncated six-week ladder.
     */
    private function seedThreeWeekReference(): void {
        global $wpdb;
        $macro_blocks = $wpdb->prefix . 'tt_vct_macro_blocks';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $macro_blocks ) ) !== $macro_blocks ) return;

        $club_id   = 1;
        $team_id   = 0;
        $season_id = 0;
        $sequence  = 4;

        $existing = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$macro_blocks}
              WHERE club_id = %d AND team_id = %d AND season_id = %d AND sequence = %d
              LIMIT 1",
            $club_id, $team_id, $season_id, $sequence
        ) );
        if ( $existing > 0 ) return;

        $profile = [
            [ 'week' => 1, 'phase' => 'introductie', 'multiplier' => 0.85 ],
            [ 'week' => 2, 'phase' => 'opbouw',      'multiplier' => 1.00 ],
            [ 'week' => 3, 'phase' => 'deload',      'multiplier' => 0.70 ],
        ];

        $wpdb->insert( $macro_blocks, [
            'club_id'            => $club_id,
            'uuid'               => wp_generate_uuid4(),
            'season_id'          => $season_id,
            'team_id'            => $team_id,
            'sequence'           => $sequence,
            'label'              => 'Reference: 3-week phase profile',
            'start_date'         => '2000-04-01',
            'end_date'           => '2000-04-21',
            'phase_profile_json' => wp_json_encode( $profile ),
        ] );
    }

    private static function addColumn( string $table, string $column, string $def ): void {
        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );
        if ( $exists === $column ) return;
        $wpdb->query( "ALTER TABLE {$table} ADD COLUMN {$column} {$def}" );
    }
};

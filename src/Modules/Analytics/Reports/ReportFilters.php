<?php
namespace TT\Modules\Analytics\Reports;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Query\QueryHelpers;

/**
 * ReportFilters (#2136) — the single source of truth for the attendance
 * reports' period quick-pills and activity-type filter, so the team report
 * and the player report can't drift.
 *
 * The attendance reports are retrospective, so the period vocabulary is
 * past-oriented (Last week / This month / This season) — distinct from the
 * forward-looking pills the activities list (`FrontendActivitiesManageView`)
 * offers. The resolution still mirrors that view's `periodWindow()`: each
 * key resolves to an inclusive `[from, to]` `Y-m-d` window in PHP, never in
 * SQL (CLAUDE.md §4 keeps date math out of the query).
 */
final class ReportFilters {

    /** Period keys this surface accepts. The empty string = manual range. */
    public const PERIODS = [ 'last_week', 'this_month', 'this_season' ];

    /**
     * Human labels for the period pills, keyed by period key. The empty
     * key is the "custom range" / manual fallback.
     *
     * @return array<string,string>
     */
    public static function periodLabels(): array {
        return [
            ''            => __( 'Custom range', 'talenttrack' ),
            'last_week'   => __( 'Last week', 'talenttrack' ),
            'this_month'  => __( 'This month', 'talenttrack' ),
            'this_season' => __( 'This season', 'talenttrack' ),
        ];
    }

    /**
     * Sanitize a raw `?period=` value to a known key (or '').
     */
    public static function sanitizePeriod( string $period ): string {
        return in_array( $period, self::PERIODS, true ) ? $period : '';
    }

    /**
     * #3293 — the period key the chrome should show, given what the query
     * actually ran on.
     *
     * Every surface that offers both a period pill and a From/To range
     * resolves its window as "manual dates beat the period", then handed the
     * bar the RAW `$period` anyway. So the pill kept reading "This month"
     * while the grid queried the user's own window — the chrome contradicting
     * the query, on nine surfaces.
     *
     * The order of the two checks below is the whole fix. Two views carried a
     * private copy of this that tested `$period !== ''` FIRST, so it only
     * blanked the pill when no explicit `?period=` was in the URL — which is
     * the one case where the contradiction is visible, because the pill is
     * only ever active when a period param put it there.
     *
     * The third check is #2136's behaviour and is preserved: a seeded
     * season-default window carries no `?period=` but should still read as
     * the season pill rather than as a custom range.
     *
     * Returns '' for "no pill active" — which `periodLabels()` maps to
     * *Custom range*.
     */
    public static function effectivePeriod(
        string $period,
        bool $has_manual_from,
        bool $has_manual_to,
        string $from,
        string $to
    ): string {
        // A manual From/To always wins: it is what the query ran on.
        if ( $has_manual_from || $has_manual_to ) return '';
        if ( $period !== '' ) return $period;

        $season = self::periodWindow( 'this_season', gmdate( 'Y-m-d' ) );
        if ( $season !== null && $season['from'] === $from && $season['to'] === $to ) {
            return 'this_season';
        }
        return '';
    }

    /**
     * #3293 — the summary chip for a window no period pill describes, or
     * null when the pill already says it.
     *
     * Seven of the nine surfaces counted the period only, so a reader who
     * set nothing but a From/To saw **Filters** with no badge and no chip —
     * the bar reporting "nothing filtered" over a custom window. This is
     * what the count and the chips were missing.
     *
     * Takes the EFFECTIVE period, not the raw one: if a pill is active it
     * describes the window and a second chip would say the same thing twice.
     */
    /**
     * The one time control a surface shows (#3331).
     *
     * Nine surfaces rendered a `period` pill-dropdown AND a standalone
     * `date_range` for the same question. The two could disagree, which is
     * what #3293 had to teach the pill to stop doing — and a control that
     * cannot contradict itself is a better answer than one taught not to.
     *
     * The presets stay link-based, so they commit themselves with JS off.
     * The custom branch carries the same `from` / `to` field names the
     * `date_range` group used, so a saved view or bookmark written before
     * this still resolves — `paramNames()` picks them up from `custom`.
     *
     * The trigger reads the window, not the preset, whenever a custom one is
     * what the query ran on. That is the whole point: the label and the data
     * agree.
     *
     * @param array<int,array<string,mixed>> $options   the preset links.
     * @param string                         $effective the period the query
     *        actually used — `effectivePeriod()`, never the raw `?period=`.
     * @return array<string,mixed> a `period` group for FilterBar.
     */
    public static function periodGroup(
        array $options,
        string $effective,
        string $from,
        string $to,
        string $from_name = 'from',
        string $to_name = 'to'
    ): array {
        $labels     = self::periodLabels();
        $range_chip = self::customRangeChip( $effective, $from, $to );

        return [
            'type'          => 'period',
            'key'           => 'period',
            'label'         => __( 'Period', 'talenttrack' ),
            'active_label'  => $range_chip ?? (string) ( $labels[ $effective ] ?? $labels[''] ),
            'options'       => $options,
            'custom'        => [
                'from' => [ 'name' => $from_name, 'value' => $from ],
                'to'   => [ 'name' => $to_name,   'value' => $to ],
            ],
            'custom_active' => $range_chip !== null,
        ];
    }

    public static function customRangeChip( string $effective_period, string $from, string $to ): ?string {
        if ( $effective_period !== '' ) return null;

        // `seasonDefaultWindow()` is typed `array{from:string,to:string}`, so
        // both keys are guaranteed — a `?? ''` here is dead code at level 8.
        $defaults = self::seasonDefaultWindow();
        if ( $from === $defaults['from'] && $to === $defaults['to'] ) {
            // The untouched season default is not a filter the reader chose.
            return null;
        }

        $from = trim( $from );
        $to   = trim( $to );
        if ( $from === '' && $to === '' ) return null;

        if ( $from !== '' && $to !== '' ) {
            /* translators: 1: window start date, 2: window end date. */
            return sprintf( __( '%1$s – %2$s', 'talenttrack' ), $from, $to );
        }
        return $from !== '' ? $from : $to;
    }

    /**
     * Resolve a period key to an inclusive [from, to] Y-m-d window. Most
     * keys are past-oriented; `this_season` spans the whole configured
     * season (start through the season's own end date, which may be in the
     * future). Returns null for the empty / unknown key (caller keeps the
     * manual From/To range).
     *
     * @return array{from:string,to:string}|null
     */
    public static function periodWindow( string $period, string $today ): ?array {
        if ( $period === '' ) return null;
        $base = strtotime( $today );
        if ( $base === false ) return null;

        switch ( $period ) {
            case 'last_week':
                // The full ISO week (Mon–Sun) immediately before this one.
                $dow         = (int) gmdate( 'N', $base ); // 1 = Monday
                $this_monday = $base - ( $dow - 1 ) * DAY_IN_SECONDS;
                $last_monday = $this_monday - 7 * DAY_IN_SECONDS;
                return [
                    'from' => gmdate( 'Y-m-d', $last_monday ),
                    'to'   => gmdate( 'Y-m-d', $last_monday + 6 * DAY_IN_SECONDS ),
                ];

            case 'this_month':
                // Month-to-date: first of the month through today.
                return [ 'from' => gmdate( 'Y-m-01', $base ), 'to' => gmdate( 'Y-m-d', $base ) ];

            case 'this_season':
                if ( ! class_exists( '\\TT\\Modules\\Pdp\\Repositories\\SeasonsRepository' ) ) return null;
                $season = ( new \TT\Modules\Pdp\Repositories\SeasonsRepository() )->current();
                if ( ! $season || empty( $season->start_date ) || empty( $season->end_date ) ) return null;
                // Span the whole season — start through the season's own end
                // date, not clamped to today. The silent default window
                // (seasonDefaultWindow) stays today-bounded for the
                // retrospective default; the explicit pill is full-season.
                return [ 'from' => (string) $season->start_date, 'to' => (string) $season->end_date ];
        }

        return null;
    }

    /**
     * The default From/To window a report seeds when the user has picked
     * neither a period pill nor a manual range. Starts at the current
     * season's start date and ends today, so the default matches how the
     * academy thinks about the year (and equals the `This season` pill's
     * From — it reuses the same SeasonsRepository resolver as
     * `periodWindow('this_season', …)`). Falls back to a 90-day rolling
     * window when the seasons repository is unavailable, no current season
     * exists, or its start date is empty, so a report never fatals or
     * renders empty just because no season is configured.
     *
     * @return array{from:string,to:string}
     */
    public static function seasonDefaultWindow(): array {
        $to = gmdate( 'Y-m-d' );
        if ( class_exists( '\\TT\\Modules\\Pdp\\Repositories\\SeasonsRepository' ) ) {
            $season = ( new \TT\Modules\Pdp\Repositories\SeasonsRepository() )->current();
            if ( $season && ! empty( $season->start_date ) ) {
                return [ 'from' => (string) $season->start_date, 'to' => $to ];
            }
        }
        return [ 'from' => gmdate( 'Y-m-d', strtotime( '-90 days' ) ), 'to' => $to ]; // fallback
    }

    /**
     * Activity-type select options (lookup name => translated label),
     * matching the activities list's Type filter.
     *
     * @return array<string,string>
     */
    public static function activityTypeOptions(): array {
        $options = [];
        foreach ( QueryHelpers::get_lookups( 'activity_type' ) as $row ) {
            $name = (string) ( $row->name ?? '' );
            if ( $name === '' ) continue;
            $options[ $name ] = (string) LookupTranslator::name( $row );
        }
        return $options;
    }

    /**
     * Sanitize a raw `?activity_type_key=` value to a known lookup name
     * (or ''), so an arbitrary URL value can't reach the query.
     */
    public static function sanitizeActivityType( string $key ): string {
        $key = sanitize_key( $key );
        if ( $key === '' ) return '';
        return array_key_exists( $key, self::activityTypeOptions() ) ? $key : '';
    }
}

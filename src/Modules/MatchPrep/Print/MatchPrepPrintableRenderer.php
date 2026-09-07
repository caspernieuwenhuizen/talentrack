<?php
namespace TT\Modules\MatchPrep\Print;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\MatchPrep\Frontend\FrontendMatchPrepView;
use TT\Modules\MatchPrep\Repositories\MatchPrepRepository;

/**
 * MatchPrepPrintableRenderer (#1059) — shared body renderer for the
 * print router + PDF exporter, mirroring the on-screen match-prep
 * view's content shape and Dutch labels.
 *
 * Why a shared renderer: before #1059, the print router and PDF
 * exporter rendered their bodies independently from the legacy
 * MatchPrepPdfExporter template — table-based numbered lineup,
 * English category labels, per-player notes filtered to "has text +
 * has flag". The on-screen view shows formation pitches, Dutch
 * category labels (Aanvallen / Verdedigen / Spelhervattingen ×2), and
 * a per-player row per available player. Coaches printing for the
 * dugout were getting a different document than the one they laid out.
 *
 * This class produces a single body HTML string from the same data
 * sources the on-screen view uses. Both print surfaces wrap it in
 * their own page chrome.
 *
 * Formation pitches reuse `FrontendMatchPrepView::defaultSlotLayouts()`
 * so the slot-position vocabulary is the on-screen view's, not a
 * print-only re-invention.
 */
final class MatchPrepPrintableRenderer {

    /**
     * #3297 — rows the "Doen per speler" block reserves on paper.
     *
     * A squad list grows and shrinks; the writing room does not. Mirrors
     * `FOCUS_LINES` in `assets/js/frontend-match-prep.js`, which does the
     * same padding for the image-capture export — one design, two renderers.
     */
    private const FOCUS_LINES = 15;

    /**
     * Render the body HTML for a match-prep printable. Empty string
     * when the activity isn't found or no prep exists (the caller's
     * page chrome surfaces the "not found" notice if it wants to).
     */
    public static function bodyHtml( int $activity_id, int $club_id ): string {
        global $wpdb;
        $p = $wpdb->prefix;

        // #1059 — ensure plugin textdomain is loaded in print contexts.
        // The print router hooks at template_redirect priority 1; on
        // some installs the textdomain isn't ready yet, which makes the
        // body emit English labels instead of the on-screen Dutch ones.
        if ( function_exists( 'load_plugin_textdomain' ) && defined( 'TT_PLUGIN_FILE' ) ) {
            load_plugin_textdomain( 'talenttrack', false, dirname( plugin_basename( TT_PLUGIN_FILE ) ) . '/languages' );
        }

        $activity = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.id, a.title, a.session_date, a.location, a.team_id, a.activity_type_key,
                    a.opponent, a.home_away, a.kickoff_time, a.formation,
                    t.name AS team_name
                FROM {$p}tt_activities a
                LEFT JOIN {$p}tt_teams t ON t.id = a.team_id AND t.club_id = a.club_id
                WHERE a.id = %d AND a.club_id = %d
                LIMIT 1",
            $activity_id, $club_id
        ) );
        if ( ! $activity ) return '';

        $repo = new MatchPrepRepository();
        $prep = $repo->findByActivity( $activity_id );
        if ( ! $prep ) return '';

        $prep_id      = (int) $prep->id;
        $availability = $repo->listAvailability( $prep_id );
        $lineup       = $repo->listLineup( $prep_id );
        $player_goals = $repo->listPlayerGoals( $prep_id );

        $player_ids = [];
        foreach ( $availability as $a ) $player_ids[] = (int) $a->player_id;
        foreach ( $lineup as $l )       $player_ids[] = (int) $l->player_id;
        foreach ( $player_goals as $g ) $player_ids[] = (int) $g->player_id;
        $player_ids = array_values( array_unique( array_filter( $player_ids ) ) );

        $players_by_id = [];
        if ( $player_ids ) {
            $in  = implode( ',', array_fill( 0, count( $player_ids ), '%d' ) );
            $sql = $wpdb->prepare(
                "SELECT id, first_name, last_name, jersey_number FROM {$p}tt_players WHERE id IN ($in) AND club_id = %d",
                array_merge( $player_ids, [ $club_id ] )
            );
            foreach ( (array) $wpdb->get_results( $sql ) as $row ) {
                $players_by_id[ (int) $row->id ] = $row;
            }
        }

        // Lineup pivot by half + slot, same shape as the on-screen view.
        $lineup_by_half    = [ 1 => [], 2 => [] ];
        $pitch_ids_by_half = [ 1 => [], 2 => [] ];
        foreach ( $lineup as $l ) {
            $h    = (int) $l->half;
            $slot = (int) $l->slot_number;
            $pid  = (int) $l->player_id;
            $lineup_by_half[ $h ][ $slot ] = $pid;
            $pitch_ids_by_half[ $h ][] = $pid;
        }

        // Available players + per-player notes index (same data the
        // on-screen view's "Doen per speler" panel iterates).
        $available_ids = [];
        $absent        = [];
        foreach ( $availability as $a ) {
            $pid = (int) $a->player_id;
            if ( strcasecmp( (string) $a->status, 'Present' ) === 0 ) {
                $available_ids[] = $pid;
            } else {
                $absent[] = [
                    'pid'    => $pid,
                    'status' => (string) $a->status,
                    'reason' => (string) ( $a->reason ?? '' ),
                ];
            }
        }
        $pgoal_by_pid = [];
        foreach ( $player_goals as $g ) {
            $pgoal_by_pid[ (int) $g->player_id ] = $g;
        }

        // #1873 — roles (captain + set-piece takers) + half length, the
        // two pieces the on-screen view shows that the print was missing.
        $roles_by_key = [];
        foreach ( $repo->listRoles( $prep_id ) as $r ) {
            $roles_by_key[ (string) $r->role_key ] = (int) $r->player_id;
        }
        $half_length = (int) ( $prep->half_length_minutes ?? 35 );

        // Resolve the slot layout the same way the on-screen view does.
        // #2099 — the bound template's own geometry (slots_json) wins, so a
        // 3-4-3 diamond prints as a diamond. Otherwise the activity's
        // `formation` shape string (e.g. "4-3-3") selects a shape default.
        $formation_shape = (string) ( $activity->formation ?? '' );
        if ( $formation_shape === '' ) $formation_shape = '4-3-3';
        $slot_layouts = FrontendMatchPrepView::defaultSlotLayouts();
        $slots        = FrontendMatchPrepView::templateSlotLayout( (int) ( $prep->formation_template_id ?? 0 ) )
            ?? ( $slot_layouts[ $formation_shape ] ?? $slot_layouts['4-3-3'] );

        ob_start();
        ?>
        <?php
        // #3297 — layout A, the same sheet the image-capture export prints.
        //
        // The two renderers had drifted into two documents for one artefact:
        // this route stacked its blocks down the page while the export laid
        // them out in three columns. One design now, two renderers — so the
        // no-JS fallback is a fallback and not a different sheet.
        //
        // Order is the screen's own: selection (with roles beneath it), the
        // pitches and the match's goals, then a player-goal column.
        ?>
        <header class="tt-mpp-head">
            <h1><?php echo esc_html( self::title( $activity ) ); ?></h1>
            <p class="tt-mpp-meta"><?php echo esc_html( self::metaLine( $activity, $prep ) ); ?></p>
        </header>

        <div class="tt-mpp-sheet">
        <div class="tt-mpp-col tt-mpp-col-left">
        <?php if ( $available_ids ) :
            // #1873 — Selectie · minuten, mirroring the on-screen left rail:
            // per-player minutes per half (full half length when on the
            // pitch that half) + the total, plus column totals.
            $tot1 = 0; $tot2 = 0; ?>
            <h2><?php esc_html_e( 'Selection · minutes', 'talenttrack' ); ?></h2>
            <table class="tt-mpp-minutes-table">
                <thead><tr>
                    <th><?php esc_html_e( 'Speler', 'talenttrack' ); ?></th>
                    <th class="tt-mpp-num-col"><?php esc_html_e( 'min', 'talenttrack' ); ?> 1e</th>
                    <th class="tt-mpp-num-col"><?php esc_html_e( 'min', 'talenttrack' ); ?> 2e</th>
                    <th class="tt-mpp-num-col"><?php esc_html_e( 'tot', 'talenttrack' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $available_ids as $pid ) :
                    if ( ! isset( $players_by_id[ $pid ] ) ) continue;
                    $on1  = in_array( $pid, $lineup_by_half[1], true );
                    $on2  = in_array( $pid, $lineup_by_half[2], true );
                    $min1 = $on1 ? $half_length : 0;
                    $min2 = $on2 ? $half_length : 0;
                    $tot1 += $min1; $tot2 += $min2;
                    ?>
                    <tr>
                        <td><?php echo esc_html( QueryHelpers::player_display_name( $players_by_id[ $pid ] ) ); ?></td>
                        <td class="tt-mpp-num-col"><?php echo (int) $min1; ?></td>
                        <td class="tt-mpp-num-col"><?php echo (int) $min2; ?></td>
                        <td class="tt-mpp-num-col"><?php echo (int) ( $min1 + $min2 ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr>
                    <th><?php esc_html_e( 'tot', 'talenttrack' ); ?></th>
                    <th class="tt-mpp-num-col"><?php echo (int) $tot1; ?></th>
                    <th class="tt-mpp-num-col"><?php echo (int) $tot2; ?></th>
                    <th class="tt-mpp-num-col"><?php echo (int) ( $tot1 + $tot2 ); ?></th>
                </tr></tfoot>
            </table>
        <?php endif; ?>

            <?php
            // #3297 — roles sit under the selection, as on the exported
            // sheet: both blocks are the same squad read the same way, a
            // name per line, so they share the narrow left column — which
            // is what frees a full-height column for the player goals.
            $role_defs = FrontendMatchPrepView::roleDefinitions();
            ?>
            <h2><?php esc_html_e( 'Roles & set pieces', 'talenttrack' ); ?></h2>
            <table class="tt-mpp-roles-table">
                <tbody>
                <?php foreach ( $role_defs as $role ) :
                    $key   = (string) $role['key'];
                    $rpid  = (int) ( $roles_by_key[ $key ] ?? 0 );
                    // #2198 — unassigned roles print blank, not a placeholder dash.
                    $rname = ( $rpid > 0 && isset( $players_by_id[ $rpid ] ) )
                        ? QueryHelpers::player_display_name( $players_by_id[ $rpid ] )
                        : '';
                    ?>
                    <tr>
                        <th><?php echo esc_html( (string) $role['label'] ); ?></th>
                        <td><?php echo esc_html( $rname ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /.tt-mpp-col-left -->

        <div class="tt-mpp-col tt-mpp-col-mid">
            <div class="tt-mpp-pitches">
                <?php self::renderPitch( __( '1e helft', 'talenttrack' ), $slots, $lineup_by_half[1], $players_by_id ); ?>
                <?php self::renderPitch( __( '2e helft', 'talenttrack' ), $slots, $lineup_by_half[2], $players_by_id ); ?>
            </div>

            <div class="tt-mpp-bench-row">
                <?php
                $bench_1 = self::benchNames( $availability, $pitch_ids_by_half[1], $players_by_id );
                $bench_2 = self::benchNames( $availability, $pitch_ids_by_half[2], $players_by_id );
                ?>
                <p><strong><?php esc_html_e( 'Bank 1e helft:', 'talenttrack' ); ?></strong> <?php echo esc_html( $bench_1 !== [] ? implode( ', ', $bench_1 ) : '—' ); ?></p>
                <p><strong><?php esc_html_e( 'Bank 2e helft:', 'talenttrack' ); ?></strong> <?php echo esc_html( $bench_2 !== [] ? implode( ', ', $bench_2 ) : '—' ); ?></p>
            </div>

            <div class="tt-mpp-bottom-col">
                <?php
                // #2831 — the principles this match is working on, above the
                // goals written against them. Unlinked: on a sheet handed to
                // an assistant a link is ink, not a destination.
                $printed_principles = \TT\Modules\Methodology\Services\ActivityPrinciples::forActivity( $activity_id );
                if ( $printed_principles !== [] ) :
                    ?>
                    <?php /* English msgid, unlike the Dutch-literal labels around it — 'Principles' is already in the catalogue and renders "Principes". */ ?>
                    <h2><?php esc_html_e( 'Principles', 'talenttrack' ); ?></h2>
                    <div class="tt-mpp-principles">
                        <?php
                        echo \TT\Modules\Methodology\Frontend\PrinciplePills::render( $printed_principles, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped in the component.
                        ?>
                    </div>
                <?php endif; ?>
                <h2><?php esc_html_e( 'Wedstrijddoelen', 'talenttrack' ); ?></h2>
                <table class="tt-mpp-goals-table">
                    <?php
                    // Same field-set + Dutch labels as the on-screen view
                    // (see FrontendMatchPrepView's renderGoalsPanel).
                    $rows = [
                        'goals_general'         => __( 'Algemeen', 'talenttrack' ),
                        'goals_attack'          => __( 'Aanvallen', 'talenttrack' ),
                        'goals_defend'          => __( 'Verdedigen', 'talenttrack' ),
                        'goals_attack_setpiece' => __( 'Spelhervattingen (aanvallend)', 'talenttrack' ),
                        'goals_defend_setpiece' => __( 'Spelhervattingen (verdedigend)', 'talenttrack' ),
                    ];
                    foreach ( $rows as $field => $label ) :
                        $value = trim( (string) ( $prep->$field ?? '' ) );
                        if ( $value === '' ) continue;
                        ?>
                        <tr>
                            <th><?php echo esc_html( $label ); ?></th>
                            <td><?php echo esc_html( $value ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php if ( $absent ) : ?>
                    <h2><?php esc_html_e( 'Niet beschikbaar', 'talenttrack' ); ?></h2>
                    <ul class="tt-mpp-absent">
                        <?php foreach ( $absent as $row ) :
                            $pl   = $players_by_id[ $row['pid'] ] ?? null;
                            $name = $pl ? QueryHelpers::player_display_name( $pl ) : '#' . $row['pid'];
                            ?>
                            <li>
                                <?php echo esc_html( $name . ' · ' . $row['status'] ); ?>
                                <?php if ( $row['reason'] !== '' ) : ?>
                                    (<?php echo esc_html( $row['reason'] ); ?>)
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div><!-- /.tt-mpp-col-mid -->

            <div class="tt-mpp-col tt-mpp-col-right">
                <h2><?php esc_html_e( 'Doen per speler', 'talenttrack' ); ?></h2>
                <?php if ( ! $available_ids ) : ?>
                    <p class="tt-mpp-empty"><?php esc_html_e( 'Geen beschikbaarheid vastgelegd.', 'talenttrack' ); ?></p>
                <?php else : ?>
                    <table class="tt-mpp-dps-table">
                        <thead><tr>
                            <th><?php esc_html_e( 'Speler', 'talenttrack' ); ?></th>
                            <th><?php esc_html_e( 'Aandachtspunt', 'talenttrack' ); ?></th>
                            <th class="tt-mpp-flag-col" title="<?php esc_attr_e( 'Specifiek doel', 'talenttrack' ); ?>">!</th>
                            <th class="tt-mpp-flag-col" title="<?php esc_attr_e( 'Video-analist toegewezen', 'talenttrack' ); ?>">🎥</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ( $available_ids as $pid ) :
                            if ( ! isset( $players_by_id[ $pid ] ) ) continue;
                            $name = QueryHelpers::player_display_name( $players_by_id[ $pid ] );
                            $g    = $pgoal_by_pid[ $pid ] ?? null;
                            $att  = trim( (string) ( $g->attention_text ?? '' ) );
                            $spec = ! empty( $g->is_specific_goal );
                            $cam  = ! empty( $g->analyst_appointed );
                            $row_cls = $spec ? ' class="tt-mpp-specific"' : '';
                            ?>
                            <tr<?php echo $row_cls; ?>>
                                <td><?php echo esc_html( $name ); ?></td>
                                <?php /* #2198 — empty attention prints blank, not a placeholder dash. */ ?>
                                <td><?php echo esc_html( $att ); ?></td>
                                <td class="tt-mpp-flag-col"><?php echo $spec ? '!' : ''; ?></td>
                                <td class="tt-mpp-flag-col"><?php echo $cam ? '🎥' : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php
                        // #3297 — pad to FOCUS_LINES ruled rows, matching the
                        // exported sheet. The block the coach writes in during
                        // the warm-up always looks the same in the hand, so
                        // the room is reserved rather than earned.
                        $filler = self::FOCUS_LINES - count( $available_ids );
                        for ( $i = 0; $i < $filler; $i++ ) : ?>
                            <tr class="tt-mpp-rule"><td></td><td></td><td></td><td></td></tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div><!-- /.tt-mpp-col-right -->
        </div><!-- /.tt-mpp-sheet -->
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Print-friendly CSS that pairs with `bodyHtml()`. Single block so
     * the print router's standalone document + the PDF exporter's
     * styled-section can both consume it without duplicating tokens.
     */
    public static function styleBlock(): string {
        return <<<CSS
        body { font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif; color: #1a1d21; font-size: 11pt; line-height: 1.35; margin: 0; padding: 12px; }
        h1 { font-size: 16pt; margin: 0 0 4px; }
        h2 { font-size: 12pt; margin: 10px 0 4px; }
        .tt-mpp-meta { color: #5b6e75; font-size: 10pt; margin: 0 0 8px; }
        /* #2831 — the O/A/V principle pill. The screen's copy lives in
           assets/css/components/principle-pills.css; this document inlines
           its CSS, so the three bucket colours appear in both places. */
        .tt-mpp-principles { margin: 0 0 8px; }
        .tt-mpp-principles .tt-act-pp { display: inline-block; padding: 3px 7px; border-radius: 6px; font-size: 9pt; font-weight: 600; margin: 0 4px 4px 0; border: 1px solid; line-height: 1.3; }
        .tt-mpp-principles .tt-act-pp--O { background: #fff3c4; border-color: #e3c75e; color: #7a5a08; }
        .tt-mpp-principles .tt-act-pp--A { background: #fde9d6; border-color: #f3c79b; color: #8a3b00; }
        .tt-mpp-principles .tt-act-pp--V { background: #dfeede; border-color: #a8d2a4; color: #1f5a1a; }
        .tt-mpp-pitches { display: table; width: 100%; margin: 0 0 6px; }
        .tt-mpp-pitch { display: table-cell; width: 50%; padding-right: 8px; vertical-align: top; }
        .tt-mpp-pitch:last-child { padding-right: 0; padding-left: 8px; }
        .tt-mpp-pitch-title { font-weight: 700; margin: 0 0 4px; font-size: 11pt; }
        .tt-mpp-pitch-svg { width: 100%; height: auto; aspect-ratio: 680/420; background: #f5f7f6; border: 1px solid #d6dadd; border-radius: 6px; position: relative; }
        .tt-mpp-slot { position: absolute; transform: translate(-50%, -50%); background: #1d7874; color: #fff; border-radius: 50%; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 9pt; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.15); }
        .tt-mpp-slot-empty { background: #d6dadd; color: #5b6e75; }
        .tt-mpp-slot-label { position: absolute; transform: translate(-50%, calc(-50% + 24px)); white-space: nowrap; font-size: 8.5pt; color: #1a1d21; background: rgba(255,255,255,0.85); padding: 1px 4px; border-radius: 3px; max-width: 90px; overflow: hidden; text-overflow: ellipsis; }
        .tt-mpp-bench-row p { margin: 2px 0; font-size: 10pt; color: #5b6e75; }
        .tt-mpp-bench-row strong { color: #1a1d21; }
        /* #3297 — layout A. Three columns in millimetres, the same
           62 / 135 / 78 the exported sheet uses, because these are paper
           measurements and the page is the same page. The blocks stacked
           down the sheet before, which is why this route and the export
           printed two different documents. */
        .tt-mpp-head { border-bottom: 0.6mm solid #0b3d2e; padding-bottom: 2mm; margin-bottom: 3mm; }
        .tt-mpp-head h1 { margin: 0; }
        .tt-mpp-sheet { display: table; width: 100%; table-layout: fixed; }
        .tt-mpp-col { display: table-cell; vertical-align: top; }
        .tt-mpp-col-left  { width: 62mm; padding-right: 3mm; }
        .tt-mpp-col-mid   { width: 135mm; padding-right: 3mm; }
        .tt-mpp-col-right { width: 78mm; }
        .tt-mpp-col h2:first-child { margin-top: 0; }
        /* The left column carries two lists against the middle column's
           pitches, so its rows run a notch tighter — height comes from
           padding before it ever comes from type size. */
        .tt-mpp-col-left th, .tt-mpp-col-left td { padding: 1.5px 4px; font-size: 9pt; }
        .tt-mpp-bottom-col { margin-top: 4px; }
        /* An unfilled row is writing room, not a gap. */
        .tt-mpp-rule td { height: 5.4mm; }
        table { width: 100%; border-collapse: collapse; margin: 2px 0; }
        th, td { border: 1px solid #d6dadd; padding: 3px 5px; text-align: left; font-size: 10pt; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        .tt-mpp-flag-col { width: 28px; text-align: center; }
        .tt-mpp-specific { background: #fff5d6; }
        .tt-mpp-empty { color: #5b6e75; font-style: italic; margin: 4px 0; }
        .tt-mpp-absent { margin: 4px 0 0; padding-left: 18px; }
        .tt-mpp-absent li { margin-bottom: 2px; font-size: 10pt; }
        .tt-mpp-num-col { width: 40px; text-align: center; }
        .tt-mpp-minutes-table tfoot th, .tt-mpp-roles-table th { background: #f3f4f6; }
        .tt-mpp-minutes-table, .tt-mpp-roles-table { page-break-inside: avoid; }
        .tt-mpp-roles-table th { width: 45%; }
CSS;
    }

    private static function title( object $activity ): string {
        return trim( sprintf(
            '%s · %s · %s',
            (string) ( $activity->team_name ?? '' ),
            (string) ( $activity->title ?? '' ),
            (string) ( $activity->session_date ?? '' )
        ), ' ·' );
    }

    private static function metaLine( object $activity, object $prep ): string {
        $kick = trim( (string) ( $activity->kickoff_time ?? '' ) );
        $home = ( ( $activity->home_away ?? '' ) === 'home' ) ? __( 'thuis', 'talenttrack' ) : __( 'uit', 'talenttrack' );
        $opp  = (string) ( $activity->opponent ?? '' );
        return sprintf(
            /* translators: 1: opponent, 2: thuis/uit, 3: kickoff_time, 4: formation */
            __( 'vs %1$s · %2$s · KO %3$s · %4$s', 'talenttrack' ),
            $opp !== '' ? $opp : '—',
            $home,
            $kick !== '' ? $kick : '—',
            (string) ( $activity->formation ?? $prep->formation_template_id ?? '—' )
        );
    }

    /**
     * @param array<int,array{num:int,label:string,x:float,y:float}> $slots
     * @param array<int,int> $lineup_by_slot
     * @param array<int,object> $players_by_id
     */
    private static function renderPitch( string $title, array $slots, array $lineup_by_slot, array $players_by_id ): void {
        ?>
        <div class="tt-mpp-pitch">
            <p class="tt-mpp-pitch-title"><?php echo esc_html( $title ); ?></p>
            <div class="tt-mpp-pitch-svg">
                <?php foreach ( $slots as $s ) :
                    $num  = (int) ( $s['num'] ?? 0 );
                    $x    = (float) ( $s['x'] ?? 50 );
                    $y    = (float) ( $s['y'] ?? 50 );
                    $pid  = (int) ( $lineup_by_slot[ $num ] ?? 0 );
                    $pl   = $pid && isset( $players_by_id[ $pid ] ) ? $players_by_id[ $pid ] : null;
                    $name = $pl ? QueryHelpers::player_display_name( $pl ) : '';
                    $cls  = $pl ? 'tt-mpp-slot' : 'tt-mpp-slot tt-mpp-slot-empty';
                    ?>
                    <span class="<?php echo esc_attr( $cls ); ?>"
                          style="left:<?php echo (float) $x; ?>%; top:<?php echo (float) $y; ?>%;">
                        <?php echo (int) $num; ?>
                    </span>
                    <?php if ( $name !== '' ) : ?>
                        <span class="tt-mpp-slot-label"
                              style="left:<?php echo (float) $x; ?>%; top:<?php echo (float) $y; ?>%;">
                            <?php echo esc_html( $name ); ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * @param array<int,object> $availability
     * @param array<int,int> $pitch_ids
     * @param array<int,object> $players_by_id
     * @return array<int,string>
     */
    private static function benchNames( array $availability, array $pitch_ids, array $players_by_id ): array {
        $out = [];
        foreach ( $availability as $a ) {
            if ( strcasecmp( (string) $a->status, 'Present' ) !== 0 ) continue;
            $pid = (int) $a->player_id;
            if ( in_array( $pid, $pitch_ids, true ) ) continue;
            if ( ! isset( $players_by_id[ $pid ] ) ) continue;
            $out[] = QueryHelpers::player_display_name( $players_by_id[ $pid ] );
        }
        return $out;
    }
}

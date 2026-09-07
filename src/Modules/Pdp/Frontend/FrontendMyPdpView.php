<?php
namespace TT\Modules\Pdp\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Goals\GoalsRepository;
use TT\Modules\Pdp\Repositories\GoalLinksRepository;
use TT\Modules\Pdp\Repositories\PdpConversationsRepository;
use TT\Modules\Pdp\Repositories\PdpFilesRepository;
use TT\Modules\Pdp\Repositories\PdpVerdictsRepository;
use TT\Modules\Pdp\Repositories\SeasonsRepository;
use TT\Modules\Pdp\Services\PdpCycleState;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendMyPdpView — the player's timeline-first development view (#1990).
 *
 * The season is the spine: the development conversations
 * (ontwikkelgesprekken) sit on a horizontal rail as markers — done /
 * next / future. Tapping a marker expands that conversation's detail
 * inline (no long scroll). Below the rail: the player's active focus
 * goals, then a single self-reflection input for the one next-planned
 * conversation (when its 2-week window is open), with any saved
 * reflection shown alongside. The end-of-season verdict card closes
 * the page.
 *
 * Same surface for parents (resolved via tt_player_parents → wp_user_id),
 * read-only: parents see the timeline + saved reflection but no editable
 * reflection input, and acknowledge via their own ack column.
 *
 * Business logic (which talk is "next planned", whether its window is
 * open, the cycle state) lives in PdpCycleState — this view only
 * composes (CLAUDE.md §4). REST parity: timeline/conversation data is
 * reachable via /pdp-conversations, goals via /goals.
 */
class FrontendMyPdpView extends FrontendViewBase {

    /**
     * Enqueue the PDP chrome stylesheet on top of the shared frontend
     * assets. Depends on the global app-chrome sheet for the shared
     * tokens; brand colours arrive at runtime from BrandStyles.
     */
    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-frontend-pop',
            TT_PLUGIN_URL . 'assets/css/frontend-pop.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-my-pdp',
            TT_PLUGIN_URL . 'assets/js/components/my-pdp.js',
            [],
            TT_VERSION,
            true
        );
    }

    public static function render( object $player ): void {
        self::enqueueAssets();

        // A parent reaching this for their child sees the child's name
        // framing, not "My …".
        $is_self   = (int) ( $player->wp_user_id ?? 0 ) === get_current_user_id();
        $is_parent = ! $is_self;
        $title     = $is_self
            ? __( 'My development plan', 'talenttrack' )
            : sprintf(
                /* translators: %s = the child's name (parent viewing their child) */
                __( "%s's development plan", 'talenttrack' ),
                \TT\Infrastructure\Query\QueryHelpers::player_display_name( $player )
            );
        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $title );
        self::renderHeader( $title );

        $current = ( new SeasonsRepository() )->current();
        if ( ! $current ) {
            echo '<p class="tt-notice">' . esc_html__( 'No current season is set.', 'talenttrack' ) . '</p>';
            return;
        }

        $files = new PdpFilesRepository();
        $file  = $files->findByPlayerSeason( (int) $player->id, (int) $current->id );
        if ( ! $file ) {
            $msg = $is_self
                ? __( 'No PDP file has been opened for you this season yet.', 'talenttrack' )
                : __( 'No PDP file has been opened for this player this season yet.', 'talenttrack' );
            echo '<p><em>' . esc_html( $msg ) . '</em></p>';
            return;
        }

        $convs   = ( new PdpConversationsRepository() )->listForFile( (int) $file->id );
        $verdict = ( new PdpVerdictsRepository() )->findForFile( (int) $file->id );

        // The single next-planned conversation: the only talk that may
        // carry an editable self-reflection. Derived in the domain layer.
        $next_planned = PdpCycleState::nextPlanned( $convs );

        self::renderSeasonTimeline( $current, $convs, $next_planned, (int) $player->id, $is_self, $is_parent );
        self::renderActiveGoals( $player );
        self::renderSelfReflection( $next_planned, $is_self );

        if ( $verdict !== null ) {
            self::renderVerdictCard( $verdict );
        }
    }

    /**
     * Season timeline — the spine. A horizontal rail of conversation
     * markers (done / next / future) with a progress fill up to the
     * latest completed talk. Each marker is a real <button>; tapping it
     * expands that conversation's detail panel inline below the rail
     * (no long scroll). Keyboard-operable; Escape closes via my-pdp.js.
     *
     * @param array<int, object> $convs
     */
    private static function renderSeasonTimeline( object $season, array $convs, ?object $next_planned, int $player_id, bool $is_self, bool $is_parent ): void {
        $next_id = $next_planned !== null ? (int) ( $next_planned->id ?? 0 ) : 0;

        $done = 0;
        foreach ( $convs as $c ) {
            if ( self::isCompleted( $c ) ) $done++;
        }
        $total = max( 1, count( $convs ) );
        $fill  = (int) round( ( $done / $total ) * 100 );

        echo '<section class="tt-card tt-pdp-season">';
        echo '<div class="tt-pdp-season-head">';
        echo '<h2 class="tt-pdp-season__name">' . esc_html( sprintf(
            /* translators: %s = season name */
            __( 'Season %s', 'talenttrack' ),
            (string) $season->name
        ) ) . '</h2>';
        echo '<span class="tt-pdp-season__meta">' . esc_html( sprintf(
            /* translators: %d = number of development conversations in the season */
            _n( '%d development conversation', '%d development conversations', count( $convs ), 'talenttrack' ),
            count( $convs )
        ) ) . '</span>';
        echo '</div>';

        if ( empty( $convs ) ) {
            echo '<p class="tt-pdp-empty">' . esc_html__( 'No development conversations have been planned yet.', 'talenttrack' ) . '</p>';
            echo '</section>';
            return;
        }

        echo '<div class="tt-pdp-rail" aria-label="' . esc_attr__( 'Season timeline with development conversations', 'talenttrack' ) . '">';
        echo '<div class="tt-pdp-track">';
        // Progress fill up to the latest completed talk. Width is a
        // genuinely dynamic value derived from the data — allowed inline.
        echo '<span class="tt-pdp-fill" style="width:' . (int) $fill . '%"></span>'; /* tt-inline-ok */

        foreach ( $convs as $c ) {
            $cid   = (int) ( $c->id ?? 0 );
            $state = self::markerState( $c, $next_id );
            $label = self::templateLabel( (string) $c->template_key );
            $date  = self::markerDate( $c, $state );
            $panel = 'tt-pdp-panel-' . $cid;

            echo '<button type="button" class="tt-pdp-marker ' . esc_attr( $state ) . '"'
                . ' aria-expanded="false" aria-controls="' . esc_attr( $panel ) . '"'
                . ( $state === 'next' ? ' aria-current="step"' : '' ) . '>';
            echo '<span class="tt-pdp-dot">' . self::markerGlyph( $c, $state ) . '</span>';
            echo '<span class="tt-pdp-mlabel">' . esc_html( $label ) . '</span>';
            if ( $date !== '' ) {
                echo '<span class="tt-pdp-mdate">' . esc_html( $date ) . '</span>';
            }
            echo '<span class="tt-pdp-chip ' . esc_attr( $state ) . '">' . esc_html( self::stateLabel( $state ) ) . '</span>';
            echo '</button>';
        }

        echo '</div>'; // .tt-pdp-track
        echo '</div>'; // .tt-pdp-rail

        // Inline detail panels — one per conversation, hidden until its
        // marker is tapped. Holds notes / agreed actions / goals
        // discussed / saved reflection, and the acknowledgement flow
        // (unchanged from before).
        echo '<div class="tt-pdp-panels">';
        foreach ( $convs as $c ) {
            self::renderConversationPanel( $c, $player_id, $is_self, $is_parent );
        }
        echo '</div>';

        echo '</section>';
    }

    /**
     * The inline detail for one conversation, revealed when its marker is
     * tapped. Read content + the acknowledgement flow (preserved as-is).
     * The editable self-reflection lives in its own dedicated section,
     * not here.
     */
    private static function renderConversationPanel( object $conv, int $player_id, bool $is_self, bool $is_parent ): void {
        $cid    = (int) ( $conv->id ?? 0 );
        $signed = ! empty( $conv->coach_signoff_at );
        $title  = sprintf(
            /* translators: %1$d sequence, %2$s template */
            __( 'Conversation %1$d (%2$s)', 'talenttrack' ),
            (int) $conv->sequence,
            self::templateLabel( (string) $conv->template_key )
        );

        echo '<div id="tt-pdp-panel-' . (int) $cid . '" class="tt-pdp-panel" hidden>';
        echo '<h3 class="tt-pdp-panel__title">' . esc_html( $title ) . '</h3>';

        $meta = [];
        if ( ! empty( $conv->scheduled_at ) ) {
            $meta[] = sprintf(
                /* translators: %s = date */
                __( 'Scheduled %s', 'talenttrack' ),
                substr( (string) $conv->scheduled_at, 0, 16 )
            );
        }
        if ( ! empty( $conv->conducted_at ) ) {
            $meta[] = sprintf(
                /* translators: %s = date */
                __( 'Conducted %s', 'talenttrack' ),
                substr( (string) $conv->conducted_at, 0, 16 )
            );
        }
        if ( ! empty( $meta ) ) {
            echo '<p class="tt-pdp-panel__meta">' . esc_html( implode( ' · ', $meta ) ) . '</p>';
        }

        if ( $signed ) {
            if ( ! empty( $conv->notes ) ) {
                echo '<div class="tt-pop-bubble"><strong>' . esc_html__( 'Notes', 'talenttrack' ) . '</strong><div>'
                    . wp_kses_post( (string) $conv->notes ) . '</div></div>';
            }
            if ( ! empty( $conv->agreed_actions ) ) {
                echo '<div class="tt-pop-bubble"><strong>' . esc_html__( 'Agreed actions', 'talenttrack' ) . '</strong><div>'
                    . wp_kses_post( (string) $conv->agreed_actions ) . '</div></div>';
            }
        }

        // #3306 (epic #3301) — the coach's `agenda` used to appear here on
        // an upcoming talk. It is now their preparation, and preparation is
        // coach + head-of-academy only: it is where a coach writes candidly
        // about a minor before sitting down with them. What the player and
        // their family see is what was agreed in the talk itself — the
        // notes and the agreed actions above.

        // Goals discussed in this talk (the self-review reflects on these).
        $gl_ids = ( new GoalLinksRepository() )->goalsForConversation( $cid );
        if ( ! empty( $gl_ids ) ) {
            $goals_repo = new GoalsRepository();
            $titles     = [];
            foreach ( $gl_ids as $gid ) {
                $g = $goals_repo->findForPlayer( (int) $gid, $player_id );
                if ( $g ) $titles[] = (string) ( $g->title ?? '' );
            }
            if ( ! empty( $titles ) ) {
                echo '<div class="tt-pop-bubble"><strong>' . esc_html__( 'Goals discussed', 'talenttrack' ) . '</strong><ul class="tt-pop-goal-discussed">';
                foreach ( $titles as $t ) {
                    echo '<li>' . esc_html( $t ) . '</li>';
                }
                echo '</ul></div>';
            }
        }

        // A previously-saved reflection is shown read-only here; the
        // editable input for the next-planned talk lives in its own
        // section below the goals.
        if ( ! empty( $conv->player_reflection ) ) {
            echo '<div class="tt-pop-bubble"><strong>' . esc_html__( 'Self-reflection', 'talenttrack' ) . '</strong><div>'
                . wp_kses_post( (string) $conv->player_reflection ) . '</div></div>';
        }

        // Acknowledgement flow — preserved exactly as-is (out of scope of
        // the redesign): once the coach has signed off.
        if ( $signed ) {
            $rest_path = 'pdp-conversations/' . $cid;
            if ( $is_self && empty( $conv->player_ack_at ) ) {
                ?>
                <form class="tt-ajax-form tt-pdp-ack" data-rest-path="<?php echo esc_attr( $rest_path ); ?>" data-rest-method="PATCH" data-redirect-after-save="reload">
                    <input type="hidden" name="player_ack_at" value="<?php echo esc_attr( current_time( 'mysql', true ) ); ?>" />
                    <button type="submit" class="tt-btn tt-btn-primary tt-btn-sm"><?php esc_html_e( 'I acknowledge this conversation', 'talenttrack' ); ?></button>
                    <div class="tt-form-msg"></div>
                </form>
                <?php
            } elseif ( ! empty( $conv->player_ack_at ) ) {
                echo '<p class="tt-pdp-acked"><em>' . esc_html__( 'You acknowledged this conversation.', 'talenttrack' ) . '</em></p>';
            }
            if ( $is_parent && empty( $conv->parent_ack_at ) ) {
                ?>
                <form class="tt-ajax-form tt-pdp-ack" data-rest-path="<?php echo esc_attr( $rest_path ); ?>" data-rest-method="PATCH" data-redirect-after-save="reload">
                    <input type="hidden" name="parent_ack_at" value="<?php echo esc_attr( current_time( 'mysql', true ) ); ?>" />
                    <button type="submit" class="tt-btn tt-btn-primary tt-btn-sm"><?php esc_html_e( 'Acknowledge as parent / guardian', 'talenttrack' ); ?></button>
                    <div class="tt-form-msg"></div>
                </form>
                <?php
            } elseif ( ! empty( $conv->parent_ack_at ) ) {
                echo '<p class="tt-pdp-acked"><em>' . esc_html__( 'A parent acknowledged this conversation.', 'talenttrack' ) . '</em></p>';
            }
        }

        echo '</div>';
    }

    /**
     * Active focus goals below the timeline — the player's current
     * non-archived, not-completed goals (not the full archive). Status
     * label is goal-specific via `status_localised` ("In ontwikkeling"),
     * not a generic "Pending".
     */
    private static function renderActiveGoals( object $player ): void {
        $goals = ( new GoalsRepository() )->topActiveForPlayer( (int) $player->id, 3 );

        echo '<section class="tt-card">';
        echo '<p class="tt-eyebrow">' . esc_html__( 'Your active goals', 'talenttrack' ) . '</p>';
        echo '<h2 class="tt-pdp-goals__h">' . esc_html__( 'What you are working on now', 'talenttrack' ) . '</h2>';

        if ( empty( $goals ) ) {
            echo '<p class="tt-pdp-empty">' . esc_html__( 'No active goals yet. Your coach will set some during your next talk.', 'talenttrack' ) . '</p>';
            echo '</section>';
            return;
        }

        echo '<div class="tt-goal-grid">';
        foreach ( $goals as $g ) {
            $status = (string) ( $g->status_localised ?? '' );
            $due    = (string) ( $g->due_date ?? '' );
            echo '<div class="tt-goal">';
            echo '<div class="tt-goal__top">';
            echo '<span class="tt-goal__title">' . esc_html( (string) ( $g->title ?? '' ) ) . '</span>';
            if ( $status !== '' ) {
                echo '<span class="tt-goal__status">' . esc_html( $status ) . '</span>';
            }
            echo '</div>';
            if ( $due !== '' ) {
                echo '<span class="tt-goal__due">' . esc_html( sprintf(
                    /* translators: %s = goal due date */
                    __( 'Target date: %s', 'talenttrack' ),
                    $due
                ) ) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</section>';
    }

    /**
     * Self-reflection — the single next-planned conversation only. Never
     * more than one form. Past and future talks never render an input.
     * Input on the left, any saved reflection on the right at ≥768px;
     * stacked on mobile (`.tt-reflect-split`). Parents see the saved
     * reflection read-only, no input.
     */
    private static function renderSelfReflection( ?object $conv, bool $is_self ): void {
        if ( $conv === null ) return;

        $cid    = (int) ( $conv->id ?? 0 );
        $label  = self::templateLabel( (string) $conv->template_key );
        $date   = self::formatTalkDate( substr( (string) ( $conv->scheduled_at ?? '' ), 0, 10 ) );
        $saved  = (string) ( $conv->player_reflection ?? '' );
        $open   = PdpCycleState::reflectionWindowOpen( $conv );

        echo '<section class="tt-card tt-reflect">';
        echo '<p class="tt-eyebrow">' . esc_html__( 'Preparing for your talk', 'talenttrack' ) . '</p>';
        $heading = $date !== ''
            ? sprintf(
                /* translators: %1$s = conversation template label, %2$s = talk date */
                __( 'Self-reflection · %1$s (%2$s)', 'talenttrack' ),
                $label, $date
            )
            : sprintf(
                /* translators: %s = conversation template label */
                __( 'Self-reflection · %s', 'talenttrack' ),
                $label
            );
        echo '<h2 class="tt-reflect__h">' . esc_html( $heading ) . '</h2>';

        // Parents: read-only — show the saved reflection, no input.
        if ( ! $is_self ) {
            if ( $saved !== '' ) {
                echo '<div class="tt-saved"><div class="tt-saved__body">' . wp_kses_post( $saved ) . '</div></div>';
            } else {
                echo '<p class="tt-pdp-empty">' . esc_html__( 'No self-reflection has been added for this talk yet.', 'talenttrack' ) . '</p>';
            }
            echo '</section>';
            return;
        }

        echo '<p class="tt-reflect__intro">' . esc_html__( 'Write your own reflection before the talk. Only the next planned talk can take a reflection — the other talks cannot. It is optional, never required.', 'talenttrack' ) . '</p>';

        if ( ! $open ) {
            echo '<p class="tt-reflect__guard">' . esc_html__( 'The reflection window opens 2 weeks before the talk. Check back closer to the planned date.', 'talenttrack' ) . '</p>';
        }

        echo '<div class="tt-reflect-split">';

        // LEFT: input (disabled until the window opens).
        $rest_path = 'pdp-conversations/' . $cid;
        $disabled  = $open ? '' : ' disabled';
        ?>
        <?php
        // #3008 (epic #2881) — the reflection saves itself while the window
        // is open, and stops with the window. A player writing about their
        // own season is composing, and a teenager typing on a phone is the
        // last person who should lose a paragraph to a mis-tap.
        //
        // Closed window means no autosave *and* no Save button: the field
        // is disabled either way, and the endpoint would answer 403.
        if ( $open ) {
            \TT\Shared\Frontend\Components\FormAutosave::enqueue();
        }
        ?>
        <form class="<?php echo $open ? 'tt-autosave-form' : 'tt-ajax-form'; ?>"
              <?php if ( $open ) {
                  echo \TT\Shared\Frontend\Components\FormAutosave::formAttrs( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — the component escapes each attribute
                      $rest_path,
                      'PATCH',
                      'pdp-reflection:' . $cid
                  );
              } else {
                  printf(
                      'data-rest-path="%s" data-rest-method="PATCH" data-redirect-after-save="reload"',
                      esc_attr( $rest_path )
                  );
              } ?>>
            <label class="tt-field-label" for="tt-myrefl-<?php echo (int) $cid; ?>"><?php esc_html_e( 'Add or update your self-reflection', 'talenttrack' ); ?></label>
            <textarea id="tt-myrefl-<?php echo (int) $cid; ?>" name="player_reflection" class="tt-input" rows="5" inputmode="text"<?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static literal ?>><?php echo esc_textarea( $saved ); ?></textarea>
            <div class="tt-form-actions">
                <?php if ( $open ) {
                    \TT\Shared\Frontend\Components\SaveState::render();
                } else { ?>
                    <button type="submit" class="tt-btn tt-btn-primary" disabled><?php esc_html_e( 'Save', 'talenttrack' ); ?></button>
                <?php } ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php

        // RIGHT: previously-saved reflection (stacked below on mobile).
        if ( $saved !== '' ) {
            echo '<div class="tt-saved">';
            if ( ! empty( $conv->updated_at ) ) {
                echo '<div class="tt-saved__when">' . esc_html( sprintf(
                    /* translators: %s = last-saved timestamp */
                    __( 'Last saved · %s', 'talenttrack' ),
                    substr( (string) $conv->updated_at, 0, 16 )
                ) ) . '</div>';
            }
            echo '<div class="tt-saved__body">' . wp_kses_post( $saved ) . '</div>';
            echo '</div>';
        } else {
            echo '<div class="tt-saved tt-saved--empty">' . esc_html__( 'No reflection saved yet.', 'talenttrack' ) . '</div>';
        }

        echo '</div>'; // .tt-reflect-split
        echo '</section>';
    }

    private static function renderVerdictCard( object $verdict ): void {
        echo '<section class="tt-card tt-pop-goal tt-pop-goal--done">';
        echo '<h2 class="tt-pdp-verdict__h">' . esc_html__( 'End-of-season verdict', 'talenttrack' ) . '</h2>';
        echo '<p class="tt-pdp-verdict__row"><strong>' . esc_html__( 'Decision:', 'talenttrack' ) . '</strong> '
            . esc_html( (string) ( $verdict->decision_localised ?? '' ) ) . '</p>';
        if ( ! empty( $verdict->summary ) ) {
            echo '<div class="tt-pdp-verdict__summary">' . wp_kses_post( (string) $verdict->summary ) . '</div>';
        }
        if ( ! empty( $verdict->signed_off_at ) ) {
            echo '<p class="tt-pdp-verdict__signoff"><em>' . esc_html( sprintf(
                /* translators: %s = signoff timestamp */
                __( 'Signed off on %s', 'talenttrack' ),
                (string) $verdict->signed_off_at
            ) ) . '</em></p>';
        }
        echo '</section>';
    }

    /** True when a conversation counts as completed on the timeline. */
    private static function isCompleted( object $conv ): bool {
        return ! empty( $conv->conducted_at ) || ! empty( $conv->coach_signoff_at );
    }

    /** Marker state: done (completed) / next (the next-planned) / future. */
    private static function markerState( object $conv, int $next_id ): string {
        if ( self::isCompleted( $conv ) ) return 'done';
        if ( (int) ( $conv->id ?? 0 ) === $next_id && $next_id > 0 ) return 'next';
        return 'future';
    }

    /** The glyph inside a marker dot: ✓ for done, sequence otherwise. */
    private static function markerGlyph( object $conv, string $state ): string {
        if ( $state === 'done' ) return '&#10003;';
        return (string) (int) ( $conv->sequence ?? 0 );
    }

    /** Date shown under a marker — conducted date for done, else scheduled. */
    private static function markerDate( object $conv, string $state ): string {
        $raw = $state === 'done' && ! empty( $conv->conducted_at )
            ? substr( (string) $conv->conducted_at, 0, 10 )
            : substr( (string) ( $conv->scheduled_at ?? '' ), 0, 10 );
        return self::formatTalkDate( $raw );
    }

    private static function stateLabel( string $state ): string {
        switch ( $state ) {
            case 'done': return __( 'Completed', 'talenttrack' );
            case 'next': return __( 'Planned', 'talenttrack' );
        }
        return __( 'Later', 'talenttrack' );
    }

    /** Human-friendly talk date (locale-aware), or '' when unscheduled. */
    private static function formatTalkDate( ?string $ymd ): string {
        if ( $ymd === null || $ymd === '' ) return '';
        $ts = strtotime( $ymd . ' UTC' );
        if ( $ts === false ) return $ymd;
        return date_i18n( (string) get_option( 'date_format', 'Y-m-d' ), $ts );
    }

    private static function templateLabel( string $key ): string {
        switch ( $key ) {
            case 'start': return __( 'Start of season', 'talenttrack' );
            case 'mid':   return __( 'Mid season', 'talenttrack' );
            case 'mid_a': return __( 'Mid-season A', 'talenttrack' );
            case 'mid_b': return __( 'Mid-season B', 'talenttrack' );
            case 'end':   return __( 'End of season', 'talenttrack' );
        }
        return $key;
    }

}

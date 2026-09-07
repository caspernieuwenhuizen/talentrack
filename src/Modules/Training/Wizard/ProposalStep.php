<?php
namespace TT\Modules\Training\Wizard;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Training\Services\TrainingPlanComposer;
use TT\Shared\Wizards\WizardStepInterface;

/**
 * Step 4 — Proposal (#2497).
 *
 * The draft, composed server-side. Nothing is saved yet: a coach can go
 * back and change the theme or the shape, and this step recomposes.
 *
 * This is the step the whole wave exists for. By the time a coach reaches
 * it they have answered four short questions and are looking at a
 * finished session, drawn from their own library, sized for the squad
 * they expect, inside the age group's intensity ceiling.
 */
final class ProposalStep implements WizardStepInterface {

    public function slug(): string { return 'proposal'; }

    public function label(): string { return __( 'Proposal', 'talenttrack' ); }

    public function render( array $state ): void {
        $draft = ( new TrainingPlanComposer() )->preview( self::payloadFrom( $state ) );

        if ( $draft['blocked'] ) {
            echo '<p class="tt-notice">'
                . esc_html__( 'This training cannot be drafted as asked:', 'talenttrack' )
                . '</p><ul>';
            foreach ( $draft['warnings'] as $warning ) {
                if ( ( $warning['severity'] ?? '' ) !== 'block' ) continue;
                echo '<li>' . esc_html( self::warningText( (string) $warning['code'] ) ) . '</li>';
            }
            echo '</ul><p>'
                . esc_html__( 'Go back and change the length or the theme. If that does not help, this team cannot be drafted for automatically yet — build the training by hand instead.', 'talenttrack' )
                . '</p>';
            return;
        }

        $total = 0;
        foreach ( $draft['blocks'] as $block ) $total += (int) $block['duration_minutes'];

        echo '<p>' . esc_html( sprintf(
            /* translators: 1: number of blocks, 2: total minutes. */
            __( 'Here is the draft — %1$d blocks, %2$d minutes.', 'talenttrack' ),
            count( $draft['blocks'] ),
            $total
        ) ) . '</p>';

        echo '<ol class="tt-training-proposal">';
        foreach ( $draft['blocks'] as $block ) {
            $name = self::exerciseName( $block['exercise_id'] ?? null );

            echo '<li>';
            echo '<strong>' . esc_html( $name ) . '</strong> ';
            echo '<span class="description">' . esc_html( sprintf(
                /* translators: 1: block duration in minutes, 2: intensity level. */
                __( '%1$d min · level %2$d', 'talenttrack' ),
                (int) $block['duration_minutes'],
                (int) $block['intensity_band']
            ) ) . '</span>';
            echo '</li>';
        }
        echo '</ol>';

        foreach ( $draft['warnings'] as $warning ) {
            if ( ( $warning['severity'] ?? '' ) === 'block' ) continue;
            echo '<p class="description">' . esc_html( self::warningText( (string) $warning['code'], $warning ) ) . '</p>';
        }

        echo '<p class="description">'
            . esc_html__( 'Nothing is saved yet. Go back to change the theme or the length, or continue to look it over and save.', 'talenttrack' )
            . '</p>';
    }

    /**
     * The composer takes the same payload here and on the review step, so
     * the plan a coach approves is the plan that gets saved.
     *
     * @param array<string,mixed> $state
     * @return array<string,mixed>
     */
    public static function payloadFrom( array $state ): array {
        return [
            'team_id'                    => (int) ( $state['team_id'] ?? 0 ),
            'age_group'                  => (string) ( $state['age_group'] ?? 'U13' ),
            'session_date'               => (string) ( $state['session_date'] ?? '' ),
            'tactical_theme'             => (string) ( $state['tactical_theme'] ?? '' ),
            'requested_duration_minutes' => (int) ( $state['requested_duration_minutes'] ?? 75 ),
            'roster_player_ids'          => (array) ( $state['roster_player_ids'] ?? [] ),
        ];
    }

    public static function exerciseName( $exercise_id ): string {
        $exercise_id = (int) $exercise_id;
        if ( $exercise_id <= 0 ) return __( 'Nothing suitable in the library yet', 'talenttrack' );

        global $wpdb;
        $name = $wpdb->get_var( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}tt_exercises WHERE id = %d",
            $exercise_id
        ) );

        return $name ? (string) $name : __( 'Unknown exercise', 'talenttrack' );
    }

    /**
     * Turn an engine warning code into something a coach can act on. An
     * unmapped code falls back to its own name rather than being
     * swallowed — a warning nobody can read is worse than an ugly one.
     */
    public static function warningText( string $code, array $warning = [] ): string {
        switch ( $code ) {
            case 'drafted_length_differs':
                return sprintf(
                    /* translators: 1: minutes the coach asked for, 2: minutes the draft came out at. */
                    __( 'You asked for %1$d minutes; this draft comes out at %2$d. The blocks follow the age group\'s training shape, so the total is not always the length you typed.', 'talenttrack' ),
                    (int) ( $warning['requested'] ?? 0 ),
                    (int) ( $warning['drafted'] ?? 0 )
                );
            case 'no_candidate_for_slot':
                return __( 'One part of the training has no matching exercise in your library yet. It is left blank for you to fill in.', 'talenttrack' );
            case 'no_macro_block_configured':
                return __( 'No periodisation calendar is set up, so the training is not adjusted for where you are in the season.', 'talenttrack' );
            // #3359 — say WHY the week is flat. A neutral week with no
            // explanation reads as a bug, and a coach who thinks the
            // planner is buggy stops using it.
            case 'cycle_week_neutral':
                return ( $warning['fixture_week'] ?? false )
                    ? __( 'There is a game this week, so the cycle is paused. This training is planned at neutral intensity, and the cycle picks up where it left off next week.', 'talenttrack' )
                    : __( 'This week is set to neutral, so the cycle is paused. This training is planned at neutral intensity, and the cycle picks up where it left off next week.', 'talenttrack' );
            case 'phase_profile_shorter_than_block':
                return __( 'This block runs longer than its weekly profile, so the later weeks are planned at neutral intensity. Add weeks to the profile to keep the progression going.', 'talenttrack' );
            case 'unrecognised_age_group_for_selection':
                return __( 'This team has no usable age group, so exercises cannot be checked as age-safe.', 'talenttrack' );
            case 'missing_age_profile':
                return __( 'This age group has no training profile yet, so there is no age-safe intensity ceiling to plan inside. Someone with the VCT configuration permission — normally the head of development — can add one under VCT configuration → Age profiles, and drafting will work from then on.', 'talenttrack' );
            // #2601 — the other half. Reads as an answer, not an omission:
            // the old copy told a U8 coach the profile was missing, which
            // invited them to go looking for a setting that will never
            // exist.
            case 'age_below_modelled_range':
                return __( 'Trainings are not drafted automatically at this age. Load is not planned in numbers for the youngest groups — build the training yourself and the plan will hold it like any other.', 'talenttrack' );
            case 'over_weekly_envelope':
                return __( 'This training pushes the team past its planned load for the week.', 'talenttrack' );
            case 'insufficient_recovery':
                return __( 'This falls too soon after a hard training for this age group.', 'talenttrack' );
            case 'phv_reduction_applied':
                return __( 'A player is flagged for a growth spurt, so the intensity has been held back.', 'talenttrack' );
        }
        return $code;
    }

    /**
     * Nothing is entered on this step, but it is still the place to
     * refuse a draft the engine has blocked.
     *
     * Without this, a coach reading "this training cannot be drafted"
     * could still click Next, name the plan on the review step, press
     * Save — and only then be told, having typed a title for a plan that
     * was never going to exist. Returning a WP_Error keeps them here,
     * next to the Back button that can actually fix it.
     */
    public function validate( array $post, array $state ) {
        $draft = ( new TrainingPlanComposer() )->preview( self::payloadFrom( $state ) );
        if ( empty( $draft['blocked'] ) ) return [];

        foreach ( $draft['warnings'] as $warning ) {
            if ( ( $warning['severity'] ?? '' ) === 'block' ) {
                return new \WP_Error( 'draft_blocked', self::warningText( (string) $warning['code'] ) );
            }
        }

        return new \WP_Error(
            'draft_blocked',
            __( 'This training cannot be drafted as asked. Go back and change the length or the theme.', 'talenttrack' )
        );
    }

    public function nextStep( array $state ): ?string { return 'review'; }

    public function submit( array $state ) { return null; }
}

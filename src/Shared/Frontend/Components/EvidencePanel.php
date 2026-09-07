<?php
namespace TT\Shared\Frontend\Components;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LookupTranslator;
use TT\Shared\Dates\TTDate;

/**
 * EvidencePanel (#3303, epic #3301) — the one rendering of an
 * {@see \TT\Modules\Pdp\EvidencePacket}.
 *
 * Three surfaces show a player's evidence before a PDP decision is made:
 * the Evidence tab on a conversation, the printed file, and the verdict
 * screen. They used to assemble it three ways and render it three ways, so
 * the same player on the same day could show a coach one set of numbers and
 * the head of academy another. Slice 1 made the assembly one; this is the
 * rendering.
 *
 * **It composes; it does not decide** (CLAUDE.md §4). It takes the packet
 * array and emits markup. Windowing, scoping, and what counts as a goal
 * that moved are the packet's business.
 *
 * Sections run in the order a coach reads them:
 *
 *   Evaluations → Attendance & minutes → Goals → Self-reflection →
 *   Notes, injuries & journey → Potential & behaviour
 *
 * **An empty section says so rather than disappearing.** "No evaluations
 * since the last conversation" is a finding — a coach about to open a
 * conversation needs to see that there is no evidence, not be shown a
 * shorter page and left to assume they scrolled past it.
 *
 * Presentation lives in `assets/css/frontend-pdp-evidence.css`, mobile-first
 * and reading the design tokens. Call {@see enqueue()} from the consuming
 * view; the print router passes `print` so the sheet drops the interactive
 * chrome.
 */
final class EvidencePanel {

    /** Enqueue the panel's stylesheet. Safe to call more than once. */
    public static function enqueue(): void {
        wp_enqueue_style(
            'tt-frontend-pdp-evidence',
            TT_PLUGIN_URL . 'assets/css/frontend-pdp-evidence.css',
            [],
            TT_VERSION
        );
    }

    /**
     * Echo the panel for one packet.
     *
     * @param array<string,mixed>|null $packet From `EvidencePacket::forFile()`
     *                                         or `::forConversation()`.
     * @param array{linked?:bool} $options `linked` false drops the record
     *                                     links — the print has no browser
     *                                     to open them in.
     */
    public static function render( ?array $packet, array $options = [] ): void {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- html() escapes internally.
        echo self::html( $packet, $options );
    }

    /**
     * @param array<string,mixed>|null $packet
     * @param array{linked?:bool} $options
     */
    public static function html( ?array $packet, array $options = [] ): string {
        if ( ! is_array( $packet ) ) {
            return '<div class="tt-evidence"><p class="tt-evidence__empty">'
                . esc_html__( 'No evidence could be assembled for this player.', 'talenttrack' )
                . '</p></div>';
        }

        $linked = ! array_key_exists( 'linked', $options ) || (bool) $options['linked'];

        $out  = '<div class="tt-evidence">';
        $out .= self::windowLine( $packet );
        $out .= self::evaluations( $packet, $linked );
        $out .= self::attendanceAndMinutes( $packet, $linked );
        $out .= self::goals( $packet, $linked );
        $out .= self::selfReflection( $packet );
        $out .= self::notesInjuriesJourney( $packet );
        $out .= self::trend( $packet );
        $out .= '</div>';

        return $out;
    }

    /**
     * What window the reader is looking at. A conversation packet covers
     * the period since the previous talk; a file packet covers the season.
     *
     * @param array<string,mixed> $packet
     */
    private static function windowLine( array $packet ): string {
        $window = is_array( $packet['window'] ?? null ) ? $packet['window'] : [];
        $from   = (string) ( $window['from'] ?? '' );
        $to     = (string) ( $window['to'] ?? '' );
        if ( $from === '' ) return '';

        $text = ( $window['scope'] ?? '' ) === 'conversation'
            ? sprintf(
                /* translators: 1: window start date, 2: window end date */
                __( 'Since the previous conversation — %1$s to %2$s', 'talenttrack' ),
                TTDate::date( $from ),
                TTDate::date( $to )
            )
            : sprintf(
                /* translators: 1: season start date, 2: season end date */
                __( 'This season — %1$s to %2$s', 'talenttrack' ),
                TTDate::date( $from ),
                TTDate::date( $to )
            );

        return '<p class="tt-evidence__window">' . esc_html( $text ) . '</p>';
    }

    /**
     * The group the old sidebar got most wrong: it listed evaluation dates
     * and nothing else, so a coach could see that an evaluation existed
     * without seeing what it said.
     *
     * @param array<string,mixed> $packet
     */
    private static function evaluations( array $packet, bool $linked ): string {
        $rows = is_array( $packet['evaluations'] ?? null ) ? $packet['evaluations'] : [];

        $out = self::sectionOpen( 'evaluations', __( 'Evaluations', 'talenttrack' ) );
        if ( $rows === [] ) {
            return $out . self::empty( __( 'No evaluations in this window.', 'talenttrack' ) ) . self::sectionClose();
        }

        $c_date     = __( 'Date', 'talenttrack' );
        $c_rating   = __( 'Rating', 'talenttrack' );
        $c_assessor = __( 'Assessor', 'talenttrack' );
        $c_notes    = __( 'Notes', 'talenttrack' );

        $out .= '<div class="tt-evidence__scroll"><table class="tt-list-table-table tt-evidence__table"><thead><tr>'
            . '<th>' . esc_html( $c_date ) . '</th>'
            . '<th>' . esc_html( $c_rating ) . '</th>'
            . '<th>' . esc_html( $c_assessor ) . '</th>'
            . '<th>' . esc_html( $c_notes ) . '</th>'
            . '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            $date  = TTDate::date( (string) ( $row['eval_date'] ?? '' ) );
            $label = $date !== '' ? $date : __( 'Undated', 'talenttrack' );
            $id    = (int) ( $row['id'] ?? 0 );

            $cell = esc_html( $label );
            if ( $linked && $id > 0 ) {
                $url = RecordLink::detailUrlForWithBack( 'evaluations', $id );
                if ( $url !== '' ) $cell = RecordLink::inline( $label, $url );
            }

            $rating = $row['rating'] ?? null;
            $rating = $rating === null ? '—' : number_format_i18n( (float) $rating, 1 );

            $assessor = (string) ( $row['assessor_name'] ?? '' );
            if ( $assessor === '' ) $assessor = '—';

            $notes = trim( wp_strip_all_tags( (string) ( $row['notes'] ?? '' ) ) );
            if ( $notes === '' ) $notes = '—';

            $out .= '<tr>'
                . '<td data-label="' . esc_attr( $c_date ) . '">' . $cell . '</td>'
                . '<td data-label="' . esc_attr( $c_rating ) . '">' . esc_html( $rating ) . '</td>'
                . '<td data-label="' . esc_attr( $c_assessor ) . '">' . esc_html( $assessor ) . '</td>'
                . '<td data-label="' . esc_attr( $c_notes ) . '">' . esc_html( $notes ) . '</td>'
                . '</tr>';

            $categories = is_array( $row['categories'] ?? null ) ? $row['categories'] : [];
            if ( $categories !== [] ) {
                $out .= '<tr class="tt-evidence__subrow"><td colspan="4" data-label="">'
                    . '<ul class="tt-evidence__chips">';
                foreach ( $categories as $cat ) {
                    $cat_label = (string) ( $cat['label'] ?? '' );
                    if ( $cat_label === '' ) continue;
                    $out .= '<li class="tt-evidence__chip">'
                        . esc_html( $cat_label ) . ' '
                        . '<strong>' . esc_html( number_format_i18n( (float) ( $cat['rating'] ?? 0 ), 1 ) ) . '</strong>'
                        . '</li>';
                }
                $out .= '</ul></td></tr>';
            }
        }

        return $out . '</tbody></table></div>' . self::sectionClose();
    }

    /**
     * Attendance and minutes together, because a coach reads them
     * together: a player present at everything who does not play is a
     * different conversation from one who is absent and does.
     *
     * @param array<string,mixed> $packet
     */
    private static function attendanceAndMinutes( array $packet, bool $linked ): string {
        $att     = is_array( $packet['attendance'] ?? null ) ? $packet['attendance'] : [];
        $minutes = is_array( $packet['minutes'] ?? null ) ? $packet['minutes'] : [];

        $out = self::sectionOpen( 'attendance', __( 'Attendance and minutes', 'talenttrack' ) );

        $activities = (int) ( $att['activities'] ?? 0 );
        if ( $activities === 0 ) {
            $out .= self::empty( __( 'No training or matches recorded in this window.', 'talenttrack' ) );
        } else {
            $rate = $att['rate'] ?? null;
            $out .= '<dl class="tt-evidence__stats">';
            $out .= self::stat( __( 'Activities', 'talenttrack' ), (string) $activities );
            $out .= self::stat(
                __( 'Present', 'talenttrack' ),
                $rate === null
                    ? (string) (int) ( $att['present'] ?? 0 )
                    : sprintf(
                        /* translators: 1: present count, 2: attendance percentage */
                        __( '%1$d (%2$d%%)', 'talenttrack' ),
                        (int) ( $att['present'] ?? 0 ),
                        (int) $rate
                    )
            );
            $out .= self::stat( __( 'Absent', 'talenttrack' ), (string) (int) ( $att['absent'] ?? 0 ) );
            $out .= self::stat( __( 'Excused', 'talenttrack' ), (string) (int) ( $att['excused'] ?? 0 ) );
            $out .= self::stat( __( 'Matches played', 'talenttrack' ), (string) (int) ( $minutes['apps'] ?? 0 ) );
            $out .= self::stat( __( 'Minutes played', 'talenttrack' ), (string) (int) ( $minutes['minutes'] ?? 0 ) );
            $out .= '</dl>';
        }

        $breakdown = is_array( $minutes['breakdown'] ?? null ) ? $minutes['breakdown'] : [];
        if ( $breakdown === [] ) {
            $out .= self::empty( __( 'No per-match minutes recorded in this window.', 'talenttrack' ) );
            return $out . self::sectionClose();
        }

        $c_date  = __( 'Date', 'talenttrack' );
        $c_match = __( 'Match', 'talenttrack' );
        $c_min   = __( 'Minutes', 'talenttrack' );

        $out .= '<div class="tt-evidence__scroll"><table class="tt-list-table-table tt-evidence__table"><thead><tr>'
            . '<th>' . esc_html( $c_date ) . '</th>'
            . '<th>' . esc_html( $c_match ) . '</th>'
            . '<th>' . esc_html( $c_min ) . '</th>'
            . '</tr></thead><tbody>';

        foreach ( $breakdown as $match ) {
            $title = (string) ( $match['title'] ?? '' );
            if ( $title === '' ) $title = __( 'Match', 'talenttrack' );
            $id   = (int) ( $match['activity_id'] ?? 0 );
            $cell = esc_html( $title );
            if ( $linked && $id > 0 ) {
                $url = RecordLink::detailUrlForWithBack( 'activities', $id );
                if ( $url !== '' ) $cell = RecordLink::inline( $title, $url );
            }

            $out .= '<tr>'
                . '<td data-label="' . esc_attr( $c_date ) . '">' . esc_html( TTDate::date( (string) ( $match['session_date'] ?? '' ) ) ) . '</td>'
                . '<td data-label="' . esc_attr( $c_match ) . '">' . $cell . '</td>'
                . '<td data-label="' . esc_attr( $c_min ) . '">' . (int) ( $match['minutes'] ?? 0 ) . '</td>'
                . '</tr>';
        }

        return $out . '</tbody></table></div>' . self::sectionClose();
    }

    /**
     * @param array<string,mixed> $packet
     */
    private static function goals( array $packet, bool $linked ): string {
        $rows = is_array( $packet['goals'] ?? null ) ? $packet['goals'] : [];

        $out = self::sectionOpen( 'goals', __( 'Goals', 'talenttrack' ) );
        if ( $rows === [] ) {
            return $out . self::empty( __( 'No goals open or closed in this window.', 'talenttrack' ) ) . self::sectionClose();
        }

        $out .= '<ul class="tt-evidence__cards">';
        foreach ( $rows as $goal ) {
            $title = (string) ( $goal['title'] ?? '' );
            if ( $title === '' ) $title = __( 'Untitled goal', 'talenttrack' );
            $id   = (int) ( $goal['id'] ?? 0 );
            $head = esc_html( $title );
            if ( $linked && $id > 0 ) {
                $url = RecordLink::detailUrlForWithBack( 'goals', $id );
                if ( $url !== '' ) $head = RecordLink::inline( $title, $url );
            }

            $status = LookupTranslator::byTypeAndName( 'goal_status', (string) ( $goal['status'] ?? '' ) );

            // The line that makes this a conversation opener rather than a
            // list: did anything happen to this goal since the last talk?
            $movement = ! empty( $goal['changed_in_window'] )
                ? ( ! empty( $goal['created_in_window'] )
                    ? __( 'Set in this window', 'talenttrack' )
                    : __( 'Moved in this window', 'talenttrack' ) )
                : __( 'No movement in this window', 'talenttrack' );

            $out .= '<li class="tt-evidence__card">'
                . '<span class="tt-evidence__card-title">' . $head . '</span>'
                . '<span class="tt-evidence__card-meta">' . esc_html( $status ) . '</span>'
                . '<span class="tt-evidence__card-meta">' . esc_html( $movement ) . '</span>'
                . '</li>';
        }

        return $out . '</ul>' . self::sectionClose();
    }

    /**
     * @param array<string,mixed> $packet
     */
    private static function selfReflection( array $packet ): string {
        $window = is_array( $packet['window'] ?? null ) ? $packet['window'] : [];
        // The season packet has no conversation, so no reflection to show.
        if ( ( $window['scope'] ?? '' ) !== 'conversation' ) return '';

        $text = trim( wp_strip_all_tags( (string) ( $packet['self_reflection'] ?? '' ) ) );

        $out = self::sectionOpen( 'self-reflection', __( 'Player self-reflection', 'talenttrack' ) );
        $out .= $text === ''
            ? self::empty( __( 'The player has not written a self-reflection for this conversation yet.', 'talenttrack' ) )
            : '<blockquote class="tt-evidence__quote">' . esc_html( $text ) . '</blockquote>';

        return $out . self::sectionClose();
    }

    /**
     * @param array<string,mixed> $packet
     */
    private static function notesInjuriesJourney( array $packet ): string {
        $notes    = is_array( $packet['notes'] ?? null ) ? $packet['notes'] : [];
        $injuries = is_array( $packet['injuries'] ?? null ) ? $packet['injuries'] : [];
        $journey  = is_array( $packet['recent_journey'] ?? null ) ? $packet['recent_journey'] : [];

        $out = self::sectionOpen( 'context', __( 'Notes, injuries and journey', 'talenttrack' ) );

        if ( $notes === [] && $injuries === [] && $journey === [] ) {
            return $out . self::empty( __( 'Nothing recorded about this player in this window.', 'talenttrack' ) ) . self::sectionClose();
        }

        $out .= '<ul class="tt-evidence__cards">';

        foreach ( $injuries as $injury ) {
            $started = TTDate::date( (string) ( $injury['started_on'] ?? '' ) );
            $meta    = ! empty( $injury['is_open'] )
                ? __( 'Still out', 'talenttrack' )
                : sprintf(
                    /* translators: %s = return-to-play date */
                    __( 'Back on %s', 'talenttrack' ),
                    TTDate::date( (string) ( $injury['actual_return'] ?? '' ) )
                );

            $body = trim( wp_strip_all_tags( (string) ( $injury['notes'] ?? '' ) ) );

            $out .= '<li class="tt-evidence__card tt-evidence__card--injury">'
                . '<span class="tt-evidence__card-title">' . esc_html( sprintf(
                    /* translators: %s = injury start date */
                    __( 'Injury from %s', 'talenttrack' ),
                    $started
                ) ) . '</span>'
                . '<span class="tt-evidence__card-meta">' . esc_html( $meta ) . '</span>'
                . ( $body !== '' ? '<span class="tt-evidence__card-body">' . esc_html( $body ) . '</span>' : '' )
                . '</li>';
        }

        foreach ( $journey as $event ) {
            $summary = trim( (string) ( $event->summary ?? '' ) );
            if ( $summary === '' ) continue;
            $out .= '<li class="tt-evidence__card">'
                . '<span class="tt-evidence__card-title">' . esc_html( $summary ) . '</span>'
                . '<span class="tt-evidence__card-meta">' . esc_html( TTDate::date( (string) ( $event->event_date ?? '' ) ) ) . '</span>'
                . '</li>';
        }

        foreach ( $notes as $note ) {
            $body = trim( wp_strip_all_tags( (string) ( $note['body'] ?? '' ) ) );
            if ( $body === '' ) continue;

            $who = (string) ( $note['author_name'] ?? '' );
            $when = TTDate::date( (string) ( $note['created_at'] ?? '' ) );
            $meta = $who !== ''
                ? sprintf(
                    /* translators: 1: staff member name, 2: date the note was written */
                    __( '%1$s · %2$s', 'talenttrack' ),
                    $who,
                    $when
                )
                : $when;

            $out .= '<li class="tt-evidence__card tt-evidence__card--note">'
                . '<span class="tt-evidence__card-title">' . esc_html__( 'Staff note', 'talenttrack' ) . '</span>'
                . '<span class="tt-evidence__card-meta">' . esc_html( $meta ) . '</span>'
                . '<span class="tt-evidence__card-body">' . esc_html( $body ) . '</span>'
                . '</li>';
        }

        return $out . '</ul>' . self::sectionClose();
    }

    /**
     * @param array<string,mixed> $packet
     */
    private static function trend( array $packet ): string {
        $potential = is_array( $packet['potential'] ?? null ) ? $packet['potential'] : [];
        $behaviour = is_array( $packet['behaviour'] ?? null ) ? $packet['behaviour'] : [];

        $out = self::sectionOpen( 'trend', __( 'Potential and behaviour', 'talenttrack' ) );

        if ( $potential === [] && $behaviour === [] ) {
            return $out . self::empty( __( 'No potential or behaviour ratings set in this window.', 'talenttrack' ) ) . self::sectionClose();
        }

        $out .= '<ul class="tt-evidence__cards">';

        foreach ( $potential as $row ) {
            $band = LookupTranslator::byTypeAndName( 'potential_band', (string) ( $row->potential_band ?? '' ) );
            $out .= '<li class="tt-evidence__card">'
                // _x() — the plain msgid already exists behind the player
                // profile's own context, and a second bare one would ship
                // to Dutch untranslated (#1223 catches exactly this).
                . '<span class="tt-evidence__card-title">' . esc_html( _x( 'Potential', 'evidence panel — a potential band set in the window', 'talenttrack' ) ) . '</span>'
                . '<span class="tt-evidence__card-meta">' . esc_html( $band ) . ' · '
                . esc_html( TTDate::date( (string) ( $row->set_at ?? '' ) ) ) . '</span>'
                . '</li>';
        }

        foreach ( $behaviour as $row ) {
            $out .= '<li class="tt-evidence__card">'
                . '<span class="tt-evidence__card-title">' . esc_html__( 'Behaviour', 'talenttrack' ) . '</span>'
                . '<span class="tt-evidence__card-meta">'
                . esc_html( number_format_i18n( (float) ( $row->rating ?? 0 ), 1 ) ) . ' · '
                . esc_html( TTDate::date( (string) ( $row->rated_at ?? '' ) ) ) . '</span>'
                . '</li>';
        }

        return $out . '</ul>' . self::sectionClose();
    }

    private static function sectionOpen( string $key, string $heading ): string {
        return '<section class="tt-evidence__section" data-tt-evidence-section="' . esc_attr( $key ) . '">'
            . '<h3 class="tt-evidence__heading">' . esc_html( $heading ) . '</h3>';
    }

    private static function sectionClose(): string {
        return '</section>';
    }

    private static function empty( string $text ): string {
        return '<p class="tt-evidence__empty">' . esc_html( $text ) . '</p>';
    }

    private static function stat( string $label, string $value ): string {
        return '<div class="tt-evidence__stat">'
            . '<dt>' . esc_html( $label ) . '</dt>'
            . '<dd>' . esc_html( $value ) . '</dd>'
            . '</div>';
    }
}

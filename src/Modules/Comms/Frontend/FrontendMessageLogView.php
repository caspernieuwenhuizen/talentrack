<?php
namespace TT\Modules\Comms\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Comms\Cron\CommsScheduledCron;
use TT\Modules\Comms\Domain\CommsStatusLabels;
use TT\Modules\Comms\Repositories\CommsLogRepository;
use TT\Modules\Comms\Rest\CommsRestController;
use TT\Modules\Comms\Template\TemplateRegistry;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendMessageLogView (#2606, Gate C) — `?tt_view=messages`.
 *
 * The staff answer to *"what has this player's family been told, and did
 * it arrive?"* — the question the #0066 spec set out to make answerable
 * and which has needed SQL ever since, because `tt_comms_log` shipped
 * with no reader anywhere in the codebase.
 *
 * ## Player-scoped first, global second
 *
 * The route carries `player_id`, and the player record links into it, so
 * the question is asked from the player rather than from a global list
 * somebody then has to narrow (§1). The unfiltered view is what you get
 * when you drop the filter, not the other way round.
 *
 * It is its own route rather than a tab on the player record: §5c says a
 * record-scoped tab strip must come from `RecordSpine`, and
 * `FrontendPlayerDetailView`'s strip is the one grandfathered hand-rolled
 * one. Adding to it would mean either extending the grandfathered strip or
 * migrating the most trafficked view in the plugin, and neither belongs in
 * a read surface's first cut.
 *
 * ## Navigation (§5)
 *
 * Two affordances: the breadcrumb chain ending at Dashboard, and the
 * `tt_back` pill it renders above itself when the entry URL carried one —
 * which it does, because the player record links here through
 * `BackLink::appendTo()`. Nothing else. The chain is emitted on every
 * path, permission-denied included.
 *
 * ## What it does not show
 *
 * The message body. The audit row stores a SHA-256 of it and nothing more,
 * deliberately, so this surface can say that a message about a child was
 * sent, to whom, and whether it arrived — and cannot be used to read what
 * a coach wrote about them. There is no column to add.
 */
final class FrontendMessageLogView extends FrontendViewBase {

    /** The same cap the REST log routes use. See `CommsRestController`. */
    private const CAP = CommsRestController::CAP_READ_LOG;

    private const PER_PAGE = 50;

    /** Templates the daily cron owns, in the order it runs them. */
    private const SCHEDULED_TEMPLATES = [
        'goal_nudge',
        'attendance_flag',
        'onboarding_nudge_inactive',
        'staff_development_reminder',
    ];

    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-frontend-message-log',
            TT_PLUGIN_URL . 'assets/css/frontend-message-log.css',
            [ 'tt-public' ],
            TT_VERSION
        );
    }

    public static function render( int $user_id, bool $is_admin = false ): void {
        $title = __( 'Message log', 'talenttrack' );

        if ( ! current_user_can( self::CAP ) ) {
            FrontendBreadcrumbs::fromDashboard( $title );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to read the message log.', 'talenttrack' ) . '</p>';
            return;
        }

        self::enqueueAssets();
        FrontendBreadcrumbs::fromDashboard( $title );

        $repo    = new CommsLogRepository();
        $filters = self::filtersFromQuery();
        $players = $repo->playersInLog();

        $player_id = (int) $filters['player_id'];
        $heading   = $player_id > 0 && isset( $players[ $player_id ] )
            /* translators: %s: the player's name */
            ? sprintf( __( 'Messages about %s', 'talenttrack' ), $players[ $player_id ] )
            : $title;
        self::renderHeader( $heading );

        self::renderCronHealth();
        self::renderFilters( $filters, $players );

        $page  = isset( $_GET['mpage'] ) ? max( 1, absint( $_GET['mpage'] ) ) : 1;
        $total = $repo->count( $filters );
        $rows  = $repo->search( $filters, $page, self::PER_PAGE );

        // #3339 — everything the filters govern, in one region the refresh
        // swaps. Summary, table and pager move together: a swap that updated
        // the rows and left the count behind would be worse than the reload.
        printf( '<div data-tt-filter-region data-tt-filter-count="%d">', (int) $total );
        self::renderSummary( $total, $page );
        self::renderTable( $rows );
        self::renderPagination( $total, $page );
        echo '</div>';
    }

    /**
     * Normalised filter state from the query string.
     *
     * @return array<string,mixed>
     */
    private static function filtersFromQuery(): array {
        $status = isset( $_GET['f_status'] ) ? sanitize_key( (string) $_GET['f_status'] ) : '';
        return [
            'player_id'    => isset( $_GET['player_id'] ) ? absint( $_GET['player_id'] ) : 0,
            'template_key' => isset( $_GET['f_template'] ) ? sanitize_key( (string) $_GET['f_template'] ) : '',
            'status'       => $status,
            'date_from'    => isset( $_GET['f_date_from'] ) ? sanitize_text_field( (string) $_GET['f_date_from'] ) : '',
            'date_to'      => isset( $_GET['f_date_to'] ) ? sanitize_text_field( (string) $_GET['f_date_to'] ) : '',
        ];
    }

    /**
     * The Gate E cron-health signal.
     *
     * A detector that found nothing to send and one that has been throwing
     * every night for three months look identical from the log — both
     * leave no rows. This is the only place the difference is visible, so
     * it sits above the table rather than behind a link.
     *
     * Silent when every detector is healthy: a panel that is always green
     * is a panel nobody reads.
     */
    private static function renderCronHealth(): void {
        $health = get_option( CommsScheduledCron::HEALTH_OPTION, [] );
        if ( ! is_array( $health ) || $health === [] ) return;

        $broken = [];
        foreach ( self::SCHEDULED_TEMPLATES as $key ) {
            $record = $health[ $key ] ?? null;
            if ( ! is_array( $record ) ) continue;
            if ( empty( $record['ok'] ) ) {
                $broken[ $key ] = $record;
            }
        }
        if ( $broken === [] ) return;

        echo '<div class="tt-msglog-health tt-flash tt-flash-warning">';
        echo '<p><strong>' . esc_html__( 'Some scheduled messages are not being worked out.', 'talenttrack' ) . '</strong></p>';
        echo '<ul>';
        foreach ( $broken as $key => $record ) {
            $label = self::templateLabel( (string) $key );
            printf(
                '<li>%1$s — %2$s</li>',
                esc_html( $label ),
                esc_html( sprintf(
                    /* translators: 1: date and time of the last run, 2: the error reported */
                    __( 'last run %1$s, failed with: %2$s', 'talenttrack' ),
                    (string) ( $record['ran_at'] ?? '' ),
                    (string) ( $record['error'] ?? '' )
                ) )
            );
        }
        echo '</ul></div>';
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<int,string>   $players
     */
    private static function renderFilters( array $filters, array $players ): void {
        $repo = new CommsLogRepository();

        $player_options = [];
        foreach ( $players as $id => $name ) {
            $player_options[ (string) $id ] = $name;
        }

        $template_options = [];
        foreach ( TemplateRegistry::all() as $key => $template ) {
            $template_options[ (string) $key ] = $template->label();
        }
        asort( $template_options );

        $status_options = [];
        foreach ( $repo->statusesInUse() as $status ) {
            $status_options[ $status ] = CommsStatusLabels::label( $status );
        }

        $sel_player   = (string) ( $filters['player_id'] > 0 ? $filters['player_id'] : '' );
        $sel_template = (string) $filters['template_key'];
        $sel_status   = (string) $filters['status'];
        $sel_from     = (string) $filters['date_from'];
        $sel_to       = (string) $filters['date_to'];

        $hidden = [];
        if ( ! empty( $_GET['tt_view'] ) ) $hidden['tt_view'] = sanitize_key( (string) $_GET['tt_view'] );
        // Keep the back-target across a filter submit, or the pill vanishes
        // the moment somebody narrows the list they arrived at.
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( (string) wp_unslash( $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $sel_player !== '' )   { $active_count++; $chips[] = $players[ (int) $sel_player ] ?? ( '#' . $sel_player ); }
        if ( $sel_template !== '' ) { $active_count++; $chips[] = $template_options[ $sel_template ] ?? $sel_template; }
        if ( $sel_status !== '' )   { $active_count++; $chips[] = $status_options[ $sel_status ] ?? $sel_status; }
        if ( $sel_from !== '' || $sel_to !== '' ) {
            $active_count++;
            $chips[] = trim( $sel_from . ' – ' . $sel_to, ' –' );
        }

        FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => self::clearUrl(),
            // #3339 — filter in place (epic #3335).
            'refresh'      => true,
            'groups'       => [
                // #3332 looked at converting this to the player typeahead and
                // deliberately did not. The options come from
                // `CommsLogRepository::playersInLog()` — only players who
                // actually appear in the log — which is a narrower and more
                // useful list than the academy roster the picker resolves.
                // Swapping it would offer players who have never been
                // messaged, which is worse, not better.
                [
                    'type'        => 'select',
                    'key'         => 'player',
                    'label'       => __( 'Player', 'talenttrack' ),
                    'name'        => 'player_id',
                    'selected'    => $sel_player,
                    'placeholder' => __( '— Any —', 'talenttrack' ),
                    'options'     => $player_options,
                ],
                [
                    'type'        => 'select',
                    'key'         => 'template',
                    'label'       => __( 'Message', 'talenttrack' ),
                    'name'        => 'f_template',
                    'selected'    => $sel_template,
                    'placeholder' => __( '— Any —', 'talenttrack' ),
                    'options'     => $template_options,
                ],
                [
                    'type'        => 'select',
                    'key'         => 'status',
                    'label'       => __( 'Outcome', 'talenttrack' ),
                    'name'        => 'f_status',
                    'selected'    => $sel_status,
                    'placeholder' => __( '— Any —', 'talenttrack' ),
                    'options'     => $status_options,
                ],
                [
                    'type'       => 'date_range',
                    'key'        => 'date',
                    'label'      => __( 'Date', 'talenttrack' ),
                    'label_from' => __( 'From', 'talenttrack' ),
                    'label_to'   => __( 'To', 'talenttrack' ),
                    'from'       => [ 'name' => 'f_date_from', 'value' => $sel_from ],
                    'to'         => [ 'name' => 'f_date_to',   'value' => $sel_to ],
                ],
            ],
        ] );
    }

    private static function renderSummary( int $total, int $page ): void {
        echo '<p class="tt-msglog-summary">';
        if ( $total === 0 ) {
            esc_html_e( 'No messages match these filters. If you expected one, nothing was attempted — which is a different problem from a message that failed to arrive.', 'talenttrack' );
        } else {
            $first = ( ( $page - 1 ) * self::PER_PAGE ) + 1;
            $last  = min( $total, $page * self::PER_PAGE );
            printf(
                /* translators: 1: first row index, 2: last row index, 3: total count */
                esc_html__( 'Showing %1$d–%2$d of %3$d messages.', 'talenttrack' ),
                (int) $first, (int) $last, (int) $total
            );
        }
        echo '</p>';
    }

    /** @param list<array<string,mixed>> $rows */
    private static function renderTable( array $rows ): void {
        if ( $rows === [] ) return;
        ?>
        <div class="tt-table-wrap">
            <table class="tt-table tt-msglog-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'When', 'talenttrack' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'talenttrack' ); ?></th>
                        <th><?php esc_html_e( 'To', 'talenttrack' ); ?></th>
                        <th><?php esc_html_e( 'Channel', 'talenttrack' ); ?></th>
                        <th><?php esc_html_e( 'Outcome', 'talenttrack' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $rows as $row ) :
                    $status = (string) ( $row['status'] ?? '' );
                    $error  = (string) ( $row['error_code'] ?? '' );
                    $hint   = CommsStatusLabels::hint( $status, $error );
                    ?>
                    <tr>
                        <td data-label="<?php esc_attr_e( 'When', 'talenttrack' ); ?>">
                            <?php echo esc_html( (string) ( $row['created_at'] ?? '' ) ); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Message', 'talenttrack' ); ?>">
                            <?php echo esc_html( self::templateLabel( (string) ( $row['template_key'] ?? '' ) ) ); ?>
                            <?php if ( ! empty( $row['subject'] ) ) : ?>
                                <span class="tt-msglog-subject"><?php echo esc_html( (string) $row['subject'] ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'To', 'talenttrack' ); ?>">
                            <?php echo esc_html( self::recipientLabel( $row ) ); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Channel', 'talenttrack' ); ?>">
                            <?php echo esc_html( (string) ( $row['channel'] ?? '' ) ); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Outcome', 'talenttrack' ); ?>">
                            <span class="tt-msglog-status tt-msglog-status--<?php echo esc_attr( CommsStatusLabels::tone( $status ) ); ?>">
                                <?php echo esc_html( CommsStatusLabels::label( $status, $error ) ); ?>
                            </span>
                            <?php if ( $hint !== '' ) : ?>
                                <span class="tt-msglog-hint"><?php echo esc_html( $hint ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function renderPagination( int $total, int $page ): void {
        $pages = (int) max( 1, ceil( $total / self::PER_PAGE ) );
        if ( $pages <= 1 ) return;

        $base = self::currentUrl();
        $prev = $page > 1      ? add_query_arg( 'mpage', $page - 1, $base ) : '';
        $next = $page < $pages ? add_query_arg( 'mpage', $page + 1, $base ) : '';
        ?>
        <nav class="tt-msglog-pagination">
            <?php if ( $prev !== '' ) : ?>
                <a class="tt-btn tt-btn-secondary" href="<?php echo esc_url( $prev ); ?>">&larr; <?php esc_html_e( 'Newer', 'talenttrack' ); ?></a>
            <?php endif; ?>
            <span><?php printf(
                /* translators: 1: current page, 2: total pages */
                esc_html__( 'Page %1$d of %2$d', 'talenttrack' ),
                (int) $page, (int) $pages
            ); ?></span>
            <?php if ( $next !== '' ) : ?>
                <a class="tt-btn tt-btn-secondary" href="<?php echo esc_url( $next ); ?>"><?php esc_html_e( 'Older', 'talenttrack' ); ?> &rarr;</a>
            <?php endif; ?>
        </nav>
        <?php
    }

    /**
     * Who the row was addressed to, in the most identifying form the row
     * carries. The address is the fallback rather than the first choice:
     * a name is what a reader recognises.
     *
     * @param array<string,mixed> $row
     */
    private static function recipientLabel( array $row ): string {
        $user_id = (int) ( $row['recipient_user_id'] ?? 0 );
        if ( $user_id > 0 ) {
            $user = get_userdata( $user_id );
            if ( $user && (string) $user->display_name !== '' ) return (string) $user->display_name;
        }
        $address = (string) ( $row['address_blob'] ?? '' );
        if ( $address !== '' ) return $address;

        return __( 'Nobody reachable', 'talenttrack' );
    }

    private static function templateLabel( string $key ): string {
        $template = TemplateRegistry::get( $key );
        return $template !== null ? $template->label() : $key;
    }

    private static function currentUrl(): string {
        $current = isset( $_SERVER['REQUEST_URI'] )
            ? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
            : '';
        return remove_query_arg( [ 'mpage' ], $current !== '' ? $current : home_url( '/' ) );
    }

    private static function clearUrl(): string {
        $base = remove_query_arg(
            [ 'mpage', 'player_id', 'f_template', 'f_status', 'f_date_from', 'f_date_to' ],
            self::currentUrl()
        );
        $view = isset( $_GET['tt_view'] ) ? sanitize_key( (string) $_GET['tt_view'] ) : '';
        return $view !== '' ? add_query_arg( 'tt_view', $view, $base ) : $base;
    }
}

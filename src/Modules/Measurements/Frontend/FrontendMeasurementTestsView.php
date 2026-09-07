<?php
namespace TT\Modules\Measurements\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Archive\ArchiveRepository;
use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Modules\Authorization\MatrixGate;
use TT\Modules\Measurements\Repositories\MeasurementDefinitionsRepository;
use TT\Modules\Measurements\Repositories\MeasurementLevelsRepository;
use TT\Modules\Measurements\Repositories\MeasurementTargetsRepository;
use TT\Modules\Measurements\Units\Dimensions;
use TT\Modules\Measurements\Units\UnitContext;
use TT\Modules\Measurements\Units\UnitRegistry;
use TT\Shared\Frontend\FrontendViewBase;
use TT\Shared\Frontend\Components\BackLink;
use TT\Shared\Frontend\Components\CrossViewLink;
use TT\Shared\Frontend\Components\FormSaveButton;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;

/**
 * FrontendMeasurementTestsView (#2121, epic #2116) — the "Manage tests"
 * configuration surface for the test catalogue.
 *
 * Lists every test definition (name, category, unit, direction, cadence,
 * active state). Create runs through the existing `measurement` wizard
 * (§3 wizard-first); edit is a flat form with Save + Cancel (§6) covering
 * the definition fields plus per-age-group target bands. Row actions:
 * activate / deactivate and soft-archive (recycle-bin pattern).
 *
 * Slug: `measurement-tests`. Matrix-gated on `measurement_definitions`
 * change. Composition only — all list / band / archive logic lives in the
 * repositories + ArchiveRepository (§4); the same domain layer the REST
 * controller (#2120) consumes, so a future SaaS front end gets identical
 * answers.
 */
final class FrontendMeasurementTestsView extends FrontendViewBase {

    public const NONCE_ACTION = 'tt_measurement_tests';
    public const NONCE_FIELD  = '_tt_measurement_tests_nonce';

    public static function render( int $user_id, bool $is_admin ): void {
        $title = __( 'Manage tests', 'talenttrack' );

        if ( ! MatrixGate::canAnyScope( $user_id, 'measurement_definitions', 'change' ) && ! $is_admin ) {
            FrontendBreadcrumbs::fromDashboard( $title );
            self::renderHeader( $title );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to manage tests.', 'talenttrack' ) . '</p>';
            return;
        }

        $action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : 'list';
        $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        // POST handling (edit save, toggle active, archive). Always
        // re-renders the list afterwards via a flash + redirect-free
        // refresh of the current request.
        $flash = '';
        if ( $_SERVER['REQUEST_METHOD'] === 'POST'
             && isset( $_POST[ self::NONCE_FIELD ] )
             && wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            $result = self::handlePost();
            $flash  = $result['flash'];
            if ( $result['back_to_list'] ) {
                $action = 'list';
                $id     = 0;
            }
        }

        self::enqueueAssets();
        self::enqueueViewCss();

        if ( $action === 'edit' && $id > 0 ) {
            self::renderEdit( $id, $title );
            return;
        }

        FrontendBreadcrumbs::fromDashboard( $title );
        self::renderHeader( $title );

        if ( $flash !== '' ) {
            echo '<div class="tt-notice tt-notice-success">' . esc_html( $flash ) . '</div>';
        }

        self::renderModuleLinks( $user_id );
        self::renderList();
    }

    /**
     * In-body action links for the test catalogue: the "+ New test" wizard
     * entry plus back-aware cross-links to the two execution surfaces so
     * staff move Configure → Record → Review without the dashboard (§5
     * back-pill via BackLink::appendTo). Not breadcrumb / back chrome —
     * these are in-body actions, so they do not breach the two-affordance
     * contract.
     */
    private static function renderModuleLinks( int $user_id ): void {
        $base = RecordLink::dashboardUrl();

        echo '<div class="tt-mt-links">';
        self::renderNewTestButton();

        // §7 (#2304) — gate each cross-link on the target surface's own
        // guard via the CrossViewLinkRegistry. Managing test definitions
        // (this page) doesn't imply recording measurements or reading
        // session coverage — separate matrix entities encoded in the gates.
        CrossViewLink::render( 'measurements-entry', static function () use ( $base ): void {
            $record_url = BackLink::appendTo( add_query_arg( [ 'tt_view' => 'measurements-entry' ], $base ) );
            echo '<a class="tt-btn tt-btn-secondary tt-mt-link" href="' . esc_url( $record_url ) . '">'
                . esc_html__( 'Record measurements', 'talenttrack' ) . '</a>';
        } );

        CrossViewLink::render( 'measurements-coverage', static function () use ( $base ): void {
            $coverage_url = BackLink::appendTo( add_query_arg( [ 'tt_view' => 'measurements-coverage' ], $base ) );
            echo '<a class="tt-btn tt-btn-secondary tt-mt-link" href="' . esc_url( $coverage_url ) . '">'
                . esc_html__( 'Testing coverage', 'talenttrack' ) . '</a>';
        } );
        echo '</div>';
    }

    // ── list ────────────────────────────────────────────────────────

    private static function renderList(): void {
        $definitions = ( new MeasurementDefinitionsRepository() )->listAll( true );

        if ( empty( $definitions ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No tests have been set up yet. Use “+ New test” to create the first one.', 'talenttrack' ) . '</p>';
            return;
        }

        echo '<ul class="tt-mt-list">';
        foreach ( $definitions as $def ) {
            self::renderRow( $def );
        }
        echo '</ul>';
    }

    private static function renderRow( object $def ): void {
        $id       = (int) $def->id;
        $active   = (int) $def->is_active === 1;
        $category = (string) ( $def->category_label ?: $def->category_name ?: '' );
        $meta     = self::rowMeta( $def );

        $edit_url = BackLink::appendTo( add_query_arg(
            [ 'tt_view' => 'measurement-tests', 'action' => 'edit', 'id' => $id ],
            RecordLink::dashboardUrl()
        ) );

        echo '<li class="tt-mt-row' . ( $active ? '' : ' tt-mt-row--inactive' ) . '">';

        echo '<div class="tt-mt-row__main">';
        echo '<a class="tt-mt-row__name" href="' . esc_url( $edit_url ) . '">' . esc_html( (string) $def->name ) . '</a>';
        if ( $category !== '' ) {
            echo '<span class="tt-mt-row__cat">' . esc_html( $category ) . '</span>';
        }
        if ( $meta !== '' ) {
            echo '<span class="tt-mt-row__meta">' . esc_html( $meta ) . '</span>';
        }
        echo '<span class="tt-mt-chip tt-mt-chip--' . ( $active ? 'on' : 'off' ) . '">'
            . esc_html( $active ? __( 'Active', 'talenttrack' ) : __( 'Inactive', 'talenttrack' ) )
            . '</span>';
        echo '</div>';

        echo '<div class="tt-mt-row__actions">';
        echo '<a class="tt-btn tt-btn-secondary tt-mt-action" href="' . esc_url( $edit_url ) . '">'
            . esc_html__( 'Edit', 'talenttrack' ) . '</a>';

        // Toggle active — small POST form so the state change is auditable
        // server-side and needs no JS.
        echo '<form method="post" class="tt-mt-inline-form">';
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
        echo '<input type="hidden" name="op" value="toggle" />';
        echo '<input type="hidden" name="id" value="' . esc_attr( (string) $id ) . '" />';
        echo '<input type="hidden" name="to" value="' . ( $active ? '0' : '1' ) . '" />';
        echo '<button type="submit" class="tt-btn tt-btn-secondary tt-mt-action">'
            . esc_html( $active ? __( 'Deactivate', 'talenttrack' ) : __( 'Activate', 'talenttrack' ) )
            . '</button>';
        echo '</form>';

        // Export this test's results to a formatted Excel workbook (#2139).
        self::renderExportForm( $id );

        // Archive — soft delete via the recycle-bin pattern.
        echo '<form method="post" class="tt-mt-inline-form">';
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
        echo '<input type="hidden" name="op" value="archive" />';
        echo '<input type="hidden" name="id" value="' . esc_attr( (string) $id ) . '" />';
        echo '<button type="submit" class="tt-btn tt-btn-secondary tt-mt-action tt-mt-action--archive">'
            . esc_html__( 'Archive', 'talenttrack' ) . '</button>';
        echo '</form>';

        echo '</div>';
        echo '</li>';
    }

    private static function rowMeta( object $def ): string {
        $parts = [];
        $unit  = (string) ( $def->unit ?? '' );
        if ( $unit !== '' ) {
            $parts[] = $unit;
        }
        $dir = self::directionLabel( (string) $def->direction );
        if ( $dir !== '' ) {
            $parts[] = $dir;
        }
        $freq = self::frequencyLabel( (string) $def->frequency );
        if ( $freq !== '' ) {
            $parts[] = $freq;
        }
        return implode( ' · ', $parts );
    }

    // ── edit ────────────────────────────────────────────────────────

    private static function renderEdit( int $id, string $list_title ): void {
        $repo = new MeasurementDefinitionsRepository();
        $def  = $repo->find( $id );

        $title = __( 'Edit test', 'talenttrack' );
        FrontendBreadcrumbs::fromDashboard( $title, [
            FrontendBreadcrumbs::viewCrumb( 'measurement-tests', $list_title ),
        ] );

        if ( ! $def ) {
            self::renderHeader( $title );
            echo '<p class="tt-notice">' . esc_html__( 'That test could not be found.', 'talenttrack' ) . '</p>';
            return;
        }

        self::renderHeader( $title . ' — ' . (string) $def->name );

        $value_type = (string) $def->value_type;
        $categories = QueryHelpers::get_lookups( 'measurement_category' );
        $units      = QueryHelpers::get_lookups( 'measurement_unit' );
        $age_groups = QueryHelpers::get_lookups( 'age_group' );

        // #3273 — the picker is fed from the unit registry, which is what
        // carries the dimension. The dimension is not a second field: two
        // pickers could contradict each other, and a unit already knows what
        // kind of quantity it measures.
        $units      = ( new UnitRegistry() )->all();
        $unit_names = array_map( static fn ( $r ) => (string) $r->symbol, $units );
        $unit       = (string) ( $def->unit ?? '' );
        $unit_listed = in_array( $unit, $unit_names, true );
        $is_duration = (string) ( $def->numeric_format ?? 'plain' ) === 'duration';

        $targets = [];
        foreach ( ( new MeasurementTargetsRepository() )->listForDefinition( $id ) as $t ) {
            $targets[ (string) $t->age_group ] = $t;
        }

        // Status-type tests carry operator-defined coloured levels instead
        // of target bands.
        $levels = $value_type === 'status'
            ? ( new MeasurementLevelsRepository() )->listForDefinition( $id )
            : [];

        // Cancel target: list view, unless tt_back captured a referrer.
        $list_url   = add_query_arg( [ 'tt_view' => 'measurement-tests' ], RecordLink::dashboardUrl() );
        $back       = BackLink::resolve();
        $cancel_url = $back !== null ? $back['url'] : $list_url;
        ?>
        <form method="post" class="tt-mt-form">
            <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
            <input type="hidden" name="op" value="edit" />
            <input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>" />

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-name"><?php esc_html_e( 'Name', 'talenttrack' ); ?></label>
                <input type="text" id="tt-mt-name" class="tt-input" name="test_name" maxlength="190" required
                       value="<?php echo esc_attr( (string) $def->name ); ?>" />
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-category"><?php esc_html_e( 'Category', 'talenttrack' ); ?></label>
                <select id="tt-mt-category" class="tt-input" name="category_id">
                    <option value="0"><?php esc_html_e( '— choose —', 'talenttrack' ); ?></option>
                    <?php foreach ( $categories as $c ) : ?>
                        <option value="<?php echo (int) $c->id; ?>"<?php selected( (int) $def->category_id, (int) $c->id ); ?>>
                            <?php echo esc_html( LookupTranslator::name( $c ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-value-type"><?php esc_html_e( 'Value type', 'talenttrack' ); ?></label>
                <select id="tt-mt-value-type" class="tt-input" name="value_type">
                    <?php foreach ( self::valueTypes() as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $value_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-unit"><?php esc_html_e( 'Unit', 'talenttrack' ); ?></label>
                <select id="tt-mt-unit" class="tt-input" name="unit">
                    <option value=""><?php esc_html_e( '— none —', 'talenttrack' ); ?></option>
                    <?php foreach ( $units as $u ) :
                        $uname = (string) $u->symbol; ?>
                        <option value="<?php echo esc_attr( $uname ); ?>"<?php selected( $unit_listed ? $unit : '', $uname ); ?>>
                            <?php echo esc_html( sprintf(
                                /* translators: 1: unit symbol, 2: the kind of quantity it measures */
                                __( '%1$s — %2$s', 'talenttrack' ),
                                $uname,
                                Dimensions::label( (string) $u->dimension )
                            ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="tt-field-hint"><?php esc_html_e( 'A unit from the list carries its dimension, so values are stored in a canonical base and can be compared and converted.', 'talenttrack' ); ?></p>
            </div>

            <?php
            // #3275 — directly under the unit picker, above the custom-unit
            // escape hatch. It modifies the unit the operator just chose, and
            // it used to render after a field most operators skip past — the
            // coach who reported this had the unit on `min — Tijd` and never
            // saw a toggle sitting below "Custom unit".
            ?>
            <div class="tt-field tt-field--check">
                <label class="tt-mt-check" for="tt-mt-duration">
                    <input type="checkbox" id="tt-mt-duration" name="numeric_format" value="duration"<?php checked( $is_duration ); ?> />
                    <span><?php esc_html_e( 'Enter and show as mm:ss', 'talenttrack' ); ?></span>
                </label>
                <p class="tt-field-hint"><?php esc_html_e( 'For a time test. A result is typed as 5:30 and reads back as 5:30; it is stored in seconds. Tests measured in minutes get this by default.', 'talenttrack' ); ?></p>
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-unit-custom"><?php esc_html_e( 'Custom unit (overrides the list)', 'talenttrack' ); ?></label>
                <input type="text" id="tt-mt-unit-custom" class="tt-input" name="unit_custom" maxlength="50"
                       value="<?php echo esc_attr( ! $unit_listed ? $unit : '' ); ?>"
                       placeholder="<?php esc_attr_e( 'e.g. watt/kg', 'talenttrack' ); ?>" />
                <p class="tt-field-hint"><?php esc_html_e( 'A custom unit has no dimension: its values are stored exactly as typed, and are never converted or compared across units.', 'talenttrack' ); ?></p>
            </div>

            <div class="tt-grid tt-grid-2">
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-mt-scale-min"><?php esc_html_e( 'Scale minimum', 'talenttrack' ); ?></label>
                    <input type="number" step="any" inputmode="decimal" id="tt-mt-scale-min" class="tt-input" name="scale_min"
                           value="<?php echo esc_attr( $def->scale_min !== null ? (string) $def->scale_min : '' ); ?>" />
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-mt-scale-max"><?php esc_html_e( 'Scale maximum', 'talenttrack' ); ?></label>
                    <input type="number" step="any" inputmode="decimal" id="tt-mt-scale-max" class="tt-input" name="scale_max"
                           value="<?php echo esc_attr( $def->scale_max !== null ? (string) $def->scale_max : '' ); ?>" />
                </div>
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-direction"><?php esc_html_e( 'Direction', 'talenttrack' ); ?></label>
                <select id="tt-mt-direction" class="tt-input" name="direction">
                    <?php foreach ( self::directions() as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( (string) $def->direction, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-mt-frequency"><?php esc_html_e( 'Recurrence', 'talenttrack' ); ?></label>
                <select id="tt-mt-frequency" class="tt-input" name="frequency">
                    <?php foreach ( self::frequencies() as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( (string) $def->frequency, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="tt-field tt-field--check">
                <label class="tt-mt-check" for="tt-mt-active">
                    <input type="checkbox" id="tt-mt-active" name="is_active" value="1"<?php checked( (int) $def->is_active, 1 ); ?> />
                    <span><?php esc_html_e( 'Active (available when recording results)', 'talenttrack' ); ?></span>
                </label>
            </div>

            <div class="tt-field tt-field--check">
                <label class="tt-mt-check" for="tt-mt-show-on-profile">
                    <input type="checkbox" id="tt-mt-show-on-profile" name="show_on_profile" value="1"<?php checked( (int) ( $def->show_on_profile ?? 1 ), 1 ); ?> />
                    <span><?php esc_html_e( 'Show this test’s results on the player profile', 'talenttrack' ); ?></span>
                </label>
            </div>

            <?php if ( $value_type !== 'passfail' && $value_type !== 'status' && ! empty( $age_groups ) ) : ?>
                <fieldset class="tt-mt-targets">
                    <legend><?php esc_html_e( 'Target bands per age group', 'talenttrack' ); ?></legend>
                    <p class="tt-mt-targets__hint">
                        <?php esc_html_e( 'Optional. The green band is on target and the amber band is a warning; anything outside flags red. Leave blank to skip an age group.', 'talenttrack' ); ?>
                    </p>
                    <?php
                    // #3273 — bands are stored canonically; they are typed and
                    // shown in the test's own unit.
                    $band_units = UnitContext::forDefinition( $def );
                    foreach ( $age_groups as $ag ) :
                        $name   = (string) $ag->name;
                        $label  = LookupTranslator::name( $ag );
                        $band   = $targets[ $name ] ?? null;
                    ?>
                        <div class="tt-mt-target-set">
                            <h3 class="tt-mt-target-set__label"><?php echo esc_html( $label ); ?></h3>
                            <div class="tt-grid tt-grid-2">
                                <?php
                                self::targetField( $name, 'green_min', __( 'Green from', 'talenttrack' ), $band, $band_units );
                                self::targetField( $name, 'green_max', __( 'Green to', 'talenttrack' ), $band, $band_units );
                                self::targetField( $name, 'amber_min', __( 'Amber from', 'talenttrack' ), $band, $band_units );
                                self::targetField( $name, 'amber_max', __( 'Amber to', 'talenttrack' ), $band, $band_units );
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>

            <?php if ( $value_type === 'status' ) : self::renderLevelsEditor( $levels ); endif; ?>

            <?php
            echo FormSaveButton::render( [
                'label'      => __( 'Save', 'talenttrack' ),
                'cancel_url' => $cancel_url,
            ] );
            ?>
        </form>
        <?php
        // Results export lives outside the edit form so its own POST to
        // admin-post.php never nests inside the save form. Mirrors the
        // per-row affordance — one test's results to a formatted .xlsx.
        echo '<div class="tt-mt-export">';
        echo '<h3 class="tt-mt-export__title">' . esc_html__( 'Export results', 'talenttrack' ) . '</h3>';
        echo '<p class="tt-mt-export__hint">'
            . esc_html__( 'Download every recorded result for this test as a formatted Excel workbook.', 'talenttrack' )
            . '</p>';
        self::renderExportForm( $id );
        echo '</div>';
    }

    /**
     * The "Export to Excel" affordance — a small POST form to admin-post.php
     * (`action=tt_export`) targeting the `measurement_results_xlsx` exporter
     * for one definition. admin-post.php bypasses theme chrome so the binary
     * .xlsx download isn't HTML-prefixed (#939). The export pipeline enforces
     * `measurements/read` server-side; this is the trigger only.
     *
     * Back-aware return: a failed export round-trips back to the current view
     * (the Manage-tests list), not the central Exports page.
     */
    private static function renderExportForm( int $definition_id ): void {
        if ( $definition_id <= 0 ) return;

        $return_url = BackLink::appendTo( add_query_arg(
            [ 'tt_view' => 'measurement-tests' ],
            RecordLink::dashboardUrl()
        ) );

        echo '<form method="POST" class="tt-mt-inline-form tt-mt-export-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'tt_export', '_tt_export_nonce' );
        echo '<input type="hidden" name="action" value="tt_export" />';
        echo '<input type="hidden" name="tt_export_key" value="measurement_results_xlsx" />';
        echo '<input type="hidden" name="format" value="xlsx" />';
        echo '<input type="hidden" name="definition_id" value="' . esc_attr( (string) $definition_id ) . '" />';
        echo '<input type="hidden" name="tt_export_return_url" value="' . esc_attr( $return_url ) . '" />';
        echo '<button type="submit" class="tt-btn tt-btn-secondary tt-mt-action tt-mt-action--export">'
            . esc_html__( 'Export Excel', 'talenttrack' ) . '</button>';
        echo '</form>';
    }

    /**
     * The status levels editor: an ordered set of { label, colour } rows the
     * operator adds / removes / reorders. Each row carries an existing level
     * id (so a save updates rather than duplicates) and a curated-palette
     * colour <select>. A blank starter row is always appended so the form is
     * usable on a brand-new status test. Row order is the saved ordinal
     * (worse → better), set server-side from the row position — no JS needed.
     *
     * @param array<int, object> $levels
     */
    private static function renderLevelsEditor( array $levels ): void {
        $palette = \TT\Modules\Measurements\Levels\MeasurementLevelPalette::labels();
        $rows    = $levels;
        $rows[]  = null; // a blank trailing row for adding a new level
        ?>
        <fieldset class="tt-mt-levels">
            <legend><?php esc_html_e( 'Status levels', 'talenttrack' ); ?></legend>
            <p class="tt-mt-levels__hint">
                <?php esc_html_e( 'List the status levels from lowest to highest. Each level shows as a coloured chip on the player profile. Clear a level’s label to remove it.', 'talenttrack' ); ?>
            </p>
            <ol class="tt-mt-levels__list">
                <?php $i = 0; foreach ( $rows as $lvl ) :
                    $lid   = $lvl ? (int) $lvl->id : 0;
                    $label = $lvl ? (string) $lvl->label : '';
                    $token = $lvl ? \TT\Modules\Measurements\Levels\MeasurementLevelPalette::safe( (string) $lvl->color_token ) : \TT\Modules\Measurements\Levels\MeasurementLevelPalette::DEFAULT_TOKEN;
                    $fid   = 'tt-mt-level-' . $i;
                ?>
                    <li class="tt-mt-level-row">
                        <input type="hidden" name="level[<?php echo (int) $i; ?>][id]" value="<?php echo esc_attr( (string) $lid ); ?>" />
                        <div class="tt-field tt-mt-level-row__label">
                            <label class="tt-field-label" for="<?php echo esc_attr( $fid ); ?>"><?php esc_html_e( 'Level', 'talenttrack' ); ?></label>
                            <input type="text" id="<?php echo esc_attr( $fid ); ?>" class="tt-input" maxlength="190"
                                   name="level[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $label ); ?>"
                                   placeholder="<?php esc_attr_e( 'e.g. On track', 'talenttrack' ); ?>" />
                        </div>
                        <div class="tt-field tt-mt-level-row__color">
                            <label class="tt-field-label" for="<?php echo esc_attr( $fid . '-c' ); ?>"><?php esc_html_e( 'Colour', 'talenttrack' ); ?></label>
                            <select id="<?php echo esc_attr( $fid . '-c' ); ?>" class="tt-input" name="level[<?php echo (int) $i; ?>][color_token]">
                                <?php foreach ( $palette as $key => $name ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $token, $key ); ?>><?php echo esc_html( $name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="tt-mlvl-swatch <?php echo esc_attr( \TT\Modules\Measurements\Levels\MeasurementLevelPalette::cssClass( $token ) ); ?>" aria-hidden="true"></span>
                        </div>
                    </li>
                <?php $i++; endforeach; ?>
            </ol>
        </fieldset>
        <?php
    }

    /**
     * #3273 — a band is stored canonically and typed in the test's own unit, so
     * an admin still writes 182 or 5:30 and never 1.82 or 330. The field takes
     * its control from the same unit context the entry grid uses.
     */
    private static function targetField( string $ag, string $key, string $label, ?object $band, UnitContext $units ): void {
        $fid  = 'tt-mt-band-' . sanitize_html_class( $ag . '-' . $key );
        $base = ( $band && isset( $band->{$key} ) && $band->{$key} !== null ) ? (float) $band->{$key} : null;
        $val  = $units->formatForInput( $base );

        $attrs = '';
        foreach ( $units->inputAttributes() as $k => $v ) {
            if ( $k === 'placeholder' ) continue; // a band has its own label
            $attrs .= esc_attr( $k ) . '="' . esc_attr( $v ) . '" ';
        }

        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="' . esc_attr( $fid ) . '">' . esc_html( $label ) . '</label>';
        echo '<input ' . $attrs . 'id="' . esc_attr( $fid ) . '" class="tt-input" ' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — every attribute escaped above
            . 'name="band[' . esc_attr( $ag ) . '][' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" />';
        echo '</div>';
    }

    // ── POST handling ───────────────────────────────────────────────

    /**
     * @return array{flash:string, back_to_list:bool}
     */
    private static function handlePost(): array {
        $op = isset( $_POST['op'] ) ? sanitize_key( (string) $_POST['op'] ) : '';
        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( $id <= 0 ) {
            return [ 'flash' => '', 'back_to_list' => false ];
        }

        $repo = new MeasurementDefinitionsRepository();
        if ( ! $repo->find( $id ) ) {
            return [ 'flash' => __( 'That test could not be found.', 'talenttrack' ), 'back_to_list' => true ];
        }

        switch ( $op ) {
            case 'toggle':
                $to = isset( $_POST['to'] ) ? (int) (bool) absint( $_POST['to'] ) : 0;
                $repo->update( $id, [ 'is_active' => $to ] );
                return [
                    'flash'        => $to === 1
                        ? __( 'Test activated.', 'talenttrack' )
                        : __( 'Test deactivated.', 'talenttrack' ),
                    'back_to_list' => true,
                ];

            case 'archive':
                $archived = ( new ArchiveRepository() )->archive( 'measurement_definition', [ $id ], get_current_user_id() );
                return [
                    'flash'        => $archived > 0
                        ? __( 'Test archived.', 'talenttrack' )
                        : __( 'That test could not be archived.', 'talenttrack' ),
                    'back_to_list' => true,
                ];

            case 'edit':
                self::saveEdit( $repo, $id );
                return [ 'flash' => __( 'Test saved.', 'talenttrack' ), 'back_to_list' => true ];
        }

        return [ 'flash' => '', 'back_to_list' => false ];
    }

    private static function saveEdit( MeasurementDefinitionsRepository $repo, int $id ): void {
        $name        = sanitize_text_field( wp_unslash( (string) ( $_POST['test_name'] ?? '' ) ) );
        $value_type  = self::safeIn( (string) ( $_POST['value_type'] ?? 'numeric' ), [ 'numeric', 'scale', 'passfail', 'status' ], 'numeric' );

        $unit_listed = sanitize_text_field( wp_unslash( (string) ( $_POST['unit'] ?? '' ) ) );
        $unit_custom = sanitize_text_field( wp_unslash( (string) ( $_POST['unit_custom'] ?? '' ) ) );
        $unit        = $unit_custom !== '' ? $unit_custom : $unit_listed;

        $direction = self::safeIn( (string) ( $_POST['direction'] ?? 'higher' ), [ 'higher', 'lower', 'neutral' ], 'higher' );
        $frequency = self::safeIn( (string) ( $_POST['frequency'] ?? 'adhoc' ), [ 'annual', 'biannual', 'quarterly', 'monthly', 'adhoc' ], 'adhoc' );

        $scale_min = self::numOrNull( $_POST['scale_min'] ?? '' );
        $scale_max = self::numOrNull( $_POST['scale_max'] ?? '' );

        // #3273 — resolve the symbol against the registry. A listed unit brings
        // its dimension with it; a custom string is dimensionless, and so is
        // any non-numeric test whatever was typed in the unit box.
        $unit_row  = $value_type === 'numeric' ? ( new UnitRegistry() )->bySymbol( $unit ) : null;
        $dimension = $unit_row ? (string) $unit_row->dimension : Dimensions::DIMENSIONLESS;

        // #3275 — on EDIT the checkbox is the operator's word, with one
        // exception: they just changed the unit.
        //
        // The box reflects the stored state on this form, so an unticked one
        // normally IS a decision and flipping it would be the silent override
        // the issue warns against. But switching a test to minutes is the
        // same statement as choosing minutes on a new test, and the operator
        // has no more reason to hunt for the toggle here than there. So the
        // default applies only when the unit actually changed.
        $existing     = $repo->find( $id );
        $unit_changed = $existing === null
            || (string) ( $existing->unit ?? '' ) !== $unit;

        if ( ! empty( $_POST['numeric_format'] ) && $dimension === Dimensions::TIME ) {
            $format = 'duration';
        } elseif ( $unit_changed ) {
            $format = MeasurementDefinitionsRepository::defaultNumericFormatFor( [
                'dimension' => $dimension,
                'unit'      => $unit,
            ] );
        } else {
            $format = 'plain';
        }

        $data = [
            'category_id' => isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0,
            'value_type'  => $value_type,
            'unit'        => $value_type === 'numeric' ? $unit : '',
            'dimension'      => $dimension,
            'entry_unit_id'  => $unit_row ? (int) $unit_row->id : null,
            'numeric_format' => $format,
            'scale_min'   => $scale_min,
            'scale_max'   => $scale_max,
            'direction'   => self::resolveDirection( $value_type, $direction ),
            'frequency'   => $frequency,
            'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
            'show_on_profile' => isset( $_POST['show_on_profile'] ) ? 1 : 0,
        ];
        if ( $name !== '' ) {
            $data['name'] = $name;
        }
        $repo->update( $id, $data );

        // Targets — only numeric / scale tests carry bands (not pass/fail
        // or status).
        if ( $value_type !== 'passfail' && $value_type !== 'status' && isset( $_POST['band'] ) && is_array( $_POST['band'] ) ) {
            $targets_repo = new MeasurementTargetsRepository();
            $bands        = wp_unslash( $_POST['band'] );

            // #3273 — the bands were typed in the unit the test was just saved
            // with, so the context is built from the *new* values rather than
            // the row on disk: changing a test's unit and its bands in one save
            // must not read the old unit back out of the database.
            $band_units = UnitContext::forDefinition( (object) [
                'unit'           => $data['unit'],
                'dimension'      => $dimension,
                'entry_unit_id'  => $data['entry_unit_id'],
                'numeric_format' => $format,
                'value_type'     => $value_type,
            ] );

            foreach ( $bands as $age_group => $row ) {
                if ( ! is_array( $row ) ) continue;
                $age_group = sanitize_text_field( (string) $age_group );
                if ( $age_group === '' ) continue;
                $targets_repo->upsert( $id, $age_group, [
                    'green_min' => self::bandToBase( $row['green_min'] ?? '', $band_units ),
                    'green_max' => self::bandToBase( $row['green_max'] ?? '', $band_units ),
                    'amber_min' => self::bandToBase( $row['amber_min'] ?? '', $band_units ),
                    'amber_max' => self::bandToBase( $row['amber_max'] ?? '', $band_units ),
                ] );
            }
        }

        // Status levels — replace the full ordered set from the editor rows.
        if ( $value_type === 'status' && isset( $_POST['level'] ) && is_array( $_POST['level'] ) ) {
            $raw_levels = wp_unslash( $_POST['level'] );
            $rows       = [];
            foreach ( $raw_levels as $row ) {
                if ( ! is_array( $row ) ) continue;
                $label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
                if ( $label === '' ) continue; // blank label removes / skips the row
                $rows[] = [
                    'id'          => isset( $row['id'] ) ? absint( $row['id'] ) : 0,
                    'label'       => $label,
                    'color_token' => sanitize_text_field( (string) ( $row['color_token'] ?? '' ) ),
                ];
            }
            ( new MeasurementLevelsRepository() )->replaceForDefinition( $id, $rows );
        }
    }

    // ── helpers ─────────────────────────────────────────────────────

    /**
     * A band bound as typed, in the test's unit, converted to the canonical
     * base. An unparseable bound is dropped rather than guessed: a target that
     * cannot be read is better absent than wrong, because a wrong one paints a
     * green flag on a player who has not earned it.
     *
     * @param mixed $raw
     */
    private static function bandToBase( $raw, UnitContext $units ): ?float {
        $raw = is_string( $raw ) ? trim( $raw ) : '';
        if ( $raw === '' ) return null;
        return $units->parse( $raw )['value'];
    }

    /**
     * @param mixed $raw
     * @return float|null
     */
    private static function numOrNull( $raw ): ?float {
        $raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';
        return ( $raw !== '' && is_numeric( $raw ) ) ? (float) $raw : null;
    }

    /**
     * @param string[] $allowed
     */
    private static function safeIn( string $value, array $allowed, string $fallback ): string {
        $value = sanitize_text_field( wp_unslash( $value ) );
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /**
     * The direction (higher / lower / neutral is better) a test saves.
     *
     * Direction is meaningful for any *ordered* value — a raw number or a
     * scale score — because both carry higher/lower-is-better semantics and
     * drive the green/amber target bands. Pass/fail and status tests have no
     * numeric ordering, so their direction is always neutral regardless of
     * what the (hidden-in-that-context) dropdown last held.
     *
     * Previously every non-`numeric` type was clamped to neutral, which
     * silently dropped the Richting an operator set on a *scale* test on
     * every save (#2195). Extracted so the round-trip is unit-testable
     * without a $_POST fixture.
     */
    public static function resolveDirection( string $value_type, string $direction ): string {
        $ordered = in_array( $value_type, [ 'numeric', 'scale' ], true );
        if ( ! $ordered ) {
            return 'neutral';
        }
        return in_array( $direction, [ 'higher', 'lower', 'neutral' ], true ) ? $direction : 'higher';
    }

    /** @return array<string, string> */
    private static function valueTypes(): array {
        return [
            'numeric'  => __( 'A number (with a unit)', 'talenttrack' ),
            'scale'    => __( 'A scale score', 'talenttrack' ),
            'passfail' => __( 'Pass / fail', 'talenttrack' ),
            'status'   => __( 'A status (coloured levels)', 'talenttrack' ),
        ];
    }

    /** @return array<string, string> */
    private static function directions(): array {
        return [
            'higher'  => __( 'Higher is better', 'talenttrack' ),
            'lower'   => __( 'Lower is better', 'talenttrack' ),
            'neutral' => __( 'Neither (just track it)', 'talenttrack' ),
        ];
    }

    /** @return array<string, string> */
    private static function frequencies(): array {
        return [
            'annual'    => __( 'Once a season', 'talenttrack' ),
            'biannual'  => __( 'Twice a season', 'talenttrack' ),
            'quarterly' => __( 'Four times a season', 'talenttrack' ),
            'monthly'   => __( 'Monthly', 'talenttrack' ),
            'adhoc'     => __( 'No fixed cadence', 'talenttrack' ),
        ];
    }

    private static function directionLabel( string $direction ): string {
        $map = self::directions();
        return $map[ $direction ] ?? '';
    }

    private static function frequencyLabel( string $frequency ): string {
        $map = self::frequencies();
        return $map[ $frequency ] ?? '';
    }

    /**
     * The "+ New test" entry point — launches the wizard. Only rendered
     * when the wizard is available; WizardEntryPoint returns the fallback
     * otherwise, which we suppress.
     */
    private static function renderNewTestButton(): void {
        if ( ! class_exists( '\TT\Shared\Wizards\WizardRegistry' )
             || ! \TT\Shared\Wizards\WizardRegistry::isAvailable( 'measurement' ) ) {
            return;
        }
        $url = \TT\Shared\Wizards\WizardEntryPoint::urlFor( 'measurement', '' );
        if ( $url === '' ) return;
        echo '<a class="tt-btn tt-btn-primary tt-mt-link" href="' . esc_url( $url ) . '">'
            . esc_html__( 'Add test', 'talenttrack' ) . '</a>';
    }

    private static function enqueueViewCss(): void {
        wp_enqueue_style(
            'tt-frontend-measurement-tests',
            TT_PLUGIN_URL . 'assets/css/frontend-measurement-tests.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
        wp_enqueue_style(
            'tt-frontend-measurement-levels',
            TT_PLUGIN_URL . 'assets/css/frontend-measurement-levels.css',
            [ 'tt-frontend-measurement-tests' ],
            TT_VERSION
        );
    }
}

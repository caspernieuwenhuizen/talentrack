<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Shared\Frontend\Components\FilterBar;

/**
 * #3291 — every filter group carries a modifier for its TYPE, not only for
 * its key.
 *
 * #2203 right-aligned the trailing utility controls with a rule written
 * against `.tt-filterbar__group--status`. That is a KEY modifier, and no
 * surface in the app uses `status` as a group key: the list tables and the
 * activities view pass `archived`, the alerts inbox passes `state`, and
 * `FrontendListTable::statusGroup()` passes the caller's own filter key. The
 * rule therefore matched nothing, on every surface, since it was written —
 * the `⋯` menu sat mid-row against its neighbour with empty space to its
 * right, reading as one more filter rather than a utility.
 *
 * The lesson the assertions below encode: a rule about what a group IS has
 * to key on the type, because the key belongs to the caller and will not
 * agree with it.
 */
final class FilterGroupTypeModifierTest extends WP_UnitTestCase {

    private function render(): string {
        return FilterBar::html( [
            'action' => '/',
            'method' => 'get',
            'groups' => [
                [
                    'type'  => 'select',
                    'key'   => 'team',
                    'name'  => 'team_id',
                    'label' => 'Team',
                    'value' => '',
                    // A select's options are a value => label MAP. The
                    // status and menu groups below take lists of arrays,
                    // which is the trap: passing a select the list shape
                    // renders `Array` into every option and raises a PHP
                    // warning that CI counts as a failure.
                    'options' => [ '' => 'All', '2' => 'Ajax U17' ],
                ],
                [
                    'type'    => 'status',
                    'key'     => 'state',
                    'label'   => 'State',
                    'param'   => 'state',
                    'options' => [ [ 'value' => 'open', 'label' => 'Open', 'url' => '/?state=open' ] ],
                ],
                [
                    'type'    => 'menu',
                    'key'     => 'archived',
                    'label'   => 'More',
                    'options' => [ [ 'value' => '1', 'label' => 'Archived', 'url' => '/?archived=1' ] ],
                ],
            ],
        ] );
    }

    /**
     * The two groups the right-align rule reaches for. Neither of them uses
     * `status` as its key, which is exactly why the old rule was dead.
     */
    public function test_status_and_menu_groups_carry_a_type_modifier(): void {
        $html = $this->render();

        $this->assertStringContainsString( 'tt-filterbar__group--t-status', $html );
        $this->assertStringContainsString( 'tt-filterbar__group--t-menu', $html );
    }

    /** The key modifier is still there — surfaces style individual groups by it. */
    public function test_the_key_modifier_is_kept_alongside_it(): void {
        $html = $this->render();

        $this->assertStringContainsString( 'tt-filterbar__group--state', $html );
        $this->assertStringContainsString( 'tt-filterbar__group--archived', $html );
        $this->assertStringContainsString( 'tt-filterbar__group--team', $html );
    }

    /** Every group gets one, not just the trailing pair. */
    public function test_an_ordinary_group_carries_its_type_too(): void {
        $this->assertStringContainsString( 'tt-filterbar__group--t-select', $this->render() );
    }

    /**
     * The alignment rule the modifier exists for must actually name it.
     *
     * Asserting the CSS rather than the markup alone is the point: the defect
     * was a selector that referred to a class nothing emitted, and a test
     * that only checked the markup would have passed throughout.
     */
    /**
     * The alignment rule must name something the markup actually emits.
     *
     * #3291's defect was a selector referring to a class nothing emitted, so
     * asserting the CSS — not just the markup — is the point of this test.
     *
     * #3319 moved the mechanism: the auto-margin is on the chips + Clear
     * cluster, which is the first block after the filters, and the trailing
     * utility groups are pushed right along with it. Two auto-margins on two
     * different flex containers is what put the chips to the right of the ⋯,
     * so there is now exactly one, on a container that is always present when
     * anything follows it. The per-group modifiers stay in the markup as
     * styling hooks, but they no longer carry alignment.
     */
    public function test_the_stylesheet_carries_exactly_one_alignment_margin(): void {
        $css = (string) file_get_contents(
            dirname( __DIR__, 2 ) . '/assets/css/frontend-filter-bar.css'
        );

        $this->assertStringContainsString( '.tt-dashboard .tt-filterbar__utils', $css );
        $this->assertStringContainsString( '.tt-dashboard .tt-filterbar__trailing', $css );

        // The old per-group auto-margins are gone: leaving them would push the
        // ⋯ away from the pills inside the trailing block.
        $this->assertDoesNotMatchRegularExpression(
            '/\.tt-filterbar__group--t-(status|menu)[^{]*\{[^}]*margin-left:\s*auto/',
            $css,
            'alignment belongs to the container now, not to the group'
        );
        $this->assertStringNotContainsString(
            '.tt-dashboard .tt-filterbar__group--status {',
            $css,
            'the dead key-modifier rule must not come back'
        );
    }

    /**
     * #3319 — the ⋯ is the LAST thing on the bar, chips and Clear included.
     *
     * The alignment fix put the utility groups at the end of the filter row;
     * the very next change added the chips + Clear cluster as a sibling
     * rendered after that row, and the form is a flex row, so the bar ended
     * "Clear, chips, ⋯". Both changes had tests. Neither asserted the two
     * blocks' relative order, which is the only place the defect lived.
     *
     * @return array<string,int>
     */
    private function domOrder( string $html ): array {
        $order = [];
        foreach ( [
            'row'      => '<div class="tt-filterbar__row">',
            'utils'    => '<div class="tt-filterbar__utils">',
            'trailing' => '<div class="tt-filterbar__trailing">',
            'status'   => 'tt-filterbar__group--t-status',
            'menu'     => 'tt-filterbar__group--t-menu',
        ] as $name => $needle ) {
            $at = strpos( $html, $needle );
            $this->assertNotFalse( $at, "expected $name in the rendered bar" );
            $order[ $name ] = (int) $at;
        }
        asort( $order ); // by position in the document, not by lookup order
        return $order;
    }

    public function test_the_overflow_menu_is_the_last_thing_on_the_bar(): void {
        $order = $this->domOrder( $this->renderWithChips() );

        $this->assertSame(
            [ 'row', 'utils', 'trailing', 'status', 'menu' ],
            array_keys( $order ),
            'filters, then chips + Clear, then the pills, then the ⋯ last'
        );
        $this->assertGreaterThan(
            $order['utils'],
            $order['menu'],
            'the ⋯ must render after the chips + Clear cluster, not before it'
        );
    }

    /**
     * And the caller cannot change that by declaring the menu first — the
     * old partition put both types in one bucket in caller order, so a bar
     * declaring the ⋯ before its status group rendered them the wrong way
     * round.
     */
    public function test_the_menu_sorts_after_status_whatever_order_the_caller_used(): void {
        $html = FilterBar::html( [
            'reset_url' => '/?reset=1',
            'groups'    => [
                [ 'type' => 'menu', 'key' => 'archived', 'label' => 'More',
                  'options' => [ [ 'value' => '1', 'label' => 'Archived', 'url' => '/?archived=1' ] ] ],
                [ 'type' => 'status', 'key' => 'state', 'label' => 'State', 'param' => 'state',
                  'options' => [ [ 'value' => 'open', 'label' => 'Open', 'url' => '/?state=open' ] ] ],
            ],
        ] );

        $status = strpos( $html, 'tt-filterbar__group--t-status' );
        $menu   = strpos( $html, 'tt-filterbar__group--t-menu' );
        $this->assertNotFalse( $status );
        $this->assertNotFalse( $menu );
        $this->assertGreaterThan( $status, $menu );
    }

    /**
     * #3334 — the sheet orders groups the way the inline row does.
     *
     * #3319 moved the utility groups to the end of the desktop row and left
     * the sheet rendering the caller's order, so the archive menu was last
     * on a laptop and first on a phone. The sheet is a single column, so
     * "last" is the bottom — where a control set once in a blue moon belongs
     * anyway.
     */
    public function test_the_sheet_orders_utility_groups_last_too(): void {
        $html = FilterBar::html( [
            'reset_url' => '/?reset=1',
            'groups'    => [
                // Declared FIRST, as the activities list declares its archive
                // menu — the case that made the two layouts disagree.
                [ 'type' => 'menu', 'key' => 'archived', 'label' => 'Archive',
                  'default_value' => 'active',
                  'options' => [
                      [ 'value' => 'active', 'label' => 'Active', 'url' => '/a', 'active' => true ],
                      [ 'value' => 'archived', 'label' => 'Archived', 'url' => '/b', 'active' => false ],
                  ] ],
                [ 'type' => 'select', 'key' => 'team', 'name' => 'team_id', 'label' => 'Team',
                  'selected' => '', 'options' => [ '2' => 'Ajax U17' ] ],
            ],
        ] );

        $start = strpos( $html, '<div class="tt-filter-sheet__body">' );
        $end   = strpos( $html, '</div>', (int) $start );
        $this->assertNotFalse( $start );
        $body = substr( $html, (int) $start, (int) $end - (int) $start + 200 );

        $team = strpos( $body, 'tt-filterbar__group--team' );
        $menu = strpos( $body, 'tt-filterbar__group--t-menu' );
        $this->assertNotFalse( $team, 'the filter group should be in the sheet' );
        $this->assertNotFalse( $menu, 'the utility group should be in the sheet' );
        $this->assertGreaterThan(
            $team,
            $menu,
            'the ⋯ menu must come after the filters in the sheet, as it does inline'
        );
    }

    /** The trailing block is inline chrome and hidden on a phone, like the row. */
    public function test_the_trailing_block_is_hidden_on_mobile(): void {
        $css = (string) file_get_contents(
            dirname( __DIR__, 2 ) . '/assets/css/frontend-filter-bar.css'
        );

        $this->assertMatchesRegularExpression(
            '/\.tt-filterbar__trailing\s*\{\s*\n\s*display:\s*none/',
            $css,
            'the trailing block must start hidden, so the sheet owns the groups on a phone'
        );
    }

    /**
     * A bar with no chips and no Clear has no cluster to carry the gap, so
     * the trailing block must pick it up — otherwise the pills and the ⋯
     * sit mid-row on exactly the surfaces with the fewest filters.
     *
     * The two selectors are adjacent-sibling on purpose: when the cluster IS
     * present it sits between the mobile block and the trailing one, so
     * neither matches and there is still only one auto-margin on the bar.
     */
    public function test_the_trailing_block_takes_the_gap_when_there_is_no_cluster(): void {
        $css = (string) file_get_contents(
            dirname( __DIR__, 2 ) . '/assets/css/frontend-filter-bar.css'
        );

        $this->assertStringContainsString(
            '.tt-filterbar__mobile + .tt-filterbar__trailing',
            $css
        );
    }

    /** A bar with chips renders all five blocks the order test needs. */
    private function renderWithChips(): string {
        return FilterBar::html( [
            'reset_url' => '/?reset=1',
            'groups'    => [
                [ 'type' => 'select', 'key' => 'team', 'name' => 'team_id', 'label' => 'Team',
                  'selected' => '2', 'options' => [ '' => 'All', '2' => 'Ajax U17' ] ],
                [ 'type' => 'status', 'key' => 'state', 'label' => 'State', 'param' => 'state',
                  'options' => [ [ 'value' => 'open', 'label' => 'Open', 'url' => '/?state=open' ] ] ],
                [ 'type' => 'menu', 'key' => 'archived', 'label' => 'More',
                  'options' => [ [ 'value' => '1', 'label' => 'Archived', 'url' => '/?archived=1' ] ] ],
            ],
        ] );
    }
}

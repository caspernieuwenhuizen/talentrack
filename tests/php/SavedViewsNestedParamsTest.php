<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Shared\Frontend\Components\SavedViews;

/**
 * #3327 — the saved-view control reads the filters that are actually set.
 *
 * `FilterBar::paramNames()` yields the FORM FIELD name, and every
 * `FrontendListTable` surface names its filters `filter[<key>]`. PHP never
 * creates a `$_GET['filter[team_id]']` key — `filter[team_id]=2` arrives as
 * `$_GET['filter']['team_id']` — so a flat lookup silently dropped every
 * nested filter and kept only the flat ones (`search`, `orderby`, `order`).
 *
 * Two consequences, both invisible until you tried to use the feature:
 *
 *  - Narrow a list with its dropdowns and the bookmark control was not
 *    rendered at all, because `$has_filters` was false and the reader had no
 *    saved views yet. There was no way to save the view you had just built.
 *    Typing into the search box made it appear, which is what made this look
 *    intermittent rather than broken.
 *  - A stored view containing `filter[team_id]` could never equal the live
 *    URL, so the "you are looking at this view" state never fired on a list.
 *
 * `saved-views.js` reads the raw query string through `URLSearchParams`,
 * where `filter[team_id]` survives intact — so the stored keys are bracketed
 * and the PHP side has to speak the same shape. These tests pin both halves
 * of that contract.
 */
final class SavedViewsNestedParamsTest extends WP_UnitTestCase {

    /** @var int */
    private $user;

    /** The players list: a registered key whose filters are all nested. */
    private const KEY = 'players-list';

    /** What `paramNames()` yields for that surface. */
    private const PARAMS = [ 'filter[team_id]', 'filter[position]', 'search', 'orderby', 'order' ];

    public function set_up(): void {
        parent::set_up();
        $this->user = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $this->user );
        $_GET = [];
    }

    public function tear_down(): void {
        $_GET = [];
        parent::tear_down();
    }

    private function render(): string {
        return SavedViews::html( self::KEY, self::PARAMS, '/', [ 'tt_view' => 'players' ] );
    }

    /** Does the render carry the bookmark control? */
    private function hasControl( string $html ): bool {
        return strpos( $html, 'tt-savedviews__trigger' ) !== false;
    }

    /**
     * The heart of it: a list narrowed by its dropdowns offers a way to save
     * that view.
     */
    public function test_a_nested_filter_alone_renders_the_save_control(): void {
        $_GET = [ 'tt_view' => 'players', 'filter' => [ 'team_id' => '2' ] ];

        $html = $this->render();
        if ( $html === '' && ! $this->canRender() ) {
            $this->markTestSkipped( 'Saved views not available for this key on this install.' );
        }

        $this->assertTrue(
            $this->hasControl( $html ),
            'a nested filter[...] param must count as "there is something to save"'
        );
    }

    /** The flat params kept working — they were never the broken half. */
    public function test_a_flat_param_alone_still_renders_the_save_control(): void {
        $_GET = [ 'tt_view' => 'players', 'search' => 'jansen' ];

        $html = $this->render();
        if ( $html === '' && ! $this->canRender() ) {
            $this->markTestSkipped( 'Saved views not available for this key on this install.' );
        }

        $this->assertTrue( $this->hasControl( $html ) );
    }

    /** Nothing set and nothing saved: no control, as designed. */
    public function test_an_untouched_list_renders_nothing(): void {
        $_GET = [ 'tt_view' => 'players' ];

        $this->assertSame( '', $this->render() );
    }

    /**
     * A repeated param arrives as an array. It is not a saved-view filter,
     * and casting it would print "Array" plus a PHP notice that CI counts as
     * a failure.
     */
    public function test_a_repeated_param_is_ignored_not_stringified(): void {
        $_GET = [ 'tt_view' => 'players', 'filter' => [ 'team_id' => [ '2', '3' ] ] ];

        $html = $this->render();

        $this->assertStringNotContainsString( 'Array', $html );
        $this->assertSame( '', $html, 'an array value is not a filter, so there is nothing to save' );
    }

    /** An empty nested value is "no filter", same as an absent one. */
    public function test_an_empty_nested_value_is_not_a_filter(): void {
        $_GET = [ 'tt_view' => 'players', 'filter' => [ 'team_id' => '' ] ];

        $this->assertSame( '', $this->render() );
    }

    /**
     * The comparison half: a stored view whose keys are bracketed matches the
     * live URL, so the control can report which view is applied.
     */
    public function test_a_stored_bracketed_view_matches_the_live_url(): void {
        // Through the repository, so the row is shaped exactly as the REST
        // save path writes it (uuid, club scope and all).
        $view = ( new \TT\Infrastructure\Filters\SavedViewsRepository() )->create(
            $this->user,
            self::KEY,
            'U17 league games',
            // Exactly the shape saved-views.js writes via URLSearchParams.
            [ 'filter[team_id]' => '2' ]
        );
        if ( $view === null ) {
            $this->markTestSkipped( 'saved filters table not present on this install.' );
        }

        $_GET = [ 'tt_view' => 'players', 'filter' => [ 'team_id' => '2' ] ];
        $html = $this->render();

        $this->assertStringContainsString( 'U17 league games', $html );
        $this->assertStringContainsString(
            'is-saved',
            $html,
            'the control must report that the live filters ARE a saved view'
        );
    }

    /** Whether this install can render the control at all, for skip logic. */
    private function canRender(): bool {
        return \TT\Infrastructure\Filters\SavedViewsRegistry::currentUserCan( self::KEY );
    }
}

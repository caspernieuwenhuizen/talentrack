<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Infrastructure\Filters\FilterParam;

/**
 * #3333 — reading and clearing a filter by its FORM FIELD NAME.
 *
 * `paramNames()` yields the name of the input that sets a filter, and every
 * `FrontendListTable` surface names its filters `filter[<key>]`. That string
 * is not a `$_GET` key and `remove_query_arg()` cannot remove it, and both
 * facts have now produced a shipped bug:
 *
 *  - #3327: `SavedViews` looked the bracketed name up flat, so narrowing a
 *    list with its dropdowns left no way to save the view.
 *  - #3333: a chip's ✕ was built with `remove_query_arg( 'filter[team_id]' )`,
 *    which removes nothing — latent only because the list surfaces passed
 *    their own chips and never reached derivation.
 *
 * Same defect, two layers. These tests pin the one implementation both use,
 * with the bracketed and the flat case asserted together — asserting only
 * the flat one is how it shipped twice.
 */
final class FilterParamNestedTest extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/dash/?tt_view=players';
    }

    public function tear_down(): void {
        $_GET = [];
        parent::tear_down();
    }

    /** The shape PHP actually builds from `filter[team_id]=2`. */
    private function seed(): void {
        $_GET = [
            'tt_view' => 'players',
            'filter'  => [ 'team_id' => '2', 'position' => 'GK' ],
            'search'  => 'jansen',
        ];
        $_SERVER['REQUEST_URI'] =
            '/dash/?tt_view=players&filter%5Bteam_id%5D=2&filter%5Bposition%5D=GK&search=jansen';
    }

    public function test_a_bracketed_name_reads_the_nested_value(): void {
        $this->seed();

        $this->assertSame( '2', FilterParam::requestValue( 'filter[team_id]' ) );
        $this->assertSame( 'GK', FilterParam::requestValue( 'filter[position]' ) );
    }

    public function test_a_flat_name_still_reads_flat(): void {
        $this->seed();

        $this->assertSame( 'jansen', FilterParam::requestValue( 'search' ) );
    }

    public function test_an_absent_or_empty_value_is_null(): void {
        $this->seed();

        $this->assertNull( FilterParam::requestValue( 'filter[foot]' ) );
        $this->assertNull( FilterParam::requestValue( 'orderby' ) );

        $_GET['filter']['team_id'] = '';
        $this->assertNull( FilterParam::requestValue( 'filter[team_id]' ) );
    }

    /**
     * A repeated param is not a single filter value. Casting the array would
     * print "Array" plus a notice CI counts as a failure.
     */
    public function test_an_array_value_is_null_not_stringified(): void {
        $_GET = [ 'filter' => [ 'team_id' => [ '2', '3' ] ] ];

        $this->assertNull( FilterParam::requestValue( 'filter[team_id]' ) );
    }

    /** The chip's ✕: drop one filter, keep the rest. */
    public function test_removing_a_bracketed_param_keeps_its_siblings(): void {
        $this->seed();

        $url = html_entity_decode( FilterParam::removeFromUrl( 'filter[team_id]' ) );

        $this->assertStringNotContainsString( 'team_id', $url );
        $this->assertStringContainsString( 'position', $url, 'the sibling filter must survive' );
        $this->assertStringContainsString( 'search=jansen', $url, 'unrelated params must survive' );
    }

    public function test_removing_a_flat_param_keeps_the_nested_ones(): void {
        $this->seed();

        $url = html_entity_decode( FilterParam::removeFromUrl( 'search' ) );

        $this->assertStringNotContainsString( 'search=jansen', $url );
        $this->assertStringContainsString( 'team_id', $url );
    }

    /**
     * Clearing the last filter in the bag drops the bag, so the URL matches
     * what Clear produces rather than trailing a stray `filter=`.
     */
    public function test_removing_the_last_nested_param_drops_the_parent(): void {
        $_GET = [ 'tt_view' => 'players', 'filter' => [ 'team_id' => '2' ] ];
        $_SERVER['REQUEST_URI'] = '/dash/?tt_view=players&filter%5Bteam_id%5D=2';

        $url = html_entity_decode( FilterParam::removeFromUrl( 'filter[team_id]' ) );

        $this->assertStringNotContainsString( 'filter', $url );
        $this->assertStringContainsString( 'tt_view=players', $url );
    }

    /** Removing something that is not set leaves the URL alone. */
    public function test_removing_an_absent_param_is_a_no_op(): void {
        $this->seed();

        $before = FilterParam::removeFromUrl( 'filter[foot]' );

        $this->assertStringContainsString( 'team_id', html_entity_decode( $before ) );
        $this->assertStringContainsString( 'search=jansen', html_entity_decode( $before ) );
    }
}

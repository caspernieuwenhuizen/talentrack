<?php
namespace TT\Infrastructure\Filters;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * FilterParam (#3333) — reading and clearing a filter param by its FORM
 * FIELD NAME.
 *
 * `FilterBar::paramNames()` yields the name of the input that sets a filter,
 * and every `FrontendListTable` surface names its filters `filter[<key>]`.
 * That string is not a `$_GET` key: PHP turns `filter[team_id]=2` into
 * `$_GET['filter']['team_id']`, and `remove_query_arg()` cannot remove a
 * nested key either. Both facts have now produced a shipped bug.
 *
 *   - #3327: `SavedViews::currentFilters()` looked the bracketed name up
 *     flat, so every nested filter was invisible to it. Narrowing a list
 *     with its dropdowns left the reader no way to save the view, and a
 *     stored view could never match the live URL.
 *   - #3333: `FilterBar::activeChips()` built a chip's ✕ target with
 *     `remove_query_arg( 'filter[team_id]' )`, which removes nothing. It was
 *     latent only because the fourteen list surfaces passed their own chips
 *     and never reached derivation — and retiring those is what makes it
 *     live.
 *
 * Same defect, two layers, so it lives in one place rather than a third
 * implementation. Both helpers accept a flat name unchanged, so a caller
 * never has to know which shape it is holding.
 */
final class FilterParam {

    /**
     * The current request's value for a form field name, or null.
     *
     * Null when the param is absent, empty, or an array — a repeated param
     * (`filter[x][]=a&filter[x][]=b`) is not a single filter value, and
     * casting one to string prints "Array" plus a notice CI counts as a
     * failure.
     */
    public static function requestValue( string $name ): ?string {
        $name = trim( $name );
        if ( $name === '' ) return null;

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        [ $parent, $child ] = self::split( $name );
        if ( $child !== null ) {
            $bag = $_GET[ $parent ] ?? null;
            $raw = is_array( $bag ) ? ( $bag[ $child ] ?? null ) : null;
        } else {
            $raw = $_GET[ $parent ] ?? null;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( ! is_scalar( $raw ) ) return null;

        $value = sanitize_text_field( wp_unslash( (string) $raw ) );
        return $value === '' ? null : $value;
    }

    /**
     * `$url` with one filter param removed, leaving every other param set.
     *
     * This is what a chip's ✕ points at. `remove_query_arg()` handles the
     * flat case and is used for it; a bracketed name needs the parent bag
     * rebuilt without that one key, and the parent dropped entirely when it
     * empties — so clearing the last filter yields the same clean URL that
     * Clear does, rather than a stray `filter=`.
     *
     * @param string|null $url defaults to the current request.
     */
    public static function removeFromUrl( string $name, ?string $url = null ): string {
        $name = trim( $name );
        $url  = $url ?? ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '' );
        if ( $name === '' ) return $url;

        [ $parent, $child ] = self::split( $name );
        if ( $child === null ) {
            return (string) remove_query_arg( $parent, $url );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bag = $_GET[ $parent ] ?? null;
        if ( ! is_array( $bag ) || ! array_key_exists( $child, $bag ) ) {
            return $url;
        }

        unset( $bag[ $child ] );
        if ( $bag === [] ) {
            return (string) remove_query_arg( $parent, $url );
        }

        // Rebuild the whole bag: add_query_arg() replaces the named key
        // outright, so passing the reduced array is what drops the child.
        return (string) add_query_arg( [ $parent => array_map( 'strval', $bag ) ], $url );
    }

    /**
     * `filter[team_id]` → `['filter', 'team_id']`; `search` → `['search', null]`.
     *
     * One level only. Nothing in the bar's vocabulary nests deeper, and
     * accepting `a[b][c]` here would promise a lookup the rest of this class
     * does not implement.
     *
     * @return array{0:string, 1:string|null}
     */
    private static function split( string $name ): array {
        if ( preg_match( '/^([^\[\]]+)\[([^\[\]]+)\]$/', $name, $m ) ) {
            return [ $m[1], $m[2] ];
        }
        return [ $name, null ];
    }
}

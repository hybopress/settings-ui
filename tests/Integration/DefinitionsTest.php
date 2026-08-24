<?php

declare(strict_types = 1);

it( 'orders tabs by their earliest control', function (): void {
    $definitions = definitions( [
        'b' => [
            'type'     => 'text',
            'tab'      => 'second',
            'priority' => 20,
        ],
        'a' => [
            'type'     => 'text',
            'tab'      => 'first',
            'priority' => 5,
        ],
    ] );

    expect( $definitions->tabs() )->toBe( [ 'first', 'second' ] );
} );

/**
 * A section sorts by its earliest control, so declaring one high-priority
 * control lifts its whole section.
 */
it( 'orders sections by their earliest control', function (): void {
    $definitions = definitions( [
        'late'  => [
            'type'     => 'text',
            'section'  => 'b',
            'priority' => 30,
        ],
        'early' => [
            'type'     => 'text',
            'section'  => 'a',
            'priority' => 20,
        ],
        'first' => [
            'type'     => 'text',
            'section'  => 'b',
            'priority' => 10,
        ],
    ] );

    expect( array_keys( $definitions->forTab( 'general' ) ) )->toBe( [ 'b', 'a' ] );
} );

it( 'orders controls within a section by priority', function (): void {
    $definitions = definitions( [
        'last'  => [
            'type'     => 'text',
            'priority' => 30,
        ],
        'first' => [
            'type'     => 'text',
            'priority' => 10,
        ],
    ] );

    expect( array_keys( $definitions->forTab( 'general' )['default'] ) )->toBe( [ 'first', 'last' ] );
} );

it( 'keeps declaration order for equal priorities', function (): void {
    $definitions = definitions( [
        'one'   => [ 'type' => 'text' ],
        'two'   => [ 'type' => 'text' ],
        'three' => [ 'type' => 'text' ],
    ] );

    expect( array_keys( $definitions->forTab( 'general' )['default'] ) )
        ->toBe( [ 'one', 'two', 'three' ] );
} );

it( 'ignores a declaration that is not an array', function (): void {
    expect( definitions( [ 'broken' => 'nope' ] )->all() )->toBeEmpty();
} );

it( 'humanises a tab label that config does not name', function (): void {
    expect( definitions( [] )->tabLabel( 'site_identity' ) )->toBe( 'Site Identity' );
} );

it( 'reads a tab label from config', function (): void {
    expect( definitions( [], [ 'tabs' => [ 'general' => 'Allgemein' ] ] )->tabLabel( 'general' ) )
        ->toBe( 'Allgemein' );
} );

it( 'resolves a closure tab label', function (): void {
    expect( definitions( [], [ 'tabs' => [ 'general' => static fn() => 'Lazy' ] ] )->tabLabel( 'general' ) )
        ->toBe( 'Lazy' );
} );

it( 'reads a section label from config', function (): void {
    expect( definitions( [], [ 'sections' => [ 'identity' => 'Identity' ] ] )->sectionLabel( 'identity' ) )
        ->toBe( 'Identity' );
} );

it( 'lists the control types on a tab', function (): void {
    $definitions = definitions( [
        'a' => [
            'type' => 'media',
            'tab'  => 'brand',
        ],
        'b' => [
            'type' => 'text',
            'tab'  => 'brand',
        ],
        'c' => [
            'type' => 'text',
            'tab'  => 'brand',
        ],
    ] );

    expect( $definitions->typesForTab( 'brand' ) )->toBe( [ 'media', 'text' ] );
} );

/**
 * A declaration may be a closure, so a set of controls can be built from data
 * that does not exist at config-load time -- roles, post types, sizes.
 *
 * This is what replaces a dedicated "group" control type: a computed set is
 * still just controls, so storage and sanitizing stay one key per control.
 */
it( 'expands a closure declaration into one control', function (): void {
    expect( array_keys( definitions( [ 'a' => static fn(): array => [ 'type' => 'text' ] ] )->all() ) )
        ->toBe( [ 'a' ] );
} );

it( 'expands a closure declaration into many controls', function (): void {
    $definitions = definitions( [
        'roles' => static fn(): array => [
            'role_admin'  => [ 'type' => 'checkbox' ],
            'role_editor' => [ 'type' => 'checkbox' ],
        ],
    ] );

    expect( array_keys( $definitions->all() ) )->toBe( [ 'role_admin', 'role_editor' ] );
} );

it( 'expands a literal map of declarations', function (): void {
    $definitions = definitions( [
        'group' => [
            'x' => [ 'type' => 'text' ],
            'y' => [ 'type' => 'text' ],
        ],
    ] );

    expect( array_keys( $definitions->all() ) )->toBe( [ 'x', 'y' ] );
} );

it( 'keeps an expanded control on its own tab', function (): void {
    $definitions = definitions( [
        'group' => static fn(): array => [
            'x' => [
                'type' => 'text',
                'tab'  => 'advanced',
            ],
        ],
    ] );

    expect( $definitions->all()['x']->tab() )->toBe( 'advanced' );
} );

it( 'gates expanded controls on their capability', function (): void {
    $definitions = definitions( [
        'group' => static fn(): array => [
            'on'  => [
                'type'    => 'checkbox',
                'feature' => 'shipped',
            ],
            'off' => [
                'type'    => 'checkbox',
                'feature' => 'never',
            ],
        ],
    ], [ 'features' => [ 'shipped' => true ] ] );

    expect( array_keys( $definitions->all() ) )->toBe( [ 'on' ] );
} );

it( 'lets a preset hide an expanded control', function (): void {
    $definitions = definitions( [
        'group' => static fn(): array => [
            'x' => [ 'type' => 'text' ],
            'y' => [ 'type' => 'text' ],
        ],
    ], [
        'presets' => [
            'active'  => 'minimal',
            'minimal' => [ 'hidden' => [ 'y' ] ],
        ],
    ] );

    expect( array_keys( $definitions->all() ) )->toBe( [ 'x' ] );
} );

/**
 * A bad entry is skipped, not thrown. Declarations are config, and one typo
 * should not take the whole screen down.
 */
it( 'skips a declaration that is not an array', function ( mixed $declaration ): void {
    expect( definitions( [ 'a' => $declaration ] )->all() )->toBe( [] );
} )->with( [
    'scalar'          => 'nope',
    'closure to junk' => [ static fn(): string => 'nope' ],
    'empty'           => [ [] ],
] );

/**
 * One file per section nests the computed sets a level below the top of
 * `controls`, so expansion has to recurse. A single-level expansion drops the
 * nested closure silently, which is the worst failure available: the screen
 * renders, just without those controls.
 */
it( 'expands a closure nested inside a map', function (): void {
    $definitions = definitions( [
        'revisions' => [
            'revisions.disable' => [ 'type' => 'checkbox' ],
            'revisions.limits'  => static fn(): array => [
                'revisions.limit_post' => [ 'type' => 'text' ],
                'revisions.limit_page' => [ 'type' => 'text' ],
            ],
        ],
    ] );

    expect( array_keys( $definitions->all() ) )
        ->toBe( [ 'revisions.disable', 'revisions.limit_post', 'revisions.limit_page' ] );
} );

it( 'expands maps nested several levels deep', function (): void {
    $definitions = definitions( [
        'outer' => [
            'inner' => [
                'deep' => [ 'type' => 'text' ],
            ],
        ],
    ] );

    expect( array_keys( $definitions->all() ) )->toBe( [ 'deep' ] );
} );

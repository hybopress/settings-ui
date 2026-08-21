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

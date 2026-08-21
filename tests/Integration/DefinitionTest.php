<?php

declare(strict_types = 1);

it( 'requires a type', function (): void {
    expect( fn() => definition( [ 'label' => 'No type' ] ) )
        ->toThrow( InvalidArgumentException::class );
} );

it( 'falls back to the key when no label is declared', function (): void {
    expect( definition( [ 'type' => 'text' ], 'site_title' )->label() )->toBe( 'site_title' );
} );

/**
 * Labels must be able to defer translation until after init, so every
 * declared key resolves closures on read.
 */
it( 'resolves a closure label', function (): void {
    expect( definition( [
        'type'  => 'text',
        'label' => static fn() => 'Lazy label',
    ] )->label() )->toBe( 'Lazy label' );
} );

it( 'resolves closure choices', function (): void {
    expect( definition( [
        'type'    => 'select',
        'choices' => static fn() => [ 'a' => 'A' ],
    ] )->choices() )->toBe( [ 'a' => 'A' ] );
} );

it( 'defaults tab, section and priority', function (): void {
    $definition = definition( [ 'type' => 'text' ] );

    expect( $definition->tab() )->toBe( 'general' )
        ->and( $definition->section() )->toBe( 'default' )
        ->and( $definition->priority() )->toBe( 10 );
} );

it( 'declares no feature by default', function (): void {
    expect( definition( [ 'type' => 'text' ] )->feature() )->toBe( '' );
} );

it( 'reports whether a value is an offered choice', function (): void {
    $definition = definition( [
        'type'    => 'select',
        'choices' => [ 'a' => 'A' ],
    ] );

    expect( $definition->allows( 'a' ) )->toBeTrue()
        ->and( $definition->allows( 'zz' ) )->toBeFalse();
} );

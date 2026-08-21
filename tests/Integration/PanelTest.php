<?php

declare(strict_types = 1);

/**
 * @return array<string, array<string, mixed>>
 */
function panelControls(): array {
    return [
        'site_title' => [
            'type'    => 'text',
            'tab'     => 'general',
            'section' => 'identity',
        ],
        'tagline'    => [
            'type'    => 'text',
            'tab'     => 'general',
            'section' => 'identity',
        ],
        'per_page'   => [
            'type' => 'number',
            'tab'  => 'reading',
            'min'  => 1,
            'max'  => 50,
        ],
        'align'      => [
            'type'    => 'radio',
            'tab'     => 'reading',
            'choices' => [ 'left' => 'Left' ],
        ],
        'classic'    => [
            'type' => 'checkbox',
            'tab'  => 'reading',
        ],
    ];
}

it( 'names inputs as an array under the option', function (): void {
    expect( panel( panelControls() )->name( 'site_title' ) )->toBe( 'child_settings[site_title]' );
} );

it( 'renders a control with its description', function (): void {
    $panel = panel( [
        'site_title' => [
            'type'        => 'text',
            'description' => 'Shown in the browser tab.',
        ],
    ] );

    expect( $panel->control( $panel->definitions()->get( 'site_title' ) ) )
        ->toContain( 'Shown in the browser tab.' );
} );

/**
 * An unchecked box posts no key at all, which is indistinguishable from a
 * key absent because it lives on another tab. A companion hidden input
 * removes the ambiguity.
 */
it( 'emits a companion hidden input for controls that post nothing when empty', function (): void {
    $panel = panel( panelControls() );

    expect( $panel->control( $panel->definitions()->get( 'classic' ) ) )
        ->toContain( '<input type="hidden" name="child_settings[classic]" value="0">' );
} );

it( 'emits no companion input for a control that always posts', function (): void {
    $panel = panel( panelControls() );

    expect( $panel->control( $panel->definitions()->get( 'site_title' ) ) )
        ->not->toContain( 'type="hidden"' );
} );

// ---------------------------------------------------------------
// Merge on save
// ---------------------------------------------------------------

it( 'sanitizes a submitted value', function (): void {
    expect( panel( panelControls() )->sanitize( [ 'site_title' => ' Hi <b>x</b> ' ] ) )
        ->toBe( [ 'site_title' => 'Hi x' ] );
} );

/**
 * One option backs every tab, so a submission from one tab must not wipe
 * the keys belonging to the others.
 */
it( 'leaves keys absent from the submission untouched', function (): void {
    update_option( TEST_OPTION, [
        'site_title' => 'Kept',
        'per_page'   => 25,
    ] );

    expect( panel( panelControls() )->sanitize( [ 'tagline' => 'New' ] ) )->toBe( [
        'site_title' => 'Kept',
        'per_page'   => 25,
        'tagline'    => 'New',
    ] );
} );

it( 'keeps the stored value when a control rejects the submission', function (): void {
    update_option( TEST_OPTION, [ 'per_page' => 25 ] );

    expect( panel( panelControls() )->sanitize( [ 'per_page' => 999 ] ) )->toBe( [ 'per_page' => 25 ] );
} );

it( 'keeps the stored value when a choice was not offered', function (): void {
    update_option( TEST_OPTION, [ 'align' => 'left' ] );

    expect( panel( panelControls() )->sanitize( [ 'align' => 'nope' ] ) )->toBe( [ 'align' => 'left' ] );
} );

/**
 * Only declared controls are considered, so a crafted request cannot reach
 * anything that is not on a form.
 */
it( 'ignores submitted keys that are not declared controls', function (): void {
    expect( panel( panelControls() )->sanitize( [
        'site_title' => 'Fine',
        'is_admin'   => true,
        'arbitrary'  => 'nope',
    ] ) )->toBe( [ 'site_title' => 'Fine' ] );
} );

it( 'ignores a submitted key whose control is hidden', function (): void {
    $panel = panel( panelControls(), [
        'presets' => [
            'active' => 'p',
            'p'      => [ 'hidden' => [ 'tagline' ] ],
        ],
    ] );

    expect( $panel->sanitize( [ 'tagline' => 'sneaky' ] ) )->toBeEmpty();
} );

it( 'survives a non-array submission', function (): void {
    expect( panel( panelControls() )->sanitize( 'not an array' ) )->toBe( [] );
} );

it( 'accepts a value at the edge of its declared range', function (): void {
    expect( panel( panelControls() )->sanitize( [ 'per_page' => 50 ] ) )->toBe( [ 'per_page' => 50 ] );
} );

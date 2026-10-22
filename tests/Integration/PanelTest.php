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

it( 'keeps authored markup in helper text', function (): void {
    $panel = panel( [
        'site_title' => [
            'type'         => 'text',
            'before_field' => '<strong>Careful.</strong>',
            'after_field'  => '<code>&lt;title&gt;</code>',
            'description'  => 'See <a href="https://example.com">the docs</a>.',
        ],
    ] );

    expect( $panel->control( $panel->definitions()->get( 'site_title' ) ) )
        ->toContain( '<strong>Careful.</strong>' )
        ->toContain( '<code>&lt;title&gt;</code>' )
        ->toContain( '<a href="https://example.com">the docs</a>' );
} );

it( 'strips script from helper text', function (): void {
    $panel = panel( [
        'site_title' => [
            'type'        => 'text',
            'description' => 'Safe<script>alert(1)</script>',
        ],
    ] );

    expect( $panel->control( $panel->definitions()->get( 'site_title' ) ) )
        ->toContain( 'Safe' )
        ->not->toContain( '<script>' );
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

it( 'renders after_field text inline with the control', function (): void {
    $html = panel( [
        'flag' => [
            'type'        => 'checkbox',
            'after_field' => 'Trailing note',
        ],
    ] )
        ->control( definition( [
            'type'        => 'checkbox',
            'after_field' => 'Trailing note',
        ], 'flag' ) );

    expect( $html )->toContain( 'hbp-after-field' )->toContain( 'Trailing note' );
} );

it( 'escapes after_field text', function (): void {
    $html = panel( [ 'flag' => [ 'type' => 'text' ] ] )
        ->control( definition( [
            'type'        => 'text',
            'after_field' => '<script>x</script>',
        ], 'flag' ) );

    expect( $html )->not->toContain( '<script>' );
} );

it( 'wraps a control declaring events and carries the rules as data', function (): void {
    $html = panel( [ 'flag' => [ 'type' => 'checkbox' ] ] )->control( definition( [
        'type'   => 'checkbox',
        'events' => [ 'true' => [ 'show' => '.dependent-wrap' ] ],
    ], 'flag' ) );

    expect( $html )
        ->toContain( 'data-hbp-events' )
        ->toContain( 'dependent-wrap' );
} );

it( 'leaves a control with no events unwrapped', function (): void {
    $html = panel( [ 'flag' => [ 'type' => 'checkbox' ] ] )
        ->control( definition( [ 'type' => 'checkbox' ], 'flag' ) );

    expect( $html )->not->toContain( 'data-hbp-events' );
} );

/**
 * An html control is markup, not a setting. A crafted post carrying its key
 * must not write through it.
 */
it( 'never stores through an html control', function (): void {
    update_option( TEST_OPTION, [ 'notice' => 'original' ] );

    $stored = panel( [
        'notice' => [
            'type'    => 'html',
            'content' => '<p>Careful.</p>',
        ],
    ] )
        ->sanitize( [ 'notice' => 'injected' ] );

    expect( $stored['notice'] )->toBe( 'original' );
} );

it( 'emits no companion input for an html control', function (): void {
    $html = panel( [
        'notice' => [
            'type'    => 'html',
            'content' => '<p>Careful.</p>',
        ],
    ] )
        ->control( definition( [
            'type'    => 'html',
            'content' => '<p>Careful.</p>',
        ], 'notice' ) );

    expect( $html )->not->toContain( 'type="hidden"' )->toContain( '<p>Careful.</p>' );
} );

it( 'resolves html content declared as a closure', function (): void {
    $html = panel( [ 'notice' => [ 'type' => 'html' ] ] )->control( definition( [
        'type'    => 'html',
        'content' => static fn(): string => '<p>Lazy.</p>',
    ], 'notice' ) );

    expect( $html )->toContain( '<p>Lazy.</p>' );
} );

/**
 * The class on a control's row and the selector an event points at used to be
 * two hand-written strings that had to match. Nothing enforced it, and a typo
 * failed silently -- the rule simply never fired.
 *
 * Both now derive from the control key, so this asserts the property itself:
 * the selector an event resolves to IS the class the target control carries.
 */
it( 'resolves an event target to the class its control actually carries', function (): void {
    $target = definition( [ 'type' => 'textarea' ], 'backend.self_ping_urls' );

    $html = panel( [ 'backend.flag' => [ 'type' => 'checkbox' ] ] )->control( definition( [
        'type'   => 'checkbox',
        'events' => [ 'true' => [ 'show' => 'backend.self_ping_urls' ] ],
    ], 'backend.flag' ) );

    expect( $html )->toContain( '.' . $target->containerClass() );
} );

it( 'derives a row class from the control key', function (): void {
    expect( definition( [ 'type' => 'text' ], 'backend.self_ping_urls' )->containerClass() )
        ->toBe( 'hbp-control-backend-self-ping-urls' );
} );

it( 'lets a declared container-class win over the derived one', function (): void {
    expect( definition( [
        'type'            => 'text',
        'container-class' => 'legacy-wrap',
    ], 'x' )->containerClass() )
        ->toBe( 'legacy-wrap' );
} );

it( 'resolves a list of event targets', function (): void {
    $html = panel( [ 'flag' => [ 'type' => 'checkbox' ] ] )->control( definition( [
        'type'   => 'checkbox',
        'events' => [ 'all' => [ 'hide' => [ 'a.b', 'c.d' ] ] ],
    ], 'flag' ) );

    expect( $html )->toContain( 'hbp-control-a-b' )->toContain( 'hbp-control-c-d' );
} );

/**
 * A rule may still point at markup this package did not render, so anything
 * already written as a selector is passed through untouched.
 */
it( 'passes a raw selector through untouched', function ( string $selector ): void {
    $html = panel( [ 'flag' => [ 'type' => 'checkbox' ] ] )->control( definition( [
        'type'   => 'checkbox',
        'events' => [ 'true' => [ 'show' => $selector ] ],
    ], 'flag' ) );

    expect( $html )->toContain( $selector )->not->toContain( 'hbp-control-' );
} )->with( [
    'class' => '.legacy-wrap',
    'id'    => '#thing',
] );

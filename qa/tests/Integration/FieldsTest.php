<?php

declare(strict_types = 1);

use HBP\Settings\Ui\Fields;

it( 'knows every built-in type', function ( string $type ): void {
    expect( ( new Fields )->has( $type ) )->toBeTrue();
} )->with( [
    'text',
    'url',
    'email',
    'textarea',
    'number',
    'checkbox',
    'toggle',
    'select',
    'radio',
    'multicheckbox',
    'radio_image',
    'media',
    'sortable',
    'multiselect',
    'html',
] );

it( 'rejects an unknown type', function (): void {
    expect( fn() => ( new Fields )->get( 'nope' ) )->toThrow( InvalidArgumentException::class );
} );

// ---------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------

it( 'renders a text input with its value', function (): void {
    expect( render( [ 'type' => 'text' ], 'Hello' ) )
        ->toContain( 'type="text"' )
        ->toContain( 'value="Hello"' )
        ->toContain( 'name="opt[field]"' );
} );

/**
 * Text and Number once appended their own attributes by string
 * concatenation, bypassing the escaped attribute builder. Declared extras
 * must come through the one escaping path.
 */
it( 'renders declared extra attributes through the escaped builder', function (): void {
    expect( render( [
        'type' => 'number',
        'min'  => 1,
        'max'  => 50,
        'step' => 5,
    ], 10 ) )
        ->toContain( 'min="1"' )
        ->toContain( 'max="50"' )
        ->toContain( 'step="5"' );
} );

it( 'escapes a value containing markup', function (): void {
    expect( render( [ 'type' => 'text' ], '"><script>alert(1)</script>' ) )
        ->not->toContain( '<script>' );
} );

it( 'escapes an attribute containing a quote', function (): void {
    expect( render( [
        'type'        => 'text',
        'placeholder' => 'say "hi"',
    ] ) )
        ->not->toContain( 'placeholder="say "hi""' );
} );

it( 'omits attributes that are not declared', function (): void {
    expect( render( [ 'type' => 'text' ] ) )->not->toContain( 'placeholder' );
} );

it( 'renders a boolean attribute without a value', function (): void {
    expect( render( [
        'type'     => 'text',
        'required' => true,
    ] ) )->toContain( ' required' );
} );

it( 'skips a boolean attribute that is false', function (): void {
    expect( render( [
        'type'     => 'text',
        'required' => false,
    ] ) )->not->toContain( 'required' );
} );

it( 'checks a checked checkbox', function (): void {
    expect( render( [ 'type' => 'checkbox' ], true ) )->toContain( 'checked' );
} );

it( 'leaves an unchecked checkbox unchecked', function (): void {
    expect( render( [ 'type' => 'checkbox' ], false ) )->not->toContain( 'checked' );
} );

it( 'marks the selected option', function (): void {
    expect( render( [
        'type'    => 'select',
        'choices' => [
            'a' => 'A',
            'b' => 'B',
        ],
    ], 'b' ) )
        ->toContain( '<option value="b" selected' );
} );

it( 'marks the selected radio', function (): void {
    expect( render( [
        'type'    => 'radio',
        'choices' => [
            'a' => 'A',
            'b' => 'B',
        ],
    ], 'b' ) )
        ->toContain( 'value="b" checked' );
} );

it( 'ticks the stored entries of a multicheckbox', function (): void {
    $html = render( [
        'type'    => 'multicheckbox',
        'choices' => [
            'a' => 'A',
            'b' => 'B',
        ],
    ], [ 'b' ] );

    expect( $html )->toContain( 'value="b" checked' )->and( $html )->toContain( 'name="opt[field][]"' );
} );

it( 'skips a radio_image choice with no image', function (): void {
    $html = render( [
        'type'    => 'radio_image',
        'choices' => [
            'one'  => [
                'label' => 'One',
                'url'   => '/1.png',
            ],
            'none' => [ 'label' => 'No image' ],
        ],
    ] );

    expect( $html )->toContain( 'value="one"' )->and( $html )->not->toContain( 'value="none"' );
} );

it( 'renders a media preview for a stored attachment', function (): void {
    // No attachment 42 exists in the test database, so wp_get_attachment_image_url()
    // would return an empty string and the preview would render without a src.
    // Stand in for the lookup, and drop the filter once it has fired: a rollback
    // restores rows but leaves hooks in place, and a filter left here would fake
    // an image for every later test that renders a media control.
    $src = static function ( $image ) use ( &$src ) {
        remove_filter( 'wp_get_attachment_image_src', $src, 10 );

        return [ 'https://example.test/img-42.jpg', 100, 100, true ];
    };

    add_filter( 'wp_get_attachment_image_src', $src, 10 );

    expect( render( [ 'type' => 'media' ], 42 ) )->toContain( 'img-42.jpg' );
} );

it( 'hides the media preview when nothing is stored', function (): void {
    expect( render( [ 'type' => 'media' ], 0 ) )->toContain( 'hidden' );
} );

/**
 * A sortable renders enabled items first in their stored order, then the
 * rest unticked, so the control shows both what is on and what order it is
 * in.
 */
it( 'orders sortable items by stored order then declaration order', function (): void {
    $html = render(
        [
            'type'    => 'sortable',
            'choices' => [
                'x'  => 'X',
                'fb' => 'FB',
                'ig' => 'IG',
            ],
        ],
        [ 'ig', 'x' ]
    );

    preg_match_all( '/value="([a-z]+)"/', $html, $matches );

    expect( $matches[1] )->toBe( [ 'ig', 'x', 'fb' ] );
} );

// ---------------------------------------------------------------
// Sanitizing
// ---------------------------------------------------------------

it( 'strips markup from text', function (): void {
    expect( sanitize( [ 'type' => 'text' ], ' Hi <b>x</b> ' ) )->toBe( 'Hi x' );
} );

it( 'casts a checkbox to a boolean', function (): void {
    expect( sanitize( [ 'type' => 'checkbox' ], '1' ) )->toBeTrue()
        ->and( sanitize( [ 'type' => 'checkbox' ], '0' ) )->toBeFalse();
} );

it( 'casts a whole number to an int', function (): void {
    expect( sanitize( [ 'type' => 'number' ], '42' ) )->toBe( 42 );
} );

it( 'casts a decimal to a float', function (): void {
    expect( sanitize( [ 'type' => 'number' ], '4.5' ) )->toBe( 4.5 );
} );

/**
 * Out-of-range values are rejected rather than clamped: a control posting
 * something its own declaration disallows is a bug, and quietly storing a
 * nearby value hides it.
 */
it( 'rejects a number outside its declared range', function ( mixed $value ): void {
    expect( sanitize( [
        'type' => 'number',
        'min'  => 1,
        'max'  => 50,
    ], $value ) )->toBeNull();
} )->with( [
    'below min'   => 0,
    'above max'   => 999,
    'not numeric' => 'abc',
] );

it( 'accepts a number inside its declared range', function (): void {
    expect( sanitize( [
        'type' => 'number',
        'min'  => 1,
        'max'  => 50,
    ], 25 ) )->toBe( 25 );
} );

it( 'rejects a choice that was not offered', function ( string $type ): void {
    expect( sanitize( [
        'type'    => $type,
        'choices' => [ 'a' => 'A' ],
    ], 'zz' ) )->toBeNull();
} )->with( [ 'select', 'radio', 'radio_image' ] );

/**
 * One stale entry must not block saving the rest, so unrecognised items are
 * dropped rather than rejecting the whole submission.
 */
it( 'drops unrecognised entries from a multi-value control', function ( string $type ): void {
    expect( sanitize( [
        'type'    => $type,
        'choices' => [
            'a' => 'A',
            'c' => 'C',
        ],
    ], [ 'a', 'zz', 'c' ] ) )
        ->toBe( [ 'a', 'c' ] );
} )->with( [ 'multicheckbox', 'sortable' ] );

it( 'preserves submitted order in a sortable', function (): void {
    expect( sanitize( [
        'type'    => 'sortable',
        'choices' => [
            'x'  => 'X',
            'ig' => 'IG',
        ],
    ], [ 'ig', 'x' ] ) )
        ->toBe( [ 'ig', 'x' ] );
} );

it( 'reads an empty multi-value submission as cleared', function (): void {
    expect( sanitize( [
        'type'    => 'multicheckbox',
        'choices' => [ 'a' => 'A' ],
    ], '' ) )->toBe( [] );
} );

it( 'casts media to an attachment ID', function (): void {
    expect( sanitize( [ 'type' => 'media' ], '42' ) )->toBe( 42 );
} );

it( 'declares an empty value for controls that post nothing when empty', function ( string $type ): void {
    expect( ( new Fields )->get( $type )->emptyValue() )->not->toBeNull();
} )->with( [ 'checkbox', 'toggle', 'multicheckbox', 'sortable', 'media' ] );

it( 'declares no empty value for controls that always post', function ( string $type ): void {
    expect( ( new Fields )->get( $type )->emptyValue() )->toBeNull();
} )->with( [ 'text', 'textarea', 'number', 'select' ] );

/**
 * A class shipped in Fields/ but left out of the registry is unreachable with
 * no error anywhere -- the declaration just throws "Unknown control type" at
 * render time, far from the omission that caused it.
 */
it( 'registers every field class the package ships', function (): void {
    $shipped = array_diff(
        array_map(
            static fn( string $file ): string => basename( $file, '.php' ),
            glob( __DIR__ . '/../../src/Fields/*.php' ) ?: []
        ),
        [ 'AbstractField' ]
    );

    $registered = array_map(
        static fn( string $type ): string => ( new ReflectionClass( ( new Fields )->get( $type ) ) )->getShortName(),
        [ 'text', 'url', 'email', 'textarea', 'number', 'checkbox', 'toggle', 'select', 'multiselect', 'radio', 'multicheckbox', 'radio_image', 'media', 'sortable', 'html' ]
    );

    expect( array_values( array_diff( $shipped, $registered ) ) )->toBe( [] );
} );

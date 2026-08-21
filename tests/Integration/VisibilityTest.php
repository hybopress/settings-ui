<?php

declare(strict_types = 1);

/**
 * Controls used across the visibility tests. Two capabilities, two tabs.
 *
 * @return array<string, array<string, mixed>>
 */
function visibilityControls(): array {
    return [
        'slider_speed' => [
            'type'    => 'number',
            'tab'     => 'gallery',
            'section' => 'slider',
            'feature' => 'gallery.slider',
        ],
        'masonry_gap'  => [
            'type'    => 'number',
            'tab'     => 'gallery',
            'section' => 'masonry',
            'feature' => 'gallery.masonry',
        ],
        'site_title'   => [
            'type'    => 'text',
            'tab'     => 'general',
            'section' => 'identity',
        ],
        'tagline'      => [
            'type'    => 'text',
            'tab'     => 'general',
            'section' => 'identity',
        ],
    ];
}

/** @return array<string, mixed> */
function withFeatures( array $preset ): array {
    return [
        'features' => [
            'gallery' => [
                'slider'  => true,
                'masonry' => true,
            ],
        ],
        'presets'  => [
            'active' => 'p',
            'p'      => $preset,
        ],
    ];
}

it( 'shows every control when no preset is active', function (): void {
    $definitions = definitions( visibilityControls(), [
        'features' => [
            'gallery' => [
                'slider'  => true,
                'masonry' => true,
            ],
        ],
    ] );

    expect( array_keys( $definitions->all() ) )->toHaveCount( 4 );
} );

it( 'hides controls whose capability is disabled', function (): void {
    $definitions = definitions(
        visibilityControls(),
        withFeatures( [ 'features' => [ 'gallery' => [ 'slider' => false ] ] ] )
    );

    expect( array_keys( $definitions->all() ) )
        ->toBe( [ 'masonry_gap', 'site_title', 'tagline' ] );
} );

/**
 * A section whose every control is hidden must disappear with them rather
 * than printing an empty heading.
 */
it( 'drops a section left empty by a disabled capability', function (): void {
    $definitions = definitions(
        visibilityControls(),
        withFeatures( [ 'features' => [ 'gallery' => [ 'slider' => false ] ] ] )
    );

    expect( array_keys( $definitions->forTab( 'gallery' ) ) )->toBe( [ 'masonry' ] );
} );

it( 'drops a tab left empty by disabled capabilities', function (): void {
    $definitions = definitions(
        visibilityControls(),
        withFeatures( [
            'features' => [
                'gallery' => [
                    'slider'  => false,
                    'masonry' => false,
                ],
            ],
        ] )
    );

    expect( $definitions->tabs() )->toBe( [ 'general' ] );
} );

it( 'hides a control the preset lists as hidden', function (): void {
    $definitions = definitions(
        visibilityControls(),
        withFeatures( [ 'hidden' => [ 'tagline' ] ] )
    );

    expect( array_keys( $definitions->all() ) )
        ->toBe( [ 'slider_speed', 'masonry_gap', 'site_title' ] );
} );

it( 'hides a control with no feature when the preset lists it', function (): void {
    $definitions = definitions(
        visibilityControls(),
        withFeatures( [ 'hidden' => [ 'site_title', 'tagline' ] ] )
    );

    expect( array_keys( $definitions->forTab( 'general' ) ) )->toBeEmpty();
} );

it( 'ignores a hidden list from an inactive preset', function (): void {
    $definitions = definitions( visibilityControls(), [
        'features' => [
            'gallery' => [
                'slider'  => true,
                'masonry' => true,
            ],
        ],
        'presets'  => [
            'active' => 'p',
            'other'  => [ 'hidden' => [ 'tagline' ] ],
        ],
    ] );

    expect( $definitions->has( 'tagline' ) )->toBeTrue();
} );

it( 'hides a control whose capability was never declared', function (): void {
    $definitions = definitions( [
        'orphan' => [
            'type'    => 'text',
            'feature' => 'never.declared',
        ],
    ] );

    expect( $definitions->all() )->toBeEmpty();
} );

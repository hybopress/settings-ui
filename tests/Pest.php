<?php

/**
 * Pest bootstrap.
 *
 * PestWP downloads WordPress, sets up SQLite and loads both for us. So these
 * tests run against real WordPress: real options, real escaping, real
 * checked()/selected(). Nothing here fakes a WordPress function, which
 * matters most for the escaping assertions -- a fake esc_attr() would let an
 * unescaped value pass a test that real WordPress would fail.
 *
 * Each test runs inside a transaction that is rolled back afterwards.
 *
 * hbp/settings, Hybrid Tools and Hybrid Contracts all load for real too, so
 * these tests exercise the actual resolution the UI sits on.
 */

declare(strict_types = 1);

use HBP\Settings\Features;
use HBP\Settings\Meta\WordPressMeta;
use HBP\Settings\Settings;
use HBP\Settings\Store\OptionStore;
use HBP\Settings\Ui\Definition;
use HBP\Settings\Ui\Definitions;
use HBP\Settings\Ui\Fields;
use HBP\Settings\Ui\Panel;
use Hybrid\Tools\Config\Repository as Config;
use PestWP\Database\TransactionManager;

uses()
    ->beforeEach( fn() => TransactionManager::beginTransaction() )
    ->afterEach( fn() => TransactionManager::rollback() )
    ->in( 'Integration' );

const TEST_OPTION = 'child_settings';

/**
 * Config with the given values under the "child" namespace.
 *
 * @param array<string, mixed> $values
 */
function child( array $values ): Config {
    return new Config( [ 'child' => $values ] );
}

/**
 * A Panel over the given control declarations and namespace config.
 *
 * @param array<string, array<string, mixed>> $controls
 * @param array<string, mixed>                $extra
 */
function panel( array $controls, array $extra = [] ): Panel {
    $config = child( [ 'controls' => $controls ] + $extra );

    return new Panel(
        new Settings( new OptionStore( TEST_OPTION ), $config, 'child', new WordPressMeta ),
        new Definitions( $config, 'child', new Features( $config, 'child' ) ),
        new Fields,
        TEST_OPTION
    );
}

/**
 * A Definitions collection over the given controls and namespace config.
 *
 * @param array<string, array<string, mixed>> $controls
 * @param array<string, mixed>                $extra
 */
function definitions( array $controls, array $extra = [] ): Definitions {
    $config = child( [ 'controls' => $controls ] + $extra );

    return new Definitions( $config, 'child', new Features( $config, 'child' ) );
}

/**
 * A single control declaration.
 *
 * @param array<string, mixed> $declaration
 */
function definition( array $declaration, string $key = 'field' ): Definition {
    return new Definition( $key, $declaration );
}

/**
 * Render one control type in isolation.
 *
 * @param array<string, mixed> $declaration
 */
function render( array $declaration, mixed $value = null, string $name = 'opt[field]' ): string {
    $definition = definition( $declaration );

    return ( new Fields )->get( $definition->type() )->render( $definition, $name, $value );
}

/**
 * Sanitize a posted value through one control type.
 *
 * @param array<string, mixed> $declaration
 */
function sanitize( array $declaration, mixed $value ): mixed {
    $definition = definition( $declaration );

    return ( new Fields )->get( $definition->type() )->sanitize( $definition, $value );
}

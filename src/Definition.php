<?php

namespace HBP\Settings\Ui;

use InvalidArgumentException;
use function Hybrid\Tools\value;

/**
 * One control declaration.
 *
 * Canonical vocabulary. Every key may be a closure, resolved on read, so a
 * declaration can call esc_html__() or build choices from data that does not
 * exist at config-load time.
 *
 *     'error_page' => [
 *         'type'        => 'select',
 *         'label'       => static fn() => esc_html__( '404 Page', 'td' ),
 *         'description' => '...',
 *         'tab'         => 'general',
 *         'section'     => 'reading',
 *         'priority'    => 10,
 *         'choices'     => static fn() => [ 0 => '-' ] + wp_list_pluck( get_pages(), 'post_title', 'ID' ),
 *     ]
 */
final class Definition {
    public function __construct(
        public readonly string $key,
        private readonly array $definition
    ) {
        if ( '' === $this->type() ) {
            throw new InvalidArgumentException( "The control [{$key}] declares no type." );
        }
    }

    public function type(): string {
        return (string) $this->get( 'type', '' );
    }

    public function label(): string {
        return (string) $this->get( 'label', $this->key );
    }

    public function description(): string {
        return (string) $this->get( 'description', '' );
    }

    public function tab(): string {
        return (string) $this->get( 'tab', 'general' );
    }

    public function section(): string {
        return (string) $this->get( 'section', 'default' );
    }

    public function priority(): int {
        return (int) $this->get( 'priority', 10 );
    }

    /**
     * The capability this control belongs to, or an empty string for none.
     *
     * Declared, not inferred. The alternative -- guessing the capability from
     * the key's segments -- silently attaches controls to capabilities that
     * do not exist and misses any key that does not fit the pattern.
     */
    public function feature(): string {
        return (string) $this->get( 'feature', '' );
    }

    /**
     * Choice list for select/radio/multicheckbox/radio_image.
     *
     * A choice value may be a scalar label, or an array for richer controls:
     * radio_image reads `label` and `url` from it.
     *
     * @return array<array-key, mixed>
     */
    public function choices(): array {
        return (array) $this->get( 'choices', [] );
    }

    /**
     * Whether a posted value is one of the offered choices.
     */
    public function allows( mixed $value ): bool {
        return array_key_exists( (string) $value, $this->choices() );
    }

    /**
     * A declared key, with closures resolved.
     */
    public function get( string $key, mixed $default = null ): mixed {
        if ( ! array_key_exists( $key, $this->definition ) ) {
            return $default;
        }

        return value( $this->definition[ $key ] );
    }

    public function has( string $key ): bool {
        return array_key_exists( $key, $this->definition );
    }
}

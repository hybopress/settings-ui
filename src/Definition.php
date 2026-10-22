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
     * Text rendered above the control.
     */
    public function beforeField(): string {
        return (string) $this->get( 'before_field', '' );
    }

    /**
     * Text rendered immediately after the control, before the description.
     *
     * Distinct from description on purpose: this sits inline with the input
     * (a checkbox's trailing sentence), the description sits under it.
     */
    public function afterField(): string {
        return (string) $this->get( 'after_field', '' );
    }

    /**
     * Class placed on the control's row, not on the input.
     *
     * This is the handle a dependent control is shown and hidden by, so it
     * has to land on the wrapper rather than the field itself.
     */
    /**
     * The row class a control key is addressed by.
     *
     * Derived rather than declared, so the class on the row and the selector
     * an event points at come from one place and cannot drift apart.
     */
    public static function classFor( string $key ): string {
        return 'hbp-control-' . str_replace( [ '.', '_' ], '-', $key );
    }

    public function containerClass(): string {
        $declared = (string) $this->get( 'container-class', $this->get( 'container_class', '' ) );

        return '' !== $declared ? $declared : self::classFor( $this->key );
    }

    /**
     * Client-side show/hide rules, keyed by this control's value.
     *
     *     'events' => [
     *         'true'  => [ 'show' => 'backend.self_ping_urls' ],
     *         'false' => [ 'hide' => 'backend.self_ping_urls' ],
     *     ]
     *
     * Targets are control keys. Panel resolves each to the class that control
     * actually carries, so the two sides cannot drift. A target that already
     * looks like a selector -- it starts with `.` or `#` -- is passed through
     * untouched, for pointing at markup this package did not render.
     *
     * Emitted as a data attribute for a script to act on. This package ships
     * no script: it states the rule, the consumer honours it.
     *
     * @return array<array-key, mixed>
     */
    public function events(): array {
        return (array) $this->get( 'events', [] );
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

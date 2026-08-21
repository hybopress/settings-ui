<?php

namespace HBP\Settings\Ui;

use HBP\Settings\Features;
use Hybrid\Contracts\Config\Repository as ConfigRepository;
use function Hybrid\Tools\value;

/**
 * Every control declared for a namespace, grouped and ordered.
 *
 * Declarations live in config under `{namespace}.controls`, keyed by setting
 * key. Kept apart from the value namespace so a setting named `controls.x`
 * and a declaration cannot be confused for each other.
 */
final class Definitions {
    /** @var array<string, \HBP\Settings\Ui\Definition>|null */
    private ?array $definitions = null;

    /** @var array<int, string>|null */
    private ?array $hidden = null;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly string $namespace,
        private readonly Features $features
    ) {}

    public function has( string $key ): bool {
        return isset( $this->all()[ $key ] );
    }

    public function get( string $key ): ?Definition {
        return $this->all()[ $key ] ?? null;
    }

    /**
     * Every visible control.
     *
     * Hidden controls are dropped here rather than skipped at render time, so
     * a section or tab whose every control is hidden disappears with them
     * instead of printing an empty heading.
     *
     * @return array<string, \HBP\Settings\Ui\Definition>
     */
    public function all(): array {
        if ( null === $this->definitions ) {
            $this->definitions = [];

            foreach ( (array) $this->config->get( "{$this->namespace}.controls", [] ) as $key => $declaration ) {
                if ( ! is_array( $declaration ) ) {
                    continue;
                }

                $definition = new Definition( (string) $key, $declaration );

                if ( ! $this->visible( $definition ) ) {
                    continue;
                }

                $this->definitions[ $key ] = $definition;
            }
        }

        return $this->definitions;
    }

    /**
     * Whether a control renders for the active preset.
     *
     * Two independent reasons to hide. A control for a capability this build
     * does not ship is hidden whether or not the preset lists it -- otherwise
     * a preset that disables a capability still renders settings that cannot
     * affect anything. A preset may also hide a control outright.
     *
     * Either way the control stays declared under its proper tab and section,
     * and visibility is decided here. That is what lets one preset hide a
     * control another preset shows.
     */
    public function visible( Definition $definition ): bool {
        $feature = $definition->feature();

        if ( '' !== $feature && $this->features->disabled( $feature ) ) {
            return false;
        }

        return ! in_array( $definition->key, $this->hidden(), true );
    }

    /**
     * Control keys the active preset hides, from
     * `{namespace}.presets.{active}.hidden`.
     *
     * @return array<int, string>
     */
    private function hidden(): array {
        if ( null === $this->hidden ) {
            $active = $this->config->get( "{$this->namespace}.presets.active" );

            $hidden = is_string( $active ) && '' !== $active
                ? $this->config->get( "{$this->namespace}.presets.{$active}.hidden", [] )
                : [];

            $this->hidden = array_map( 'strval', (array) $hidden );
        }

        return $this->hidden;
    }

    /**
     * Tab slugs in priority order.
     *
     * @return array<int, string>
     */
    public function tabs(): array {
        $tabs = [];

        foreach ( $this->all() as $definition ) {
            $tabs[ $definition->tab() ] ??= $definition->priority();
        }

        asort( $tabs );

        return array_keys( $tabs );
    }

    /**
     * Controls on one tab, grouped by section, both in priority order.
     *
     * @return array<string, array<string, \HBP\Settings\Ui\Definition>>
     */
    public function forTab( string $tab ): array {
        $sections = [];
        $order    = [];

        foreach ( $this->all() as $key => $definition ) {
            if ( $definition->tab() !== $tab ) {
                continue;
            }

            $sections[ $definition->section() ][ $key ] = $definition;

            // A section sorts by its earliest control, so declaring one
            // high-priority control lifts its whole section.
            $section           = $definition->section();
            $order[ $section ] = min( $order[ $section ] ?? PHP_INT_MAX, $definition->priority() );
        }

        asort( $order );

        $sorted = [];

        foreach ( array_keys( $order ) as $section ) {
            // PHP sorts are stable, so equal priorities keep declaration order.
            uasort(
                $sections[ $section ],
                static fn( Definition $a, Definition $b ): int => $a->priority() <=> $b->priority()
            );

            $sorted[ $section ] = $sections[ $section ];
        }

        return $sorted;
    }

    /**
     * A section's heading, from `{namespace}.sections.{slug}`.
     */
    public function sectionLabel( string $section ): string {
        return $this->label( "{$this->namespace}.sections.{$section}", $section );
    }

    /**
     * A tab's label, from `{namespace}.tabs.{slug}`.
     */
    public function tabLabel( string $tab ): string {
        return $this->label( "{$this->namespace}.tabs.{$tab}", $tab );
    }

    private function label( string $path, string $fallback ): string {
        $label = value( $this->config->get( $path ) );

        return is_string( $label ) && '' !== $label
            ? $label
            : ucwords( str_replace( [ '-', '_' ], ' ', $fallback ) );
    }

    /** @return array<int, string> */
    public function typesForTab( string $tab ): array {
        $types = [];

        foreach ( $this->forTab( $tab ) as $controls ) {
            foreach ( $controls as $definition ) {
                $types[] = $definition->type();
            }
        }

        return array_values( array_unique( $types ) );
    }
}

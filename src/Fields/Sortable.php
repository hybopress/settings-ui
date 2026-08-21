<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

/**
 * A reorderable, individually toggleable list of choices.
 *
 * Stores an ordered array of the enabled choices, so both which items are on
 * and what order they appear in are one value.
 *
 * Needs jquery-ui-sortable and this package's script.
 * FieldsView::needs( 'sortable' ) reports whether a tab contains one.
 */
final class Sortable extends AbstractField {
    public function emptyValue(): ?string {
        return '';
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        $choices = $definition->choices();
        $enabled = array_values( array_filter(
            array_map( 'strval', (array) $value ),
            static fn( $item ) => array_key_exists( $item, $choices )
        ) );

        // Enabled items first, in their stored order; everything else after,
        // unticked, in declaration order.
        $ordered = array_merge(
            $enabled,
            array_values( array_diff( array_map( 'strval', array_keys( $choices ) ), $enabled ) )
        );

        $html = '<ul class="hbp-sortable" data-hbp-sortable>';

        foreach ( $ordered as $choice ) {
            $html .= sprintf(
                '<li><label><input type="checkbox" name="%s[]" value="%s"%s> %s</label></li>',
                esc_attr( $name ),
                esc_attr( $choice ),
                checked( in_array( $choice, $enabled, true ), true, false ),
                esc_html( $this->choiceLabel( $choices[ $choice ], $choice ) )
            );
        }

        return $html . '</ul>';
    }

    /**
     * @return array<int, string>
     */
    public function sanitize( Definition $definition, mixed $value ): mixed {
        $values = array_filter( array_map( 'strval', (array) $value ), static fn( $v ) => '' !== $v );

        return array_values( array_filter( $values, $definition->allows( ...) ) );
    }
}

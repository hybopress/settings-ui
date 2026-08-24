<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

/**
 * A select accepting more than one choice.
 *
 * Deliberately its own type rather than a `multiple` flag on select. The two
 * differ in what they post when empty -- a single select always posts, this
 * posts no key at all -- and that difference has to be visible to
 * emptyValue(), which the contract hands no declaration.
 */
final class Multiselect extends AbstractField {
    /**
     * With nothing selected the control posts no key, which is
     * indistinguishable from "not on this form" without a companion input.
     */
    public function emptyValue(): ?string {
        return '';
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        $selected = array_map( 'strval', (array) $value );

        $html = sprintf(
            '<select id="%s" name="%s[]" multiple%s>',
            esc_attr( $name ),
            esc_attr( $name ),
            $this->attributes( $definition )
        );

        foreach ( $definition->choices() as $choice => $label ) {
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( (string) $choice ),
                selected( in_array( (string) $choice, $selected, true ), true, false ),
                esc_html( $this->choiceLabel( $label, (string) $choice ) )
            );
        }

        return $html . '</select>';
    }

    /**
     * @return array<int, string>
     */
    public function sanitize( Definition $definition, mixed $value ): mixed {
        $values = array_filter( array_map( 'strval', (array) $value ), static fn( $v ) => '' !== $v );

        // Unrecognised entries are dropped rather than rejecting the whole
        // submission, so one stale choice cannot block saving the rest.
        return array_values( array_filter( $values, $definition->allows( ...) ) );
    }
}

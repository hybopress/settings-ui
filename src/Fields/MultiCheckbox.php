<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class MultiCheckbox extends AbstractField {
    /**
     * With nothing ticked the control posts no key at all, which would read
     * as "absent from this form" rather than "cleared".
     */
    public function emptyValue(): ?string {
        return '';
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        $selected = array_map( 'strval', (array) $value );
        $html     = '<fieldset class="hbp-multicheckbox">';

        foreach ( $definition->choices() as $choice => $label ) {
            $html .= sprintf(
                '<label><input type="checkbox" name="%s[]" value="%s"%s%s> %s</label>',
                esc_attr( $name ),
                esc_attr( (string) $choice ),
                checked( in_array( (string) $choice, $selected, true ), true, false ),
                $this->attributes( $definition ),
                esc_html( $this->choiceLabel( $label, (string) $choice ) )
            );
        }

        return $html . '</fieldset>';
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

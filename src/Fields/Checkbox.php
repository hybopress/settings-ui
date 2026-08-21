<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

class Checkbox extends AbstractField {
    /**
     * An unchecked box posts nothing, so a companion hidden input carries 0.
     */
    public function emptyValue(): ?string {
        return '0';
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        return sprintf(
            '<label><input type="checkbox" id="%s" name="%s" value="1"%s%s> %s</label>',
            esc_attr( $name ),
            esc_attr( $name ),
            checked( (bool) $value, true, false ),
            $this->attributes( $definition ),
            esc_html( (string) $definition->get( 'checkbox_label', '' ) )
        );
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    }
}

<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class Radio extends AbstractField {
    public function render( Definition $definition, string $name, mixed $value ): string {
        $html = '<fieldset class="hbp-radio">';

        foreach ( $definition->choices() as $choice => $label ) {
            $html .= sprintf(
                '<label><input type="radio" name="%s" value="%s"%s%s> %s</label>',
                esc_attr( $name ),
                esc_attr( (string) $choice ),
                checked( (string) $choice, (string) $value, false ),
                $this->attributes( $definition ),
                esc_html( $this->choiceLabel( $label, (string) $choice ) )
            );
        }

        return $html . '</fieldset>';
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return $definition->allows( $value ) ? (string) $value : null;
    }
}

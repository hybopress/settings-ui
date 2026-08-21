<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class Select extends AbstractField {
    public function render( Definition $definition, string $name, mixed $value ): string {
        $html = sprintf(
            '<select id="%s" name="%s"%s>',
            esc_attr( $name ),
            esc_attr( $name ),
            $this->attributes( $definition )
        );

        foreach ( $definition->choices() as $choice => $label ) {
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( (string) $choice ),
                selected( (string) $choice, (string) $value, false ),
                esc_html( $this->choiceLabel( $label, (string) $choice ) )
            );
        }

        return $html . '</select>';
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return $definition->allows( $value ) ? (string) $value : null;
    }
}

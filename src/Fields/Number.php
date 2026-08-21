<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class Number extends AbstractField {
    protected function extraAttributes(): array {
        return [ 'min', 'max', 'step' ];
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        return sprintf(
            '<input type="number" id="%s" name="%s" value="%s"%s>',
            esc_attr( $name ),
            esc_attr( $name ),
            esc_attr( $this->scalar( $value ) ),
            $this->attributes( $definition )
        );
    }

    /**
     * Out-of-range values are rejected, not clamped.
     */
    public function sanitize( Definition $definition, mixed $value ): mixed {
        if ( ! is_numeric( $value ) ) {
            return null;
        }

        $number = str_contains( (string) $value, '.' ) ? (float) $value : (int) $value;

        if ( $definition->has( 'min' ) && $definition->get( 'min' ) > $number ) {
            return null;
        }

        if ( $definition->has( 'max' ) && $definition->get( 'max' ) < $number ) {
            return null;
        }

        return $number;
    }
}

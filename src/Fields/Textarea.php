<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class Textarea extends AbstractField {
    protected function extraAttributes(): array {
        return [ 'rows', 'cols', 'maxlength' ];
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        return sprintf(
            '<textarea id="%s" name="%s"%s>%s</textarea>',
            esc_attr( $name ),
            esc_attr( $name ),
            $this->attributes( $definition ),
            esc_textarea( $this->scalar( $value ) )
        );
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return sanitize_textarea_field( $this->scalar( $value ) );
    }
}

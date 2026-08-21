<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

class Text extends AbstractField {
    protected string $type = 'text';

    protected function extraAttributes(): array {
        return [ 'maxlength', 'minlength', 'pattern', 'size' ];
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        return sprintf(
            '<input type="%s" id="%s" name="%s" value="%s"%s>',
            esc_attr( $this->type ),
            esc_attr( $name ),
            esc_attr( $name ),
            esc_attr( $this->scalar( $value ) ),
            $this->attributes( $definition )
        );
    }
}

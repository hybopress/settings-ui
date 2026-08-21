<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class Email extends Text {
    protected string $type = 'email';

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return sanitize_email( $this->scalar( $value ) );
    }
}

<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

final class Url extends Text {
    protected string $type = 'url';

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return esc_url_raw( $this->scalar( $value ) );
    }
}

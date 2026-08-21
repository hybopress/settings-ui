<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

/**
 * A checkbox styled as a switch.
 *
 * Identical semantics to Checkbox; the class hook is what differs, so a
 * declaration can ask for a switch without the consumer restyling every
 * checkbox on the screen.
 */
final class Toggle extends Checkbox {
    public function render( Definition $definition, string $name, mixed $value ): string {
        return sprintf(
            '<span class="hbp-toggle">%s</span>',
            parent::render( $definition, $name, $value )
        );
    }
}

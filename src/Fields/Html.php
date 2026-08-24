<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

/**
 * Declared markup that sits on the screen without being a setting.
 *
 * Warnings, explanations, the paragraph above a dangerous toggle. It reads
 * nothing and stores nothing: sanitize() always rejects, so a crafted post
 * carrying this key cannot write through it, and emptyValue() is null so no
 * companion input is emitted for a control that has no value to clear.
 *
 * The declared `content` is trusted markup from config, not user input, so it
 * is not escaped -- escaping it would defeat the point of the type. Anything
 * interpolated into it is the declaration's job to escape:
 *
 *     'content' => static fn() => sprintf(
 *         '<p class="description">%s</p>',
 *         esc_html__( 'Changing this affects every user.', 'td' )
 *     ),
 */
final class Html extends AbstractField {
    public function render( Definition $definition, string $name, mixed $value ): string {
        return (string) $definition->get( 'content', '' );
    }

    /**
     * Never stores. Returning null is how a control rejects a value, and this
     * one rejects every value it is ever handed.
     */
    public function sanitize( Definition $definition, mixed $value ): mixed {
        return null;
    }
}

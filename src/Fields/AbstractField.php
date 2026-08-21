<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Contracts\Field;
use HBP\Settings\Ui\Definition;

abstract class AbstractField implements Field {
    /**
     * Attribute keys every control accepts from its declaration.
     */
    private const COMMON = [ 'class', 'placeholder', 'required', 'disabled', 'readonly' ];

    /**
     * Extra declaration keys this control passes through as attributes.
     *
     * Subclasses override this instead of concatenating onto the rendered
     * string, so escaping happens in exactly one place.
     *
     * @return array<int, string>
     */
    protected function extraAttributes(): array {
        return [];
    }

    public function emptyValue(): ?string {
        return null;
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return sanitize_text_field( $this->scalar( $value ) );
    }

    protected function scalar( mixed $value ): string {
        return is_scalar( $value ) ? (string) $value : '';
    }

    /**
     * Escaped attribute string built from the declaration.
     */
    protected function attributes( Definition $definition ): string {
        $html = '';

        foreach ( [ ...self::COMMON, ...$this->extraAttributes() ] as $key ) {
            if ( ! $definition->has( $key ) ) {
                continue;
            }

            $value = $definition->get( $key );

            if ( false === $value || null === $value || '' === $value ) {
                continue;
            }

            $html .= true === $value
                ? ' ' . esc_attr( $key )
                : sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
        }

        return $html;
    }

    /**
     * The label text for a choice, which may be a scalar or an array.
     */
    protected function choiceLabel( mixed $choice, string $fallback ): string {
        if ( is_array( $choice ) ) {
            return (string) ( $choice['label'] ?? $fallback );
        }

        return is_scalar( $choice ) ? (string) $choice : $fallback;
    }
}

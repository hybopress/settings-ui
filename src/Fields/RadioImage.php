<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

/**
 * A radio group drawn as images. The layout picker.
 *
 * Each choice is `[ 'label' => ..., 'url' => ... ]`. A choice with no url is
 * skipped, since there is nothing to draw for it.
 */
final class RadioImage extends AbstractField {
    public function render( Definition $definition, string $name, mixed $value ): string {
        $html = '<fieldset class="hbp-radio-image">';

        foreach ( $definition->choices() as $choice => $option ) {
            $url = is_array( $option ) ? ( $option['url'] ?? '' ) : '';

            if ( '' === $url ) {
                continue;
            }

            $html .= sprintf(
                '<label><input type="radio" name="%s" value="%s"%s class="screen-reader-text">'
                . '<img src="%s" alt="%1$s"><span>%5$s</span></label>',
                esc_attr( $name ),
                esc_attr( (string) $choice ),
                checked( (string) $choice, (string) $value, false ),
                esc_url( (string) $url ),
                esc_html( $this->choiceLabel( $option, (string) $choice ) )
            );
        }

        return $html . '</fieldset>';
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return $definition->allows( $value ) ? (string) $value : null;
    }
}

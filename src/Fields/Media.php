<?php

namespace HBP\Settings\Ui\Fields;

use HBP\Settings\Ui\Definition;

/**
 * An attachment ID chosen through the media modal.
 *
 * Stores the ID, not the URL, so the value survives a domain change and can
 * ask for any registered size at render time.
 *
 * Needs the media modal and this package's script. FieldsView::needs( 'media' )
 * reports whether a tab contains one.
 */
final class Media extends AbstractField {
    public function emptyValue(): ?string {
        return '0';
    }

    public function render( Definition $definition, string $name, mixed $value ): string {
        $id  = absint( $value );
        $src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';

        return sprintf(
            '<div class="hbp-media" data-hbp-media>'
            . '<input type="hidden" id="%1$s" name="%1$s" value="%2$s" data-hbp-media-value>'
            . '<img src="%3$s" alt=""%4$s data-hbp-media-preview>'
            . '<button type="button" class="button" data-hbp-media-select>%5$s</button>'
            . '<button type="button" class="button-link" data-hbp-media-clear%6$s>%7$s</button>'
            . '</div>',
            esc_attr( $name ),
            esc_attr( (string) $id ),
            esc_url( (string) $src ),
            $src ? '' : ' hidden',
            esc_html__( 'Select', 'hbp-settings-ui' ),
            $id ? '' : ' hidden',
            esc_html__( 'Remove', 'hbp-settings-ui' )
        );
    }

    public function sanitize( Definition $definition, mixed $value ): mixed {
        return absint( $value );
    }
}

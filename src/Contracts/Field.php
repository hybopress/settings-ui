<?php

namespace HBP\Settings\Ui\Contracts;

use HBP\Settings\Ui\Definition;

interface Field {
    /**
     * The control's HTML.
     *
     * @param string $name Form input name, e.g. `theme_settings[error_page]`.
     */
    public function render( Definition $definition, string $name, mixed $value ): string;

    /**
     * Cast a posted value.
     *
     * Return null to reject it, which leaves the stored value untouched.
     * Out-of-range and unrecognised values are rejected rather than clamped:
     * a control posting something its declaration disallows is a bug, and
     * quietly storing a nearby value hides it.
     */
    public function sanitize( Definition $definition, mixed $value ): mixed;

    /**
     * A value to post when the control submits nothing, or null.
     *
     * Unchecked checkboxes and empty multi-selects send no key at all, which
     * is indistinguishable from "not on this form". A companion hidden input
     * carrying this value removes the ambiguity.
     */
    public function emptyValue(): ?string;
}

<?php

/**
 * Helpers. Namespaced: this package exports no global functions.
 */

namespace HBP\Settings\Ui;

use function Hybrid\app;

if ( ! function_exists( __NAMESPACE__ . '\\panel' ) ) {
    /**
     * The settings panel for a namespace.
     */
    function panel( string $namespace, ?string $option = null ): Panel {
        return app( PanelFactory::class )->make( $namespace, $option );
    }
}

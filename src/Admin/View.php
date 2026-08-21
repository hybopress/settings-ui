<?php

namespace HBP\Settings\Ui\Admin;

/**
 * One tab of a settings screen.
 *
 * Not every tab is a settings form. An import/export manager or a theme
 * browser implements this too and never touches a Panel, which is why the
 * host knows nothing about fields.
 */
abstract class View {
    abstract public function name(): string;

    abstract public function label(): string;

    /**
     * Called on admin_init. Register settings here.
     */
    public function register(): void {}

    /**
     * Called on load-{$page}, for this view only. Enqueue here.
     */
    public function boot(): void {}

    abstract public function template(): void;
}

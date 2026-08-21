<?php

namespace HBP\Settings\Ui\Admin;

use Hybrid\Contracts\Bootable;
use Hybrid\Tools\Collection;

/**
 * A tabbed settings screen.
 *
 * Optional. A consumer with its own options page can ignore this entirely
 * and drive Panel directly; nothing else in the package depends on it.
 */
final class Page implements Bootable {
    private string $hook = '';

    /**
     * @param string                                                        $slug Menu slug, e.g. `theme-settings`.
     * @param \Hybrid\Tools\Collection<string, \HBP\Settings\Ui\Admin\View> $views
     * @param string                                                        $parent Parent menu file, e.g. `themes.php`.
     */
    public function __construct(
        private readonly string $slug,
        private readonly Collection $views,
        private readonly string $label,
        private readonly string $capability = 'edit_theme_options',
        private readonly string $parent = 'themes.php'
    ) {}

    public function boot(): void {
        add_action( 'admin_init', [ $this, 'register' ] );
        add_action( 'admin_menu', [ $this, 'menu' ] );
    }

    public function register(): void {
        foreach ( $this->views as $view ) {
            $view->register();
        }
    }

    public function menu(): void {
        $this->hook = (string) add_submenu_page(
            $this->parent,
            esc_html( $this->label ),
            esc_html( $this->label ),
            $this->capability,
            $this->slug,
            [ $this, 'template' ]
        );

        if ( '' !== $this->hook ) {
            add_action( "load-{$this->hook}", [ $this, 'load' ] );
        }
    }

    /**
     * Boots only the view being viewed, so a tab's assets never load on
     * another tab.
     */
    public function load(): void {
        $this->current()?->boot();
    }

    public function template(): void {
        echo '<div class="wrap">';
        printf( '<h1 class="wp-heading-inline">%s</h1>', esc_html( $this->label ) );

        $this->tabs();

        $this->current()?->template();

        echo '</div>';
    }

    public function current(): ?View {
        $requested = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';

        if ( '' !== $requested && $this->views->has( $requested ) ) {
            return $this->views->get( $requested );
        }

        return $this->views->first() ?: null;
    }

    private function tabs(): void {
        if ( $this->views->count() < 2 ) {
            return;
        }

        $current = $this->current()?->name();

        echo '<nav class="nav-tab-wrapper">';

        foreach ( $this->views as $view ) {
            printf(
                '<a href="%s" class="nav-tab%s">%s</a>',
                esc_url( add_query_arg(
                    [
                        'page' => $this->slug,
                        'view' => $view->name(),
                    ],
                    admin_url( $this->parent )
                ) ),
                $view->name() === $current ? ' nav-tab-active' : '',
                esc_html( $view->label() )
            );
        }

        echo '</nav>';
    }
}

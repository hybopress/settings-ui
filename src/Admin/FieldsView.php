<?php

namespace HBP\Settings\Ui\Admin;

use HBP\Settings\Ui\Panel;

/**
 * A tab built from control declarations.
 *
 * One class serves every declarative tab: the slug is a constructor
 * argument, so adding a tab means adding config entries and nothing else.
 */
class FieldsView extends View {
    public function __construct(
        protected readonly Panel $panel,
        protected readonly string $slug,
        protected readonly string $page
    ) {}

    public function name(): string {
        return $this->slug;
    }

    public function label(): string {
        return $this->panel->definitions()->tabLabel( $this->slug );
    }

    public function register(): void {
        $this->panel->registerTab( $this->slug, $this->page );
    }

    /**
     * Whether this tab holds a control of the given type.
     *
     * Tabs enqueue on what they actually contain, so the media modal and
     * jquery-ui-sortable do not load on a tab holding neither.
     */
    public function needs( string $type ): bool {
        return in_array( $type, $this->panel->definitions()->typesForTab( $this->slug ), true );
    }

    public function boot(): void {
        if ( $this->needs( 'media' ) ) {
            add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );
        }

        if ( $this->needs( 'sortable' ) ) {
            add_action( 'admin_enqueue_scripts', static function (): void {
                wp_enqueue_script( 'jquery-ui-sortable' );
            } );
        }
    }

    public function template(): void {
        printf( '<form method="post" action="%s">', esc_url( admin_url( 'options.php' ) ) );

        settings_fields( $this->panel->option() );
        do_settings_sections( $this->page );
        submit_button();

        echo '</form>';
    }
}

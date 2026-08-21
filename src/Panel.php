<?php

namespace HBP\Settings\Ui;

use HBP\Settings\Settings;

/**
 * Drives the WordPress Settings API for one namespace.
 *
 * The form posts to options.php, so core owns the nonce, the capability
 * check, the redirect and the notice. This class supplies the sections, the
 * fields and the sanitize callback.
 *
 * Registration happens once for the whole option, not once per tab.
 * register_setting() adds a `sanitize_option_{$option}` filter per call and
 * those filters chain: with one registration per tab only the first callback
 * would see the real submission, and every later one would be handed the
 * previous callback's merged array and treat it as posted input.
 */
final class Panel {
    public function __construct(
        private readonly Settings $settings,
        private readonly Definitions $definitions,
        private readonly Fields $fields,
        private readonly string $option
    ) {}

    public function option(): string {
        return $this->option;
    }

    public function definitions(): Definitions {
        return $this->definitions;
    }

    /**
     * Register the option. Call once, on admin_init.
     */
    public function registerSetting(): void {
        register_setting( $this->option, $this->option, [
            'sanitize_callback' => [ $this, 'sanitize' ],
            'type'              => 'array',
        ] );
    }

    /**
     * Register one tab's sections and fields against a Settings API page.
     */
    public function registerTab( string $tab, string $page ): void {
        foreach ( $this->definitions->forTab( $tab ) as $section => $controls ) {
            add_settings_section(
                $section,
                $this->sectionLabel( $section ),
                null,
                $page
            );

            foreach ( $controls as $key => $definition ) {
                add_settings_field(
                    $key,
                    esc_html( $definition->label() ),
                    fn() => print ( $this->control( $definition ) ),
                    $page,
                    $section,
                    [ 'label_for' => $this->name( $key ) ]
                );
            }
        }
    }

    /**
     * One control plus its description.
     */
    public function control( Definition $definition ): string {
        $field = $this->fields->get( $definition->type() );
        $html  = '';

        // A companion hidden input so a control that posts nothing when empty
        // is still distinguishable from a control absent from this form.
        $empty = $field->emptyValue();

        if ( null !== $empty ) {
            $html .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                esc_attr( $this->name( $definition->key ) ),
                esc_attr( $empty )
            );
        }

        $html .= $field->render(
            $definition,
            $this->name( $definition->key ),
            $this->settings->get( $definition->key )
        );

        if ( '' !== $definition->description() ) {
            $html .= sprintf( '<p class="description">%s</p>', esc_html( $definition->description() ) );
        }

        return $html;
    }

    /**
     * Merge a submission into the stored settings.
     *
     * Only declared keys are considered, so a crafted request cannot reach
     * anything that is not a control. A key absent from the submission is
     * left exactly as stored, which is what makes it safe for one option to
     * back many tabs: the submission itself says which tab it came from, by
     * which keys are present.
     *
     * @param mixed $posted
     *
     * @return array<string, mixed>
     */
    public function sanitize( $posted ): array {
        $posted = (array) $posted;
        $stored = (array) get_option( $this->option, [] );

        foreach ( $this->definitions->all() as $key => $definition ) {
            if ( ! array_key_exists( $key, $posted ) ) {
                continue;
            }

            $clean = $this->fields->get( $definition->type() )->sanitize( $definition, $posted[ $key ] );

            // null means the control rejected the value; leave the stored one.
            if ( null !== $clean ) {
                $stored[ $key ] = $clean;
            }
        }

        return $stored;
    }

    /**
     * The form input name for a setting key.
     */
    public function name( string $key ): string {
        return sprintf( '%s[%s]', $this->option, $key );
    }

    private function sectionLabel( string $section ): string {
        return esc_html( $this->definitions->sectionLabel( $section ) );
    }
}

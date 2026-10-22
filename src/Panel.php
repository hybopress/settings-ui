<?php

namespace HBP\Settings\Ui;

use HBP\Settings\Settings;
use Hybrid\Tools\Arr;

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
                    array_filter( [
                        'label_for' => $this->name( $key ),
                        'class'     => $definition->containerClass(),
                    ] )
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

        // Helper text is authored markup, not user input: these three come
        // from the consumer's own control declarations, which is the same
        // trust level as the template calling this. wp_kses_post() keeps the
        // links, <code> samples and emphasis those declarations rely on, and
        // still strips script. esc_html() would render the markup as visible
        // literal text instead.
        if ( '' !== $definition->beforeField() ) {
            $html .= sprintf( '<p class="hbp-before-field">%s</p>', wp_kses_post( $definition->beforeField() ) );
        }

        $control = $field->render(
            $definition,
            $this->name( $definition->key ),
            $this->settings->get( $definition->key )
        );

        // A locked control is shown, not removed -- that is the whole
        // difference from `hidden`. It has to advertise the value the build
        // fixes, so a reader can see what they would get by changing build.
        //
        // A disabled <fieldset> rather than a `disabled` attribute on the
        // input: `disabled` is read from the declaration, and a lock belongs
        // to the preset, so threading it through every field type would mean
        // every field type learning about locks. The fieldset disables
        // whatever it contains, including controls made of several inputs.
        if ( $this->settings->locked( $definition->key ) ) {
            $reason = $this->settings->lockReason( $definition->key );

            $control = sprintf(
                '<fieldset disabled class="hbp-locked">%s%s</fieldset>',
                $control,
                '' === $reason ? '' : sprintf( '<p class="description hbp-lock-reason">%s</p>', wp_kses_post( $reason ) )
            );
        }

        $html .= $control;

        if ( '' !== $definition->afterField() ) {
            $html .= sprintf( ' <span class="hbp-after-field">%s</span>', wp_kses_post( $definition->afterField() ) );
        }

        if ( '' !== $definition->description() ) {
            $html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $definition->description() ) );
        }

        return $this->wrap( $definition, $html );
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

            // A locked control renders disabled, and a disabled input posts
            // nothing -- but a crafted request still can. Without this the
            // lock is only a visual state and the value it fixes can be
            // written anyway.
            if ( $this->settings->locked( $key ) ) {
                continue;
            }

            $clean = $this->fields->get( $definition->type() )->sanitize( $definition, $posted[ $key ] );

            // null means the control rejected the value; leave the stored one.
            //
            // Arr::set, not $stored[ $key ], because a control key is dotted
            // and the store is nested. Assigning it flat would write a
            // literal `section.control` key alongside the nested one, and
            // Arr::get checks the literal key first -- so the flat copy would
            // shadow every later write made through Settings::set().
            if ( null !== $clean ) {
                Arr::set( $stored, $key, $clean );
            }
        }

        return $stored;
    }

    /**
     * Wrap a control in its event rules, when it declares any.
     *
     * The rules ride as JSON on a data attribute rather than as generated
     * inline script, so nothing here has to know what the consumer's script
     * is called or when it loads.
     */
    private function wrap( Definition $definition, string $html ): string {
        $events = $definition->events();

        if ( [] === $events ) {
            return $html;
        }

        $json = wp_json_encode( $this->resolve( $events ) );

        if ( false === $json ) {
            return $html;
        }

        return sprintf(
            '<span class="hbp-control" data-hbp-events="%s">%s</span>',
            esc_attr( $json ),
            $html
        );
    }

    /**
     * Rewrite every event target from a control key to that control's class.
     *
     * A target already written as a selector is left alone, so a rule can
     * still point at markup this package did not render.
     *
     * @param array<array-key, mixed> $events
     *
     * @return array<array-key, mixed>
     */
    private function resolve( array $events ): array {
        foreach ( $events as $value => $rules ) {
            if ( ! is_array( $rules ) ) {
                continue;
            }

            foreach ( $rules as $action => $targets ) {
                $events[ $value ][ $action ] = is_array( $targets )
                    ? array_map( [ $this, 'selector' ], $targets )
                    : $this->selector( $targets );
            }
        }

        return $events;
    }

    /**
     * One event target as a CSS selector.
     *
     * @param mixed $target
     */
    private function selector( $target ): string {
        $target = (string) $target;

        if ( str_starts_with( $target, '.' ) || str_starts_with( $target, '#' ) ) {
            return $target;
        }

        return '.' . Definition::classFor( $target );
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

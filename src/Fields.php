<?php

namespace HBP\Settings\Ui;

use HBP\Settings\Ui\Contracts\Field;
use InvalidArgumentException;

/**
 * The control type registry.
 */
final class Fields {
    private const BUILT_IN = [
        'text'          => Fields\Text::class,
        'url'           => Fields\Url::class,
        'email'         => Fields\Email::class,
        'textarea'      => Fields\Textarea::class,
        'number'        => Fields\Number::class,
        'checkbox'      => Fields\Checkbox::class,
        'toggle'        => Fields\Toggle::class,
        'select'        => Fields\Select::class,
        'radio'         => Fields\Radio::class,
        'multicheckbox' => Fields\MultiCheckbox::class,
        'radio_image'   => Fields\RadioImage::class,
        'media'         => Fields\Media::class,
        'sortable'      => Fields\Sortable::class,
    ];

    /** @var array<string, \HBP\Settings\Ui\Contracts\Field> */
    private array $fields = [];

    public function __construct() {
        foreach ( self::BUILT_IN as $type => $class ) {
            $this->fields[ $type ] = new $class;
        }
    }

    public function register( string $type, Field $field ): self {
        $this->fields[ $type ] = $field;

        return $this;
    }

    public function has( string $type ): bool {
        return isset( $this->fields[ $type ] );
    }

    public function get( string $type ): Field {
        return $this->fields[ $type ]
            ?? throw new InvalidArgumentException( "Unknown control type [{$type}]." );
    }
}

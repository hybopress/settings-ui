<?php

namespace HBP\Settings\Ui;

use HBP\Settings\SettingsFactory;
use Hybrid\Contracts\Config\Repository as ConfigRepository;

/**
 * Builds and memoises one Panel per namespace.
 */
final class PanelFactory {
    /** @var array<string, \HBP\Settings\Ui\Panel> */
    private array $panels = [];

    public function __construct(
        private readonly SettingsFactory $settings,
        private readonly ConfigRepository $config,
        private readonly Fields $fields
    ) {}

    public function make( string $namespace, ?string $option = null ): Panel {
        $option = $option ?: "{$namespace}_settings";

        return $this->panels[ "{$namespace}|{$option}" ] ??= new Panel(
            $this->settings->make( $namespace, $option ),
            new Definitions( $this->config, $namespace, $this->settings->features( $namespace ) ),
            $this->fields,
            $option
        );
    }
}

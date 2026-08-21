<?php

namespace HBP\Settings\Ui\Providers;

use HBP\Settings\SettingsFactory;
use HBP\Settings\Ui\Fields;
use HBP\Settings\Ui\PanelFactory;
use Hybrid\Contracts\Core\DeferrableProvider;
use Hybrid\Core\ServiceProvider;

final class SettingsUiServiceProvider extends ServiceProvider implements DeferrableProvider {
    public function register(): void {
        $this->app->singleton( Fields::class );

        $this->app->singleton(
            PanelFactory::class,
            static fn( $app ) => new PanelFactory(
                $app->make( SettingsFactory::class ),
                $app->make( 'config' ),
                $app->make( Fields::class )
            )
        );
    }

    /** @return array<int, string> */
    public function provides(): array {
        return [ PanelFactory::class, Fields::class ];
    }
}

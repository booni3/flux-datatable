<?php

namespace Booni3\FluxDatatable;

use Illuminate\Support\ServiceProvider;

class FluxDatatableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving('blade.compiler', fn ($blade) => $blade->anonymousComponentPath(__DIR__.'/../resources/views/flux', 'flux'));
    }

    public function boot(): void
    {
        $this->app['view']->addLocation(__DIR__.'/../resources/views');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/flux-datatable'),
        ], 'flux-datatable-views');
    }
}

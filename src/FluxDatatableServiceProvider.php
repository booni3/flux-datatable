<?php

namespace Booni3\FluxDatatable;

use Illuminate\Support\ServiceProvider;

class FluxDatatableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['view']->addLocation(__DIR__.'/../resources/views');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/flux-datatable'),
        ], 'flux-datatable-views');
    }
}

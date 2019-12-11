<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        // Custom routes

            $this->mapBranchRoutes();

            $this->mapClientRoutes();

            $this->mapCountryRoutes();

            $this->mapGroupRoutes();

            $this->mapNationalityRoutes();

            $this->mapUserRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    // Custom routes

        protected function mapBranchRoutes()
        {
            Route::prefix('api/v1/branches')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/branch.php'));
        }

        protected function mapClientRoutes()
        {
            Route::prefix('api/v1/clients')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/client.php'));
        }

        protected function mapCountryRoutes()
        {
            Route::prefix('api/v1/countries')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/country.php'));
        }

        protected function mapGroupRoutes()
        {
            Route::prefix('api/v1/groups')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/group.php'));
        }

        protected function mapNationalityRoutes()
        {
            Route::prefix('api/v1/nationalities')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/nationality.php'));
        }

        protected function mapUserRoutes()
        {
            Route::prefix('api/v1')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/user.php'));
        }
}

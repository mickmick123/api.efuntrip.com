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
            $this->mapAccountsRoutes();

            $this->mapActionRoutes();

            $this->mapAppRoutes();

            $this->mapAttendanceRoutes();

            $this->mapBranchRoutes();

            $this->mapBreakdownRoutes();

            $this->mapClientDocuments();

            $this->mapClientDocumentTypeRoutes();

            $this->mapClientRoutes();

            $this->mapCountryRoutes();

            $this->mapDashboardRoutes();

            $this->mapDepartmentRoutes();

            $this->mapDocumentRoutes();

            $this->mapFinancingRoutes();

            $this->mapFormRoutes();

            $this->mapGroupRoutes();

            $this->mapGroupServiceProfileRoutes();

            $this->mapLogRoutes();

            $this->mapNationalityRoutes();

            $this->mapOrderRoutes();

            $this->mapReportRoutes();

            $this->mapRoleRoutes();

            $this->mapServiceRoutes();

            $this->mapServiceProcedureRoutes();

            $this->mapServiceProfileRoutes();

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

        protected function mapAccountsRoutes()
        {
            Route::prefix('api/v1/accounts')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/accounts.php'));
        }

        protected function mapActionRoutes()
        {
            Route::prefix('api/v1/actions')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/actions.php'));
        }

        protected function mapAppRoutes()
        {
            Route::prefix('api/v1/app')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/app.php'));
        }

        protected function mapAttendanceRoutes()
        {
            Route::prefix('api/v1/attendance')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/attendance.php'));
        }

        protected function mapBranchRoutes()
        {
            Route::prefix('api/v1/branches')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/branch.php'));
        }

        protected function mapBreakdownRoutes()
        {
            Route::prefix('api/v1/breakdowns')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/breakdown.php'));
        }

        protected function mapClientDocuments()
        {
            Route::prefix('api/v1/client-documents')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/client-documents.php'));
        }

        protected function mapClientDocumentTypeRoutes()
        {
            Route::prefix('api/v1/client-document-types')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/client-document-types.php'));
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

        protected function mapDashboardRoutes()
        {
            Route::prefix('api/v1/dashboard')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/dashboard.php'));
        }

        protected function mapDepartmentRoutes()
        {
            Route::prefix('api/v1/department')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/department.php'));
        }


        protected function mapDocumentRoutes()
        {
            Route::prefix('api/v1/documents')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/document.php'));
        }

        protected function mapFinancingRoutes()
        {
            Route::prefix('api/v1/financing')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/financing.php'));
        }

        protected function mapFormRoutes()
        {
            Route::prefix('api/v1/forms')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/forms.php'));
        }

        protected function mapGroupRoutes()
        {
            Route::prefix('api/v1/groups')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/group.php'));
        }

        protected function mapGroupServiceProfileRoutes() {
            Route::prefix('api/v1/group-service-profile')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/group-service-profile.php'));
        }

        protected function mapLogRoutes()
        {
            Route::prefix('api/v1/logs')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/logs.php'));
        }

        protected function mapNationalityRoutes()
        {
            Route::prefix('api/v1/nationalities')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/nationality.php'));
        }

        protected function mapOrderRoutes()
        {
            Route::prefix('api/v1/order')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/order.php'));
        }

        protected function mapRoleRoutes() {
            Route::prefix('api/v1/roles')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/role.php'));
        }

        protected function mapReportRoutes() {
            Route::prefix('api/v1/reports')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/report.php'));
        }

        protected function mapServiceRoutes() {
            Route::prefix('api/v1/services')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/service.php'));
        }

        protected function mapServiceProcedureRoutes()
        {
            Route::prefix('api/v1/service-procedures')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/service-procedure.php'));
        }

        protected function mapServiceProfileRoutes() {
            Route::prefix('api/v1/service-profiles')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/service-profile.php'));
        }

        protected function mapUserRoutes()
        {
            Route::prefix('api/v1')
                 ->middleware('api')
                 ->namespace($this->namespace)
                 ->group(base_path('routes/api/user.php'));
        }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AuthPermServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerBladeExtensions();
    }

    protected function registerBladeExtensions()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('superadmin', function () {
            return '<?php if (Auth::check() && Auth::user()->isSuperAdmin()): ?>';
        });

        $blade->directive('endsuperadmin', function () {
            return '<?php endif; ?>';
        });

        $blade->directive('permission', function ($expression) {
            return "<?php if (Auth::check() && Auth::user()->hasPermission($expression)): ?>";
        });

        $blade->directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        $blade->directive('anygate', function ($expression) {
            return "<?php if (Auth::check() && Auth::user()->canAnyGate({$expression})): ?>";
        });

        $blade->directive('endanygate', function () {
            return '<?php endif; ?>';
        });

        $blade->directive('anypermission', function ($expression) {
            return "<?php if (Auth::check() && Auth::user()->hasAnyPermission({$expression})): ?>";
        });

        $blade->directive('endanypermission', function () {
            return '<?php endif; ?>';
        });

        $blade->directive('allpermission', function ($expression) {
            return "<?php if (Auth::check() && Auth::user()->hasAllPermissions({$expression})): ?>";
        });

        $blade->directive('endallpermission', function () {
            return '<?php endif; ?>';
        });

        $blade->directive('level', function ($expression) {
            $level = trim($expression, '()');

            return "<?php if (Auth::check() && Auth::user()->level() >= {$level}): ?>";
        });

        $blade->directive('endlevel', function () {
            return '<?php endif; ?>';
        });
    }
}

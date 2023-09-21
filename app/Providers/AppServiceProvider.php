<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        Paginator::useBootstrap();

        Builder::macro('whereLike', function(string $f, string $s) {
            return $s ? $this->orWhere($f, 'LIKE', '%'.$s.'%') : $this;
        });

        Builder::macro('search', function($f, $s) {
            return $s ? $this->where($f, 'LIKE', '%'.$s.'%') : $this;
        });

        Builder::macro('orSearch', function($f, $s) {
            return $s ? $this->whereLike($f, $s) : $this;
        });

        Collection::macro('recursive', function () {
            return $this->map(function ($value) {
                if (is_array($value) || is_object($value)) {
                    return collect($value)->recursive();
                }

                return $value;
            });
        });

        Log::shareContext([
            'invocation-id' => (string) Str::uuid(),
        ]);

        \Illuminate\Support\Collection::macro('recursive', function () {
            return $this->map(function ($value) {
                if (is_array($value) || is_object($value)) {
                    return collect($value)->recursive();
                }
                return $value;
            });
        });

        if ($request->server->has('HTTP_X_ORIGINAL_HOST')) {
            $this->app['url']->forceRootUrl('http://'.$request->server->get('HTTP_X_ORIGINAL_HOST'));
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        $this->app->alias('bugsnag.logger', \Illuminate\Contracts\Logging\Log::class);
        $this->app->alias('bugsnag.logger', \Psr\Log\LoggerInterface::class);
    }
}

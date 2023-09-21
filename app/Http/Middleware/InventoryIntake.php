<?php

namespace App\Http\Middleware;

use App\BatchLocation;
use Closure;

class InventoryIntake
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $inventory_intake = BatchLocation::needIntakeApproval()->count();

        app()->instance('inventory_intake', $inventory_intake);

        view()->share('inventory_count', $inventory_intake);

        return $next($request);
    }
}

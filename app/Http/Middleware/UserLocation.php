<?php

namespace App\Http\Middleware;

use App\Location;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserLocation
{
    public function __construct()
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $the_hub = new Location(['id'=>0,'name' => 'All']);

        if (Auth::check()) {
            $my_locations = Location::availableLocations()->get();
            $active_locations = Location::activeLocations()->get();

            Auth::user()->my_locations = $my_locations;
            Auth::user()->only_my_locations = clone $my_locations;
            Auth::user()->active_locations = $active_locations;

            if (Auth::user()->isAdmin()) {
                $my_locations->prepend($the_hub);
            }
        }

        if (Auth::check() && ! Auth::user()->current_location->exists && Auth::user()->my_locations->count()) {
            Auth::user()->current_location = Auth::user()->my_locations->first();
        }

        //set the location to user model if location being set via URI route
        if ($request->route()->getName() == 'switch-location') {
            $location = $request->route('location');
            if (is_null($location)) {
                $location = $the_hub;
            }

            if (Auth::user()->my_locations->contains($location)) {
                Auth::user()->current_location = $location;
            }
        }

//        dump(Auth::user()->current_location);
        //dd(Auth::user()->my_locations);
        //if user is manager or sales rep and no location association log out.
        if (Auth::check() && Auth::user()->hasRole(['locationmanager', 'salesrep']) && ! Auth::user()->current_location->exists) {
            flash()->error('no location');
            Auth::logout();
            Session::flush();

            return redirect('/');
        }

        if(Auth::check()) {
            debug('current location');
            debug(Auth::user()->current_location->name);

            debug('active locations');
            debug(Auth::user()->active_locations->pluck('name'));

            debug('my locations');
            debug(Auth::user()->my_locations->pluck('name'));

            debug('only my locations');
            debug(Auth::user()->only_my_locations->pluck('name'));
        }

        return $next($request);
    }
}

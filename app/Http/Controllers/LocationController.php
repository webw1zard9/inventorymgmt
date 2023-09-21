<?php

namespace App\Http\Controllers;

use App\Events\LocationCreated;
use App\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Gate::denies('locations.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Locations');

        $locations = Location::withTrashed()->get();

        return view('locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Gate::denies('locations.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        return view('locations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Gate::denies('locations.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'name' => 'required|min:2',
        ];

        $validator = Validator::make($request->all(), $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('locations.create'))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            try {
                $location = Location::create([
                    'name' => $request->get('name'),
                    'address' => $request->get('address'),
                    'address2' => $request->get('address2'),
                    'active' => ($request->has('active') ? 1 : 0),
                ]);

                event(new LocationCreated($location));

                flash()->success('Successfully created location: '.$location->name);

                return redirect(route('locations.index'));
            } catch (\Exception $e) {
                flash()->error($e->getMessage());

                return redirect(route('locations.create'))
                    ->withInput($request->all());
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function show(Location $location)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function edit(Location $location)
    {
        if (Gate::denies('locations.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        return view('locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Location $location)
    {
        if (Gate::denies('locations.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'name' => 'required|min:2',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect(route('locations.edit', $location->id))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            $location->name = $request->get('name');
            $location->address = $request->get('address');
            $location->address2 = $request->get('address2');
            $location->active = ($request->has('active') ? 1 : 0);
            $location->save();

            flash()->success('Successfully updated location');

            return redirect(route('locations.index'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Location $location)
    {
        if (Gate::denies('locations.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $location->delete();

        flash()->success($location->name.' Successfully Deleted!');

        return redirect(route('locations.index'));
    }

    public function restore(Location $location)
    {
        $location->restore();
        flash()->success($location->name.' Successfully Restored!');

        return redirect(route('locations.index'));
    }

}

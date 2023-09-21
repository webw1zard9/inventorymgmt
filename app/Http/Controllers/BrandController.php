<?php

namespace App\Http\Controllers;

use App\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Gate::denies('brands.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Brands');

        $brands = Brand::orderBy('name')->with('batches')->get();

        return view('brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Gate::denies('brands.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Create Brand');

        return view('brands.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Gate::denies('brands.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'name' => 'required|min:2',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect(route('brands.create'))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            try {
                $brand = Brand::create([
                    'name' => $request->get('name'),
                    'is_active' => ($request->has('is_active') ? 1 : 0),
                ]);

                flash()->success('Successfully created brand: '.$brand->name);

                return redirect(route('brands.index'));
            } catch (\Exception $e) {
                flash()->error($e->getMessage());

                return redirect(route('brands.create'))
                    ->withInput($request->all());
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Brand $brand)
    {
        if (Gate::denies('brands.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Edit Brand');

        return view('brands.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Brand $brand)
    {
        if (Gate::denies('brands.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'name' => 'required|min:2',
        ];

        try {
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect(route('brands.edit', $brand->id))
                    ->withErrors($validator)
                    ->withInput($request->all());
            } else {
                $brand->name = $request->get('name');
                $brand->is_active = ($request->has('is_active') ? 1 : 0);
                $brand->save();

                flash()->success('Successfully updated brand');

                return redirect(route('brands.index'));
            }
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return redirect(route('brands.edit'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Brand $brand)
    {
        if (Gate::denies('brands.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $brand_name = $brand->name;

        try {
            $brand->delete();
            flash()->success($brand_name.' Successfully Deleted!');

            return redirect(route('brands.index'));
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return redirect(route('brands.index'));
        }
    }
}

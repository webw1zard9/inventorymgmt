<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Gate::denies('categories.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Categories');

        $categories = Category::orderBy('name')->with('batches')->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Gate::denies('categories.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Create Category');

        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Gate::denies('categories.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'name' => 'required|min:2',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect(route('locations.create'))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            try {
                $category = Category::create([
                    'name' => $request->get('name'),
                    'is_active' => ($request->has('is_active') ? 1 : 0),
                ]);

                flash()->success('Successfully created category: '.$category->name);

                return redirect(route('categories.index'));
            } catch (\Exception $e) {
                flash()->error($e->getMessage());

                return redirect(route('categories.create'))
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
    public function edit(Category $category)
    {
        if (Gate::denies('categories.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Edit Category');

        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        if (Gate::denies('categories.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'name' => 'required|min:2',
        ];

        try {
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect(route('categories.edit', $category->id))
                    ->withErrors($validator)
                    ->withInput($request->all());
            } else {
                $category->name = $request->get('name');
                $category->is_active = ($request->has('is_active') ? 1 : 0);
                $category->save();

                flash()->success('Successfully updated category');

                return redirect(route('categories.index'));
            }
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return redirect(route('categories.edit'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        if (Gate::denies('categories.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        $cat_name = $category->name;

        try {
            $category->delete();
            flash()->success($cat_name.' Successfully Deleted!');

            return redirect(route('categories.index'));
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return redirect(route('categories.index'));
        }
    }
}

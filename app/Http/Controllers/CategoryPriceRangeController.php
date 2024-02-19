<?php

namespace App\Http\Controllers;

use App\Category;
use App\CategoryPriceRange;
use Illuminate\Http\Request;

class CategoryPriceRangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Category $category)
    {
        view()->share('title', 'Settings / Categories / Price Ranges');

        return view('categories.price-ranges.index', compact('category'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Category $category)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Category $category)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category, CategoryPriceRange $categoryPriceRange)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category, CategoryPriceRange $categoryPriceRange)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category, CategoryPriceRange $categoryPriceRange)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category, CategoryPriceRange $categoryPriceRange)
    {
        //
    }
}

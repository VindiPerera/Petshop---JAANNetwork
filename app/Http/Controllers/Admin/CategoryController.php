<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Category::create($request->validate(['name' => 'required|string|max:100|unique:categories,name']));
        return back()->with('ok', 'Category added.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('ok', 'Category deleted.');
    }
}

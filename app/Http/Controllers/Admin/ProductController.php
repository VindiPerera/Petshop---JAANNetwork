<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%")))
            ->orderBy('name')->paginate(25)->withQueryString();
        return view('admin.products.index', compact('products'));
    }

    public function create() { return view('admin.products.form', ['product' => new Product(['unit' => 'pcs', 'reorder_level' => 5, 'is_active' => true]), 'categories' => Category::orderBy('name')->get()]); }

    public function store(Request $request)
    {
        Product::create($this->rules($request) + ['is_active' => true]);
        return redirect()->route('admin.products.index')->with('ok', 'Product created.');
    }

    public function edit(Product $product) { return view('admin.products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]); }

    public function update(Request $request, Product $product)
    {
        $product->update($this->rules($request, $product->id) + ['is_active' => $request->boolean('is_active')]);
        return redirect()->route('admin.products.index')->with('ok', 'Product updated.');
    }

    private function rules(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku,'.$id,
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:20',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    /** Products whose labels this user may print (shops: only what they hold; admin: everything). */
    private function products(Request $request)
    {
        $q = Product::with('category')->where('is_active', true)->orderBy('name');
        if (! $request->user()->isAdmin()) {
            $q->whereIn('id', Stock::where('branch_id', $request->user()->branch_id)->pluck('product_id'));
        }
        return $q;
    }

    public function index(Request $request)
    {
        $products = $this->products($request)
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%")->orWhere('barcode', 'like', "%$s%")))
            ->get();
        return view('barcodes.index', ['products' => $products, 'preselect' => $request->integer('product')]);
    }

    public function print(Request $request)
    {
        $data = $request->validate([
            'copies' => 'required|array',
            'copies.*' => 'nullable|integer|min:0|max:500',
            'size' => 'required|in:small,medium,large',
            'show_price' => 'nullable|boolean',
            'show_name' => 'nullable|boolean',
        ]);
        $copies = collect($data['copies'])->filter(fn ($n) => (int) $n > 0);
        abort_if($copies->isEmpty(), 422, 'Enter at least one label quantity.');

        $products = $this->products($request)->whereIn('id', $copies->keys())->get();
        $labels = $products->flatMap(fn ($p) => array_fill(0, (int) $copies[$p->id], $p));

        return view('barcodes.print', [
            'labels' => $labels, 'size' => $data['size'],
            'showPrice' => $request->boolean('show_price'), 'showName' => $request->boolean('show_name', true),
            'currency' => $request->user()->isAdmin() ? 'Rs.' : ($request->user()->branch->currency ?: 'Rs.'),
        ]);
    }
}

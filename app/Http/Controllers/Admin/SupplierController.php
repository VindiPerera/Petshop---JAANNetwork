<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('admin.suppliers', ['suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Supplier::create($request->validate([
            'name' => 'required|string|max:255', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email', 'address' => 'nullable|string|max:255',
        ]));
        return back()->with('ok', 'Supplier added.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return back()->with('ok', 'Supplier deleted.');
    }
}

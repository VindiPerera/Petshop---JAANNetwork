<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $customers = Customer::with('branch')->withCount('sales')->withSum('sales', 'total')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('pet_name', 'like', "%$s%")))
            ->orderBy('name')->paginate(25)->withQueryString();
        return view('customers.index', ['customers' => $customers, 'branches' => $this->branchOptions()]);
    }

    public function create() { return view('customers.form', ['customer' => new Customer()]); }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        // Branch users own their customers; admin-created customers have no branch.
        $customer = Customer::create($data + ['branch_id' => $request->user()->branch_id]);
        if ($request->wantsJson()) {
            return response()->json($customer->only('id', 'name', 'phone'));
        }
        return redirect()->route('customers.index')->with('ok', 'Customer saved.');
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($request, $customer);
        $sales = $customer->sales()->with('branch')->latest()->paginate(15);
        return view('customers.show', compact('customer', 'sales'));
    }

    public function edit(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($request, $customer);
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($request, $customer);
        $customer->update($this->validated($request));
        return redirect()->route('customers.index')->with('ok', 'Customer updated.');
    }

    private function authorizeCustomer(Request $request, Customer $customer): void
    {
        abort_unless($request->user()->isAdmin() || $customer->branch_id === $request->user()->branch_id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255', 'phone' => 'nullable|string|max:30', 'email' => 'nullable|email',
            'address' => 'nullable|string|max:255', 'pet_name' => 'nullable|string|max:100', 'pet_type' => 'nullable|string|max:100',
        ]);
    }
}

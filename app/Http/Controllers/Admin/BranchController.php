<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('users')->orderBy('type')->orderBy('name')->get();
        return view('admin.branches.index', compact('branches'));
    }

    public function create() { return view('admin.branches.form', ['branch' => new Branch(['type' => 'shop', 'is_active' => true])]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'login_name' => 'required|string|max:255',
            'login_email' => 'required|email|unique:users,email',
            'login_password' => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($data) {
            $branch = Branch::create([
                'name' => $data['name'], 'code' => strtoupper($data['code']), 'type' => 'shop',
                'address' => $data['address'] ?? null, 'phone' => $data['phone'] ?? null,
            ]);
            User::create([
                'name' => $data['login_name'], 'email' => $data['login_email'],
                'password' => $data['login_password'], 'role' => 'branch', 'branch_id' => $branch->id,
            ]);
        });

        return redirect()->route('admin.branches.index')->with('ok', 'Branch and login created.');
    }

    public function edit(Branch $branch) { return view('admin.branches.form', compact('branch')); }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code,'.$branch->id,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);
        $branch->update($data + ['is_active' => $branch->type === 'warehouse' ? true : $request->boolean('is_active')]);
        return redirect()->route('admin.branches.index')->with('ok', 'Branch updated.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

/** Shop logins (cashiers / managers) assigned to a branch. */
class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branch')->orderBy('role')->orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create() { return view('admin.users.form', ['user' => new User(['is_active' => true]), 'branches' => Branch::shops()->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'branch_id' => 'required|exists:branches,id',
        ]);
        User::create($data + ['role' => 'branch']);
        return redirect()->route('admin.users.index')->with('ok', 'Login created.');
    }

    public function edit(User $user) { return view('admin.users.form', ['user' => $user, 'branches' => Branch::shops()->get()]); }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6',
            'branch_id' => $user->isAdmin() ? 'nullable' : 'required|exists:branches,id',
        ]);
        if (empty($data['password'])) unset($data['password']);
        $data['is_active'] = $user->id === $request->user()->id ? true : $request->boolean('is_active');
        $user->update($data);
        return redirect()->route('admin.users.index')->with('ok', 'Login updated.');
    }
}

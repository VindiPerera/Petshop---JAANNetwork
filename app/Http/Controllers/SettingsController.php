<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/** Each shop edits the details printed on its bills. */
class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('settings', ['branch' => $request->user()->branch]);
    }

    public function update(Request $request)
    {
        $branch = $request->user()->branch;
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_no' => 'nullable|string|max:50',
            'currency' => 'required|string|max:10',
            'receipt_footer' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
        ]);

        if ($request->hasFile('logo')) {
            $dir = public_path('uploads/logos');
            File::ensureDirectoryExists($dir);
            $name = 'branch-'.$branch->id.'-'.time().'.'.$request->file('logo')->extension();
            $request->file('logo')->move($dir, $name);
            if ($branch->logo_path) {
                File::delete(public_path($branch->logo_path));
            }
            $data['logo_path'] = 'uploads/logos/'.$name;
        }
        if ($request->boolean('remove_logo') && ! $request->hasFile('logo')) {
            if ($branch->logo_path) {
                File::delete(public_path($branch->logo_path));
            }
            $data['logo_path'] = null;
        }
        unset($data['logo']);

        $branch->update($data);
        return back()->with('ok', 'Shop details saved. They will appear on your next printed bill.');
    }
}

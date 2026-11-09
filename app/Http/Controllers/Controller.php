<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Branch users are locked to their own branch; admins may filter with ?branch_id= (null = all). */
    protected function scopeBranchId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return $user->branch_id;
        }
        return $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
    }

    /** 'pdf' | 'xlsx' when the request asks for a download, otherwise null. */
    protected function exportFormat(Request $request): ?string
    {
        return in_array($request->input('export'), ['pdf', 'xlsx'], true) ? $request->input('export') : null;
    }

    /**
     * Money value of a discount typed as a fixed amount or a percentage of $base.
     * Never more than $base, never negative; percentages are capped at 100.
     */
    protected function discountAmount(float|int|string|null $value, ?string $type, float $base): float
    {
        $value = max((float) $value, 0);
        $amount = $type === 'percent' ? $base * min($value, 100) / 100 : $value;
        return round(min($amount, $base), 2);
    }

    protected function branchOptions()
    {
        return Branch::orderBy('type')->orderBy('name')->get();
    }
}

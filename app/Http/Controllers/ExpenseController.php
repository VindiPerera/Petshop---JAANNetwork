<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Shop/warehouse running costs. Admin manages every location; a branch manages its own. */
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->scopeBranchId($request);
        $valid = fn ($d) => $d && strtotime($d) ? date('Y-m-d', strtotime($d)) : null;
        $from = $valid($request->input('from')) ?? now()->startOfMonth()->toDateString();
        $to = $valid($request->input('to')) ?? now()->toDateString();

        $query = Expense::with('branch', 'user')->overlapping($from, $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(in_array($request->type, ['single', 'range'], true), fn ($q) => $q->where('type', $request->type))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->q, fn ($q, $s) => $q->where('title', 'like', "%$s%"));

        $all = (clone $query)->get();
        $byCategory = $all->groupBy('category')->map(fn ($g) => $g->sum(fn ($e) => $e->allocatedBetween($from, $to)))->sortDesc();
        $expenses = $query->orderByDesc('start_date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses, 'from' => $from, 'to' => $to, 'branches' => $this->branchOptions(),
            'total' => $byCategory->sum(), 'byCategory' => $byCategory,
        ]);
    }

    public function create(Request $request)
    {
        return view('expenses.form', ['expense' => new Expense(['type' => 'single', 'start_date' => now(), 'end_date' => now()]), 'branches' => $this->locations($request)]);
    }

    public function store(Request $request)
    {
        $expense = Expense::create($this->validated($request) + ['user_id' => $request->user()->id]);
        return redirect()->route('expenses.index')->with('ok', "Expense '{$expense->title}' saved.");
    }

    public function edit(Request $request, Expense $expense)
    {
        $this->authorizeExpense($request, $expense);
        return view('expenses.form', ['expense' => $expense, 'branches' => $this->locations($request)]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeExpense($request, $expense);
        $expense->update($this->validated($request));
        return redirect()->route('expenses.index')->with('ok', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeExpense($request, $expense);
        $expense->delete();
        return redirect()->route('expenses.index')->with('ok', 'Expense deleted.');
    }

    private function validated(Request $request): array
    {
        $user = $request->user();
        $data = $request->validate([
            'branch_id' => $user->isAdmin() ? 'required|exists:branches,id' : 'nullable',
            'type' => ['required', Rule::in(['single', 'range'])],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:9999999999',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:type,range',
            'note' => 'nullable|string|max:1000',
        ]);
        $data['branch_id'] = $user->isAdmin() ? (int) $data['branch_id'] : $user->branch_id;
        if ($data['type'] === 'single') {
            $data['end_date'] = $data['start_date'];
        }
        return $data;
    }

    private function locations(Request $request)
    {
        $user = $request->user();
        return $user->isAdmin() ? Branch::where('is_active', true)->orderBy('type')->orderBy('name')->get() : collect([$user->branch]);
    }

    private function authorizeExpense(Request $request, Expense $expense): void
    {
        abort_unless($request->user()->isAdmin() || $expense->branch_id === $request->user()->branch_id, 403);
    }
}

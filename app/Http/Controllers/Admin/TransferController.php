<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** GRN: a single note that moves many products from the warehouse to a shop. */
class TransferController extends Controller
{
    public function index()
    {
        $transfers = Transfer::with('to', 'user')->withCount('items')->latest()->paginate(20);
        return view('admin.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouse = Branch::warehouse();
        $stock = Stock::where('branch_id', $warehouse->id)->pluck('quantity', 'product_id');
        $products = Product::where('is_active', true)->orderBy('name')->get()
            ->each(fn ($p) => $p->wh_stock = (int) ($stock[$p->id] ?? 0));
        return view('admin.transfers.create', ['shops' => Branch::shops()->where('is_active', true)->get(), 'products' => $products]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'to_branch_id' => 'required|exists:branches,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        abort_unless(Branch::shops()->whereKey($data['to_branch_id'])->exists(), 422, 'Destination must be a shop.');

        $transfer = DB::transaction(function () use ($data, $request) {
            $warehouse = Branch::warehouse();
            $transfer = Transfer::create([
                'grn_no' => (string) Str::uuid(),
                'from_branch_id' => $warehouse->id, 'to_branch_id' => $data['to_branch_id'],
                'user_id' => $request->user()->id, 'note' => $data['note'] ?? null,
            ]);
            $transfer->update(['grn_no' => 'GRN-'.str_pad($transfer->id, 6, '0', STR_PAD_LEFT)]);
            foreach ($data['items'] as $row) {
                $transfer->items()->create($row);
                StockService::change($warehouse->id, $row['product_id'], -$row['quantity'], 'transfer_out', $transfer->grn_no, $request->user()->id);
                StockService::change($data['to_branch_id'], $row['product_id'], $row['quantity'], 'transfer_in', $transfer->grn_no, $request->user()->id);
            }
            return $transfer;
        });

        return redirect()->route('admin.transfers.show', $transfer)->with('ok', 'GRN created and stock moved to the shop.');
    }

    public function show(Transfer $transfer)
    {
        $transfer->load('from', 'to', 'user', 'items.product');
        return view('admin.transfers.show', compact('transfer'));
    }
}

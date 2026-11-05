<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

/**
 * Single place that changes stock levels. Always call inside a DB transaction.
 */
class StockService
{
    public static function change(int $branchId, int $productId, int $qty, string $type, ?string $reference, int $userId): void
    {
        $stock = Stock::where(['branch_id' => $branchId, 'product_id' => $productId])->lockForUpdate()->first()
            ?? Stock::create(['branch_id' => $branchId, 'product_id' => $productId, 'quantity' => 0]);

        if ($stock->quantity + $qty < 0) {
            throw ValidationException::withMessages([
                'items' => "Insufficient stock for product #{$productId} (available {$stock->quantity}).",
            ]);
        }

        $stock->increment('quantity', $qty);

        StockMovement::create([
            'branch_id' => $branchId, 'product_id' => $productId, 'type' => $type,
            'quantity' => $qty, 'reference' => $reference, 'user_id' => $userId,
        ]);
    }

    public static function available(int $branchId, int $productId): int
    {
        return (int) Stock::where(['branch_id' => $branchId, 'product_id' => $productId])->value('quantity');
    }
}

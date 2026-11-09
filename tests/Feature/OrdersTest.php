<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $shop = Branch::create(['name' => 'Shop A', 'code' => 'A', 'type' => 'shop']);
        $other = Branch::create(['name' => 'Shop B', 'code' => 'B', 'type' => 'shop']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@x.test', 'password' => 'secret1', 'role' => 'admin', 'is_active' => true]);
        $user = User::create(['name' => 'A', 'email' => 'sa@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $shop->id, 'is_active' => true]);
        $stranger = User::create(['name' => 'B', 'email' => 'sb@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $other->id, 'is_active' => true]);
        $p = Product::create(['sku' => 'P1', 'name' => 'Food', 'cost_price' => 100, 'sale_price' => 150]);
        Stock::create(['branch_id' => $shop->id, 'product_id' => $p->id, 'quantity' => 10]);
        return compact('shop', 'admin', 'user', 'stranger', 'p');
    }

    private function orderPayload(int $branchId, int $productId): array
    {
        return [
            'branch_id' => $branchId, 'customer_name' => 'Nimal', 'customer_phone' => '0771234567', 'source' => 'facebook',
            'discount' => 20, 'delivery_fee' => 50,
            'items' => [['product_id' => $productId, 'quantity' => 2, 'unit_price' => 150]],
        ];
    }

    public function test_admin_creates_order_and_bills_it_into_sales_and_reports(): void
    {
        extract($this->world());

        $this->actingAs($admin)->post('/orders', $this->orderPayload($shop->id, $p->id))->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertEquals(330.0, (float) $order->total); // 300 - 20 + 50
        $this->assertSame(10, (int) Stock::where('branch_id', $shop->id)->value('quantity')); // no stock taken until billed

        $this->actingAs($admin)->post("/orders/{$order->id}/bill", ['payments' => [['method' => 'cash', 'amount' => 400]]])->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $sale = Sale::firstOrFail();
        $this->assertSame('online', $sale->channel);
        $this->assertSame($order->id, $sale->order_id);
        $this->assertEquals(330.0, (float) $sale->total);
        $this->assertEquals(330.0, (float) $sale->payments()->sum('amount')); // net of change
        $this->assertSame(8, (int) Stock::where('branch_id', $shop->id)->value('quantity'));
        $this->assertEquals(280.0, (float) $sale->items()->sum('line_total')); // 300 - 20 discount

        $this->actingAs($admin)->get('/reports/sales?from=2000-01-01&to=2100-01-01')->assertOk()->assertSee('Online orders');
        $this->actingAs($admin)->get('/reports/sales?export=xlsx&from=2000-01-01&to=2100-01-01')->assertOk();

        // Cannot be billed twice
        $this->actingAs($admin)->post("/orders/{$order->id}/bill", ['payments' => [['method' => 'cash', 'amount' => 400]]])->assertSessionHasErrors('order');
        $this->assertSame(1, Sale::count());
    }

    public function test_underpayment_and_insufficient_stock_are_rejected(): void
    {
        extract($this->world());
        $this->actingAs($user)->post('/orders', $this->orderPayload($shop->id, $p->id))->assertRedirect();
        $order = Order::firstOrFail();

        $this->actingAs($user)->post("/orders/{$order->id}/bill", ['payments' => [['method' => 'cash', 'amount' => 100]]])->assertSessionHasErrors('payments');
        $this->assertSame(0, Sale::count());

        Stock::where('branch_id', $shop->id)->update(['quantity' => 1]);
        $this->actingAs($user)->post("/orders/{$order->id}/bill", ['payments' => [['method' => 'cash', 'amount' => 330]]])->assertSessionHasErrors('items');
        $this->assertSame(0, Sale::count());
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_branch_user_is_locked_to_own_branch(): void
    {
        extract($this->world());
        // branch_id in the payload is ignored for branch users
        $this->actingAs($user)->post('/orders', $this->orderPayload($stranger->branch_id, $p->id))->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame($shop->id, $order->branch_id);

        $this->actingAs($stranger)->get("/orders/{$order->id}")->assertForbidden();
        $this->actingAs($stranger)->post("/orders/{$order->id}/bill", ['payments' => [['method' => 'cash', 'amount' => 330]]])->assertForbidden();
        $this->actingAs($user)->post("/orders/{$order->id}/status", ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
    }
}

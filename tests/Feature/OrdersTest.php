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

    public function test_percent_and_amount_discounts_per_product_and_per_order(): void
    {
        extract($this->world());
        $p2 = Product::create(['sku' => 'P2', 'name' => 'Toy', 'cost_price' => 50, 'sale_price' => 80]);
        Stock::create(['branch_id' => $shop->id, 'product_id' => $p2->id, 'quantity' => 10]);

        // Food 2 x 150 = 300, 10% off -> 270. Toy 1 x 80, Rs 30 off -> 50. Net 320, order discount 25% -> 80 => 240, +50 delivery = 290
        $this->actingAs($user)->post('/orders', [
            'customer_name' => 'Nimal', 'source' => 'phone', 'delivery_fee' => 50, 'discount' => 25, 'discount_type' => 'percent',
            'items' => [
                ['product_id' => $p->id, 'quantity' => 2, 'unit_price' => 150, 'discount' => 10, 'discount_type' => 'percent'],
                ['product_id' => $p2->id, 'quantity' => 1, 'unit_price' => 80, 'discount' => 30, 'discount_type' => 'amount'],
            ],
        ])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertEquals(380.0, (float) $order->subtotal);
        $this->assertEquals(80.0, (float) $order->discount);
        $this->assertEquals(290.0, (float) $order->total);

        $this->actingAs($user)->post("/orders/{$order->id}/bill", ['payments' => [['method' => 'card', 'amount' => 290]]])->assertRedirect();
        $sale = Sale::firstOrFail();
        $this->assertEquals(290.0, (float) $sale->total);
        $this->assertEquals(380.0 - 320.0 + 80.0, (float) $sale->discount); // item discounts 60 + order discount 80
        // gross - all discounts = revenue of the lines (delivery fee is not product revenue)
        $this->assertEquals(240.0, (float) $sale->items()->sum('line_total'));
        $this->assertEquals(380.0, (float) $sale->items->sum(fn ($i) => $i->quantity * $i->unit_price));
    }

    public function test_discounts_never_exceed_the_amount_they_apply_to(): void
    {
        extract($this->world());
        $this->actingAs($user)->post('/orders', [
            'customer_name' => 'X', 'source' => 'phone', 'discount' => 500, 'discount_type' => 'amount',
            'items' => [['product_id' => $p->id, 'quantity' => 1, 'unit_price' => 150, 'discount' => 300, 'discount_type' => 'percent']],
        ])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertEquals(0.0, (float) $order->total);
        $this->assertEquals(150.0, (float) $order->items->first()->discount);
    }

    public function test_pos_accepts_percent_discounts(): void
    {
        extract($this->world());
        // 2 x 150 = 300; line 10% -> 270; bill 10% of 270 -> 27; total 243
        $this->actingAs($user)->postJson('/pos', [
            'discount' => 10, 'discount_type' => 'percent',
            'items' => [['product_id' => $p->id, 'quantity' => 2, 'discount' => 10, 'discount_type' => 'percent']],
            'payments' => [['method' => 'cash', 'amount' => 243]],
        ])->assertOk();
        $sale = Sale::firstOrFail();
        $this->assertEquals(243.0, (float) $sale->total);
        $this->assertEquals(57.0, (float) $sale->discount);
        $this->assertEquals(243.0, (float) $sale->items()->sum('line_total'));
    }

    public function test_main_admin_can_bill_at_pos_from_any_location(): void
    {
        extract($this->world());

        $this->actingAs($admin)->get('/pos')->assertOk(); // defaults to the first location
        $this->actingAs($admin)->get('/pos?branch_id='.$shop->id)->assertOk()->assertSee('Food');

        $this->actingAs($admin)->postJson('/pos', [
            'branch_id' => $shop->id,
            'items' => [['product_id' => $p->id, 'quantity' => 3]],
            'payments' => [['method' => 'cash', 'amount' => 450]],
        ])->assertOk();

        $sale = Sale::firstOrFail();
        $this->assertSame($shop->id, $sale->branch_id);
        $this->assertSame('pos', $sale->channel);
        $this->assertSame(7, (int) Stock::where('branch_id', $shop->id)->value('quantity'));

        // POS and online bills land in the same Sales list and reports
        $this->actingAs($admin)->get('/sales')->assertOk()->assertSee($sale->invoice_no);
        $this->actingAs($admin)->get('/sales?channel=online')->assertOk()->assertDontSee($sale->invoice_no);
        $this->actingAs($admin)->get('/reports/sales?from=2000-01-01&to=2100-01-01')->assertOk()->assertSee('POS (in shop)');

        // A branch user cannot bill from someone else's shop
        $this->actingAs($stranger)->postJson('/pos', [
            'branch_id' => $shop->id, 'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payments' => [['method' => 'cash', 'amount' => 150]],
        ])->assertStatus(422); // own shop B has no stock; branch_id in the payload is ignored
        $this->assertSame(1, Sale::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetShopFlowTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $wh = Branch::create(['name' => 'WH', 'code' => 'WH', 'type' => 'warehouse']);
        $a = Branch::create(['name' => 'Shop A', 'code' => 'A', 'type' => 'shop']);
        $b = Branch::create(['name' => 'Shop B', 'code' => 'B', 'type' => 'shop']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@x.test', 'password' => 'secret1', 'role' => 'admin', 'is_active' => true]);
        $ua = User::create(['name' => 'A', 'email' => 'sa@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $a->id, 'is_active' => true]);
        $ub = User::create(['name' => 'B', 'email' => 'sb@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $b->id, 'is_active' => true]);
        $p1 = Product::create(['sku' => 'P1', 'name' => 'Food', 'cost_price' => 100, 'sale_price' => 150]);
        $p2 = Product::create(['sku' => 'P2', 'name' => 'Toy', 'cost_price' => 50, 'sale_price' => 80]);
        return compact('wh', 'a', 'b', 'admin', 'ua', 'ub', 'p1', 'p2');
    }

    private function qty(int $branch, int $product): int
    {
        return (int) Stock::where(['branch_id' => $branch, 'product_id' => $product])->value('quantity');
    }

    public function test_full_flow_receive_grn_sell_and_report(): void
    {
        extract($this->world());

        $this->actingAs($admin)->post('/admin/purchases', [
            'received_date' => '2026-11-05',
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 20, 'unit_cost' => 100],
                ['product_id' => $p2->id, 'quantity' => 10, 'unit_cost' => 50],
            ],
        ])->assertRedirect();
        $this->assertSame(20, $this->qty($wh->id, $p1->id));

        // One GRN, many products, warehouse -> Shop A
        $this->actingAs($admin)->post('/admin/transfers', [
            'to_branch_id' => $a->id,
            'items' => [['product_id' => $p1->id, 'quantity' => 8], ['product_id' => $p2->id, 'quantity' => 4]],
        ])->assertRedirect();
        $this->assertSame(12, $this->qty($wh->id, $p1->id));
        $this->assertSame(8, $this->qty($a->id, $p1->id));
        $this->assertSame(4, $this->qty($a->id, $p2->id));

        // Over-transfer is rejected and the whole GRN rolls back
        $this->actingAs($admin)->post('/admin/transfers', [
            'to_branch_id' => $a->id,
            'items' => [['product_id' => $p2->id, 'quantity' => 1], ['product_id' => $p1->id, 'quantity' => 999]],
        ])->assertSessionHasErrors('items');
        $this->assertSame(6, $this->qty($wh->id, $p2->id));

        // POS sale at Shop A
        $this->actingAs($ua)->postJson('/pos', [
            'payments' => [['method' => 'cash', 'amount' => 500]], 'discount' => 10,
            'items' => [['product_id' => $p1->id, 'quantity' => 2], ['product_id' => $p2->id, 'quantity' => 1]],
        ])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(6, $this->qty($a->id, $p1->id));
        $this->assertDatabaseHas('sales', ['branch_id' => $a->id, 'subtotal' => 380, 'discount' => 10, 'total' => 370]);

        // Cannot oversell; cannot underpay (and nothing is deducted)
        $this->actingAs($ua)->postJson('/pos', ['payments' => [['method' => 'cash', 'amount' => 99999]],
            'items' => [['product_id' => $p1->id, 'quantity' => 50]]])->assertStatus(422);
        $this->actingAs($ua)->postJson('/pos', ['payments' => [['method' => 'cash', 'amount' => 1]],
            'items' => [['product_id' => $p1->id, 'quantity' => 1]]])->assertStatus(422);
        $this->assertSame(6, $this->qty($a->id, $p1->id));
        $this->assertSame(1, Sale::count());

        // Reports and pages render for admin and shop
        $this->actingAs($admin)->get('/reports/sales')->assertOk()->assertSee('370.00');
        $this->actingAs($admin)->get('/reports/profit')->assertOk()->assertSee('Shop A');
        $this->actingAs($admin)->get('/reports/stock-value')->assertOk();
        $this->actingAs($admin)->get('/inventory/movements')->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/inventory?branch_id='.$a->id)->assertOk()->assertSee('Food');
        $this->actingAs($ua)->get('/dashboard')->assertOk();
        $this->actingAs($ua)->get('/pos')->assertOk();
        $this->actingAs($ua)->get('/customers')->assertOk();
        $this->actingAs($admin)->get('/admin/transfers/create')->assertOk();
        $this->actingAs($admin)->get('/admin/purchases/create')->assertOk();
    }

    public function test_branch_users_are_isolated_and_locked_out_of_admin(): void
    {
        extract($this->world());
        Stock::create(['branch_id' => $a->id, 'product_id' => $p1->id, 'quantity' => 5]);
        $this->actingAs($ua)->postJson('/pos', ['payments' => [['method' => 'cash', 'amount' => 150]],
            'items' => [['product_id' => $p1->id, 'quantity' => 1]]])->assertOk();
        $sale = Sale::firstOrFail();

        $this->actingAs($ub)->get('/sales/'.$sale->id)->assertForbidden();
        $this->actingAs($ub)->get('/sales?branch_id='.$a->id)->assertOk()->assertDontSee($sale->invoice_no);
        $this->actingAs($ub)->get('/admin/branches')->assertForbidden();
        $this->actingAs($ub)->post('/admin/transfers', [])->assertForbidden();
        $this->actingAs($admin)->get('/pos')->assertForbidden();
        $this->actingAs($admin)->get('/sales/'.$sale->id)->assertOk();
    }

    public function test_branch_creation_creates_working_login(): void
    {
        extract($this->world());
        $this->actingAs($admin)->post('/admin/branches', [
            'name' => 'Kandy', 'code' => 'kdy', 'login_name' => 'Kandy Cashier',
            'login_email' => 'kandy@x.test', 'login_password' => 'kandy123',
        ])->assertRedirect();
        auth()->logout();

        $this->post('/login', ['email' => 'kandy@x.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'kandy@x.test', 'password' => 'kandy123'])->assertRedirect('/dashboard');
        $this->assertSame('KDY', auth()->user()->branch->code);
        $this->get('/dashboard')->assertOk();
    }

    public function test_new_product_can_have_opening_warehouse_stock(): void
    {
        extract($this->world());
        $this->actingAs($admin)->post('/admin/products', [
            'sku' => 'NEW1', 'name' => 'Leash', 'unit' => 'pcs', 'cost_price' => 10, 'sale_price' => 20,
            'reorder_level' => 2, 'opening_qty' => 15,
        ])->assertRedirect();
        $id = Product::where('sku', 'NEW1')->value('id');
        $this->assertSame(15, $this->qty($wh->id, $id));
        $this->actingAs($admin)->get('/admin/products')->assertOk()->assertSee('Warehouse qty')->assertSee('15');
    }
}

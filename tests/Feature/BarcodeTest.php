<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_barcodes_autogenerate_print_and_scan(): void
    {
        Branch::create(['name' => 'WH', 'code' => 'WH', 'type' => 'warehouse']);
        $shop = Branch::create(['name' => 'Shop A', 'code' => 'A', 'type' => 'shop']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@x.test', 'password' => 'secret1', 'role' => 'admin', 'is_active' => true]);
        $user = User::create(['name' => 'A', 'email' => 'sa@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $shop->id, 'is_active' => true]);

        // auto-generated through the admin form, valid EAN-13
        $this->actingAs($admin)->post('/admin/products', ['sku' => 'N1', 'name' => 'New', 'unit' => 'pcs', 'cost_price' => 1, 'sale_price' => 2, 'reorder_level' => 1])->assertSessionHasNoErrors();
        $p = Product::where('sku', 'N1')->firstOrFail();
        $this->assertMatchesRegularExpression('/^\d{13}$/', $p->barcode);
        $sum = 0;
        foreach (str_split(substr($p->barcode, 0, 12)) as $i => $d) { $sum += $d * ($i % 2 ? 3 : 1); }
        $this->assertSame((10 - $sum % 10) % 10, (int) $p->barcode[12]);

        // manual barcode kept, duplicates rejected
        $this->actingAs($admin)->post('/admin/products', ['sku' => 'N2', 'barcode' => '8901234567890', 'name' => 'Own', 'unit' => 'pcs', 'cost_price' => 1, 'sale_price' => 2, 'reorder_level' => 1])->assertSessionHasNoErrors();
        $this->assertSame('8901234567890', Product::where('sku', 'N2')->value('barcode'));
        $this->actingAs($admin)->post('/admin/products', ['sku' => 'N3', 'barcode' => '8901234567890', 'name' => 'Dup', 'unit' => 'pcs', 'cost_price' => 1, 'sale_price' => 2, 'reorder_level' => 1])->assertSessionHasErrors('barcode');

        // label printing renders SVG barcodes; shops only see their own products
        $other = Product::where('sku', 'N2')->first();
        Stock::create(['branch_id' => $shop->id, 'product_id' => $p->id, 'quantity' => 5]);
        $this->actingAs($user)->get('/barcodes')->assertOk()->assertSee('New')->assertDontSee('Own');
        $this->actingAs($user)->post('/barcodes/print', ['size' => 'medium', 'show_price' => 1, 'copies' => [$p->id => 3, $other->id => 2]])
            ->assertOk()->assertSee('<svg', false)->assertSee($p->barcode);
        $html = $this->actingAs($user)->post('/barcodes/print', ['size' => 'medium', 'copies' => [$p->id => 3, $other->id => 2]])->getContent();
        $this->assertSame(3, substr_count($html, 'class="label"'));
        $this->actingAs($user)->post('/barcodes/print', ['size' => 'wide', 'copies' => [$p->id => 1]])->assertOk()->assertSee('size: 50mm 25mm', false);
        $this->actingAs($user)->get('/pos')->assertSee($p->barcode);

        // invoice barcode on the bill
        $this->actingAs($user)->postJson('/pos', ['items' => [['product_id' => $p->id, 'quantity' => 1]], 'payments' => [['method' => 'cash', 'amount' => 2]]])->assertOk();
        $this->actingAs($user)->get('/sales/'.Sale::first()->id)->assertOk()->assertSee('<svg', false)->assertSee('inv-bc', false);
    }
}

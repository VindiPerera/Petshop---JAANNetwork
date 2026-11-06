<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReceiptsAndExportsTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        Branch::create(['name' => 'WH', 'code' => 'WH', 'type' => 'warehouse']);
        $a = Branch::create(['name' => 'Shop A', 'code' => 'A', 'type' => 'shop']);
        $b = Branch::create(['name' => 'Shop B', 'code' => 'B', 'type' => 'shop']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@x.test', 'password' => 'secret1', 'role' => 'admin', 'is_active' => true]);
        $ua = User::create(['name' => 'A', 'email' => 'sa@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $a->id, 'is_active' => true]);
        $ub = User::create(['name' => 'B', 'email' => 'sb@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $b->id, 'is_active' => true]);
        $p1 = Product::create(['sku' => 'P1', 'name' => 'Food', 'cost_price' => 100, 'sale_price' => 150]);
        $p2 = Product::create(['sku' => 'P2', 'name' => 'Toy', 'cost_price' => 50, 'sale_price' => 80]);
        return compact('a', 'b', 'admin', 'ua', 'ub', 'p1', 'p2');
    }

    private function stockShop(array $w, bool $both = true): void
    {
        Stock::create(['branch_id' => $w['a']->id, 'product_id' => $w['p1']->id, 'quantity' => 10]);
        if ($both) {
            Stock::create(['branch_id' => $w['a']->id, 'product_id' => $w['p2']->id, 'quantity' => 10]);
        }
    }

    public function test_split_payment_with_item_discount_and_change(): void
    {
        $w = $this->world();
        extract($w);
        $this->stockShop($w);

        // 2 x 150 (item discount 20) + 1 x 80 = 360; extra discount 10 -> total 350
        $res = $this->actingAs($ua)->postJson('/pos', [
            'discount' => 10,
            'items' => [['product_id' => $p1->id, 'quantity' => 2, 'discount' => 20], ['product_id' => $p2->id, 'quantity' => 1]],
            'payments' => [['method' => 'card', 'amount' => 200], ['method' => 'cash', 'amount' => 200]],
        ])->assertOk();

        $sale = Sale::with('payments', 'items')->firstOrFail();
        $this->assertEquals(380, $sale->subtotal);
        $this->assertEquals(30, $sale->discount);
        $this->assertEquals(350, $sale->total);
        $this->assertSame('split', $sale->payment_method);
        // change (50) comes out of cash, so the stored payments add up to the total
        $this->assertEquals(350, $sale->payments->sum('amount'));
        $this->assertEquals(150, $sale->payments->firstWhere('method', 'cash')->amount);
        $this->assertEquals(350, $sale->items->sum('line_total'));
        $this->assertStringContainsString('print=1', $res->json('url'));

        // Underpaying a split, or paying too much by card, is rejected
        $bad = ['items' => [['product_id' => $p1->id, 'quantity' => 1]]];
        $this->actingAs($ua)->postJson('/pos', $bad + ['payments' => [['method' => 'card', 'amount' => 100], ['method' => 'cash', 'amount' => 10]]])->assertStatus(422);
        $this->actingAs($ua)->postJson('/pos', $bad + ['payments' => [['method' => 'card', 'amount' => 200]]])->assertStatus(422);
        $this->assertSame(1, Sale::count());

        // Receipt shows both payment lines and prints automatically when asked
        $this->actingAs($ua)->get('/sales/'.$sale->id.'?print=1')->assertOk()
            ->assertSee('Paid (Card)', false)->assertSee('Paid (Cash)', false)->assertSee('window.print()', false);
    }

    public function test_pos_and_inventory_show_only_this_branchs_products(): void
    {
        $w = $this->world();
        extract($w);
        $this->stockShop($w, both: false); // Toy was never sent to Shop A
        $this->actingAs($ua)->get('/pos')->assertOk()->assertSee('Food')->assertDontSee('Toy');
        $this->actingAs($ua)->get('/inventory')->assertOk()->assertSee('Food')->assertDontSee('Toy');
    }

    public function test_shop_settings_and_logo_appear_on_the_bill(): void
    {
        $w = $this->world();
        extract($w);
        $this->stockShop($w);
        $this->actingAs($ua)->post('/settings', [
            'name' => 'Shop A Deluxe', 'currency' => 'LKR', 'address' => '12 Galle Rd', 'phone' => '011 111 2222',
            'email' => 'a@shop.test', 'tax_no' => 'VAT-99', 'receipt_footer' => 'Come again!',
            'logo' => UploadedFile::fake()->image('logo.png', 60, 60),
        ])->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertNotNull($a->logo_path);
        $this->assertFileExists(public_path($a->logo_path));

        $this->actingAs($ua)->postJson('/pos', ['items' => [['product_id' => $p1->id, 'quantity' => 1]], 'payments' => [['method' => 'cash', 'amount' => 150]]])->assertOk();
        $this->actingAs($ua)->get('/sales/'.Sale::first()->id)->assertOk()
            ->assertSee('Shop A Deluxe')->assertSee('12 Galle Rd')->assertSee('011 111 2222')->assertSee('VAT-99')
            ->assertSee('Come again!')->assertSee('LKR 150.00')->assertSee($a->logo_path);
        @unlink(public_path($a->logo_path));

        $this->actingAs($admin)->get('/settings')->assertForbidden();
    }

    public function test_reports_and_inventory_export_to_pdf_and_excel(): void
    {
        $w = $this->world();
        extract($w);
        $this->stockShop($w);
        $this->actingAs($ua)->postJson('/pos', ['items' => [['product_id' => $p1->id, 'quantity' => 1]], 'payments' => [['method' => 'cash', 'amount' => 150]]])->assertOk();

        $week = ['from' => now()->subWeek()->startOfWeek()->toDateString(), 'to' => now()->addDay()->toDateString()];
        foreach (['/reports/sales', '/reports/profit', '/reports/stock-value', '/inventory'] as $url) {
            $pdf = $this->actingAs($admin)->get($url.'?'.http_build_query($week + ['export' => 'pdf']));
            $pdf->assertOk();
            $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'), $url);
            ob_start();
            $pdf->baseResponse->sendContent();
            $this->assertSame('%PDF-', substr(ob_get_clean(), 0, 5), "$url is not a real PDF");
            $xl = $this->actingAs($admin)->get($url.'?'.http_build_query($week + ['export' => 'xlsx']));
            $xl->assertOk();
            $this->assertStringContainsString('spreadsheetml', $xl->headers->get('content-type'), $url);
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($xl->baseResponse->getFile()->getPathname()) === true, "$url is not a real xlsx");
            $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet1.xml'));
        }
        // a shop can export its own data (the branch filter is ignored for shop users)
        $this->actingAs($ub)->get('/reports/sales?export=pdf&branch_id='.$a->id)->assertOk();
        // the page shows quick ranges and export buttons
        $this->actingAs($admin)->get('/reports/sales')->assertOk()->assertSee('Last week')->assertSee('Excel');
    }
}

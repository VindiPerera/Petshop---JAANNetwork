<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $a = Branch::create(['name' => 'Shop A', 'code' => 'A', 'type' => 'shop']);
        $b = Branch::create(['name' => 'Shop B', 'code' => 'B', 'type' => 'shop']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@x.test', 'password' => 'secret1', 'role' => 'admin', 'is_active' => true]);
        $ua = User::create(['name' => 'A', 'email' => 'sa@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $a->id, 'is_active' => true]);
        $ub = User::create(['name' => 'B', 'email' => 'sb@x.test', 'password' => 'secret1', 'role' => 'branch', 'branch_id' => $b->id, 'is_active' => true]);
        return compact('a', 'b', 'admin', 'ua', 'ub');
    }

    public function test_branch_creates_edits_and_deletes_normal_and_range_expenses(): void
    {
        extract($this->world());

        $this->actingAs($ua)->post('/expenses', ['type' => 'single', 'category' => 'Utilities', 'title' => 'Electricity', 'amount' => 500, 'start_date' => '2026-11-03', 'branch_id' => $b->id])->assertRedirect();
        $this->actingAs($ua)->post('/expenses', ['type' => 'range', 'category' => 'Rent', 'title' => 'Q4 rent', 'amount' => 3000, 'start_date' => '2026-10-01', 'end_date' => '2026-12-31'])->assertRedirect();

        $single = Expense::where('title', 'Electricity')->firstOrFail();
        $range = Expense::where('title', 'Q4 rent')->firstOrFail();
        $this->assertSame($a->id, $single->branch_id); // branch users cannot post to another location
        $this->assertSame('2026-11-03', $single->end_date->toDateString());

        // Range is spread per day: 92 days, November = 30 days
        $this->assertEquals(round(3000 * 30 / 92, 2), $range->allocatedBetween('2026-11-01', '2026-11-30'));
        $this->assertEquals(3000.0, $range->allocatedBetween('2026-01-01', '2027-01-01'));
        $this->assertEquals(0.0, $range->allocatedBetween('2027-01-01', '2027-02-01'));

        $this->actingAs($ua)->put("/expenses/{$range->id}", ['type' => 'range', 'category' => 'Rent', 'title' => 'Q4 rent', 'amount' => 3000, 'start_date' => '2026-11-01', 'end_date' => '2026-11-30'])->assertRedirect();
        $this->assertEquals(3000.0, $range->fresh()->allocatedBetween('2026-11-01', '2026-11-30'));

        $this->actingAs($ua)->put("/expenses/{$single->id}", ['type' => 'single', 'category' => 'Utilities', 'title' => 'Electricity', 'amount' => 650, 'start_date' => '2026-11-04'])->assertRedirect();
        $this->assertEquals(650.0, (float) $single->fresh()->amount);

        $this->actingAs($ua)->get('/expenses?from=2026-11-01&to=2026-11-30')->assertOk()->assertSee('Electricity');
        $this->actingAs($ua)->delete("/expenses/{$single->id}")->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['id' => $single->id]);
    }

    public function test_isolation_validation_and_profit_report(): void
    {
        extract($this->world());
        $this->actingAs($ua)->post('/expenses', ['type' => 'range', 'category' => 'Rent', 'title' => 'x', 'amount' => 10, 'start_date' => '2026-11-10', 'end_date' => '2026-11-01'])->assertSessionHasErrors('end_date');

        $e = Expense::create(['branch_id' => $a->id, 'user_id' => $ua->id, 'type' => 'single', 'category' => 'Other', 'title' => 'Tape', 'amount' => 120, 'start_date' => '2026-11-05', 'end_date' => '2026-11-05']);
        $this->actingAs($ub)->get("/expenses/{$e->id}/edit")->assertForbidden();
        $this->actingAs($ub)->delete("/expenses/{$e->id}")->assertForbidden();

        // Admin can add to any location; expense-only branch still shows in profit report
        $this->actingAs($admin)->post('/expenses', ['branch_id' => $b->id, 'type' => 'single', 'category' => 'Rent', 'title' => 'B rent', 'amount' => 80, 'start_date' => '2026-11-06'])->assertRedirect();
        $this->actingAs($admin)->get('/reports/profit?from=2026-11-01&to=2026-11-30')->assertOk()->assertSee('Shop A')->assertSee('Shop B')->assertSee('120.00');
        $this->actingAs($admin)->get('/reports/profit?export=xlsx&from=2026-11-01&to=2026-11-30')->assertOk();
    }
}

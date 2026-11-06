<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'cash' | 'card' | 'transfer' | 'split'
        DB::statement("ALTER TABLE sales MODIFY payment_method VARCHAR(20) NOT NULL DEFAULT 'cash'");

        Schema::create('sale_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->string('method', 20);
            $t->decimal('amount', 12, 2);
            $t->timestamps();
        });

        // Backfill existing sales so reports on payments stay complete.
        DB::statement("INSERT INTO sale_payments (sale_id, method, amount, created_at, updated_at)
                       SELECT id, payment_method, total, created_at, created_at FROM sales");

        Schema::table('sale_items', function (Blueprint $t) {
            $t->decimal('discount', 12, 2)->default(0)->after('unit_cost'); // discount for the whole line
        });

        Schema::table('branches', function (Blueprint $t) {
            $t->string('email')->nullable()->after('phone');
            $t->string('tax_no', 50)->nullable()->after('email');
            $t->string('logo_path')->nullable()->after('tax_no');
            $t->string('currency', 10)->default('Rs.')->after('logo_path');
            $t->string('receipt_footer')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('branches', fn (Blueprint $t) => $t->dropColumn(['email', 'tax_no', 'logo_path', 'currency', 'receipt_footer']));
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn('discount'));
        Schema::dropIfExists('sale_payments');
    }
};

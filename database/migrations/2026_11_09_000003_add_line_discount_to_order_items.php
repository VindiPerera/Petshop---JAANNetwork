<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money discount on the whole line; line_total stays the amount after this discount.
        Schema::table('order_items', function (Blueprint $t) {
            $t->decimal('discount', 12, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('discount'));
    }
};

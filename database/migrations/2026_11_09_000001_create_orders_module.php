<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Online / phone orders taken in the admin panel (or by a branch) and billed later.
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_no', 40)->unique();
            $t->foreignId('branch_id')->constrained();           // location that fulfils the order (stock is taken from here)
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->constrained();             // who created the order
            $t->string('customer_name');
            $t->string('customer_phone', 30)->nullable();
            $t->string('delivery_address')->nullable();
            $t->string('source', 20)->default('website');        // website | facebook | instagram | whatsapp | phone | other
            $t->string('status', 20)->default('pending');       // pending | confirmed | shipped | completed | cancelled
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('delivery_fee', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->text('note')->nullable();
            $t->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete(); // set when the bill is created
            $t->timestamps();
            $t->index(['branch_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('line_total', 12, 2);
            $t->timestamps();
        });

        Schema::table('sales', function (Blueprint $t) {
            $t->string('channel', 10)->default('pos')->after('payment_method'); // pos | online
            $t->decimal('delivery_fee', 12, 2)->default(0)->after('discount');
            $t->foreignId('order_id')->nullable()->after('channel')->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->dropConstrainedForeignId('order_id');
            $t->dropColumn(['channel', 'delivery_fee']);
        });
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

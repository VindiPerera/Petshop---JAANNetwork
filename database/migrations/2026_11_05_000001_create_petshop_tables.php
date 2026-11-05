<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A "location" that holds stock: the central warehouse or a shop branch.
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 20)->unique();
            $t->enum('type', ['warehouse', 'shop'])->default('shop');
            $t->string('address')->nullable();
            $t->string('phone', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->enum('role', ['admin', 'branch'])->default('branch')->after('email');
            $t->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
            $t->boolean('is_active')->default(true)->after('branch_id');
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('sku', 50)->unique();
            $t->string('name');
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('unit', 20)->default('pcs');
            $t->decimal('cost_price', 12, 2)->default(0);
            $t->decimal('sale_price', 12, 2)->default(0);
            $t->unsignedInteger('reorder_level')->default(5);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->integer('quantity')->default(0);
            $t->timestamps();
            $t->unique(['branch_id', 'product_id']);
        });

        // Ledger of every stock change (audit trail + inventory reports).
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->enum('type', ['purchase', 'transfer_out', 'transfer_in', 'sale', 'adjustment', 'return']);
            $t->integer('quantity'); // signed: + in, - out
            $t->string('reference')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['branch_id', 'product_id']);
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
        });

        // Goods received into the warehouse from a supplier.
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->string('purchase_no', 40)->unique();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->date('received_date');
            $t->decimal('total_cost', 12, 2)->default(0);
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_cost', 12, 2);
            $t->timestamps();
        });

        // GRN: one transfer note moving many products warehouse -> shop.
        Schema::create('transfers', function (Blueprint $t) {
            $t->id();
            $t->string('grn_no', 40)->unique();
            $t->foreignId('from_branch_id')->constrained('branches');
            $t->foreignId('to_branch_id')->constrained('branches');
            $t->foreignId('user_id')->constrained();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('transfer_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->timestamps();
        });

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('pet_name')->nullable();
            $t->string('pet_type')->nullable();
            $t->timestamps();
        });

        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_no', 40)->unique();
            $t->foreignId('branch_id')->constrained();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->decimal('paid', 12, 2);
            $t->enum('payment_method', ['cash', 'card', 'transfer'])->default('cash');
            $t->timestamps();
            $t->index(['branch_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('unit_cost', 12, 2)->default(0);
            $t->decimal('line_total', 12, 2);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sale_items', 'sales', 'customers', 'transfer_items', 'transfers', 'purchase_items', 'purchases',
                  'suppliers', 'stock_movements', 'stocks', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropColumn(['role', 'is_active']);
        });
        Schema::dropIfExists('branches');
    }
};

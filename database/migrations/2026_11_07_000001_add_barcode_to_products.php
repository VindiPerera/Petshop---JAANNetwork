<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->string('barcode', 40)->nullable()->unique()->after('sku');
        });
        // Give every existing product an auto-generated barcode.
        Product::whereNull('barcode')->orderBy('id')->each(fn ($p) => $p->update(['barcode' => Product::ean13($p->id)]));
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('barcode'));
    }
};

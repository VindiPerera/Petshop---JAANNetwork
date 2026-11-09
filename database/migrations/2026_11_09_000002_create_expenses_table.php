<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // type 'single': one date (start_date = end_date). type 'range': the amount covers start_date..end_date
        // (e.g. a quarter's rent) and reports spread it evenly over those days.
        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('type', 10)->default('single');
            $t->string('category', 40);
            $t->string('title');
            $t->decimal('amount', 12, 2);
            $t->date('start_date');
            $t->date('end_date');
            $t->text('note')->nullable();
            $t->timestamps();
            $t->index(['branch_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

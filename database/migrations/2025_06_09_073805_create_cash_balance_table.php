<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_cash_balance', function (Blueprint $table) {
            $table->id(); // SERIAL PRIMARY KEY (auto-increment bigint)
            $table->unsignedBigInteger('cashier_id'); // INT NOT NULL
            $table->date('shift_date'); // DATE NOT NULL
            $table->integer('shift_number'); // INT NOT NULL
            $table->decimal('opening_balance', 18, 2); // DECIMAL(18,2) NOT NULL
            $table->decimal('cash_sales_total', 18, 2)->default(0.00); // DECIMAL(18,2) DEFAULT 0.00
            $table->decimal('cash_in', 18, 2)->default(0.00); // DECIMAL(18,2) DEFAULT 0.00
            $table->decimal('cash_out', 18, 2)->default(0.00); // DECIMAL(18,2) DEFAULT 0.00
            $table->decimal('closing_balance', 18, 2)->nullable(); // DECIMAL(18,2)
            $table->decimal('discrepancy', 18, 2)->nullable(); // DECIMAL(18,2)
            $table->text('notes')->nullable(); // TEXT
            $table->timestamp('opened_at')->useCurrent(); // TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            $table->timestamp('closed_at')->nullable(); // TIMESTAMP
            $table->string('status', 20)->default('open'); // VARCHAR(20) NOT NULL DEFAULT 'open'

            // If you have a users table for cashier_id, you can add:
            $table->foreign('cashier_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_cash_balance');
    }
};
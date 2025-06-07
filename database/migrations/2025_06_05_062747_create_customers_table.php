<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_customer', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('name', 100);
            $table->string('address', 200);
            $table->string('phone_number', 20);
            $table->decimal('saldo_kredit', 15, 2)->default(0);
            $table->enum('is_active', ['Y', 'N'])->default('Y');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Add foreign keys if needed
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_customer');
    }
};
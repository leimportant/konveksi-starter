<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tr_transfer_stock_detail', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_id', 30);
            $table->unsignedBigInteger('product_id');
            $table->string('uom_id', 10);
            $table->string('size_id', 10);
            $table->decimal('qty', 15, 2);
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('tr_transfer')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mst_product');
            $table->foreign('uom_id')->references('id')->on('mst_uom');
            $table->foreign('size_id')->references('id')->on('mst_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_transfer_stock_detail');
    }
};

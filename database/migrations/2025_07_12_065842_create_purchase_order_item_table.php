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
        Schema::create('purchase_order_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('purchase_order_id');
            $table->bigInteger('product_id');
            $table->double('qty');
            $table->string('uom_id', 10);
            $table->double('price');
            $table->double('total');
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_order')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mst_product')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('mst_uom')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_item');
    }
};

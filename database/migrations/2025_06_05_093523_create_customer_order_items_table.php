<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_order_items', function (Blueprint $table) {
            $table->string('item_id')->primary(); // uniqid
            $table->bigInteger('order_id');
            $table->bigInteger('product_id');
            $table->decimal('qty', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('price', 15, 2);
            $table->decimal('price_final', 15, 2);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('t_orders');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_order_items');
    }
};
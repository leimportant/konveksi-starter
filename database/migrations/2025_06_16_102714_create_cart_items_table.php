<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('product_id'); // Foreign key for product
            $table->string('size_id', 10)->nullable()->default('');
            $table->string('uom_id', 10)->nullable()->default('');
            $table->decimal('quantity', 15, 2); // Quantity of the product
            $table->unsignedBigInteger('created_by'); // User who created the cart item
            $table->unsignedBigInteger('updated_by')->nullable(); // User who updated the cart item
            $table->timestamps(); // created_at and updated_at timestamps

            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('mst_product')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
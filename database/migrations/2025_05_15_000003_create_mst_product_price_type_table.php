<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mst_product_price_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_id');
            $table->unsignedBigInteger('price_type_id');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('uom_id', 10)->nullable();
            $table->string('size_id', 10)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Add foreign keys separately
            $table->foreign('price_id')
                  ->references('id')
                  ->on('mst_product_price')
                  ->onDelete('cascade');

            $table->foreign('price_type_id')
                  ->references('id')
                  ->on('mst_price_type')
                  ->onDelete('cascade');

            $table->foreign('uom_id')
                  ->references('id')
                  ->on('mst_uom')
                  ->nullOnDelete();

            $table->foreign('size_id')
                  ->references('id')
                  ->on('mst_size')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_product_price_type');
    }
};
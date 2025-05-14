<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mst_product_price', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('price_type_id');
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('product_id')
                  ->references('id')
                  ->on('mst_product')
                  ->onDelete('cascade');
                  
            $table->foreign('price_type_id')
                  ->references('id')
                  ->on('mst_price_type')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_product_price');
    }
};
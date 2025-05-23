<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mst_product_price', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Add foreign key separately to avoid duplicate key issues
            $table->foreign('product_id')
                  ->references('id')
                  ->on('mst_product')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_product_price');
    }
};
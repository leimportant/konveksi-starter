<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_product', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('uom_id', 10)->nullable();
            $table->string('remark')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('category_id')->references('id')->on('mst_category')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_product');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_stock_opname', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('sloc_id');
            $table->unsignedBigInteger('uom_id');
            $table->integer('qty_system');
            $table->integer('qty_physical');
            $table->integer('difference');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_stock_opname');
    }
};
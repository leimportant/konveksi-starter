<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_inventory_history', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            $table->string('location_id');
            $table->string('uom_id');
            $table->string('sloc_id');
            $table->string('size_id');
            $table->integer('qty_before');
            $table->integer('qty_change');
            $table->integer('qty_after');
            $table->string('transaction_type');
            $table->string('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_inventory_history');
    }
};
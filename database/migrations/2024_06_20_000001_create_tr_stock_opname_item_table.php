<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_stock_opname_item', function (Blueprint $table) {
            $table->string('opname_id', 100);
            $table->string('size_id', 10);
            $table->integer('qty_system');
            $table->integer('qty_physical');
            $table->integer('difference');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->default(0);
            $table->timestamps();

            $table->primary('opname_id');
            $table->index('size_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_stock_opname_item');
    }
};
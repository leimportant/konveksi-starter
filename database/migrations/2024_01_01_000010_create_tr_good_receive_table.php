<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_good_receive', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('model_id');
            $table->text('description');
            $table->decimal('qty_base', 15, 2);
            $table->decimal('qty_convert', 15, 2);
            $table->string('uom_base', 10);
            $table->string('uom_convert', 10);
            $table->string('recipent');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('model_id')->references('id')->on('tr_model');
            $table->foreign('uom_base')->references('id')->on('mst_uom');
            $table->foreign('uom_convert')->references('id')->on('mst_uom');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_good_receive');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_proses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('good_receive_id');
            $table->text('description');
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('model_id')->references('id')->on('tr_model');
            $table->foreign('good_receive_id')->references('id')->on('tr_good_receive');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_proses');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_model_size', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('size_id');
            $table->primary(['model_id', 'size_id']);
            $table->foreign('model_id')->references('id')->on('tr_model')->onDelete('cascade');
            $table->foreign('size_id')->references('id')->on('mst_size')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_model_size');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_model_activity', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('role_id');
            $table->string('size_id', 10);
            $table->decimal('price', 15, 2);
            $table->primary(['model_id', 'role_id', 'size_id']);
            $table->foreign('model_id')->references('id')->on('tr_model')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('size_id')->references('id')->on('mst_size')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_model_activity');
    }
};
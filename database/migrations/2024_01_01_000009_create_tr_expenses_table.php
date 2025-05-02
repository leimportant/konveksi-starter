<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_expenses', function (Blueprint $table) {
            $table->id();
            $table->text('description');
            $table->unsignedBigInteger('expense_type_id');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('expense_type_id')->references('id')->on('mst_expense_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_expenses');
    }
};
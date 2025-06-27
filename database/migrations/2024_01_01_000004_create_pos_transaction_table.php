<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 20);
            $table->dateTime('transaction_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('change_amount', 15, 2);
            $table->string('payment_method', 20);
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            // $table->foreign('customer_id')->references('id')->on('mst_customer');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_transaction');
    }
};
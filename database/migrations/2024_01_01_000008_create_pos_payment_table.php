<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_payment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('payment_method', 20); // cash, card, etc
            $table->decimal('amount', 15, 2);
            $table->string('reference_number', 50)->nullable(); // for card payments, transfer, etc
            $table->string('status', 20); // pending, completed, failed
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('transaction_id')->references('id')->on('pos_transaction');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_payment');
    }
};
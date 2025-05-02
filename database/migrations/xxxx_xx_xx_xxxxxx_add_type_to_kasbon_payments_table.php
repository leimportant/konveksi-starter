<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kasbon_payments', function (Blueprint $table) {
            $table->enum('type', ['kasbon', 'payment'])->after('id')->default('payment');
        });
    }

    public function down()
    {
        Schema::table('kasbon_payments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tr_good_receive', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'approved', 'rejected', 'cancelled'])
                  ->default('waiting')
                  ->after('remark'); // Adjust position as needed
        });
    }

    public function down()
    {
        Schema::table('tr_good_receive', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
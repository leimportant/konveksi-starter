<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tr_model_material', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('remark');
        });
    }

    public function down()
    {
        Schema::table('tr_model_material', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
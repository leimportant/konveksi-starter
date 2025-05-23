<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tr_production', function (Blueprint $table) {
            $table->integer('status')->default(1)->comment('1=draft, 2=approved, 3=rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tr_production', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
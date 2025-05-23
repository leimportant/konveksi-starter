<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mst_price_type', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique(); // e.g., 'retail', 'grosir'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_price_type');
    }
};
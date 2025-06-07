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
         Schema::create('tr_transfer_stock', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('location_destination_id');
            $table->string('sloc_id', 10);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Tambahkan foreign key jika diperlukan
            // $table->foreign('location_id')->references('id')->on('mst_location');
            // $table->foreign('location_destination_id')->references('id')->on('mst_location');
            // $table->foreign('sloc_id')->references('id')->on('mst_sloc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_transfer_stock');
    }
};

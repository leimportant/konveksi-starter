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
        Schema::create('mst_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->default(0);
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null')->onUpdate('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_product');
    }
};
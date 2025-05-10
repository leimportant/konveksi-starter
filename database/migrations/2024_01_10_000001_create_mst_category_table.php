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
        Schema::create('mst_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->onUpdate('restrict');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null')->onUpdate('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_category');
    }
};
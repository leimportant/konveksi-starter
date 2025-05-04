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
        Schema::create('tr_document_attachment', function (Blueprint $table) {
            $table->id();
            $table->string('doc_id');
            $table->unsignedBigInteger('reference_id');  // Changed from doc_type to reference_id
            $table->string('path');
            $table->string('extension');
            $table->string('url');
            $table->string('filename');
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('reference_id')->references('id')->on('mst_reference')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_document_attachment');
    }
};
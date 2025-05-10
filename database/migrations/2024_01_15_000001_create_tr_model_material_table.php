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
        Schema::dropIfExists('tr_model_material');
        
        Schema::create('tr_model_material', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('item');
            $table->string('remark', 100)->nullable()->default('');
            $table->unsignedInteger('qty')->nullable()->default(0);
            $table->string('uom_id', 10)->nullable()->default('');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Primary Key
            $table->primary(['model_id', 'product_id', 'item']);

            // Foreign Keys with explicit constraint names
            $table->foreign('model_id', 'fk_model_material_model_id')
                ->references('id')
                ->on('tr_model')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            $table->foreign('product_id', 'fk_model_material_product_id')
                ->references('id')
                ->on('mst_product')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            $table->foreign('uom_id', 'fk_model_material_uom_id')
                ->references('id')
                ->on('mst_uom')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_model_material');
    }
};
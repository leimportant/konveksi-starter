<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tr_good_receive_item', function (Blueprint $table) {
            $table->bigIncrements('id'); // BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->bigInteger('good_receive_id')->default(0);
            $table->unsignedBigInteger('model_material_id');
            $table->unsignedBigInteger('model_material_item');
            $table->decimal('qty', 15, 2);
            $table->decimal('qty_convert', 15, 2);
            $table->string('uom_base', 10);
            $table->string('uom_convert', 10);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at

            // Indexes
            $table->index(['good_receive_id', 'model_material_id'], 'good_receive_id_model_material_id');
            $table->index('model_material_item'); // Laravel will name it like tr_good_receive_item_model_material_item_index

            // Foreign Keys
            // Assuming tr_model_material table has 'product_id' and 'item' columns that are part of its primary or unique key structure
            // Note: Laravel's default foreign key naming convention will be used if specific names like 'FK1_material_id' are not provided.
            // If tr_model_material.product_id is not the primary key of tr_model_material, ensure it's at least indexed.
            // Same for tr_model_material.item.
            // The SQL provided references `tr_model_material(product_id)` and `tr_model_material(item)`.
            // This implies `product_id` and `item` are columns in `tr_model_material`.
            // For composite foreign keys or referencing non-primary keys, ensure the referenced columns form a unique key or primary key.
            // Laravel's foreign key constraints typically reference the 'id' column by default.
            // To reference other columns, you specify them.

            $table->foreign('model_material_id', 'FK1_material_id')
                  ->references('product_id')->on('tr_model_material') // Assuming 'product_id' is the column name in tr_model_material
                  ->onUpdate('no action')
                  ->onDelete('no action');

            $table->foreign('model_material_item', 'FK2_material_item')
                  ->references('item')->on('tr_model_material') // Assuming 'item' is the column name in tr_model_material
                  ->onUpdate('no action')
                  ->onDelete('no action');

            // Table engine and collation are usually set in config/database.php
            // $table->engine = 'InnoDB';
            // $table->charset = 'utf8mb4';
            // $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tr_good_receive_item', function (Blueprint $table) {
            // Drop foreign keys in reverse order of creation or by specific name
            $table->dropForeign('FK1_material_id');
            $table->dropForeign('FK2_material_item');
        });
        Schema::dropIfExists('tr_good_receive_item');
    }
};
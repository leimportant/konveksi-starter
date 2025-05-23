<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tr_inventory', function (Blueprint $table) {
            $table->bigInteger('product_id');
            $table->bigInteger('location_id');
            $table->string('uom_id', 10);
            $table->string('sloc_id', 10);
            $table->integer('qty')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->primary(['product_id', 'location_id', 'uom_id', 'sloc_id']);
            $table->index('created_by', 'pos_product_created_by_foreign');
            $table->index('location_id', 'pos_product_category_id_foreign');
            $table->index('product_id');
            
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onUpdate('restrict')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tr_inventory');
    }
};
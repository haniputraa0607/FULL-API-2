<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_stock_logs', function (Blueprint $table) {
            $table->bigIncrements('id_product_stock_log');
            $table->unsignedInteger('id_product')->nullable();
            $table->unsignedInteger('id_product_variant_group')->nullable();
            $table->integer('stock_item')->default(0);
            $table->integer('stock_service')->default(0);
            $table->integer('stock_item_before')->default(0);
            $table->integer('stock_service_before')->default(0);
            $table->integer('stock_item_after')->default(0);
            $table->integer('stock_service_after')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_stock_logs');
    }
}

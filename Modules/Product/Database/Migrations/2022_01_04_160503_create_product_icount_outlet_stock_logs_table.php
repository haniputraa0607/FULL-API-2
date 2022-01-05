<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductIcountOutletStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_icount_outlet_stock_logs', function (Blueprint $table) {
            $table->increments('id_product_icount_outlet_stock_log');
            $table->integer('id_outlet')->unsigned();
            $table->integer('id_product_icount')->unsigned();
            $table->string('unit');
            $table->integer('qty');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->integer('id_reference')->nullable();
            $table->string('source')->nullable();
            $table->text('desctiption')->nullable();
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_outlet_stock_logs')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product_icount', 'fk_product_icount_stock_logs')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_icount_outlet_stock_logs');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductIcountStockAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_icount_stock_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id_product_icount_stock_adjustment');
            $table->unsignedInteger('id_product_icount');
            $table->unsignedInteger('id_user')->nullable();
            $table->unsignedInteger('id_outlet');
            $table->string('unit');
            $table->bigInteger('stock_adjustment');
            $table->string('title')->default('Stock Adjustment');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('id_product_icount', 'fk_ipi_pisa_pi')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');
            $table->foreign('id_outlet', 'fk_io_pisa_o')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_user', 'fk_iu_pisa_u')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_icount_stock_adjustments');
    }
}

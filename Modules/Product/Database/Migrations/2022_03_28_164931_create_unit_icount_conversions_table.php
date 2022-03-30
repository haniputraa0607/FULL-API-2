<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitIcountConversionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_icount_conversions', function (Blueprint $table) {
            $table->increments('id_unit_icount_conversion');
            $table->integer('id_product_icount')->unsigned();
            $table->integer('qty')->default('1');
            $table->string('unit')->nullable();
            $table->integer('qty_conversion')->nullable();
            $table->string('unit_conversion')->nullable();
            $table->timestamps();

            $table->foreign('id_product_icount', 'fk_unit_conversion_product_icount')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_icount_conversions');
    }
}

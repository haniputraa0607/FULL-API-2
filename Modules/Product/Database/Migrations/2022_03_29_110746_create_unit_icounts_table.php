<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitIcountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_icounts', function (Blueprint $table) {
            $table->increments('id_unit_icount');
            $table->integer('id_product_icount')->unsigned();
            $table->string('unit');
            $table->timestamps();

            $table->foreign('id_product_icount', 'fk_unit_product_icount')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_icounts');
    }
}

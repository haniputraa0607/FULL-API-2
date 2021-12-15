<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePivotTableProductToProductIcountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_product_icounts', function (Blueprint $table) {
            $table->increments('id_product_product_icount');
            $table->integer('id_product');
            $table->integer('id_product_icount');
            $table->string('unit')->nullable();
            $table->string('qty')->nullable();
            $table->timestamps();
        });
        Schema::create('product_outlet_stocks', function (Blueprint $table) {
            $table->increments('id_product_outlet_stock');
            $table->integer('id_product_icount');
            $table->integer('id_outlet');
            $table->string('unit')->nullable();
            $table->string('stock')->nullable();
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
        Schema::dropIfExists('product_product_icounts');
        Schema::dropIfExists('product_outlet_stocks');
    }
}

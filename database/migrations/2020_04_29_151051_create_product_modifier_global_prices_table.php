<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierGlobalPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_global_prices', function (Blueprint $table) {
            $table->increments('id_product_modifier_global_price');
            $table->unsignedInteger('id_product_modifier');
            $table->decimal('product_modifier_price',8,2)->unsigned();
            $table->timestamps();

            $table->foreign('id_product_modifier')->references('id_product_modifier')->on('product_modifiers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifier_global_prices');
    }
}

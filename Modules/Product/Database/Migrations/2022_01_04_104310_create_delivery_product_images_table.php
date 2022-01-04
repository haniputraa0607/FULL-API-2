<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryProductImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_product_images', function (Blueprint $table) {
            $table->increments('id_delivery_product_image');
            $table->integer('id_delivery_product')->unsigned();
            $table->string('path');
            $table->timestamps();

            $table->foreign('id_delivery_product', 'fk_delivery_product_image')->references('id_delivery_product')->on('delivery_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_product_images');
    }
}

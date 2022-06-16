<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestProductImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_product_images', function (Blueprint $table) {
            $table->increments('id_request_product_image');
            $table->integer('id_request_product')->unsigned();
            $table->string('path');
            $table->timestamps();

            $table->foreign('id_request_product', 'fk_request_product_image')->references('id_request_product')->on('request_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_product_images');
    }
}

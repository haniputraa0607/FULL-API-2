<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestProductDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_product_details', function (Blueprint $table) {
            $table->increments('id_request_product_detail');
            $table->integer('id_request_product')->unsigned();
            $table->integer('id_product_icount')->unsigned();
            $table->string('unit');
            $table->integer('value');
            $table->enum('status',['Pending','Approved','Rejected'])->default('Pending');
            $table->timestamps();

            $table->foreign('id_request_product', 'fk_request_product_detail')->references('id_request_product')->on('request_products')->onDelete('cascade');
            $table->foreign('id_product_icount', 'fk_product_request_product')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_product_details');
    }
}

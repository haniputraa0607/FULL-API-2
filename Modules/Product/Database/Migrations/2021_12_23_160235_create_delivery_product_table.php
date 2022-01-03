<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_products', function (Blueprint $table) {
            $table->increments('id_delivery_product');
            $table->string('code');
            $table->integer('id_outlet')->unsigned();
            $table->enum('type', ['Sell','Use']);
            $table->enum('charged', ['Outlet','Central']);
            $table->integer('id_user_delivery')->unsigned();
            $table->integer('id_user_accept')->unsigned()->nullable();
            $table->enum('status', ['Draft','On Progress','Completed', 'Cancelled'])->default('Draft');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_outlet_delivery_product')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_user_delivery', 'fk_user_delivery_delivery_product')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_user_accept', 'fk_user_accept_delivery_product')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('delivery_product_details', function (Blueprint $table) {
            $table->increments('id_delivery_product_detail');
            $table->integer('id_delivery_product')->unsigned();
            $table->integer('id_product_icount')->unsigned();
            $table->string('unit');
            $table->integer('value');
            $table->enum('status',['Pending','Approved','Rejected'])->default('Pending');
            $table->timestamps();

            $table->foreign('id_delivery_product', 'fk_delivery_product_detail')->references('id_delivery_product')->on('delivery_products')->onDelete('cascade');
            $table->foreign('id_product_icount', 'fk_product_delivery_product')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');
        });

        Schema::create('delivery_request_products', function (Blueprint $table) {
            $table->integer('id_delivery_product')->unsigned();
            $table->integer('id_request_product')->unsigned();

            $table->foreign('id_delivery_product', 'fk_delivery_product')->references('id_delivery_product')->on('delivery_products')->onDelete('cascade');
            $table->foreign('id_request_product', 'fk_request_product')->references('id_request_product')->on('request_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_product_details');
        Schema::dropIfExists('request_delivery_products');
        Schema::dropIfExists('delivery_products');
    }
}

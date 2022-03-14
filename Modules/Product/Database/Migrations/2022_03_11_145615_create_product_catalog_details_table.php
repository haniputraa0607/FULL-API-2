<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductCatalogDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_catalog_details', function (Blueprint $table) {
            $table->increments('id_product_catalog_detail');
            $table->integer('id_product_catalog')->unsigned();
            $table->integer('id_product_icount')->unsigned();
            $table->enum('filter',['Inventory','Non Inventory','Service','Assets']);
            $table->enum('budget_code',['Invoice','Beban','Assets']);
            $table->timestamps();

            $table->foreign('id_product_catalog', 'fk_product_catalog_details_id_product_catalog')->references('id_product_catalog')->on('product_catalogs')->onDelete('cascade');
            $table->foreign('id_product_icount', 'fk_product_catalog_details_id_product_icount')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_catalog_details');
    }
}

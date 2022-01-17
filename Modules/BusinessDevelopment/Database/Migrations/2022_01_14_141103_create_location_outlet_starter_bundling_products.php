<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationOutletStarterBundlingProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_outlet_starter_bundling_products', function (Blueprint $table) {
            $table->increments('id_location_outlet_starter_bundling_product');
            $table->integer('id_location')->unsigned();
            $table->integer('id_product_icount')->unsigned();
            $table->string('unit');
            $table->integer('qty')->default(1);
            $table->enum('budget_code',['Invoice', 'Beban', 'Assets'])->default('Invoice');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('id_location', 'fk_location_outlet_starter')->on('locations')->references('id_location')->onDelete('cascade');
            $table->foreign('id_product_icount', 'fk_location_outlet_starter_product')->on('product_icounts')->references('id_product_icount')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_outlet_starter_bundling_products');
    }
}

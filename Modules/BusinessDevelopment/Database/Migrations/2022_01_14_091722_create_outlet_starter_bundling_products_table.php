<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletStarterBundlingProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_starter_bundling_products', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_starter_bundling_product');
            $table->unsignedInteger('id_product_icount');
            $table->string('unit');
            $table->unsignedInteger('qty')->default(1);
            $table->enum('budget_code', ['Invoice', 'Beban', 'Assets'])->default('Invoice');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('id_product_icount')->on('product_icounts')->references('id_product_icount')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_starter_bundling_products');
    }
}

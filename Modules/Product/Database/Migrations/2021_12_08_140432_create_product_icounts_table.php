<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductIcountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_icounts', function (Blueprint $table) {
            $table->increments('id_product_icount');
            $table->string('id_item')->unique();
            $table->string('id_company')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('id_brand')->nullable();
            $table->string('id_category')->nullable();
            $table->string('id_sub_category')->nullable();
            $table->string('item_group')->nullable();
            $table->string('image_item')->nullable();
            $table->string('unit1')->nullable();
            $table->string('unit2')->nullable();
            $table->string('unit3')->nullable();
            $table->integer('ratio2')->nullable();
            $table->integer('ratio3')->nullable();
            $table->double('buy_price_1',10,4)->nullable();
            $table->double('buy_price_2',10,4)->nullable();
            $table->double('buy_price_3',10,4)->nullable();
            $table->double('unit_price_1',10,4)->nullable();
            $table->double('unit_price_2',10,4)->nullable();
            $table->double('unit_price_3',10,4)->nullable();
            $table->double('unit_price_4',10,4)->nullable();
            $table->double('unit_price_5',10,4)->nullable();
            $table->double('unit_price_6',10,4)->nullable();
            $table->text('notes')->nullable();
            $table->enum('is_suspended',['true','false'])->default('false');
            $table->enum('is_sellable',['true','false'])->default('true');
            $table->enum('is_buyable',['true','false'])->default('true');
            $table->string('id_cogs')->nullable();
            $table->string('id_purchase')->nullable();
            $table->string('id_sales')->nullable();
            $table->enum('id_deleted',['true','false'])->default('false');
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
        Schema::dropIfExists('product_icounts');
    }
}

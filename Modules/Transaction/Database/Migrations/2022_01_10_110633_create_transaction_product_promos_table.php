<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionProductPromosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_promos', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_product_promo');
            $table->unsignedInteger('id_transaction_product')->index();
            $table->unsignedInteger('id_deals')->nullable();
            $table->unsignedInteger('id_promo_campaign')->nullable();
            $table->enum('promo_type', array('Deals','Promo Campaign'));
            $table->integer('total_discount');
            $table->integer('base_discount');
            $table->integer('qty_discount');

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
        Schema::dropIfExists('transaction_product_promos');
    }
}

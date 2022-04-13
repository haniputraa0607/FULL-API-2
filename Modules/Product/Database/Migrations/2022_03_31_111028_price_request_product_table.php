<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PriceRequestProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_product_details', function (Blueprint $table) {
            $table->integer('price')->nullable();
            $table->integer('total_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_product_details', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('total_price');
        });
    }
}

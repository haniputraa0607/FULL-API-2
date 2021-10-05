<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumStockToProductDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_detail', function (Blueprint $table) {
            $table->integer('product_detail_stock_service')->default(0)->after('product_detail_stock_status');
            $table->integer('product_detail_stock_item')->default(0)->after('product_detail_stock_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_detail', function (Blueprint $table) {
            $table->dropColumn('product_detail_stock_item');
            $table->dropColumn('product_detail_stock_service');
        });
    }
}

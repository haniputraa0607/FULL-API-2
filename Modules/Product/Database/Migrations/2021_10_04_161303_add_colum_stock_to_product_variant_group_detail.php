<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumStockToProductVariantGroupDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_variant_group_details', function (Blueprint $table) {
            $table->integer('product_variant_group_detail_stock_service')->default(0)->after('product_variant_group_stock_status');
            $table->integer('product_variant_group_detail_stock_item')->default(0)->after('product_variant_group_stock_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variant_group_details', function (Blueprint $table) {
            $table->dropColumn('product_variant_group_detail_stock_service');
            $table->dropColumn('product_variant_group_detail_stock_item');
        });
    }
}

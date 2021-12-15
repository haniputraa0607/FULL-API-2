<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNameProductOutletStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('product_outlet_stocks', 'product_icount_outlet_stocks');
        Schema::table('product_icount_outlet_stocks', function (Blueprint $table) {
            $table->renameColumn('id_product_outlet_stock', 'id_product_icount_outlet_stock');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('product_icount_outlet_stocks','product_outlet_stocks');
        Schema::table('product_outlet_stocks', function (Blueprint $table) {
            $table->renameColumn('id_product_icount_outlet_stock', 'id_product_outlet_stock');
        });
    }
}

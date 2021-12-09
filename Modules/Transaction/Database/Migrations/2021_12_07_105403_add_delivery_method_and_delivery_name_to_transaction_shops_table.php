<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliveryMethodAndDeliveryNameToTransactionShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shops', function (Blueprint $table) {
        	$table->string('delivery_method')->after('shop_status');
        	$table->string('delivery_name')->after('delivery_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_shops', function (Blueprint $table) {
        	$table->dropColumn('delivery_method');
        	$table->dropColumn('delivery_name');
        });
    }
}

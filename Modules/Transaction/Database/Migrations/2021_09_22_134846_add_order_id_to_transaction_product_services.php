<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderIdToTransactionProductServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
            $table->string('order_id')->nullable()->after('id_user_hair_stylist');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
}

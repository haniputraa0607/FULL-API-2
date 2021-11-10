<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionProductToTrxProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE transaction_product_service_use CHANGE id_transaction_product_service id_transaction_product INTEGER NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement('ALTER TABLE transaction_product_service_use CHANGE id_transaction_product id_transaction_product_service INTEGER NOT NULL;');
    }
}

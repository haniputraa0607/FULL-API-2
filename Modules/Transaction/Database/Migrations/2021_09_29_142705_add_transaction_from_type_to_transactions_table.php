<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionFromTypeToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `transaction_from` `transaction_from` ENUM('outlet-service', 'home-service', 'shop', 'academy') COLLATE 'utf8mb4_unicode_ci' NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `transaction_from` `transaction_from` ENUM('outlet-service', 'home-service') COLLATE 'utf8mb4_unicode_ci' NULL");
    }
}

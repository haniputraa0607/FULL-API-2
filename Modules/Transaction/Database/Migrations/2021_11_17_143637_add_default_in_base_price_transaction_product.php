<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultInBasePriceTransactionProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transaction_products` CHANGE COLUMN `transaction_product_price` `transaction_product_price` DECIMAL(30,2) COLLATE 'utf8mb4_unicode_ci' NOT NULL Default(0)");
        \DB::statement("ALTER TABLE `transaction_products` CHANGE COLUMN `transaction_product_price_base` `transaction_product_price_base` DECIMAL(30,2) COLLATE 'utf8mb4_unicode_ci' NOT NULL Default(0)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transaction_products` CHANGE COLUMN `transaction_product_price` `transaction_product_price` DECIMAL(30,2) COLLATE 'utf8mb4_unicode_ci' NOT NULL");
        \DB::statement("ALTER TABLE `transaction_products` CHANGE COLUMN `transaction_product_price_base` `transaction_product_price_base` DECIMAL(30,2) COLLATE 'utf8mb4_unicode_ci' NOT NULL");
    }
}

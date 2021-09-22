<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompletedAtToTransactionProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transaction_products` CHANGE COLUMN `type` `type` ENUM('Product', 'Plastic', 'Service') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'Product'");
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->dateTime('transaction_product_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transaction_products` CHANGE COLUMN `type` `type` ENUM('Product', 'Plastic') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'Product'");
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->dropColumn('transaction_product_completed_at');
        });
    }
}

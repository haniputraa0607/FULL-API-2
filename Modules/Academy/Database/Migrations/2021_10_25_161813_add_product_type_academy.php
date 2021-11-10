<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductTypeAcademy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `product_type` `product_type` ENUM('product', 'plastic', 'service', 'academy') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'product'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `product_type` `product_type` ENUM('product', 'plastic', 'service') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'product'");
    }
}

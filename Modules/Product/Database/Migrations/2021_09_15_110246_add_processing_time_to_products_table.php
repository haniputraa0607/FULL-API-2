<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProcessingTimeToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `product_type` `product_type` ENUM('product', 'plastic', 'service') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'product'");
        Schema::table('products', function (Blueprint $table) {
            $table->integer('processing_time_service')->nullable()->after('product_variant_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `product_type` `product_type` ENUM('product', 'plastic') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'product'");
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('processing_time_service');
        });
    }
}

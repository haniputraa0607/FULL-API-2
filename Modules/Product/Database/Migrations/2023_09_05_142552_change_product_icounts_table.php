<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeProductIcountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_item` `id_item` INT");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_company` `id_company` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_brand` `id_brand` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_category` `id_category` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_sub_category` `id_sub_category` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_cogs` `id_cogs` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_purchase` `id_purchase` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `product_icounts` CHANGE COLUMN `id_sales` `id_sales` INT NULL DEFAULT NULL");
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->integer('minimum_qty')->nullable()->after('unit_price_6');
            $table->integer('use_full_life')->nullable()->after('minimum_qty');
            $table->integer('id_depreciation')->nullable()->after('id_sales');
            $table->integer('id_accumulated')->nullable()->after('id_depreciation');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->string('id_item')->change();
            $table->string('id_company')->nullable()->change();
            $table->string('id_brand')->nullable()->change();
            $table->string('id_category')->nullable()->change();
            $table->string('id_sub_category')->nullable()->change();
            $table->string('id_cogs')->nullable()->change();
            $table->string('id_purchase')->nullable()->change();
            $table->string('id_sales')->nullable()->change();
            $table->dropColumn('minimum_qty');
            $table->dropColumn('use_full_life');
            $table->dropColumn('id_depreciation');
            $table->dropColumn('id_accumulated');
        });
    }
}

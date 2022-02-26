<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnFilterToProductDetailBundling extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::table('outlet_starter_bundling_products', function (Blueprint $table) {
            $table->enum('filter', ['Inventory', 'Non Inventory', 'Assets', 'Service'])->nullable()->after('qty');
        });
        Schema::table('location_outlet_starter_bundling_products', function (Blueprint $table) {
            $table->enum('filter', ['Inventory', 'Non Inventory', 'Assets', 'Service'])->nullable()->after('qty');
        });
        Schema::table('request_product_details', function (Blueprint $table) {
            $table->enum('filter', ['Inventory', 'Non Inventory', 'Assets', 'Service'])->nullable()->after('value');
        });
        Schema::table('delivery_product_details', function (Blueprint $table) {
            $table->enum('filter', ['Inventory', 'Non Inventory', 'Assets', 'Service'])->nullable()->after('status');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_starter_bundling_products', function (Blueprint $table) {
            $table->dropColumn('filter');
        });
        Schema::table('location_outlet_starter_bundling_products', function (Blueprint $table) {
            $table->dropColumn('filter');
        });
        Schema::table('request_product_details', function (Blueprint $table) {
            $table->dropColumn('filter');
        });
        Schema::table('delivery_product_details', function (Blueprint $table) {
            $table->dropColumn('filter');
        });
    }
}

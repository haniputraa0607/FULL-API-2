<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletStarterBundlingToOutletStarterBundlingProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_starter_bundling_products', function (Blueprint $table) {
            $table->unsignedBigInteger('id_outlet_starter_bundling')->after('id_outlet_starter_bundling_product');
            $table->foreign('id_outlet_starter_bundling', 'fk_iosb_osbp_osb')->on('outlet_starter_bundlings')->references('id_outlet_starter_bundling')->onDelete('cascade');
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
            $table->dropForeign('fk_iosb_osbp_osb');
            $table->dropColumn('id_outlet_starter_bundling');
        });
    }
}

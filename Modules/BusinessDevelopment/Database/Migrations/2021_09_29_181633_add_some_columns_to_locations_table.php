<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsToLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('location_large')->nullable()->after('id_partner');
            $table->integer('rental_price')->nullable()->after('location_large');
            $table->integer('service_charge')->nullable()->after('rental_price');
            $table->integer('promotion_levy')->nullable()->after('service_charge');
            $table->integer('renovation_cost')->nullable()->after('promotion_levy');
            $table->integer('partnership_fee')->nullable()->after('renovation_cost');
            $table->integer('income')->nullable()->after('partnership_fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('location_large');
            $table->dropColumn('rental_price');
            $table->dropColumn('service_charge');
            $table->dropColumn('promotion_levy');
            $table->dropColumn('renovation_cost');
            $table->dropColumn('partnership_fee');
            $table->dropColumn('income');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToLocationsAndOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('is_tax')->nullable()->after('id_brand');
        });
        Schema::table('outlets', function (Blueprint $table) {
            $table->integer('is_tax')->nullable()->after('id_location');
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
            $table->dropColumn('is_tax');
        });
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('is_tax');
        });
    }
}

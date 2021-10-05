<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandToLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('id_brand')->unsigned()->nullable()->after('income');
            $table->foreign('id_brand','fk_locations_brand')->references('id_brand')->on('brands')->onDelete('restrict'); 
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
            $table->dropForeign('fk_locations_brand');
            $table->dropIndex('fk_locations_brand');
            $table->dropColumn('id_brand');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletManageChangeLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_change_location', function (Blueprint $table) {
            $table->integer('id_outlet_manage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_change_location', function (Blueprint $table) {
            $table->dropColumn('id_outlet_manage');
        });
    }
}

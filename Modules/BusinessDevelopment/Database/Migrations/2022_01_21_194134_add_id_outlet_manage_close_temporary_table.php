<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletManageCloseTemporaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_close_temporary', function (Blueprint $table) {
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
        Schema::table('outlet_close_temporary', function (Blueprint $table) {
            $table->dropColumn('id_outlet_manage');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDayToOutletTimeShiftTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_time_shift', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet_schedule')->after('id_outlet')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_time_shift', function (Blueprint $table) {
            $table->dropColumn('id_outlet_schedule');
        });
    }
}

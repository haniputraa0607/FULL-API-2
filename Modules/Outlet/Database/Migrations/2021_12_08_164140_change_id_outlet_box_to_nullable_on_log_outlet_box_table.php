<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdOutletBoxToNullableOnLogOutletBoxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->table('log_outlet_box', function (Blueprint $table) {
        	$table->unsignedInteger('id_outlet_box')->unsigned()->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql2')->table('log_outlet_box', function (Blueprint $table) {
        	$table->unsignedInteger('id_outlet_box')->unsigned()->nullable(false)->change();
        });
    }
}

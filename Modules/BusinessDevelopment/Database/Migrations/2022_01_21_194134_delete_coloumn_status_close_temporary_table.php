<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteColoumnStatusCloseTemporaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_close_temporary', function (Blueprint $table) {
            $table->dropColumn('status_steps');
            $table->dropColumn('jenis_active');
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
            $table->integer('status_steps');
            $table->integer('jenis_active');
        });
    }
}

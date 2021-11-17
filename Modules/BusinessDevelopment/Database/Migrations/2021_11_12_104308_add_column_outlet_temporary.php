<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnOutletTemporary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_close_temporary', function (Blueprint $table) {
            $table->enum('jenis_active',['Change Location','No Change Location'])->nullable();
            $table->enum('status_steps',['On Follow Up','Finished Follow Up','Survey Location','Calculation','Confirmation Letter','Payment'])->nullable();            
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
            $table->dropcolumn('jenis_active');
            $table->dropcolumn('status_steps');
        });
    }
}

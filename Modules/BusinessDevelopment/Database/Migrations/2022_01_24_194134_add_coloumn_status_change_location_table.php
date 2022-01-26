<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColoumnStatusChangeLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_change_location', function (Blueprint $table) {
           $table->integer('to_id_location')->nullable();
           $table->enum('status',['Process',"Waiting",'Success','Reject'])->default('Process');
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
            $table->dropColumn('status');
            $table->dropColumn('to_id_location');
        });
    }
}

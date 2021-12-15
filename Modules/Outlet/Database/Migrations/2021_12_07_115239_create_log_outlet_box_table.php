<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogOutletBoxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('log_outlet_box', function (Blueprint $table) {
        	$table->bigIncrements('id_log_outlet_box');
            $table->integer('id_user_hair_stylist')->unsigned()->index('fk_log_outlet_box_user_hair_stylist');
            $table->integer('assigned_by')->unsigned()->nullable()->index('fk_log_outlet_box_users');
            $table->integer('id_outlet_box')->unsigned()->index('fk_log_outlet_box_outlet_box');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql2')->dropIfExists('log_outlet_box');
    }
}

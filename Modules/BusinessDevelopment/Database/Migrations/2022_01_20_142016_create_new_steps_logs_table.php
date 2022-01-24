<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewStepsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('new_steps_logs', function (Blueprint $table) {
            $table->increments('id_new_steps_log');
            $table->integer('index')->default(1);
            $table->integer('id_partner');
            $table->enum('follow_up',["Select Location","Calculation","Confirmation Letter","Payment","Success"])->nullable();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
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
        Schema::dropIfExists('new_steps_logs');
    }
}

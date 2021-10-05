<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStepLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('steps_logs', function (Blueprint $table) {
            $table->increments('id_steps_log');
            $table->integer('id_partner')->unsigned();
            $table->enum('follow_up', ['Follow Up 1','Follow Up 2','Follow Up 3','Follow Up 4','Follow Up 5','Follow Up 6','Approved'])->default('Follow Up 1');
            $table->string('note',255)->nullable();
            $table->timestamps();
            $table->foreign('id_partner', 'fk_step_partner')->references('id_partner')->on('partners')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('steps_logs');
    }
}

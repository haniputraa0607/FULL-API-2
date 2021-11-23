<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCloseTemporaryStepsTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_close_temporary_steps', function (Blueprint $table) {
            $table->increments('id_outlet_close_temporary_steps');
            $table->integer('id_outlet_close_temporary')->unsigned();
            $table->enum('follow_up', ["Follow Up 1","Follow Up 2","Follow Up 3","Follow Up 4","Follow Up 5","Follow Up 6","Approved","Survey Location","Calculation","Confirmation Letter","Payment"])->default('Follow Up 1');
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_outlet_close_temporary', 'fk_step_outlet_close_temporary')->references('id_outlet_close_temporary')->on('outlet_close_temporary')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_close_temporary_steps');
    }
}

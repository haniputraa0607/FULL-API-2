<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeFormEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_form_evaluations', function (Blueprint $table) {
            $table->Increments('id_employee_form_evaluation');
            $table->integer('id_employee')->unsigned();
            $table->enum('work_productivity',['Perfect','Good','Enough','Bad']);
            $table->enum('work_quality',['Perfect','Good','Enough','Bad']);
            $table->enum('knwolege_task',['Perfect','Good','Enough','Bad']);
            $table->enum('relationship',['Perfect','Good','Enough','Bad']);
            $table->enum('cooperation',['Perfect','Good','Enough','Bad']);
            $table->enum('discipline',['Perfect','Good','Enough','Bad']);
            $table->enum('initiative',['Perfect','Good','Enough','Bad']);
            $table->enum('expandable',['Perfect','Good','Enough','Bad']);
            $table->text('comment')->nullable();
            $table->enum('update_status', ['Permanent','Terminated','Extension']);
            $table->integer('current_extension')->nullable();
            $table->enum('time_extension', ['Month','Year'])->nullable();
            $table->integer('id_manager')->unsigned()->nullable();
            $table->datetime('update_manager')->nullable();
            $table->integer('id_hrga')->unsigned()->nullable();
            $table->datetime('update_hrga')->nullable();
            $table->integer('id_director')->unsigned()->nullable();
            $table->datetime('update_director')->nullable();
            $table->enum('status_form',['approve_manager','approve_hr','reject_hr','approve_director','reject_director']);
            $table->timestamps();

            $table->foreign('id_employee', 'fk_employee_form_evaluation')->references('id_employee')->on('employees')->onDelete('cascade');
            $table->foreign('id_manager', 'fk_manager_form_evaluation')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_hrga', 'fk_hrga_form_evaluation')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_director', 'fk_director_form_evaluation')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_form_evaluations');
    }
}

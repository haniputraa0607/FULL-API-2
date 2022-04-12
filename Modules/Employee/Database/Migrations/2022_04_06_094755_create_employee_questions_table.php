<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_questions', function (Blueprint $table) {
            $table->Increments('id_employee_question');
            $table->integer('id_user')->unsigned();
            $table->string('category')->nullable();
            $table->text('question')->nullable();
            $table->text('answer')->nullable();
            $table->foreign('id_user', 'fk_user_employee_questions')->references('id')->on('users')->onDelete('restrict');
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
        Schema::dropIfExists('employee_questions');
    }
}

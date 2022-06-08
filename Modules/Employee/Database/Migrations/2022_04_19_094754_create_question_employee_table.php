<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_employees', function (Blueprint $table) {
            $table->Increments('id_question_employee');
            $table->integer('id_category_question')->unsigned();
            $table->enum('type',['Type 1','Type 2','Type 3'])->nullable();
            $table->text('question')->nullable();
            $table->foreign('id_category_question', 'fk_question_employees_category_questions')->references('id_category_question')->on('category_questions')->onDelete('restrict');
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
        Schema::dropIfExists('employee_main_families');
    }
}

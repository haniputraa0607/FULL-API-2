<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdQuestionEmployeeQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_questions', function (Blueprint $table) {
            $table->dropColumn('category')->nullable();
            $table->dropColumn('question')->nullable();
            $table->integer('id_question_employee')->unsigned();
            $table->foreign('id_question_employee', 'fk_question_employees_employee_questions')->references('id_question_employee')->on('question_employees')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('id_question_employee')->unsigned();
            $table->string('category')->nullable();
            $table->text('question')->nullable();
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeJobExperiencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_job_experiences', function (Blueprint $table) {
            $table->Increments('id_employee_job_experience');
            $table->integer('id_user')->unsigned();
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_position')->nullable();
            $table->string('industry_type')->nullable();
            $table->string('working_period')->nullable();
            $table->string('employment_contract')->nullable();
            $table->integer('total_income')->nullable();
            $table->string ('scope_work')->nullable();
            $table->string ('achievement')->nullable();
            $table->string ('reason_resign')->nullable();
            $table->foreign('id_user', 'fk_user_employee_job_experiences')->references('id')->on('users')->onDelete('restrict');
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
        Schema::dropIfExists('employee_job_experiences');
    }
}

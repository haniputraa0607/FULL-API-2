<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeRoleSalaryCut extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_role_salary_cuts', function (Blueprint $table) {
            $table->increments('id_employee_role_salary_cut');
            $table->bigInteger('id_role')->unsigned();
            $table->foreign('id_role', 'fk_id_employee_role_salary_cut')->references('id_role')->on('roles')->onDelete('restrict');
            $table->integer('id_employee_role_default_salary_cut');
            $table->integer('value')->nullable();
            $table->text('formula')->nullable();
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
        Schema::dropIfExists('employee_role_overtimes');
    }
}

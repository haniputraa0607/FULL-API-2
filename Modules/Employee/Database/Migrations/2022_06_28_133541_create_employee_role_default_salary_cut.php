<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeRoleDefaultSalaryCut extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_role_default_salary_cuts', function (Blueprint $table) {
            $table->Increments('id_employee_role_default_salary_cut');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
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
        Schema::dropIfExists('employee_role_default_salary_cuts');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeRoleDefaultFixedIncentives extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_role_default_fixed_incentives', function (Blueprint $table) {
            $table->Increments('id_employee_role_default_fixed_incentive');
            $table->string('name_fixed_incentive')->nullable();
            $table->enum('status',['incentive','salary_cut'])->nullable();
            $table->enum('type',['Single','Multiple'])->nullable();
            $table->enum('formula',['outlet_age','years_of_service','monthly'])->nullable();
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
        Schema::dropIfExists('employee_role_default_fixed_incentives');
    }
}

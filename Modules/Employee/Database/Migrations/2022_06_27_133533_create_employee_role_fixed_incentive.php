<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeRoleFixedIncentive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_role_fixed_incentives', function (Blueprint $table) {
            $table->Increments('id_employee_role_fixed_incentive');
            $table->bigInteger('id_role')->unsigned();
            $table->integer('id_employee_role_default_fixed_incentive_detail')->unsigned();
            $table->foreign('id_role', 'fk_employee_role_fixed_incentive')->references('id_role')->on('roles')->onDelete('restrict');
            $table->foreign('id_employee_role_default_fixed_incentive_detail', 'fk_id_employee_role_default_fixed_incentive_detail')->references('id_employee_role_default_fixed_incentive_detail')->on('employee_role_default_fixed_incentive_details')->onDelete('restrict');
            $table->string('value')->nullable();
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
        Schema::dropIfExists('employee_role_fixed_incentive');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmployeeDepartmen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('id_department')->nullable();
            $table->foreign('id_department', 'fk_employee_departments')->references('id_department')->on('departments')->onDelete('restrict');
            $table->unsignedInteger('id_manager')->nullable();
            $table->foreign('id_manager', 'fk_employee_managers')->references('id')->on('users')->onDelete('restrict');
            
        });
        

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      
        Schema::table('employee_income_details', function (Blueprint $table) {
            $table->dropColumn('id_manager');
            $table->dropColumn('id_department');
        });
    }
}

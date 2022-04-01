<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdEmployeeOfficeHourToUserRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('employee_office_hour_assign');
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedInteger('id_employee_office_hour')->nullable()->after('id_job_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('employee_office_hour_assign', function (Blueprint $table) {
            $table->bigIncrements('id_employee_office_hour_assign');
            $table->string('employee_office_hour_assign_name');
            $table->unsignedInteger('id_employee_office_hour');
            $table->unsignedInteger('id_department');
            $table->unsignedInteger('id_job_level');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('id_employee_office_hour');
        });
    }
}

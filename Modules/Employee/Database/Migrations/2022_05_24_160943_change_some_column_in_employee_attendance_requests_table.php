<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSomeColumnInEmployeeAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::table('employee_attendance_requests', function (Blueprint $table) {
            $table->renameColumn('id_hairstylist_attendance_request', 'id_employee_attendance_request');
            $table->dropColumn('id_hairstylist_schedule_date');
            $table->date('attendance_date')->after('id_outlet');
            $table->time('clock_in')->nullable()->change();
            $table->time('clock_out')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_attendance_requests', function (Blueprint $table) {
            $table->renameColumn('id_employee_attendance_request', 'id_hairstylist_attendance_request');
            $table->unsignedBigInteger('id_hairstylist_schedule_date')->after('id_outlet');
            $table->dropColumn('attendance_date');
            $table->time('clock_in')->nullable(false)->change();
            $table->time('clock_out')->nullable(false)->change();

        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReadToSomeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_attendance_logs', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('approve_notes');
        });
        Schema::table('employee_attendance_requests', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('status');
        });
        Schema::table('employee_outlet_attendance_logs', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('approve_notes');
        });
        Schema::table('employee_outlet_attendance_requests', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('status');
        });
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('use_quota_time_off');
        });
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('reject_at');
        });
        Schema::table('employee_reimbursements', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('date_disburse');
        });
        Schema::table('asset_inventory_logs', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('date_action');
        });
        Schema::table('request_products', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('use_department_budget');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_attendance_logs', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('employee_attendance_requests', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('employee_outlet_attendance_logs', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('employee_outlet_attendance_logs', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('employee_reimbursements', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('asset_inventory_logs', function (Blueprint $table) {
            $table->dropColumn('read');
        });
        Schema::table('request_products', function (Blueprint $table) {
            $table->dropColumn('read');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApproveNoteToSomeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_attendance_logs', function (Blueprint $table) {
            $table->text('approve_notes')->nullable()->after('notes');
        });
        Schema::table('employee_attendance_requests', function (Blueprint $table) {
            $table->string('approve_notes')->nullable()->after('notes');
        });
        Schema::table('employee_outlet_attendance_logs', function (Blueprint $table) {
            $table->text('approve_notes')->nullable()->after('notes');
        });
        Schema::table('employee_outlet_attendance_requests', function (Blueprint $table) {
            $table->string('approve_notes')->nullable()->after('notes');
        });
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->text('approve_notes')->nullable()->after('notes');
        });
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->text('approve_notes')->nullable()->after('notes');
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
            $table->dropColumn('approve_notes');
        });
        Schema::table('employee_attendance_requests', function (Blueprint $table) {
            $table->dropColumn('approve_notes');
        });
        Schema::table('employee_outlet_attendance_logs', function (Blueprint $table) {
            $table->dropColumn('approve_notes');
        });
        Schema::table('employee_outlet_attendance_requests', function (Blueprint $table) {
            $table->dropColumn('approve_notes');
        });
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->dropColumn('approve_notes');
        });
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->dropColumn('approve_notes');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeOvertimeTimeOffDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_overtime_documents', function (Blueprint $table) {
            $table->bigIncrements('id_employee_overtime_document');
            $table->unsignedBigInteger('id_employee_overtime');
            $table->unsignedInteger('id_user_approved');
            $table->enum('type',["Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved"])->default('Manager Approved');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_time_off_documents', function (Blueprint $table) {
            $table->bigIncrements('id_employee_time_off_document');
            $table->unsignedBigInteger('id_employee_time_off');
            $table->unsignedInteger('id_user_approved');
            $table->enum('type',["Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved"])->default('Manager Approved');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });

        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->enum('status',["Pending","Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved"])->default('Pending')->after('read');
        });
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->enum('status',["Pending","Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved"])->default('Pending')->after('read');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_overtime_documents');
        Schema::dropIfExists('employee_time_off_documents');
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}

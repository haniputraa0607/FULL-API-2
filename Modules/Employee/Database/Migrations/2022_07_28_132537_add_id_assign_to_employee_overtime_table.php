<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdAssignToEmployeeOvertimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->integer('id_assign')->nullable()->after('id_employee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->dropColumn('id_assign');

        });
    }
}

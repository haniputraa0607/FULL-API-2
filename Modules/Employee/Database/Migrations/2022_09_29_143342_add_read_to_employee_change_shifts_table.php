<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReadToEmployeeChangeShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_change_shifts', function (Blueprint $table) {
            $table->tinyInteger('read')->default(0)->after('approve_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_change_shifts', function (Blueprint $table) {
            $table->dropColumn('read');
        });
    }
}

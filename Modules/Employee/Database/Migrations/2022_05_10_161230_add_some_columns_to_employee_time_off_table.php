<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsToEmployeeTimeOffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->string('type')->after('id_outlet');
            $table->text('notes')->nullable()->after('reject_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('notes');
        });
    }
}

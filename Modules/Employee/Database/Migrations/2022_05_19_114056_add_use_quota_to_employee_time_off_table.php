<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUseQuotaToEmployeeTimeOffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_time_off', function (Blueprint $table) {
            $table->tinyInteger('use_quota_time_off')->after('notes')->default(0);
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
            $table->dropColumn('use_quota_time_off');
        });
    }
}

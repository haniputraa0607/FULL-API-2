<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExportPayrollQueues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('export_payroll_queues', function (Blueprint $table) {
            $table->enum('type_export', array('Combine', 'Separated'))->default('Separated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('export_payroll_queues', function (Blueprint $table) {
            $table->dropColumn('type_export', array('Combine', 'Separated'))->default('Separated');
        });
    }
}

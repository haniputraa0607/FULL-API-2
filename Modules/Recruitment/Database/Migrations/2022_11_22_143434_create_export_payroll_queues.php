<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExportPayrollQueues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('export_payroll_queues', function (Blueprint $table) {
            $table->bigIncrements('id_export_payroll_queue');
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->text('id_outlet')->nullable();
            $table->text('name_outlet')->nullable();
            $table->string('url_export', 200)->nullable();
            $table->enum('status_export', array('Running', 'Ready', 'Deleted'))->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('export_payroll_queues');
    }
}
